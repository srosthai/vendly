<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Postgres does not index foreign keys on its own. These cover the
     * category filter, image and cart lookups, and the admin lists. Keys
     * that already lead a unique index (store and cart ids) are left out.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('brand_id');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->index(['product_id', 'sort']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->index('product_id');
        });

        Schema::table('inquiry_items', function (Blueprint $table) {
            $table->index('inquiry_id');
            $table->index('product_id');
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->index(['store_id', 'status']);
            $table->index('plan_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index('plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', fn (Blueprint $table) => $table->dropIndex(['plan_id']));
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropIndex(['plan_id']);
            $table->dropIndex(['store_id', 'status']);
        });
        Schema::table('inquiry_items', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['inquiry_id']);
        });
        Schema::table('cart_items', fn (Blueprint $table) => $table->dropIndex(['product_id']));
        Schema::table('product_images', fn (Blueprint $table) => $table->dropIndex(['product_id', 'sort']));
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['brand_id']);
            $table->dropIndex(['category_id']);
        });
    }
};
