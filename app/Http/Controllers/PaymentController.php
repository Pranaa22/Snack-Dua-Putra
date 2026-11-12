<?php
namespace App\Http\Controllers;

use App\Services\KriukCommerce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Transaction;

class PaymentController extends Controller
{
    private function configMidtrans(): void {
        MidtransConfig::$serverKey=config('kriuk.midtrans_server_key');
        MidtransConfig::$isProduction=(bool)config('kriuk.midtrans_production');
    }
    public function status(string $code) {
        $o=DB::table('orders')->where('order_code',$code)->where('channel','online')->first();
        abort_unless($o,404);
        if($o->payment_status==='pending' && config('kriuk.midtrans_server_key')) {
            try {
                $this->configMidtrans();
                $r=(array)Transaction::status($code);
                if((int)round((float)($r['gross_amount']??-1)) === (int)$o->total_amount)
                    KriukCommerce::settle($code,(string)($r['transaction_status']??'pending'),$r['fraud_status']??null);
            }catch(\Throwable $e){
                // Snap may not have a payment status until a payment method is selected.
                Log::info('Midtrans status not yet available',['order'=>$code]);
            }
        }
        $now=DB::table('orders')->where('order_code',$code)->first();
        return response()->json(['code'=>$code,'paymentStatus'=>$now->payment_status,'status'=>$now->status,'receiptUrl'=>$now->payment_status==='paid' && $now->receipt_token ? route('receipt.show',$now->receipt_token) : null]);
    }
    public function token(string $code) {
        $o=DB::table('orders')->where('order_code',$code)->where('channel','online')->first();
        abort_unless($o,404);
        if ($o->payment_status!=='pending' || !$o->snap_token) {
            return response()->json(['message'=>'Pembayaran ini tidak dapat dilanjutkan.'],422);
        }
        return response()->json(['token'=>$o->snap_token]);
    }
    public function webhook(Request $request) {
        $p=$request->all();
        $code=(string)($p['order_id']??'');
        $server=(string) config('kriuk.midtrans_server_key');
        if (!$server || !$code) abort(403);
        $expected=hash('sha512',$code.($p['status_code']??'').($p['gross_amount']??'').$server);
        if(!isset($p['signature_key']) || !hash_equals($expected,(string)$p['signature_key'])) abort(403);
        $o=DB::table('orders')->where('order_code',$code)->where('channel','online')->first();
        abort_unless($o && (int)round((float)($p['gross_amount']??-1)) === (int)$o->total_amount,400);
        KriukCommerce::settle($code,(string)($p['transaction_status']??'pending'),$p['fraud_status']??null);
        return response()->json(['ok'=>true]);
    }
}
