<?php

namespace Database\Seeders;

use App\Models\Insurer;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        // Delta is a multi-service broker. Phase 1: only Motor is sold online.
        $products = [
            ['code' => 'motor', 'name' => 'Motor Insurance', 'capabilities' => ['online_quote', 'compare', 'online_purchase', 'online_payment', 'policy_issuance', 'lead_form'], 'sort' => 1],
            ['code' => 'medical', 'name' => 'Medical Insurance', 'capabilities' => ['lead_form'], 'sort' => 2],
            ['code' => 'home', 'name' => 'Home Insurance', 'capabilities' => ['lead_form'], 'sort' => 3],
            ['code' => 'travel', 'name' => 'Travel Insurance', 'capabilities' => ['lead_form'], 'sort' => 4],
            ['code' => 'boat', 'name' => 'Boat Insurance', 'capabilities' => ['lead_form'], 'sort' => 5],
            ['code' => 'pet', 'name' => 'Pet Insurance', 'capabilities' => ['lead_form'], 'sort' => 6],
        ];
        foreach ($products as $p) {
            Product::updateOrCreate(['code' => $p['code']], $p + ['status' => 'active']);
        }

        $motor = Product::where('code', 'motor')->first();
        foreach (config('insurers.providers') as $code => $cfg) {
            $insurer = Insurer::updateOrCreate(['code' => $code], [
                'name' => $cfg['name'],
                'environment' => 'uat',
                'enabled' => (bool) $cfg['enabled'],
                'logo' => "images/insurers/{$code}.png",
            ]);
            if (in_array('motor', $cfg['products'], true)) {
                $insurer->products()->syncWithoutDetaching([$motor->id => ['enabled' => true]]);
            }
        }
    }
}
