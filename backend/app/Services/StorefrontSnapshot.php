<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class StorefrontSnapshot
{
    public static function invalidate(): void
    {
        // A revision prevents a slow old reader from repopulating the current key.
        Cache::forever('storefront:revision', (string) Str::uuid());
    }

    public function data(): array
    {
        $key = 'storefront:v1:'.Cache::get('storefront:revision', 'initial');
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }
        try {
            return Cache::lock($key.':lock', 10)->block(3, function () use ($key) {
                return Cache::remember($key, 20, function () {
                    $catalog = app(CatalogService::class);

                    $data = [
                        'categories' => CategoryResource::collection(Category::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get())->resolve(),
                        'hot' => ProductResource::collection($catalog->query(['section' => 'hot'])->limit(12)->get())->resolve(),
                        'bestseller' => ProductResource::collection($catalog->query(['section' => 'bestseller'])->limit(7)->get())->resolve(),
                        'brands' => $catalog->brands(),
                        'new' => ProductResource::collection($catalog->query(['section' => 'new'])->limit(12)->get())->resolve(),
                        'recommended' => ProductResource::collection($catalog->recommendations(null))->resolve(),
                    ];

                    return json_decode(json_encode($data, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
                });
            });
        } catch (LockTimeoutException) {
            throw new BusinessException('STOREFRONT_BUSY', 'Please retry shortly.', 503);
        }
    }
}
