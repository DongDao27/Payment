<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_implemented_read_responses_follow_openapi_contract(): void
    {
        $spec = json_decode(file_get_contents(base_path('../docs/openapi.json')), true, flags: JSON_THROW_ON_ERROR);
        $product = Product::factory()->create(['is_hot' => true, 'is_bestseller' => true]);
        $this->actingAs(User::factory()->create());
        foreach (['/products', '/products/'.$product->id, '/categories', '/home', '/auth/me'] as $path) {
            $specPath = $path === '/products/'.$product->id ? '/products/{product}' : $path;
            $response = $this->getJson('/api/v1'.$path)->assertOk();
            $schema = $spec['paths'][$specPath]['get']['responses']['200']['content']['application/json']['schema'];
            $this->assertSchema($response->json(), $schema, $spec);
        }
    }

    private function assertSchema(mixed $value, array $schema, array $spec): void
    {
        if (isset($schema['$ref'])) {
            $target = $spec;
            foreach (explode('/', substr($schema['$ref'], 2)) as $part) {
                $target = $target[$part];
            }
            $this->assertSchema($value, $target, $spec);

            return;
        }
        if ($value === null && ($schema['nullable'] ?? false)) {
            $this->assertNull($value);

            return;
        }
        switch ($schema['type'] ?? null) {
            case 'object':
                $this->assertIsArray($value);
                foreach ($schema['required'] ?? [] as $key) {
                    $this->assertArrayHasKey($key, $value);
                }
                foreach ($value as $key => $child) {
                    if (($schema['additionalProperties'] ?? true) === false) {
                        $this->assertArrayHasKey($key, $schema['properties']);
                    }
                    if (isset($schema['properties'][$key])) {
                        $this->assertSchema($child, $schema['properties'][$key], $spec);
                    }
                }
                break;
            case 'array':
                $this->assertIsArray($value);
                $this->assertTrue(array_is_list($value));
                foreach ($value as $child) {
                    $this->assertSchema($child, $schema['items'], $spec);
                }
                break;
            case 'string':
                $this->assertIsString($value);
                break;
            case 'integer':
                $this->assertIsInt($value);
                if (isset($schema['minimum'])) {
                    $this->assertGreaterThanOrEqual($schema['minimum'], $value);
                }
                break;
            case 'boolean':
                $this->assertIsBool($value);
                break;
        }
        if (isset($schema['enum'])) {
            $this->assertContains($value, $schema['enum']);
        }
    }

    public function test_cart_quote_and_checkout_responses_follow_openapi_contract(): void
    {
        $spec = json_decode(file_get_contents(base_path('../docs/openapi.json')), true, flags: JSON_THROW_ON_ERROR);
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create();
        $cart = $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();
        $this->assertSchema($cart->json(), $spec['paths']['/cart/items']['post']['responses']['200']['content']['application/json']['schema'], $spec);
        $quote = $this->postJson('/api/v1/checkout/quotes', ['fulfillment_method' => 'pickup', 'recipient_name' => 'Test recipient', 'recipient_phone' => '0912345678'])->assertCreated();
        $this->assertSchema($quote->json(), $spec['paths']['/checkout/quotes']['post']['responses']['201']['content']['application/json']['schema'], $spec);
        $order = $this->postJson('/api/v1/orders', ['quote_id' => $quote->json('data.id')], ['Idempotency-Key' => 'contract-order-key'])->assertCreated();
        $this->assertSchema($order->json(), $spec['paths']['/orders']['post']['responses']['201']['content']['application/json']['schema'], $spec);
    }
}
