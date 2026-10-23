<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiRouteContractTest extends TestCase
{
    public function test_every_documented_api_method_and_path_matches_source(): void
    {
        $spec = json_decode(file_get_contents(base_path('../docs/openapi.json')), true, flags: JSON_THROW_ON_ERROR);
        $expected = [];
        foreach ($spec['paths'] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                $expected[] = strtoupper($method).' '.$path;
            }
        }
        $actual = [];
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $actual[] = $method.' /'.substr($route->uri(), strlen('api/v1/'));
            }
        }
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);
    }

    public function test_unexpected_api_errors_never_expose_debug_stack_or_sql(): void
    {
        config(['app.debug' => true]);
        Route::get('/api/v1/_test/failure', fn () => throw new \RuntimeException('Internal SQL/config fixture'));
        $this->getJson('/api/v1/_test/failure')->assertStatus(500)->assertJsonPath('error.code', 'SERVER_ERROR')
            ->assertDontSee('Internal SQL/config fixture')->assertJsonMissingPath('trace')->assertJsonMissingPath('exception');
    }
}
