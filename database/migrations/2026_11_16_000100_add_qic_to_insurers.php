<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** QIC (Qatar Insurance) as a partner, Coming soon. Position after the existing partners; changeable in the admin panel. */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('insurers')->where('code', 'qic')->exists()) {
            return;
        }
        DB::table('insurers')->insert([
            'code' => 'qic', 'name' => 'QIC Insurance', 'environment' => 'uat', 'enabled' => false,
            'status' => 'coming_soon', 'sort' => ((int) DB::table('insurers')->max('sort')) + 1,
            'logo' => is_file(public_path('images/insurers/qic.png')) ? 'images/insurers/qic.png' : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('insurers')->where('code', 'qic')->delete();
    }
};
