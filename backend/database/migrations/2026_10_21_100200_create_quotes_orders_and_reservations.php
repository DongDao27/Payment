<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('cart_id')->constrained()->restrictOnDelete();
            $table->char('cart_fingerprint', 64);
            $table->json('payload');
            $table->unsignedBigInteger('items_subtotal');
            $table->unsignedBigInteger('discount_total');
            $table->unsignedBigInteger('shipping_fee');
            $table->unsignedBigInteger('payable_total');
            $table->string('pricing_version', 100);
            $table->dateTime('expires_at', 6);
            $table->dateTime('consumed_at', 6)->nullable();
            $table->timestamps(6);
            $table->index(['user_id', 'expires_at']);
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 40)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('quote_id')->unique()->constrained()->restrictOnDelete();
            $table->string('fulfillment_method', 20);
            $table->string('recipient_name', 120);
            $table->string('recipient_phone', 20);
            $table->json('address')->nullable();
            $table->unsignedBigInteger('items_subtotal');
            $table->unsignedBigInteger('discount_total');
            $table->unsignedBigInteger('shipping_fee');
            $table->unsignedBigInteger('payable_total');
            $table->string('lifecycle', 20)->default('open');
            $table->string('payment_status', 40)->default('unpaid');
            $table->string('fulfillment_status', 30)->default('unprocessed');
            $table->dateTime('expires_at', 6);
            $table->dateTime('paid_at', 6)->nullable();
            $table->dateTime('completed_at', 6)->nullable();
            $table->dateTime('cancelled_at', 6)->nullable();
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamps(6);
            $table->index(['user_id', 'created_at', 'id']);
            $table->index(['lifecycle', 'expires_at']);
            $table->index(['fulfillment_method', 'fulfillment_status', 'created_at'], 'orders_fulfillment_index');
        });
        foreach (['quotes', 'orders'] as $table) {
            DB::statement("ALTER TABLE $table ADD CONSTRAINT {$table}_totals CHECK (discount_total <= items_subtotal AND payable_total = items_subtotal - discount_total + shipping_fee AND payable_total <= 1000000000)");
        }
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_method CHECK ((fulfillment_method = 'pickup' AND address IS NULL AND shipping_fee = 0) OR (fulfillment_method = 'delivery' AND address IS NOT NULL)), ADD CONSTRAINT orders_lifecycle CHECK (lifecycle IN ('open','completed','cancelled','expired')), ADD CONSTRAINT orders_payment CHECK (payment_status IN ('unpaid','pending','paid','reconciliation_required')), ADD CONSTRAINT orders_fulfillment CHECK (fulfillment_status IN ('unprocessed','preparing','ready_for_pickup','shipping','fulfilled'))");
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('sku', 100);
            $table->string('name');
            $table->unsignedBigInteger('list_unit_price');
            $table->unsignedBigInteger('charged_unit_price');
            $table->unsignedTinyInteger('quantity');
            $table->unsignedBigInteger('discount_line_total');
            $table->unsignedBigInteger('line_total');
            $table->timestamps(6);
        });
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_values CHECK (quantity BETWEEN 1 AND 99 AND charged_unit_price <= list_unit_price AND line_total = charged_unit_price * quantity AND discount_line_total = (list_unit_price - charged_unit_price) * quantity)');
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('quantity');
            $table->string('status', 20)->default('active');
            $table->dateTime('expires_at', 6);
            $table->dateTime('released_at', 6)->nullable();
            $table->timestamps(6);
            $table->index(['status', 'expires_at']);
        });
        DB::statement("ALTER TABLE stock_reservations ADD CONSTRAINT reservations_values CHECK (quantity BETWEEN 1 AND 99 AND status IN ('active','consumed','released'))");
    }

    public function down(): void
    {
        foreach (['stock_reservations', 'order_items', 'orders', 'quotes'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
