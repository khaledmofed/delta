<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Website display of insurers ("Trusted by leading UAE insurers" strip):
 * status = live (connected, quotes online) | coming_soon (partner, not connected yet); sort = display order.
 * Both are changed from the admin panel (Providers → Website logo strip), not in code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurers', function (Blueprint $table) {
            $table->string('status', 20)->default('coming_soon')->after('enabled');
            $table->unsignedSmallInteger('sort')->default(100)->after('status');
        });

        // Initial list. Existing rows (din, awnic from the seeder) keep their name and `enabled` switch.
        $now = now();
        foreach ([
            ['din', 'Dubai Insurance', 'live', 1],
            ['awnic', 'Al Wathba Insurance', 'live', 2],
            ['sukoon', 'Sukoon Insurance', 'coming_soon', 3],
            ['orient', 'Orient Insurance', 'coming_soon', 4],
            ['adnic', 'ADNIC', 'coming_soon', 5],
            ['gig', 'GIG Gulf', 'coming_soon', 6],
            ['albuhaira', 'Al Buhaira National Insurance', 'coming_soon', 7],
            ['methaq', 'Methaq Takaful Insurance', 'coming_soon', 8],
            ['fidelity', 'Fidelity United', 'coming_soon', 9],
            ['rak', 'RAK Insurance', 'coming_soon', 10],
        ] as [$code, $name, $status, $sort]) {
            if (DB::table('insurers')->where('code', $code)->exists()) {
                DB::table('insurers')->where('code', $code)->update(['status' => $status, 'sort' => $sort, 'updated_at' => $now]);
            } else {
                DB::table('insurers')->insert([
                    'code' => $code, 'name' => $name, 'environment' => 'uat',
                    // `enabled` is the quoting switch for connected insurers; a missing row means "config decides".
                    'enabled' => $status === 'live',
                    'status' => $status, 'sort' => $sort,
                    'logo' => is_file(public_path("images/insurers/{$code}.png")) ? "images/insurers/{$code}.png" : null,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('insurers')->whereIn('code', ['sukoon', 'orient', 'adnic', 'gig', 'albuhaira', 'methaq', 'fidelity', 'rak'])->delete();
        Schema::table('insurers', function (Blueprint $table) {
            $table->dropColumn(['status', 'sort']);
        });
    }
};
