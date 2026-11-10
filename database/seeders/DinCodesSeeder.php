<?php

namespace Database\Seeders;

use App\Models\InsurerCode;
use Illuminate\Database\Seeder;

/** Dubai Insurance reference codes ("Codes and Description" sent by DIN on 05-Nov-2026). */
class DinCodesSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('data/din-codes.json')), true);

        foreach ($data as $type => $rows) {
            foreach ($rows as $row) {
                InsurerCode::updateOrCreate(
                    ['insurer' => 'din', 'type' => $type, 'code' => (string) $row[0], 'parent' => null],
                    ['label' => $row[1], 'meta' => isset($row[2]) ? ['required' => (bool) $row[2]] : null],
                );
            }
        }
    }
}
