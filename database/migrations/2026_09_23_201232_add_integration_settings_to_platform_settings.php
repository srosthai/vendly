<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->text('cutluy_api_key')->nullable();
            $table->text('cutluy_webhook_secret')->nullable();
            $table->string('cutluy_base_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['cutluy_api_key', 'cutluy_webhook_secret', 'cutluy_base_url']);
        });
    }
};
