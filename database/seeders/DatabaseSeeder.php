<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = ('admin@kriukkita.com');
        $password = ('AdminKelompok1');

        if (!$email || !$password || strlen($password) < 12) {
            throw new \RuntimeException(
                'Isi ADMIN_EMAIL dan ADMIN_PASSWORD (minimal 12 karakter) di .env'
            );
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin KriukKita',
                'password' => Hash::make($password),
                'is_admin' => true,
            ]
        );

        DB::table('store_settings')->updateOrInsert(
            ['id' => 1],
            [
                'ribbon' => 'Kenalan dulu sama kriuk favoritmu! 6 rasa pilihan • Gratis Ongkir min. Rp50.000',
                'about_title' => 'Dari UMKM lokal, untuk teman ngemil kamu.',
                'about_description' => 'KriukKita adalah rancangan toko keripik untuk tugas Bisnis Elektronik 2. Identitas dan detail UMKM akan disesuaikan.',
                'reg_shipping' => 12000,
                'exp_shipping' => 22000,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $fields = [
            'name', 'subtitle', 'group', 'base_price',
            'stock', 'tag', 'taste', 'bg', 'bag',
            'label', 'crunch', 'spice', 'ingredients'
        ];

        $rows = [
            [
                'Keripik Singkong Original', 'ORIGINAL',
                'gurih', 12000, 34, 'Best Seller',
                'Gurih klasik dengan irisan tipis renyah dan taburan garam rempah.',
                '#f7e6cb', '#d96b27', '#fff3df', 5, 0,
                'Singkong mentega segar, bawang putih, ketumbar, garam laut, minyak nabati.'
            ],
            [
                'Keripik Singkong Balado', 'BALADO',
                'pedas', 15000, 22, 'Favorit',
                'Bumbu balado merah manis-pedas khas nusantara yang melimpah.',
                '#f6ddd0', '#b83c27', '#fff0db', 5, 3,
                'Singkong pilihan, cabai merah keriting, bawang merah, gula aren, asam jawa.'
            ],
            [
                'Keripik Pedas Daun Jeruk', 'DAUN JERUK',
                'pedas', 16000, 7, 'Extra Pedas',
                'Pedas nendang cabai rawit asli dengan wangi segar daun jeruk purut.',
                '#f4e3c9', '#8c3b24', '#fff3db', 5, 5,
                'Singkong pilihan, cabai rawit merah, irisan daun jeruk purut asli, kencur.'
            ],
            [
                'Keripik Pisang Cokelat', 'CHOCO BANANA',
                'manis', 14000, 19, 'Manis Favorit',
                'Irisan pisang kepok garing berbalut bubuk cokelat panggang yang lumer.',
                '#efe0cf', '#4a3223', '#f9e8ce', 4, 0,
                'Pisang kepok kuning, cokelat bubuk, susu bubuk, gula halus.'
            ],
            [
                'Basreng Pedas Rempah', 'BASRENG',
                'pedas', 18000, 5, 'Renyah Gurih',
                'Bakso goreng renyah tanpa keras dengan taburan bumbu cabai kencur.',
                '#f5dcc8', '#c95224', '#ffefd6', 5, 4,
                'Olahan ikan tenggiri, tepung tapioka, cabai bubuk, kencur, daun jeruk.'
            ],
            [
                'Keripik Ubi Madu Panggang', 'UBI MADU',
                'manis', 16000, 15, 'Manis Alami',
                'Manis alami dari ubi madu pilihan dengan tekstur garing karamel.',
                '#f6e8d2', '#9e5828', '#fff4e0', 4, 0,
                'Ubi cilembu madu, madu, mentega tawar.'
            ],
        ];

        foreach ($rows as $index => $row) {
            $data = array_combine($fields, $row);

            DB::table('products')->updateOrInsert(
                ['id' => $index + 1],
                $data + [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}