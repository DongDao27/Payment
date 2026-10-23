<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\CatalogImportService;
use App\Services\CatalogService;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class CatalogImportTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
        Storage::fake('public');
        $this->directory = storage_path('framework/testing/import-'.bin2hex(random_bytes(8)));
        File::makeDirectory($this->directory.'/images', recursive: true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_dry_run_then_import_preserves_owner_prices_stock_images_and_opening_ledger(): void
    {
        $file = $this->manifest();
        $service = app(CatalogImportService::class);
        $this->assertSame(['create' => 1, 'skip_existing' => 0, 'applied' => false], $service->run($file));
        $this->assertDatabaseCount('products', 0);
        $service->run($file, true);
        $product = Product::firstOrFail();
        $this->assertSame(25000, $product->list_price);
        $this->assertSame(20000, $product->sale_price);
        $this->assertSame(7, $product->stock_on_hand);
        $this->assertSame(0, $product->stock_reserved);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $product->id, 'kind' => 'opening', 'delta_on_hand' => 7, 'actor_id' => null]);
        Storage::disk('public')->assertExists($product->images()->firstOrFail()->path);
        $product->update(['stock_on_hand' => 3]);
        $this->assertSame(['create' => 0, 'skip_existing' => 1, 'applied' => true], $service->run($file, true));
        $this->assertSame(3, $product->fresh()->stock_on_hand);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseCount('product_images', 1);
    }

    public function test_missing_owner_price_or_invalid_image_rejects_entire_batch_before_writing(): void
    {
        foreach ([['store_price' => null], ['image' => '../secret.png'], ['image_sha256' => str_repeat('0', 64)]] as $invalid) {
            try {
                app(CatalogImportService::class)->run($this->manifest($invalid), true);
                $this->fail('Invalid manifest accepted.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('products', 0);
                $this->assertSame([], Storage::disk('public')->allFiles());
            }
        }
    }

    public function test_second_product_failure_rolls_back_all_products_ledgers_and_new_files(): void
    {
        $file = $this->manifest();
        $manifest = json_decode(file_get_contents($file), true);
        $second = $manifest['products'][0];
        $second['sku'] = 'TT-TEST-002';
        $second['slug'] = 'test-product-2';
        $second['image'] = 'images/TT-TEST-002.png';
        copy($this->directory.'/images/TT-TEST-001.png', $this->directory.'/'.$second['image']);
        $manifest['products'][] = $second;
        file_put_contents($file, json_encode($manifest));
        $real = new CatalogService;
        $calls = 0;
        $catalog = $this->mock(CatalogService::class);
        $catalog->shouldReceive('save')->twice()->andReturnUsing(function ($data, $actor) use ($real, &$calls) {
            if (++$calls === 2) {
                throw new RuntimeException('Injected failure');
            }

            return $real->save($data, $actor);
        });
        try {
            app(CatalogImportService::class)->run($file, true);
            $this->fail('Expected failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected failure', $exception->getMessage());
            $this->assertDatabaseCount('products', 0);
            $this->assertDatabaseCount('inventory_movements', 0);
            $this->assertDatabaseCount('product_images', 0);
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    private function manifest(array $override = []): string
    {
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l9sAAAAASUVORK5CYII=');
        file_put_contents($this->directory.'/images/TT-TEST-001.png', $image);
        $data = ['sha256' => str_repeat('a', 64), 'products' => [[
            'sku' => 'TT-TEST-001', 'slug' => 'test-product', 'name' => 'Test product',
            'category' => 'Sách giáo khoa', 'subcategory' => 'Lớp 1–5', 'publisher' => 'NXB', 'author' => '', 'grade' => 1,
            'store_price' => 20000, 'source_cover_price' => 25000, 'store_stock' => 7,
            'image' => 'images/TT-TEST-001.png', 'image_sha256' => hash('sha256', $image),
            ...$override,
        ]]];
        $file = $this->directory.'/catalog.json';
        file_put_contents($file, json_encode($data));

        return $file;
    }
}
