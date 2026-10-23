<?php

namespace App\Services;

use App\Jobs\DeleteProductImageFile;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProductImageService
{
    public function store(Product $product, UploadedFile $file, array $attributes): ProductImage
    {
        $path = $file->store('products', 'public');
        if ($path === false) {
            throw new RuntimeException('Image storage failed.');
        }
        try {
            return DB::transaction(function () use ($product, $file, $attributes, $path) {
                Product::whereKey($product->id)->lockForUpdate()->firstOrFail()->increment('catalog_version');

                return ProductImage::create([
                    'product_id' => $product->id, 'storage_disk' => 'public', 'path' => $path,
                    'mime' => $file->getMimeType(), 'byte_size' => $file->getSize(),
                    'alt_text' => $attributes['alt_text'] ?? null, 'sort_order' => $attributes['sort_order'] ?? 0,
                ]);
            }, 3);
        } catch (Throwable $exception) {
            // No database row references this random file after transaction rollback.
            if (! Storage::disk('public')->delete($path)) {
                DeleteProductImageFile::dispatch('public', $path);
            }
            throw $exception;
        }
    }

    public function delete(Product $product, ProductImage $image): void
    {
        abort_unless($image->product_id === $product->id, 404);
        DB::transaction(function () use ($product, $image) {
            Product::whereKey($product->id)->lockForUpdate()->firstOrFail()->increment('catalog_version');
            $image = ProductImage::whereKey($image->id)->lockForUpdate()->firstOrFail();
            DeleteProductImageFile::dispatch($image->storage_disk, $image->path)->afterCommit();
            $image->delete();
        }, 3);
    }
}
