<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function lockedCart(User $user): Cart
    {
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('Cart locks require a transaction.');
        }
        User::whereKey($user->id)->lockForUpdate()->firstOrFail();

        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    public function get(User $user): Cart
    {
        return DB::transaction(fn () => $this->load($this->lockedCart($user)), 3);
    }

    public function add(User $user, int $productId, int $quantity): Cart
    {
        return DB::transaction(function () use ($user, $productId, $quantity) {
            $cart = $this->lockedCart($user);
            $item = $cart->items()->where('product_id', $productId)->first();
            $next = ($item?->quantity ?? 0) + $quantity;
            $this->validateQuantity(Product::with('category')->findOrFail($productId), $next);
            $cart->items()->updateOrCreate(['product_id' => $productId], ['quantity' => $next]);

            return $this->load($cart);
        }, 3);
    }

    public function set(User $user, CartItem $item, int $quantity): Cart
    {
        return DB::transaction(function () use ($user, $item, $quantity) {
            $cart = $this->lockedCart($user);
            $item = $cart->items()->whereKey($item->id)->firstOrFail();
            $this->validateQuantity(Product::with('category')->findOrFail($item->product_id), $quantity);
            $item->update(['quantity' => $quantity]);

            return $this->load($cart);
        }, 3);
    }

    public function remove(User $user, CartItem $item): void
    {
        DB::transaction(function () use ($user, $item) {
            $cart = $this->lockedCart($user);
            $cart->items()->whereKey($item->id)->firstOrFail()->delete();
        }, 3);
    }

    public function load(Cart $cart): Cart
    {
        return $cart->load(['items' => fn ($query) => $query->orderBy('product_id')->orderBy('id'), 'items.product.category', 'items.product.images']);
    }

    public function fingerprint(Cart $cart): string
    {
        return hash('sha256', json_encode($cart->items->map(fn ($item) => [$item->id, $item->product_id, $item->quantity])->values()->all(), JSON_THROW_ON_ERROR));
    }

    public function reorder(User $user, Order $order): array
    {
        return DB::transaction(function () use ($user, $order) {
            $cart = $this->lockedCart($user);
            $warnings = [];
            foreach ($order->items()->orderBy('product_id')->get() as $line) {
                $product = Product::with('category')->findOrFail($line->product_id);
                $existing = $cart->items()->where('product_id', $product->id)->first();
                $current = $existing?->quantity ?? 0;
                $available = $product->stock_on_hand - $product->stock_reserved;
                if (! $product->is_active || ! $product->category->is_active || $available <= $current) {
                    $warnings[] = 'Product '.$product->id.' cannot be added at the requested quantity.';

                    continue;
                }
                $next = min(99, $available, $current + $line->quantity);
                if ($next < $current + $line->quantity) {
                    $warnings[] = 'Product '.$product->id.' quantity capped by current stock or quantity limit.';
                }
                $cart->items()->updateOrCreate(['product_id' => $product->id], ['quantity' => $next]);
            }

            return ['cart' => $this->load($cart), 'warnings' => $warnings];
        }, 3);
    }

    public function restoreCancelled(Cart $cart, Order $order): void
    {
        // Caller holds the user/cart and order locks. A saved cart never reserves stock.
        foreach ($order->items()->orderBy('product_id')->get() as $line) {
            $current = $cart->items()->where('product_id', $line->product_id)->first()?->quantity ?? 0;
            $cart->items()->updateOrCreate(['product_id' => $line->product_id], ['quantity' => min(99, $current + $line->quantity)]);
        }
    }

    private function validateQuantity(Product $product, int $quantity): void
    {
        if ($quantity < 1 || $quantity > 99) {
            throw new BusinessException('QUANTITY_LIMIT', 'Cart quantity must be between 1 and 99.');
        }
        if (! $product->is_active || ! $product->category->is_active) {
            throw new BusinessException('PRODUCT_UNAVAILABLE', 'The product is not available.');
        }
        if ($quantity > $product->stock_on_hand - $product->stock_reserved) {
            throw new BusinessException('INSUFFICIENT_STOCK', 'The product has insufficient available stock.');
        }
    }
}
