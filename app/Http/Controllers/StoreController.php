<?php
namespace App\Http\Controllers;

use App\Services\KriukCommerce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;

class StoreController extends Controller
{
    public function index() { return view('store', ['settings'=>$this->settings()]); }
    public function detail(string $slug) {
        $p=DB::table('products')->where('slug',$slug)->where('is_active',true)->first();
        abort_unless($p,404);
        return view('product', ['product'=>$p,'settings'=>$this->settings()]);
    }
    public function checkoutView() { return view('checkout',['settings'=>$this->settings()]); }
    public function orderStatus(string $code) {
        $o=DB::table('orders')->where('order_code',$code)->where('channel','online')->first();
        abort_unless($o,404);
        return view('order',['order'=>$o,'settings'=>$this->settings()]);
    }
    private function settings() {
        return DB::table('store_settings')->where('id',1)->first();
    }
    public function catalog() {
        return response()->json([
            'products'=>DB::table('products')->where('is_active',true)->orderBy('id')->get()->map(fn($p)=>[
                'id'=>$p->id,'slug'=>$p->slug,'name'=>$p->name,'subtitle'=>$p->subtitle,
                'group'=>$p->group,'taste'=>$p->taste,'basePrice'=>(int)$p->base_price,
                'stock'=>(int)$p->stock,'tag'=>$p->tag,'bg'=>$p->bg,'bag'=>$p->bag,
                'label'=>$p->label,'crunch'=>(int)$p->crunch,'spice'=>(int)$p->spice,
                'ingredients'=>$p->ingredients,
                'imageUrl'=>$p->image_path ? asset('storage/'.$p->image_path) : null,
            ]),
            'shipping'=>['regular'=>(int)($this->settings()->reg_shipping ?? 12000),'express'=>(int)($this->settings()->exp_shipping ?? 22000)],
        ]);
    }
    public function validatePromo(Request $request)
    {
        $d = $request->validate([
            'code' => 'required|string|max:24',
            'items' => 'required|array|min:1|max:30',
            'items.*.key' => 'required|string|max:90',
            'items.*.qty' => 'required|integer|min:1|max:50',
        ]);
        $subtotal = KriukCommerce::quoteSubtotal($d['items']);
        return response()->json(KriukCommerce::promotion($d['code'], $subtotal) + ['subtotal' => $subtotal]);
    }

    public function placeOrder(Request $request) {
        $d=$request->validate([
            'buyer'=>'required|string|max:80', 'phone'=>'required|string|min:9|max:20',
            'city'=>'required|string|max:70','address'=>'required|string|max:300',
            'courier'=>'required|in:Reguler,Express',
            'method'=>'required|in:QRIS,Transfer Bank,E-Wallet',
            'promo_code'=>'nullable|string|max:24','items'=>'required|array|min:1|max:30',
            'items.*.key'=>'required|string|max:90','items.*.qty'=>'required|integer|min:1|max:50',
        ]);
        if(!config('kriuk.midtrans_server_key') || !config('kriuk.midtrans_client_key')) {
            return response()->json(['message'=>'Metode pembayaran online sedang disiapkan. Silakan hubungi pengelola toko.'],503);
        }
        $o=KriukCommerce::create($d,$d['items']);
        try {
            MidtransConfig::$serverKey=config('kriuk.midtrans_server_key');
            MidtransConfig::$isProduction=(bool)config('kriuk.midtrans_production');
            MidtransConfig::$isSanitized=true;
            MidtransConfig::$is3ds=true;
            $enabled=match($d['method']){
                'QRIS'=>['gopay','shopeepay','other_qris'],
                'Transfer Bank'=>['bank_transfer'],
                default=>['gopay','shopeepay'],
            };
            $token=Snap::getSnapToken([
                'transaction_details'=>['order_id'=>$o['code'],'gross_amount'=>$o['total']],
                'customer_details'=>['first_name'=>$d['buyer'],'phone'=>$d['phone']],
                'enabled_payments'=>$enabled,
                'expiry'=>['unit'=>'minutes','duration'=>60],
            ]);
            DB::table('orders')->where('id',$o['id'])->update(['snap_token'=>$token]);
            return response()->json(['code'=>$o['code'],'token'=>$token,'total'=>$o['total']]);
        } catch (\Throwable $e) {
            Log::error('Midtrans create token failed',['error'=>$e->getMessage()]);
            KriukCommerce::settle($o['code'],'failure');
            return response()->json(['message'=>'Gagal membuka pembayaran Midtrans. Coba beberapa saat lagi.'],502);
        }
    }
}
