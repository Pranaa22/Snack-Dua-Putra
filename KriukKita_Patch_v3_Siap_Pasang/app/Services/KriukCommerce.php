<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KriukCommerce
{
    public static function price(int $base, int $weight): int
    {
        $factor = [100 => 1, 250 => 2.2, 500 => 4][$weight] ?? null;
        if ($factor === null) throw ValidationException::withMessages(['items' => 'Ukuran kemasan tidak valid.']);
        return (int) round($base * $factor / 500) * 500;
    }

    private static function unpack(array $items): array
    {
        $parsed = []; $needs=[];
        foreach ($items as $item) {
            $qty=(int)($item['qty'] ?? 0);
            if ($qty<1 || $qty>50) throw ValidationException::withMessages(['items'=>'Jumlah produk tidak valid.']);
            $key=(string)($item['key'] ?? '');
            if (preg_match('/^(\d+)_(100|250|500)$/',$key,$m)) {
                $ids=[(int)$m[1]]; $weight=(int)$m[2]; $bundle=false;
            } elseif (preg_match('/^combo_(\d+)_(\d+)_(\d+)$/',$key,$m)) {
                $ids=[(int)$m[1],(int)$m[2],(int)$m[3]]; $weight=100; $bundle=true;
            } else throw ValidationException::withMessages(['items'=>'Pilihan produk tidak dikenali.']);
            foreach($ids as $id) $needs[$id]=($needs[$id] ?? 0)+$qty;
            $parsed[]=['ids'=>$ids,'weight'=>$weight,'qty'=>$qty,'bundle'=>$bundle];
        }
        return [$parsed,$needs];
    }

    // Preview subtotal uses prices and stock from the database, never client-submitted totals.
    public static function quoteSubtotal(array $items): int
    {
        [$parsed, $needs] = self::unpack($items);
        if (!$needs) throw ValidationException::withMessages(['items' => 'Keranjang kosong.']);
        $products = DB::table('products')->whereIn('id', array_keys($needs))->get()->keyBy('id');
        foreach ($needs as $id => $qty) {
            $p = $products->get($id);
            if (!$p || !$p->is_active || $p->stock < $qty) {
                throw ValidationException::withMessages(['items' => 'Stok produk tidak cukup. Perbarui keranjang.']);
            }
        }
        $total = 0;
        foreach ($parsed as $item) {
            if ($item['bundle']) {
                $unit = max(0, array_sum(array_map(
                    fn ($id) => (int) $products[$id]->base_price, $item['ids']
                )) - 6000);
            } else {
                $unit = self::price((int) $products[$item['ids'][0]]->base_price, $item['weight']);
            }
            $total += $unit * $item['qty'];
        }
        return $total;
    }

    public static function promotion(string $rawCode, int $subtotal): array
    {
        $code = Str::upper(trim($rawCode));
        if (!$code) return ['code' => '', 'discount' => 0];
        $p = DB::table('promotions')->where('code', $code)->first();
        if (!$p || !$p->is_active ||
            ($p->starts_on && now()->toDateString() < $p->starts_on) ||
            ($p->ends_on && now()->toDateString() > $p->ends_on)) {
            throw ValidationException::withMessages(['promo_code' => 'Kode promo tidak tersedia atau sudah kedaluwarsa.']);
        }
        if ($subtotal < $p->min_subtotal) {
            throw ValidationException::withMessages(['promo_code' =>
                'Minimal belanja untuk promo ini adalah Rp'.number_format($p->min_subtotal, 0, ',', '.')]);
        }
        $discount = $p->type === 'percent'
            ? (int) floor($subtotal * $p->value / 100)
            : (int) $p->value;
        if ($p->max_discount !== null && $p->type === 'percent') {
            $discount = min($discount, (int) $p->max_discount);
        }
        return [
            'code' => $p->code,
            'description' => $p->description,
            'type' => $p->type,
            'value' => (int) $p->value,
            'min_subtotal' => (int) $p->min_subtotal,
            'max_discount' => $p->max_discount === null ? null : (int) $p->max_discount,
            'discount' => max(0, min($subtotal, $discount)),
        ];
    }

    public static function create(array $customer, array $items, bool $cash=false): array
    {
        return DB::transaction(function() use($customer,$items,$cash) {
            [$parsed,$needs]=self::unpack($items);
            if (count($needs)===0) throw ValidationException::withMessages(['items'=>'Keranjang kosong.']);
            $products=DB::table('products')->whereIn('id',array_keys($needs))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach($needs as $id=>$qty) {
                $p=$products->get($id);
                if (!$p || !$p->is_active || $p->stock<$qty) throw ValidationException::withMessages(['items'=>'Stok produk berubah atau habis. Perbarui keranjang.']);
            }
            $lines=[]; $subtotal=0; $costTotal=0;
            foreach($parsed as $it) {
                $qty=$it['qty'];
                if ($it['bundle']) {
                    $price=max(0,array_sum(array_map(fn($id)=>(int)$products[$id]->base_price,$it['ids']))-6000);
                    $cost=array_sum(array_map(fn($id)=>(int)$products[$id]->base_cost,$it['ids']));
                    $name='Paket Kombo 3 Rasa ('.implode(' + ',array_map(fn($id)=>$products[$id]->subtitle,$it['ids'])).')';
                    $weight='3×100g'; $id=null;
                } else {
                    $p=$products[$it['ids'][0]];
                    $price=self::price((int)$p->base_price,$it['weight']);
                    $cost=self::price((int)$p->base_cost,$it['weight']);
                    $name=$p->name; $weight=$it['weight'].'g'; $id=$p->id;
                }
                $subtotal+=$price*$qty; $costTotal+=$cost*$qty;
                $lines[]=['product_id'=>$id,'product_name'=>$name,'weight_label'=>$weight,
                    'bundle_ids'=>$it['bundle']?json_encode($it['ids']):null,
                    'unit_price'=>$price,'unit_cost'=>$cost,'quantity'=>$qty,
                    'created_at'=>now(),'updated_at'=>now()];
            }
            $setting=DB::table('store_settings')->where('id',1)->first();
            $promo = $cash ? ['code' => '', 'discount' => 0] : self::promotion((string)($customer['promo_code'] ?? ''), $subtotal);
            $discount = $promo['discount'];
            $shipping=$cash?0:($customer['courier']==='Express'?(int)($setting->exp_shipping??22000):($subtotal>=50000?0:(int)($setting->reg_shipping??12000)));
            $total=$subtotal-$discount+$shipping;
            $cashReceived=$cash?(int)$customer['cash_received']:0;
            if ($cash && $cashReceived<$total) throw ValidationException::withMessages(['cash_received'=>'Uang tunai kurang dari total belanja.']);
            $code='KRK-'.now()->format('ymd').'-'.strtoupper(Str::random(10));
            $token=Str::random(48);
            $orderId=DB::table('orders')->insertGetId([
                'order_code'=>$code,'buyer_name'=>$customer['buyer'],'buyer_phone'=>$customer['phone']??null,
                'buyer_city'=>$customer['city']??null,'buyer_address'=>$customer['address']??null,
                'courier'=>$cash?'Ambil di toko':$customer['courier'],
                'payment_method'=>$cash?'Tunai':$customer['method'],
                'subtotal'=>$subtotal,'discount'=>$discount,'promo_code'=>$promo['code'] ?: null,
                'shipping_cost'=>$shipping,'total_amount'=>$total,
                'cost_total'=>$costTotal,'channel'=>$cash?'kasir':'online',
                'cash_received'=>$cashReceived,'cash_change'=>$cash?($cashReceived-$total):0,
                'receipt_token'=>$token,'payment_status'=>$cash?'paid':'pending',
                'status'=>$cash?'Selesai':'Menunggu Pembayaran',
                'stock_reserved'=>!$cash,'paid_at'=>$cash?now():null,
                'created_at'=>now(),'updated_at'=>now(),
            ]);
            foreach($lines as $line) DB::table('order_items')->insert(['order_id'=>$orderId]+$line);
            foreach($needs as $id=>$qty) DB::table('products')->where('id',$id)->decrement('stock',$qty);
            return ['id'=>$orderId,'code'=>$code,'total'=>$total,'receipt_token'=>$token,'change'=>$cash?($cashReceived-$total):0];
        }, 3);
    }

    public static function settle(string $code,string $status,?string $fraud=null): void
    {
        DB::transaction(function()use($code,$status,$fraud){
            $o=DB::table('orders')->where('order_code',$code)->lockForUpdate()->first();
            if (!$o || $o->channel!=='online' || $o->payment_status==='paid') return;
            $paid=in_array($status,['settlement','capture'],true) && (!$fraud || $fraud==='accept');
            $failed=in_array($status,['deny','cancel','expire','failure'],true);
            if ($paid && $o->payment_status==='pending') {
                DB::table('orders')->where('id',$o->id)->update([
                    'payment_status'=>'paid','status'=>'Sedang Dikemas','paid_at'=>now(),'updated_at'=>now(),
                    'stock_reserved'=>false,
                ]);
            } elseif($failed && $o->payment_status==='pending') {
                if($o->stock_reserved) {
                    $lines=DB::table('order_items')->where('order_id',$o->id)->get();
                    foreach($lines as $line){
                        $ids=$line->bundle_ids?json_decode($line->bundle_ids,true):[$line->product_id];
                        foreach($ids as $pid) if($pid) DB::table('products')->where('id',$pid)->increment('stock',(int)$line->quantity);
                    }
                }
                DB::table('orders')->where('id',$o->id)->update([
                    'payment_status'=>'failed','status'=>'Dibatalkan','stock_reserved'=>false,'updated_at'=>now(),
                ]);
            }
        },3);
    }
}
