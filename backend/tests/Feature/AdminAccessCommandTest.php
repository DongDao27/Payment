<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccessCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_command_is_read_only_and_never_prints_password_hash(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $hash = $admin->password;
        $this->artisan('admin:account')->doesntExpectOutputToContain($hash)->assertSuccessful();
        $this->assertSame($hash, $admin->fresh()->password);
        $this->artisan('admin:password', ['--no-interaction' => true])->assertFailed();
        $this->assertSame($hash, $admin->fresh()->password);
    }

    public function test_local_password_recovery_requires_confirmation_and_revokes_only_admin_sessions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $hash = $admin->password;
        $this->artisan('admin:password')->expectsConfirmation('Replace the existing admin password and sign out every admin session?', 'no')->assertFailed();
        $this->assertSame($hash, $admin->fresh()->password);
        DB::table('sessions')->insert(['id' => 'admin-session', 'user_id' => $admin->id, 'payload' => '', 'last_activity' => time()]);
        $this->artisan('admin:password')->expectsConfirmation('Replace the existing admin password and sign out every admin session?', 'yes')->expectsQuestion('New password (12–128 characters)', 'NewTestPassword-2026')->expectsQuestion('Confirm new password', 'NewTestPassword-2026')->assertSuccessful();
        $this->assertTrue(Hash::check('NewTestPassword-2026', $admin->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'admin-session']);
        $this->assertDatabaseCount('users', 1);
    }
}
