<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('kind', 30);
            $table->integer('delta_on_hand');
            $table->integer('delta_reserved');
            $table->integer('resulting_on_hand');
            $table->integer('resulting_reserved');
            $table->text('reason');
            $table->string('business_key', 191)->unique();
            $table->timestamps(6);
            $table->index(['product_id', 'created_at', 'id']);
        });
        DB::statement('ALTER TABLE inventory_movements ADD CONSTRAINT movements_stock CHECK (resulting_on_hand >= 0 AND resulting_reserved >= 0 AND resulting_reserved <= resulting_on_hand)');
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('event', 100);
            $table->json('before');
            $table->json('after');
            $table->text('reason')->nullable();
            $table->uuid('request_id')->nullable();
            $table->timestamps(6);
            $table->index(['order_id', 'created_at']);
        });
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('operation', 60);
            $table->string('key', 100);
            $table->char('request_hash', 64);
            $table->string('status', 20)->default('processing');
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('cached_response')->nullable();
            $table->timestamps(6);
            $table->unique(['user_id', 'operation', 'key']);
        });
        DB::statement("ALTER TABLE idempotency_keys ADD CONSTRAINT idempotency_status CHECK (status IN ('processing','succeeded'))");
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->json('value');
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamps(6);
        });
        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('receipt_id')->constrained('payment_receipts')->restrictOnDelete();
            $table->string('provider', 30);
            $table->string('external_reference', 100);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('merchandise_amount');
            $table->unsignedBigInteger('shipping_amount');
            $table->dateTime('refunded_at', 6);
            $table->foreignId('verified_by')->constrained('users')->restrictOnDelete();
            $table->string('evidence_path');
            $table->text('reason');
            $table->timestamps(6);
            $table->unique(['provider', 'external_reference']);
        });
        DB::statement('ALTER TABLE refunds ADD CONSTRAINT refunds_amount CHECK (amount > 0 AND amount = merchandise_amount + shipping_amount)');
    }

    public function down(): void
    {
        foreach (['refunds', 'settings', 'idempotency_keys', 'order_status_histories', 'inventory_movements'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
