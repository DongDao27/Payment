<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name', 120);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps(6);
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('grade')->nullable();
            $table->unsignedBigInteger('list_price');
            $table->unsignedBigInteger('sale_price')->nullable();
            $table->integer('stock_on_hand')->default(0);
            $table->integer('stock_reserved')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_hot')->default(false);
            $table->boolean('is_bestseller')->default(false);
            $table->unsignedBigInteger('catalog_version')->default(1);
            $table->timestamps(6);
            $table->index(['category_id', 'is_active', 'id']);
            $table->index(['is_hot', 'is_active']);
            $table->index(['is_bestseller', 'is_active']);
        });
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_stock CHECK (stock_on_hand >= 0 AND stock_reserved >= 0 AND stock_reserved <= stock_on_hand), ADD CONSTRAINT products_sale CHECK (sale_price IS NULL OR sale_price <= list_price), ADD CONSTRAINT products_grade CHECK (grade IS NULL OR grade BETWEEN 1 AND 12)');
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('storage_disk', 40)->default('public');
            $table->string('path')->unique();
            $table->string('mime', 100);
            $table->unsignedInteger('byte_size');
            $table->integer('sort_order')->default(0);
            $table->string('alt_text')->nullable();
            $table->timestamps(6);
        });
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->timestamps(6);
        });
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('quantity');
            $table->timestamps(6);
            $table->unique(['cart_id', 'product_id']);
        });
        DB::statement('ALTER TABLE cart_items ADD CONSTRAINT cart_items_quantity CHECK (quantity BETWEEN 1 AND 99)');
    }

    public function down(): void
    {
        foreach (['cart_items', 'carts', 'product_images', 'products', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
