<?php

namespace App\Services;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CatalogImportService
{
    private const CATEGORIES = [
        'Sách giáo khoa' => 'sach-giao-khoa', 'Sách văn học' => 'sach-van-hoc',
        'VPP · Dụng cụ học sinh' => 'van-phong-pham', 'Đồ chơi' => 'do-choi',
        'Truyện tranh' => 'truyen-tranh', 'Quà lưu niệm' => 'qua-luu-niem',
    ];

    public function __construct(private CatalogService $catalog) {}

    public function run(string $file, bool $apply = false): array
    {
        $manifest = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        Validator::make($manifest, [
            'sha256' => ['required', 'regex:/^[a-f0-9]{64}$/D'],
            'products' => ['required', 'array', 'min:1', 'max:10000'],
            'products.*.sku' => ['required', 'distinct', 'regex:/^TT-[A-Z]+-[0-9]+$/D', 'max:100'],
            'products.*.slug' => ['required', 'distinct', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', 'max:255'],
            'products.*.name' => ['required', 'string', 'max:255'],
            'products.*.store_price' => ['required', 'integer', 'between:0,1000000000'],
            'products.*.store_stock' => ['required', 'integer', 'between:0,2147483647'],
            'products.*.source_cover_price' => ['nullable', 'integer', 'between:0,1000000000'],
            'products.*.grade' => ['nullable', 'integer', 'between:1,12'],
            'products.*.image_sha256' => ['required', 'regex:/^[a-f0-9]{64}$/D'],
            'products.*.subcategory' => ['required', 'string', 'max:120'],
            'products.*.publisher' => ['nullable', 'string', 'max:255'],
            'products.*.author' => ['nullable', 'string', 'max:255'],
        ])->validate();
        $categories = Category::all()->keyBy('slug');
        $directory = realpath(dirname($file));
        $prepared = [];
        $skipped = 0;
        foreach ($manifest['products'] as $row) {
            $slug = self::CATEGORIES[$row['category'] ?? ''] ?? null;
            $category = $slug ? $categories->get($slug) : null;
            if (! $category || (! empty($row['grade']) && $slug !== 'sach-giao-khoa')) {
                throw ValidationException::withMessages(['category' => 'Unknown category or invalid grade for '.$row['sku']]);
            }
            $existing = Product::where('sku', $row['sku'])->first();
            if ($existing) {
                if ($existing->slug !== $row['slug']) {
                    throw ValidationException::withMessages(['sku' => 'Existing SKU has a different slug: '.$row['sku']]);
                }
                $skipped++;

                continue;
            }
            if (Product::where('slug', $row['slug'])->exists()) {
                throw ValidationException::withMessages(['slug' => 'Slug already belongs to another SKU.']);
            }
            $relative = $row['image'] ?? '';
            if (! preg_match('/^images\/'.preg_quote($row['sku'], '/').'\.(jpg|png)$/D', $relative)) {
                throw ValidationException::withMessages(['image' => 'Invalid image path.']);
            }
            $imageFile = realpath($directory.DIRECTORY_SEPARATOR.$relative);
            if (! $imageFile || ! str_starts_with($imageFile, $directory.DIRECTORY_SEPARATOR)) {
                throw ValidationException::withMessages(['image' => 'Image must exist inside the import directory.']);
            }
            $image = file_get_contents($imageFile);
            $size = getimagesizefromstring($image);
            if (! $size || ! in_array($size['mime'], ['image/jpeg', 'image/png'], true) || strlen($image) > 5 * 1024 * 1024 || max($size[0], $size[1]) > 8192 || ! hash_equals($row['image_sha256'], hash('sha256', $image))) {
                throw ValidationException::withMessages(['image' => 'Image MIME, size or hash invalid: '.$row['sku']]);
            }
            $price = (int) $row['store_price'];
            $list = max($price, (int) ($row['source_cover_price'] ?? $price));
            $prepared[] = [
                'data' => [
                    'category_id' => $category->id, 'sku' => $row['sku'], 'slug' => $row['slug'], 'name' => $row['name'],
                    'description' => implode("\n", array_filter(['Nhóm: '.$row['subcategory'], empty($row['publisher']) ? null : 'NXB / Thương hiệu: '.$row['publisher'], empty($row['author']) ? null : 'Tác giả: '.$row['author']])),
                    'grade' => $row['grade'], 'list_price' => $list, 'sale_price' => $list > $price ? $price : null,
                    'opening_stock' => (int) $row['store_stock'], 'is_active' => true,
                ],
                'path' => 'products/imports/'.$manifest['sha256'].'/basename-'.basename($relative),
                'image' => $image, 'mime' => $size['mime'],
            ];
        }
        if (! $apply) {
            return ['create' => count($prepared), 'skip_existing' => $skipped, 'applied' => false];
        }
        $createdFiles = [];
        $disk = Storage::disk('public');
        try {
            // Filesystem I/O occurs before taking DB locks; no remote downloads.
            foreach ($prepared as $item) {
                if ($disk->exists($item['path'])) {
                    if (! hash_equals(hash('sha256', $item['image']), hash('sha256', $disk->get($item['path'])))) {
                        throw new RuntimeException('Import image collision.');
                    }

                    continue;
                }
                if (! $disk->put($item['path'], $item['image'])) {
                    throw new RuntimeException('Could not store import image.');
                }
                $createdFiles[] = $item['path'];
            }
            DB::transaction(function () use ($prepared, $manifest) {
                foreach ($prepared as $item) {
                    // Null actor denotes this explicit trusted CLI/system import, not a fake admin.
                    $product = $this->catalog->save($item['data'], null);
                    InventoryMovement::where('business_key', 'product:'.$product->id.':opening')->update(['reason' => 'Owner workbook '.$manifest['sha256'].'; SKU '.$product->sku]);
                    ProductImage::create(['product_id' => $product->id, 'storage_disk' => 'public', 'path' => $item['path'], 'mime' => $item['mime'], 'byte_size' => strlen($item['image']), 'sort_order' => 0, 'alt_text' => $product->name]);
                }
            }, 3);
        } catch (Throwable $exception) {
            foreach ($createdFiles as $path) {
                if (! ProductImage::where('storage_disk', 'public')->where('path', $path)->exists()) {
                    $disk->delete($path);
                }
            }
            throw $exception;
        }

        return ['create' => count($prepared), 'skip_existing' => $skipped, 'applied' => true];
    }
}
