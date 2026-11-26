<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * QIC now has an adapter (config/insurers.php). Its row was created as a partner with enabled=false, which the
 * InsurerManager reads as "switched off in the admin panel". Turn the switch on so QIC_ENABLED in .env decides,
 * like DIN and AWNIC. The website still shows Coming soon until status is set to Live in the panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('insurers')->where('code', 'qic')->update(['enabled' => true, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('insurers')->where('code', 'qic')->update(['enabled' => false, 'updated_at' => now()]);
    }
};
