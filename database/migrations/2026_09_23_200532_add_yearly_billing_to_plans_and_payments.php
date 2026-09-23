<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('yearly_price_cents')->nullable()->after('price_cents');
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->string('period', 16)->default('monthly')->after('amount_cents');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropColumn('period');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('yearly_price_cents');
        });
    }
};
