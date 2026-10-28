<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_requires_session_and_only_updates_current_user(): void
    {
        $user = User::factory()->create(['name' => 'Tên cũ', 'phone' => null]);
        $other = User::factory()->create();
        $data = ['name' => 'Tên mới', 'email' => $user->email, 'phone' => null];
        $this->patchJson('/api/v1/auth/profile', $data)->assertUnauthorized();
        $this->actingAs($user)->patchJson('/api/v1/auth/profile', $data)->assertOk()->assertJsonPath('data.name', 'Tên mới')->assertJsonMissingPath('data.password');
        $this->actingAs($user->fresh())->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.name', 'Tên mới');
        $this->assertSame('Tên mới', $user->fresh()->name);
        $this->assertSame($other->name, $other->fresh()->name);
        foreach (['role' => 'admin', 'user_id' => $other->id, 'password' => 'Changed-password-123'] as $field => $value) {
            $this->patchJson('/api/v1/auth/profile', [...$data, $field => $value])->assertUnprocessable();
        }
        $this->assertSame('customer', $user->fresh()->role);
    }

    public function test_identity_change_requires_current_password_and_normalizes_unique_identity(): void
    {
        $user = User::factory()->create(['password' => 'Current-password-123', 'email_verified_at' => now()]);
        $other = User::factory()->create(['email' => 'taken@example.test']);
        $this->actingAs($user);
        DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => 'old-token-hash', 'created_at' => now()]);
        $data = ['name' => $user->name, 'email' => ' NEW@EXAMPLE.TEST ', 'phone' => '0915466951'];
        $this->patchJson('/api/v1/auth/profile', $data)->assertUnprocessable()->assertJsonStructure(['error' => ['fields' => ['current_password']]]);
        $this->patchJson('/api/v1/auth/profile', [...$data, 'current_password' => 'Wrong-password-123'])->assertUnprocessable();
        $this->assertSame($user->email, $user->fresh()->email);
        $this->patchJson('/api/v1/auth/profile', [...$data, 'current_password' => 'Current-password-123'])->assertOk()
            ->assertJsonPath('data.email', 'new@example.test')->assertJsonPath('data.phone', '+84915466951');
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->patchJson('/api/v1/auth/profile', [...$data, 'email' => $other->email, 'current_password' => 'Current-password-123'])->assertUnprocessable();
        $this->patchJson('/api/v1/auth/profile', [...$data, 'email' => null, 'phone' => null])->assertUnprocessable();
    }
}
