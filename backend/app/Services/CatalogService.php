<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Support\ProductPricing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CatalogService
{
    public function query(array $filters, bool $admin = false): Builder
    {
        $query = Product::with(['category', 'images' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')]);
        if (! $admin) {
            $query->where('is_active', true)->whereHas('category', fn ($q) => $q->where('is_active', true));
        } elseif (array_key_exists('is_active', $filters)) {
            $query->where('is_active', $filters['is_active']);
        }
        if (filled($filters['q'] ?? null)) {
            // Honor the catalog's accent-insensitive collation, treating wildcards literally.
            $term = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['q']);
            $query->whereRaw("products.name LIKE ? ESCAPE '!'", ['%'.$term.'%']);
        }
        foreach (['category_id', 'grade'] as $field) {
            if (isset($filters[$field])) {
                $query->where('products.'.$field, $filters[$field]);
            }
        }
        if (isset($filters['brand'])) {
            $brand = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['brand']);
            $query->whereRaw("CONCAT(description, CHAR(10)) LIKE ? ESCAPE '!'", ['%NXB / Thương hiệu: '.$brand."\n%"]);
        }
        if (($filters['section'] ?? null) === 'hot') {
            $query->where('is_hot', true);
        }
        if (($filters['section'] ?? null) === 'bestseller') {
            $sales = DB::table('order_items as sold_items')
                ->join('orders as sold_orders', 'sold_orders.id', '=', 'sold_items.order_id')
                ->where('sold_orders.payment_status', 'paid')
                ->whereExists(fn ($receipts) => $receipts->selectRaw('1')->from('payment_receipts')->whereColumn('payment_receipts.order_id', 'sold_orders.id')->where('allocation', 'accepted'))
                ->groupBy('sold_items.product_id')
                ->selectRaw('sold_items.product_id, SUM(sold_items.quantity) AS sold_count');

            return $query->leftJoinSub($sales, 'sales', fn ($join) => $join->on('products.id', '=', 'sales.product_id'))
                ->where(fn ($visible) => $visible->where('products.is_bestseller', true)->orWhereNotNull('sales.product_id'))
                ->select('products.*')->selectRaw('COALESCE(sales.sold_count, 0) AS sold_count')
                ->orderByDesc('sold_count')->orderByDesc('products.is_bestseller')->orderBy('products.id');
        }
        $sort = $filters['sort'] ?? 'newest';
        if (in_array($sort, ['price_asc', 'price_desc'], true)) {
            $query->orderByRaw('COALESCE(sale_price, list_price) '.($sort === 'price_asc' ? 'ASC' : 'DESC'))->orderBy('id');
        } else {
            $query->orderByDesc('created_at')->orderByDesc('id');
        }

        return $query;
    }

    public function recommendations(?int $customerId): Collection
    {
        $preferred = [];
        if ($customerId) {
            $preferred = DB::table('cart_items')->join('carts', 'carts.id', '=', 'cart_items.cart_id')
                ->join('products', 'products.id', '=', 'cart_items.product_id')->where('carts.user_id', $customerId)
                ->distinct()->pluck('products.category_id')->all();
            $purchased = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'products.id', '=', 'order_items.product_id')->where('orders.user_id', $customerId)
                ->where('orders.payment_status', 'paid')
                ->whereExists(fn ($receipts) => $receipts->selectRaw('1')->from('payment_receipts')->whereColumn('payment_receipts.order_id', 'orders.id')->where('allocation', 'accepted'))
                ->distinct()->pluck('products.category_id')->all();
            $preferred = array_unique([...$preferred, ...$purchased]);
        }
        $ranked = $this->query([])->whereColumn('stock_on_hand', '>', 'stock_reserved')->reorder()
            ->select('products.id')->selectRaw('ROW_NUMBER() OVER (PARTITION BY products.category_id ORDER BY products.created_at DESC, products.id DESC) AS category_rank');
        $ids = DB::query()->fromSub($ranked->toBase(), 'ranked')->where('category_rank', '<=', 12)->select('id');
        $groups = $this->query([])->whereIn('products.id', $ids)->get()->groupBy('category_id');
        $keys = $groups->keys()->sortBy(fn ($id) => in_array($id, $preferred, true) ? 0 : 1)->values();
        $result = collect();
        for ($offset = 0; $result->count() < 12; $offset++) {
            $added = false;
            foreach ($keys as $id) {
                if (isset($groups[$id][$offset])) {
                    $result->push($groups[$id][$offset]);
                    $added = true;
                    if ($result->count() === 12) {
                        break;
                    }
                }
            }
            if (! $added) {
                break;
            }
        }

        return $result;
    }

    public function brands(): array
    {
        return Product::query()->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->select(['id', 'description'])->toBase()->get()->map(function ($product) {
                preg_match('/^NXB \/ Thương hiệu: (.+)$/mu', $product->description ?? '', $match);

                return isset($match[1]) ? trim($match[1]) : null;
            })->filter()->countBy()->sortKeys()->map(fn ($count, $name) => ['name' => $name, 'product_count' => $count])->values()->all();
    }

    public function archive(Product $product, int $version): void
    {
        DB::transaction(function () use ($product, $version) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            if (! $locked->is_active) {
                return;
            }
            if ($locked->catalog_version !== $version) {
                throw new BusinessException('VERSION_CONFLICT', 'The product changed. Reload before deleting.');
            }
            $locked->update(['is_active' => false, 'catalog_version' => $locked->catalog_version + 1]);
        }, 3);
    }

    public function save(array $data, ?int $actorId, ?Product $existing = null): Product
    {
        try {
            return DB::transaction(function () use ($data, $actorId, $existing) {
                $product = $existing ? Product::whereKey($existing->id)->lockForUpdate()->firstOrFail() : new Product;
                if ($existing && (int) $data['version'] !== $product->catalog_version) {
                    throw new BusinessException('VERSION_CONFLICT', 'The product changed. Reload before saving.');
                }
                $stock = $data['opening_stock'] ?? 0;
                if (array_key_exists('discount_percent', $data)) {
                    $listPrice = (int) ($data['list_price'] ?? $product->list_price);
                    $data['sale_price'] = ProductPricing::salePrice($listPrice, (int) $data['discount_percent']);
                }
                unset($data['opening_stock'], $data['version'], $data['discount_percent']);
                if (! $existing && ! array_key_exists('slug', $data)) {
                    $prefix = Str::limit(Str::slug($data['name']), 200, '') ?: 'san-pham';
                    $data['slug'] = $prefix.'-'.Str::lower((string) Str::ulid());
                }
                $product->fill($data);
                $category = Category::findOrFail($product->category_id);
                if ($product->grade !== null && $category->slug !== 'sach-giao-khoa') {
                    throw ValidationException::withMessages(['grade' => 'Chỉ nhập lớp cho sản phẩm thuộc danh mục Sách giáo khoa.']);
                }
                if ($product->sale_price !== null && $product->sale_price > $product->list_price) {
                    throw ValidationException::withMessages(['sale_price' => 'Giá bán không được cao hơn giá niêm yết.']);
                }
                $product->catalog_version = $existing ? $product->catalog_version + 1 : 1;
                if (! $existing) {
                    $product->stock_on_hand = $stock;
                    $product->stock_reserved = 0;
                }
                $product->save();
                if (! $existing) {
                    InventoryMovement::create([
                        'product_id' => $product->id, 'actor_id' => $actorId, 'kind' => 'opening',
                        'delta_on_hand' => $stock, 'delta_reserved' => 0, 'resulting_on_hand' => $stock,
                        'resulting_reserved' => 0, 'reason' => 'Opening stock', 'business_key' => 'product:'.$product->id.':opening',
                    ]);
                }

                return $product->load(['category', 'images']);
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['product' => 'SKU or slug is already in use.']);
        }
    }
}
