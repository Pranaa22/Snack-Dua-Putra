<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KriukPromoSeeder extends Seeder
{
    public function run(): void
    {
        if (!DB::table('promotions')->where('code', 'UMKMLOKAL')->exists()) {
            DB::table('promotions')->insert([
                'code' => 'UMKMLOKAL',
                'description' => 'Potongan Rp5.000 untuk belanja pertama',
                'type' => 'fixed',
                'value' => 5000,
                'min_subtotal' => 0,
                'max_discount' => null,
                'starts_on' => null,
                'ends_on' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
