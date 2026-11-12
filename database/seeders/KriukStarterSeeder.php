<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class KriukStarterSeeder extends Seeder
{
    public function run(): void
    {
        // Admin is created/updated only when explicitly configured by the project owner.
        $email = config('kriuk.admin_email');
        $password = config('kriuk.admin_password');
        if ($email && $password && strlen($password) >= 12) {
            DB::table('users')->updateOrInsert(
                ['email' => $email],
                ['name'=>'Admin KriukKita','password'=>Hash::make($password),
                 'is_admin'=>true,'updated_at'=>now(),'created_at'=>now()]
            );
        }

        if (!DB::table('store_settings')->where('id',1)->exists()) {
            DB::table('store_settings')->insert([
                'id'=>1,
                'ribbon'=>'Keripik lokal dengan pilihan rasa untuk setiap suasana • Belanja mudah dari rumah',
                'about_title'=>'Renyahnya keripik, hangatnya kebersamaan',
                'about_description'=>'Berawal dari semangat menghadirkan camilan yang akrab di lidah, kami memilih aneka keripik dengan rasa khas Nusantara. Setiap pesanan disiapkan dengan perhatian pada rasa, kemasan, dan kenyamanan pelanggan. Temukan keripik favoritmu dan nikmati momen sederhana bersama orang-orang terdekat.',
                'reg_shipping'=>12000,'exp_shipping'=>22000,
                'created_at'=>now(),'updated_at'=>now(),
            ]);
        } else {
            $setting = DB::table('store_settings')->where('id',1)->first();
            if (str_contains(strtolower($setting->about_description ?? ''),'rancangan') || str_contains(strtolower($setting->about_description ?? ''),'tugas')) {
                DB::table('store_settings')->where('id',1)->update([
                    'ribbon'=>'Keripik lokal dengan pilihan rasa untuk setiap suasana • Belanja mudah dari rumah',
                    'about_title'=>'Renyahnya keripik, hangatnya kebersamaan',
                    'about_description'=>'Berawal dari semangat menghadirkan camilan yang akrab di lidah, kami memilih aneka keripik dengan rasa khas Nusantara. Setiap pesanan disiapkan dengan perhatian pada rasa, kemasan, dan kenyamanan pelanggan.',
                ]);
            }
        }
        $samples=[
            ['Keripik Singkong Original','ORIGINAL','gurih',12000,6500,34,'Best Seller','Gurih klasik dengan irisan tipis renyah.','#f7e6cb','#d96b27','#fff3df',5,0,'Singkong, rempah, garam, minyak nabati'],
            ['Keripik Singkong Balado','BALADO','pedas',15000,8200,22,'Favorit','Manis dan pedas dengan bumbu balado.','#f6ddd0','#b83c27','#fff0db',5,3,'Singkong, cabai, bawang merah, gula, minyak nabati'],
            ['Keripik Pedas Daun Jeruk','DAUN JERUK','pedas',16000,8500,7,'Pedas Favorit','Pedas dan aroma daun jeruk yang khas.','#f4e3c9','#8c3b24','#fff3db',5,5,'Singkong, cabai, daun jeruk, rempah'],
            ['Keripik Pisang Cokelat','CHOCO BANANA','manis',14000,7500,19,'Pilihan','Manis gurih berpadu rasa cokelat.','#efe0cf','#4a3223','#f9e8ce',4,0,'Pisang, cokelat, gula, minyak nabati'],
            ['Basreng Pedas Rempah','BASRENG','pedas',18000,9700,5,'Renyah Gurih','Basreng dengan bumbu pedas rempah.','#f5dcc8','#c95224','#ffefd6',5,4,'Olahan bakso, cabai bubuk, kencur'],
            ['Keripik Ubi Madu','UBI MADU','manis',16000,8700,15,'Manis Favorit','Ubi renyah dengan manis yang lembut.','#f6e8d2','#9e5828','#fff4e0',4,0,'Ubi madu, sedikit gula, minyak nabati'],
        ];
        if (DB::table('products')->count()===0) {
            foreach ($samples as $r) {
                DB::table('products')->insert([
                    'name'=>$r[0],'subtitle'=>$r[1],'group'=>$r[2],
                    'base_price'=>$r[3],'base_cost'=>$r[4],'stock'=>$r[5],
                    'tag'=>$r[6],'taste'=>$r[7],'bg'=>$r[8],'bag'=>$r[9],
                    'label'=>$r[10],'crunch'=>$r[11],'spice'=>$r[12],
                    'ingredients'=>$r[13],'slug'=>Str::slug($r[0]),
                    'low_stock_threshold'=>10,'is_active'=>true,
                    'created_at'=>now(),'updated_at'=>now(),
                ]);
            }
        } else {
            foreach(DB::table('products')->get() as $p) {
                DB::table('products')->where('id',$p->id)->update([
                    'slug'=>$p->slug ?: Str::slug($p->name).'-'.$p->id,
                    'base_cost'=>$p->base_cost ?: (int) round($p->base_price*.55),
                ]);
            }
        }
    }
}
