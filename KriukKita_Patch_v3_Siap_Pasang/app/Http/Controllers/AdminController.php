<?php
namespace App\Http\Controllers;

use App\Services\KriukCommerce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function loginView() { return Auth::check() && Auth::user()->is_admin ? redirect()->route('admin.dashboard') : view('admin.login'); }
    public function login(Request $request) {
        $d=$request->validate(['email'=>'required|email','password'=>'required|string']);
        if(!Auth::attempt($d)) throw ValidationException::withMessages(['email'=>'Email atau kata sandi tidak cocok.']);
        $request->session()->regenerate();
        if(!Auth::user()->is_admin){Auth::logout();abort(403,'Akun ini tidak memiliki akses penjual.');}
        return redirect()->intended(route('admin.dashboard'));
    }
    public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('admin.login');}
    private function view(string $page){return view('admin.app',['page'=>$page]);}
    public function dashboard(){return $this->view('dashboard');}
    public function products(){return $this->view('products');}
    public function orders(){return $this->view('orders');}
    public function pos(){return $this->view('pos');}
    public function reports(){return $this->view('reports');}
    public function history(){return $this->view('history');}
    public function settings(){return $this->view('settings');}
    public function promos(){return $this->view('promos');}

    public function data(Request $r){
        $date=$r->query('date',now()->toDateString());
        if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)$date)) $date=now()->toDateString();
        $orders=DB::table('orders')->orderByDesc('id')->get();
        $daily=DB::table('orders')->whereDate('paid_at',$date)->where('payment_status','paid')->get();
        $revenue=(int)$daily->sum('subtotal')-(int)$daily->sum('discount');
        $cost=(int)$daily->sum('cost_total');
        return response()->json([
            'products'=>DB::table('products')->where('is_active',true)->orderBy('id')->get()
                ->map(function ($p) {
                    $p->image_url = $p->image_path ? asset('storage/'.$p->image_path) : null;
                    return $p;
                }),
            'orders'=>$orders,
            'promotions'=>DB::table('promotions')->orderByDesc('id')->get(),
            'midtrans_ready'=>(bool)(config('kriuk.midtrans_server_key') && config('kriuk.midtrans_client_key')),
            'settings'=>DB::table('store_settings')->where('id',1)->first(),
            'date'=>$date,
            'daily'=>['revenue'=>$revenue,'transactions'=>$daily->count(),'profit'=>$revenue-$cost],
        ]);
    }
    private function validProduct(Request $r): array{
        return $r->validate([
            'name'=>'required|string|max:100','subtitle'=>'required|string|max:30',
            'group'=>'required|string|in:gurih,pedas,manis',
            'taste'=>'required|string|max:500','ingredients'=>'required|string|max:1000',
            'base_price'=>'required|integer|min:1000|max:10000000',
            'base_cost'=>'required|integer|min:0|max:10000000',
            'stock'=>'required|integer|min:0|max:1000000',
            'low_stock_threshold'=>'required|integer|min:0|max:10000',
            'tag'=>'required|string|max:30','crunch'=>'required|integer|between:1,5','spice'=>'required|integer|between:0,5',
            'bg'=>['required','regex:/^#[0-9A-Fa-f]{6}$/'],
            'bag'=>['required','regex:/^#[0-9A-Fa-f]{6}$/'],
            'label'=>['required','regex:/^#[0-9A-Fa-f]{6}$/'],
            'photo'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'remove_photo'=>['nullable','boolean'],
        ]);
    }
    public function saveProduct(Request $r,?int $id=null)
    {
        $d=$this->validProduct($r);
        if ((int)$d['base_cost'] > (int)$d['base_price']) {
            throw ValidationException::withMessages(['base_cost'=>'Harga modal tidak boleh lebih besar dari harga jual.']);
        }

        // Upload is optional: without a photo, the existing vector pouch remains as fallback.
        unset($d['photo'], $d['remove_photo']);
        $old = $id ? DB::table('products')->where('id',$id)->where('is_active',true)->first() : null;
        if ($id) abort_unless($old,404);

        $newPath = null;
        if ($r->hasFile('photo')) {
            $newPath = $r->file('photo')->store('products','public');
            $d['image_path'] = $newPath;
        } elseif ($id && $r->boolean('remove_photo')) {
            $d['image_path'] = null;
        }

        $slug=Str::slug($d['name']);
        $taken=DB::table('products')->where('slug',$slug)->when($id,fn($q)=>$q->where('id','!=',$id))->exists();
        if ($taken) $slug.='-'.($id ?: Str::lower(Str::random(5)));
        $d['slug']=$slug;
        $d['subtitle']=Str::upper($d['subtitle']);
        $d['updated_at']=now();

        try {
            if ($id) {
                DB::table('products')->where('id',$id)->update($d);
            } else {
                DB::table('products')->insert($d+['is_active'=>true,'created_at'=>now()]);
            }
        } catch (\Throwable $e) {
            if ($newPath) Storage::disk('public')->delete($newPath);
            throw $e;
        }

        // Delete old image only after product row is saved successfully.
        if ($old && array_key_exists('image_path',$d) && $old->image_path
            && $old->image_path !== $d['image_path']) {
            Storage::disk('public')->delete($old->image_path);
        }

        return response()->json(['message'=>'Produk berhasil disimpan.']);
    }
    public function deleteProduct(int $id){
        DB::table('products')->where('id',$id)->update(['is_active'=>false,'updated_at'=>now()]);
        return response()->json(['message'=>'Produk dinonaktifkan tanpa menghapus riwayat.']);
    }
    public function saveSettings(Request $r){
        $d=$r->validate([
            'ribbon'=>'required|string|max:140','about_title'=>'required|string|max:150',
            'about_description'=>'required|string|max:1000','reg_shipping'=>'required|integer|min:0|max:1000000',
            'exp_shipping'=>'required|integer|min:0|max:1000000',
        ]);
        DB::table('store_settings')->updateOrInsert(['id'=>1],$d+['updated_at'=>now()]);
        return response()->json(['message'=>'Informasi toko diperbarui.']);
    }
    public function changeOrder(Request $r,string $code){
        $d=$r->validate(['status'=>'required|in:Sedang Dikemas,Dikirim,Selesai']);
        $o=DB::table('orders')->where('order_code',$code)->first();abort_unless($o,404);
        if($o->payment_status!=='paid' || $o->channel!=='online') throw ValidationException::withMessages(['status'=>'Hanya pesanan online lunas yang bisa diproses.']);
        DB::table('orders')->where('id',$o->id)->update(['status'=>$d['status'],'updated_at'=>now()]);
        return response()->json(['message'=>'Status pesanan diperbarui.']);
    }
    public function savePromotion(Request $request, ?int $id = null)
    {
        $d = $request->validate([
            'code' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{3,24}$/'],
            'description' => 'nullable|string|max:120',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|integer|min:1|max:10000000',
            'min_subtotal' => 'required|integer|min:0|max:100000000',
            'max_discount' => 'nullable|integer|min:1|max:10000000',
            'starts_on' => 'nullable|date',
            'ends_on' => 'nullable|date',
            'is_active' => 'required|boolean',
        ]);
        if (!empty($d['starts_on']) && !empty($d['ends_on']) && $d['ends_on'] < $d['starts_on']) {
            throw ValidationException::withMessages(['ends_on'=>'Tanggal berakhir tidak boleh sebelum tanggal mulai.']);
        }
        if ($d['type'] === 'percent' && $d['value'] > 100) {
            throw ValidationException::withMessages(['value'=>'Diskon persen maksimal 100%.']);
        }
        $d['code'] = Str::upper($d['code']);
        $duplicate = DB::table('promotions')->where('code', $d['code'])
            ->when($id, fn($q) => $q->where('id', '!=', $id))->exists();
        if ($duplicate) throw ValidationException::withMessages(['code'=>'Kode promo sudah digunakan.']);
        $d['max_discount'] = $d['type'] === 'fixed' ? null : ($d['max_discount'] ?? null);
        $d['updated_at'] = now();
        if ($id) {
            $count = DB::table('promotions')->where('id', $id)->update($d);
            abort_unless($count, 404);
        } else {
            DB::table('promotions')->insert($d + ['created_at'=>now()]);
        }
        return response()->json(['message'=>'Promo berhasil disimpan.']);
    }

    public function deletePromotion(int $id)
    {
        $deleted = DB::table('promotions')->where('id', $id)->delete();
        abort_unless($deleted, 404);
        return response()->json(['message'=>'Kode promo dihapus.']);
    }

    public function cashSale(Request $r){
        $d=$r->validate([
            'buyer'=>'required|string|max:80',
            'cash_received'=>'required|integer|min:0|max:100000000',
            'items'=>'required|array|min:1|max:30',
            'items.*.key'=>'required|string|max:90',
            'items.*.qty'=>'required|integer|min:1|max:50',
        ]);
        // Cashier uses the same authoritative pricing, stock locking and receipt snapshot as online checkout.
        $customer=['buyer'=>$d['buyer'],'cash_received'=>$d['cash_received']];
        $o=KriukCommerce::create($customer,$d['items'],true);
        return response()->json(['message'=>'Penjualan tunai tersimpan.','code'=>$o['code'],'total'=>$o['total'],
            'change'=>$o['change'],'receipt_url'=>route('receipt.show',$o['receipt_token'])]);
    }
}
