<?php

namespace App\Jobs;

use App\Models\ProductImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DeleteProductImageFile implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public string $disk, public string $path) {}

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(): void
    {
        if (ProductImage::where('storage_disk', $this->disk)->where('path', $this->path)->exists()) {
            return;
        }
        if (! Storage::disk($this->disk)->delete($this->path)) {
            throw new RuntimeException('Image cleanup failed; retry required.');
        }
    }
}
