<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Requests get a short number per store that people can read out loud,
     * remember whether they came from a cart, and keep why a send failed.
     */
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->unsignedInteger('number')->nullable()->after('public_id');
            $table->boolean('from_cart')->default(false)->after('contact');
            $table->string('admin_error')->nullable()->after('admin_notified_at');
            $table->string('vendor_error')->nullable()->after('vendor_notified_at');
        });

        $next = [];

        DB::table('inquiries')->orderBy('id')->select(['id', 'store_id'])->lazyById()->each(function (object $inquiry) use (&$next): void {
            $next[$inquiry->store_id] = ($next[$inquiry->store_id] ?? 0) + 1;
            DB::table('inquiries')->where('id', $inquiry->id)->update(['number' => $next[$inquiry->store_id]]);
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->unique(['store_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'number']);
            $table->dropColumn(['number', 'from_cart', 'admin_error', 'vendor_error']);
        });
    }
};
