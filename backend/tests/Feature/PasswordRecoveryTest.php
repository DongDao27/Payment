<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\CustomerResetPassword;
use App\Services\PasswordRecoveryService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_is_generic_normalized_throttled_and_token_is_not_returned(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reader@example.test']);
        $first = $this->postJson('/api/v1/auth/forgot-password', ['email' => ' Reader@Example.Test '])
            ->assertStatus(202)->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $again = $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertStatus(202)->json('data');
        $missing = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'missing@example.test'])->assertStatus(202)->json('data');
        $this->assertSame($first, $missing);
        $this->assertSame($first, $again);
        Notification::assertSentToTimes($user, CustomerResetPassword::class, 1);
        Notification::assertSentTo($user, CustomerResetPassword::class, function ($notification) use ($user, $first) {
            $this->assertInstanceOf(ShouldBeEncrypted::class, $notification);
            $this->assertNotSame($notification->token, DB::table('password_reset_tokens')->value('token'));
            $this->assertStringNotContainsString($notification->token, json_encode($first));
            $url = $notification->toMail($user)->actionUrl;
            $this->assertStringStartsWith('http://localhost:3000/reset-password?', $url);
            $this->assertStringNotContainsString('evil.example', $url);

            return true;
        });
    }

    public function test_valid_reset_rotates_password_consumes_token_revokes_only_own_sessions_and_does_not_login(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach ([$user, $other] as $account) {
            DB::table('sessions')->insert(['id' => 'session-'.$account->id, 'user_id' => $account->id, 'payload' => '', 'last_activity' => time()]);
        }
        $token = Password::createToken($user);
        $input = ['email' => $user->email, 'token' => $token, 'password' => 'updated-private-password', 'password_confirmation' => 'updated-private-password'];
        $this->postJson('/api/v1/auth/reset-password', $input)->assertOk()->assertJsonMissingPath('data.token');
        $this->assertTrue(Hash::check($input['password'], $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('sessions', ['user_id' => $other->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertGuest('web');
        $this->postJson('/api/v1/auth/reset-password', $input)->assertUnprocessable();
    }

    public function test_expired_wrong_email_invalid_tokens_and_password_confirmation_do_not_change_password(): void
    {
        $user = User::factory()->create();
        $original = $user->password;
        $token = Password::createToken($user);
        $input = ['email' => $user->email, 'token' => $token, 'password' => 'updated-private-password', 'password_confirmation' => 'updated-private-password'];
        $this->postJson('/api/v1/auth/reset-password', array_replace($input, ['token' => str_repeat('a', 64)]))->assertUnprocessable();
        $this->postJson('/api/v1/auth/reset-password', array_replace($input, ['email' => 'other@example.test']))->assertUnprocessable();
        $this->postJson('/api/v1/auth/reset-password', array_replace($input, ['password_confirmation' => 'different']))->assertUnprocessable();
        $this->travel(61)->minutes();
        $this->postJson('/api/v1/auth/reset-password', $input)->assertUnprocessable();
        $this->assertSame($original, $user->fresh()->password);
    }

    public function test_public_recovery_never_recovers_admin_accounts_or_phone_only_users(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['email' => null, 'phone' => '+84912345678']);
        $original = $admin->password;
        $this->postJson('/api/v1/auth/forgot-password', ['email' => $admin->email])->assertStatus(202);
        Notification::assertNothingSent();
        $this->postJson('/api/v1/auth/forgot-password', ['email' => '0912345678'])->assertUnprocessable();
        $this->postJson('/api/v1/auth/reset-password', ['email' => $admin->email, 'token' => Password::createToken($admin), 'password' => 'updated-private-password', 'password_confirmation' => 'updated-private-password'])->assertUnprocessable();
        $this->assertSame($original, $admin->fresh()->password);
    }

    public function test_strict_fields_rate_limits_and_production_log_mail_guard(): void
    {
        Notification::fake();
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'reader@example.test', 'role' => 'admin'])->assertUnprocessable();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/forgot-password', ['email' => 'rate@example.test'])->assertStatus(202);
        }
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'rate@example.test'])->assertTooManyRequests();
        $this->app->instance('env', 'production');
        config(['mail.default' => 'log']);
        $this->withSession(['_token' => 'production-csrf'])->withHeader('X-CSRF-TOKEN', 'production-csrf')
            ->postJson('/api/v1/auth/forgot-password', ['email' => 'fresh@example.test'])->assertStatus(503)->assertJsonPath('error.code', 'RECOVERY_UNAVAILABLE');
    }

    public function test_recovery_posts_require_csrf_outside_the_test_middleware_bypass(): void
    {
        $this->app->instance('env', 'local');
        foreach (['forgot-password', 'reset-password'] as $path) {
            $this->postJson('/api/v1/auth/'.$path, [])->assertStatus(419);
        }
    }

    public function test_password_hash_binding_rejects_stale_non_database_sessions_and_private_home(): void
    {
        $user = User::factory()->create(['password' => 'original-private-password']);
        $this->postJson('/api/v1/auth/login', ['identifier' => $user->email, 'password' => 'original-private-password'])->assertOk()->assertSessionHas('password_hash_web');
        $oldSession = session()->all();
        app(PasswordRecoveryService::class)->reset([
            'email' => $user->email, 'token' => Password::createToken($user),
            'password' => 'updated-private-password', 'password_confirmation' => 'updated-private-password',
        ]);
        foreach (['auth/me', 'home', 'cart'] as $path) {
            Auth::forgetGuards();
            $this->withSession($oldSession)->getJson('/api/v1/'.$path)->assertUnauthorized();
        }
        Auth::forgetGuards();
        $this->postJson('/api/v1/auth/login', ['identifier' => $user->email, 'password' => 'updated-private-password'])->assertOk();
    }
}
