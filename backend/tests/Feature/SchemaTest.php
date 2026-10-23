<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentAttempt;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\OrderFixture;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use OrderFixture, RefreshDatabase;

    public function test_default_seed_is_idempotent_and_contains_no_accounts_or_demo_stock(): void
    {
        $this->seed(DatabaseSeeder::class);
        Category::where('slug', 'sach-giao-khoa')->update(['is_active' => false]);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('categories', 6);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('products', 0);
        $this->assertFalse(Category::where('slug', 'sach-giao-khoa')->first()->is_active);
    }

    public function test_mysql_rejects_invalid_stock_price_grade_and_quantity(): void
    {
        $product = Product::factory()->create();
        foreach ([['stock_reserved' => 11], ['stock_on_hand' => -1], ['sale_price' => 35001], ['grade' => 13]] as $invalid) {
            try {
                DB::table('products')->where('id', $product->id)->update($invalid);
                $this->fail('MySQL accepted an invalid product.');
            } catch (QueryException $exception) {
                $this->assertSame('HY000', $exception->errorInfo[0]);
            }
        }
        $user = User::factory()->create();
        $cart = DB::table('carts')->insertGetId(['user_id' => $user->id]);
        $this->expectException(QueryException::class);
        DB::table('cart_items')->insert(['cart_id' => $cart, 'product_id' => $product->id, 'quantity' => 0]);
    }

    public function test_database_requires_identity_and_at_most_one_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertTrue($admin->isAdmin());
        $this->expectException(QueryException::class);
        User::factory()->create(['role' => 'admin']);
    }

    public function test_database_rejects_an_account_without_email_or_phone(): void
    {
        $this->expectException(QueryException::class);
        User::factory()->create(['email' => null, 'phone' => null]);
    }

    public function test_admin_bootstrap_is_idempotent_and_never_resets_password(): void
    {
        config(['admin.email' => ' Admin@Example.test ', 'admin.password' => 'private-test-password']);
        $this->artisan('admin:create', ['--name' => 'Test Admin', '--no-interaction' => true])->assertSuccessful();
        config(['admin.password' => 'another-test-password']);
        $this->artisan('admin:create', ['--name' => 'Other', '--no-interaction' => true])->assertSuccessful();
        $this->assertDatabaseCount('users', 1);
        $admin = User::first();
        $this->assertSame('admin@example.test', $admin->email);
        $this->assertTrue(Hash::check('private-test-password', $admin->password));
    }

    public function test_noninteractive_admin_bootstrap_has_no_default_secret(): void
    {
        config(['admin.email' => null, 'admin.phone' => null, 'admin.password' => null]);
        $this->artisan('admin:create', ['--name' => 'Test Admin', '--no-interaction' => true])->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_mysql_allows_only_one_accepted_receipt_per_order(): void
    {
        $order = $this->orderFor([Product::factory()->create()]);
        $attempt = PaymentAttempt::create(['order_id' => $order->id, 'provider' => 'fixture', 'provider_reference' => 'fixture-ref', 'requested_amount' => 35000, 'expires_at' => now()->addMinutes(5)]);
        $data = ['provider' => 'fixture', 'provider_transaction_id' => 'fixture-txn-1', 'order_id' => $order->id, 'attempt_id' => $attempt->id, 'received_amount' => 35000, 'provider_paid_at' => now(), 'verified_at' => now(), 'allocation' => 'accepted'];
        PaymentReceipt::create($data);
        PaymentReceipt::create([...$data, 'provider_transaction_id' => 'fixture-txn-2', 'allocation' => 'duplicate']);
        $this->assertDatabaseCount('payment_receipts', 2);
        $this->expectException(QueryException::class);
        PaymentReceipt::create([...$data, 'provider_transaction_id' => 'fixture-txn-3']);
    }
}
