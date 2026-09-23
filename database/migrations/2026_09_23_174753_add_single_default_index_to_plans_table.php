<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * At most one plan may be the default. Older rows that broke the rule
     * keep only the earliest default before the partial unique index lands.
     */
    public function up(): void
    {
        $firstDefault = DB::table('plans')->where('is_default', true)->min('id');

        if ($firstDefault !== null) {
            DB::table('plans')
                ->where('is_default', true)
                ->where('id', '!=', $firstDefault)
                ->update(['is_default' => false]);
        }

        DB::statement('CREATE UNIQUE INDEX plans_single_default ON plans (is_default) WHERE is_default');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX plans_single_default');
    }
};
