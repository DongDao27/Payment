<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('provider', 30);
            $table->string('provider_reference', 100)->unique();
            $table->unsignedBigInteger('requested_amount');
            $table->string('status', 20)->default('created');
            $table->dateTime('expires_at', 6);
            $table->string('provider_transaction_id', 100)->nullable();
            $table->dateTime('query_requested_at', 6)->nullable();
            $table->dateTime('verified_at', 6)->nullable();
            $table->timestamps(6);
            $table->index(['order_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
        DB::statement("ALTER TABLE payment_attempts ADD CONSTRAINT attempts_status CHECK (status IN ('created','pending','succeeded','failed','cancelled','expired','unknown'))");
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('provider_transaction_id', 100);
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('attempt_id')->constrained('payment_attempts')->restrictOnDelete();
            $table->unsignedBigInteger('received_amount');
            $table->dateTime('provider_paid_at', 6);
            $table->dateTime('verified_at', 6);
            $table->string('allocation', 20);
            $table->unsignedBigInteger('accepted_order_id')->nullable()->storedAs("IF(allocation = 'accepted', order_id, NULL)")->unique();
            $table->timestamps(6);
            $table->unique(['provider', 'provider_transaction_id']);
            $table->index(['order_id', 'allocation', 'verified_at']);
        });
        DB::statement("ALTER TABLE payment_receipts ADD CONSTRAINT receipts_allocation CHECK (allocation IN ('accepted','duplicate','late','mismatch'))");
        Schema::create('payment_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source', 20);
            $table->string('provider_reference', 100)->nullable();
            $table->char('fingerprint', 64)->unique();
            $table->boolean('signature_valid');
            $table->json('safe_payload');
            $table->string('disposition', 50);
            $table->foreignId('receipt_id')->nullable()->constrained('payment_receipts')->restrictOnDelete();
            $table->dateTime('created_at', 6);
            $table->dateTime('processed_at', 6)->nullable();
        });
        DB::statement("ALTER TABLE payment_events ADD CONSTRAINT events_source CHECK (source IN ('ipn','query','return'))");
        Schema::create('reconciliation_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('receipt_id')->nullable()->constrained('payment_receipts')->restrictOnDelete();
            $table->string('reason', 100);
            $table->string('status', 20)->default('open');
            $table->string('resolution', 100)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('resolved_at', 6)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamps(6);
            $table->index(['status', 'created_at']);
        });
        DB::statement("ALTER TABLE reconciliation_cases ADD CONSTRAINT cases_status CHECK (status IN ('open','resolved'))");
    }

    public function down(): void
    {
        foreach (['reconciliation_cases', 'payment_events', 'payment_receipts', 'payment_attempts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
