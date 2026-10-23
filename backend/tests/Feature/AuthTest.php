<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Policies\CartPolicy;
use App\Policies\OrderPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_normalizes_email_and_never_exposes_secrets(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registration(['email' => ' Customer@Example.Test ']))
            ->assertCreated()->assertJsonPath('data.email', 'customer@example.test')
            ->assertJsonPath('data.role', 'customer')->assertJsonMissingPath('data.password')
            ->assertJsonStructure(['meta' => ['request_id', 'server_time']]);
        $this->assertTrue(Hash::check('a-private-password', User::first()->password));
        $this->postJson('/api/v1/auth/register', $this->registration())
            ->assertUnprocessable()->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_phone_only_registration_normalizes_and_rejects_duplicates(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registration(['email' => null, 'phone' => '0912345678']))
            ->assertCreated()->assertJsonPath('data.phone', '+84912345678');
        $this->postJson('/api/v1/auth/register', $this->registration(['email' => null, 'phone' => '+84912345678']))
            ->assertUnprocessable();
    }

    public function test_registration_rejects_admin_fields_invalid_phone_and_missing_identity(): void
    {
        foreach ([['role' => 'admin'], ['is_admin' => true], ['email' => null, 'phone' => '+14155550100'], ['email' => null, 'phone' => null]] as $invalid) {
            $this->postJson('/api/v1/auth/register', $this->registration($invalid))->assertUnprocessable();
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_by_email_and_phone_then_logout_invalidates_session(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.test', 'phone' => '+84912345678', 'password' => 'a-private-password']);
        foreach ([' Customer@Example.Test ', '0912345678', '+84912345678'] as $identifier) {
            $this->postJson('/api/v1/auth/login', ['identifier' => $identifier, 'password' => 'a-private-password'])
                ->assertOk()->assertJsonPath('data.id', $user->id);
            $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonMissingPath('data.remember_token');
            $this->postJson('/api/v1/auth/logout')->assertNoContent();
            $this->assertGuest('web');
        }
    }

    public function test_unauthenticated_me_has_error_envelope_and_no_shared_cache(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_login_is_throttled_and_does_not_enumerate_accounts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['identifier' => 'absent@example.test', 'password' => 'bad-password'])
                ->assertUnprocessable()->assertJsonPath('error.fields.identifier.0', 'The credentials are invalid.');
        }
        $this->postJson('/api/v1/auth/login', ['identifier' => 'absent@example.test', 'password' => 'bad-password'])
            ->assertStatus(429)->assertJsonPath('error.code', 'TOO_MANY_REQUESTS');
    }

    public function test_role_middleware_denies_customer_and_accepts_admin(): void
    {
        // Test-only probe; real admin endpoints arrive in B03.
        Route::middleware(['web', 'auth:sanctum', 'role:admin'])->get('/api/v1/_test/admin', fn () => response()->json(['ok' => true]));
        $this->actingAs(User::factory()->create())->getJson('/api/v1/_test/admin')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->getJson('/api/v1/_test/admin')->assertOk();
    }

    public function test_ownership_policies_hide_other_customers_resources(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = new Order(['user_id' => $owner->id]);
        $cart = new Cart(['user_id' => $owner->id]);
        $this->assertTrue((new OrderPolicy)->view($owner, $order)->allowed());
        $this->assertSame(404, (new OrderPolicy)->view($other, $order)->status());
        $this->assertSame(404, (new CartPolicy)->update($other, $cart)->status());
    }

    private function registration(array $overrides = []): array
    {
        return array_merge(['name' => 'Test Customer', 'email' => 'customer@example.test', 'password' => 'a-private-password', 'password_confirmation' => 'a-private-password'], $overrides);
    }

    public function test_long_passwords_are_not_truncated_at_72_bytes(): void
    {
        $password = str_repeat('a', 72).'tail-one';
        $this->postJson('/api/v1/auth/register', $this->registration(['password' => $password, 'password_confirmation' => $password]))->assertCreated();
        $this->assertTrue(str_starts_with(User::first()->password, '$argon2id$'));
        $this->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->postJson('/api/v1/auth/login', ['identifier' => 'customer@example.test', 'password' => str_repeat('a', 72).'tail-two'])->assertUnprocessable();
        $this->postJson('/api/v1/auth/login', ['identifier' => 'customer@example.test', 'password' => $password])->assertOk();
    }
}
