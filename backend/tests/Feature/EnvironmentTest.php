<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EnvironmentTest extends TestCase
{
    public function test_health_endpoint_boots_without_exposing_configuration(): void
    {
        $this->get('/up')->assertOk()->assertDontSee('DB_PASSWORD');
    }

    public function test_isolated_mysql_database_supports_required_constraints(): void
    {
        $this->assertSame('thu_thanh_test', DB::selectOne('SELECT DATABASE() AS db')->db);
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertGreaterThanOrEqual('8.0.16', DB::selectOne('SELECT VERSION() AS version')->version);
        $this->artisan('app:environment-check')->assertSuccessful();
    }
}
