<?php

namespace Tests\Feature;

use App\Jobs\DeleteProductImageFile;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_png_upload_gets_random_path_and_owned_delete_schedules_cleanup(): void
    {
        Storage::fake('public');
        Queue::fake();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = Product::factory()->create();
        $file = UploadedFile::fake()->createWithContent('original.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jGesAAAAASUVORK5CYII='));
        $imageId = $this->postJson('/api/v1/admin/products/'.$product->id.'/images', ['image' => $file, 'alt_text' => 'Test image'])->assertCreated()->json('data.id');
        $image = ProductImage::findOrFail($imageId);
        $this->assertStringNotContainsString('original', $image->path);
        Storage::disk('public')->assertExists($image->path);
        $this->deleteJson('/api/v1/admin/products/'.Product::factory()->create()->id.'/images/'.$imageId)->assertNotFound();
        $this->deleteJson('/api/v1/admin/products/'.$product->id.'/images/'.$imageId)->assertNoContent();
        Queue::assertPushed(DeleteProductImageFile::class, fn ($job) => $job->path === $image->path && $job->afterCommit);
        (new DeleteProductImageFile('public', $image->path))->handle();
        (new DeleteProductImageFile('public', $image->path))->handle();
        Storage::disk('public')->assertMissing($image->path);
        $this->assertDatabaseMissing('product_images', ['id' => $imageId]);
    }

    public function test_upload_rejects_script_svg_fake_mime_and_oversize(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = Product::factory()->create();
        foreach ([
            UploadedFile::fake()->createWithContent('fake.jpg', '<?php echo "bad";'),
            UploadedFile::fake()->createWithContent('test.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            UploadedFile::fake()->create('huge.png', 5121, 'image/png'),
        ] as $file) {
            $this->postJson('/api/v1/admin/products/'.$product->id.'/images', ['image' => $file])->assertUnprocessable();
        }
        $this->assertDatabaseCount('product_images', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }
}
