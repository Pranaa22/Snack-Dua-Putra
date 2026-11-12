<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
class ReceiptController extends Controller
{
    public function show(string $token) {
        abort_unless(preg_match('/^[A-Za-z0-9]{48}$/',$token),404);
        $o=DB::table('orders')->where('receipt_token',$token)->where('payment_status','paid')->first();
        abort_unless($o,404);
        $items=DB::table('order_items')->where('order_id',$o->id)->get();
        return view('receipt',['o'=>$o,'items'=>$items, 'receiptItems'=>$items->map(fn($i)=>['name'=>$i->product_name,'weight'=>$i->weight_label,'qty'=>$i->quantity,'price'=>$i->unit_price])->values()]);
    }
}
