<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('accent', 16)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('address')->nullable();
            $table->string('hours', 120)->nullable();
            $table->json('social_links')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['accent', 'phone', 'address', 'hours', 'social_links']);
        });
    }
};
