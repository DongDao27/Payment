<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\CatalogService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

$app = require __DIR__.'/e2e-bootstrap.php';
Artisan::call('migrate', ['--force' => true]);
$admin = User::where('role', 'admin')->first() ?? new User;
$admin->forceFill(['name' => 'Quản trị E2E', 'email' => 'admin-e2e@example.test', 'phone' => null,
    'role' => 'admin', 'password' => 'E2E-Local-Password-2026!'])->save();
$category = Category::firstOrCreate(['slug' => 'e2e-books'], [
    'name' => 'Sách kiểm thử E2E', 'is_active' => true, 'sort_order' => 0,
]);
$cover = glob($root.'/frontend/public/catalog-hd/TT-VH-001.*')[0];
for ($i = 1; $i <= 7; $i++) {
    $coverPath = 'products/e2e-cover-'.$i.'.'.pathinfo($cover, PATHINFO_EXTENSION);
    Storage::disk('public')->put($coverPath, file_get_contents($cover));
    $product = Product::where('sku', 'E2E-BOOK-'.$i)->first();
    if (! $product) {
        $product = $app->make(CatalogService::class)->save([
            'sku' => 'E2E-BOOK-'.$i, 'name' => 'Sách kiểm thử E2E '.$i,
            'category_id' => $category->id, 'list_price' => 100000,
            'discount_percent' => 15, 'opening_stock' => 500,
            'description' => "Tác phẩm dành cho kiểm thử tự động.\nNXB / Thương hiệu: E2E Books",
            'is_active' => true, 'is_bestseller' => true,
        ], $admin->id);
    }
    ProductImage::updateOrCreate(['product_id' => $product->id, 'sort_order' => 0], [
        'path' => $coverPath,
        'storage_disk' => 'public', 'mime' => mime_content_type($cover), 'byte_size' => filesize($cover),
        'sort_order' => 0, 'alt_text' => $product->name,
    ]);
}
echo "E2E fixtures ready: isolated MySQL thu_thanh_test and private storage; main/Sandbox unchanged.\n";
