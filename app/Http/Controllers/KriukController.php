<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use Midtrans\Transaction;

class KriukController extends Controller
{
    private function midtrans(): void
    {
        MidtransConfig::$serverKey =
            config('services.midtrans.server_key');

        MidtransConfig::$isProduction =
            (bool) config('services.midtrans.is_production');

        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;
    }

    private function setting()
    {
        return DB::table('store_settings')->first();
    }

    private function productData($p): array
    {
        return [
            'id' => (int) $p->id,
            'name' => $p->name,
            'subtitle' => $p->subtitle,
            'group' => $p->group,
            'taste' => $p->taste,
            'basePrice' => (int) $p->base_price,
            'stock' => (int) $p->stock,
            'tag' => $p->tag,
            'bg' => $p->bg,
            'bag' => $p->bag,
            'label' => $p->label,
            'crunch' => (int) $p->crunch,
            'spice' => (int) $p->spice,
            'ingredients' => $p->ingredients,
        ];
    }

    private function products(): array
    {
        return DB::table('products')
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn ($p) => $this->productData($p))
            ->all();
    }

    private function settingsData(): array
    {
        $s = $this->setting();

        return [
            'ribbon' => $s->ribbon,
            'aboutTitle' => $s->about_title,
            'aboutDescription' => $s->about_description,
            'regShipping' => (int) $s->reg_shipping,
            'expShipping' => (int) $s->exp_shipping,
        ];
    }

    public function home()
    {
        return view('store');
    }

    public function catalog()
    {
        return response()->json([
            'products' => $this->products(),
            'settings' => $this->settingsData(),
            'authenticated' => Auth::check()
                && (bool) Auth::user()->is_admin,
        ]);
    }

    public function adminData()
    {
        $orders = DB::table('orders')
            ->orderByDesc('id')
            ->get()
            ->map(function ($o) {
                $items = DB::table('order_items')
                    ->where('order_id', $o->id)
                    ->get();

                return [
                    'id' => $o->order_code,
                    'time' => $o->created_at,
                    'buyer' => $o->buyer_name,
                    'phone' => $o->buyer_phone,
                    'city' => $o->buyer_city,
                    'address' => $o->buyer_address,
                    'itemsText' => $items->map(
                        fn ($i) => $i->product_name
                            . ' (' . $i->weight_label . ') × '
                            . $i->quantity
                    )->join(', '),
                    'courier' => $o->courier,
                    'method' => $o->payment_method,
                    'total' => (int) $o->total_amount,
                    'status' => $o->status,
                    'paymentStatus' => $o->payment_status,
                ];
            })
            ->all();

        return response()->json([
            'products' => $this->products(),
            'orders' => $orders,
            'settings' => $this->settingsData(),
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)
            || !Auth::user()->is_admin) {
            Auth::logout();

            return response()->json([
                'message' => 'Email atau password salah.'
            ], 422);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login berhasil.',
            'csrfToken' => $request->session()->token(),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout berhasil.'
        ]);
    }

        private function validProduct(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'subtitle' => ['required', 'string', 'max:14'],
            'group' => ['required', 'in:gurih,pedas,manis'],
            'taste' => ['required', 'string', 'max:110'],
            'basePrice' => ['required', 'integer', 'min:1000'],
            'stock' => ['required', 'integer', 'min:0', 'max:99999'],
            'tag' => ['required', 'string', 'max:20'],
            'bg' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bag' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'label' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'crunch' => ['required', 'integer', 'between:1,5'],
            'spice' => ['required', 'integer', 'between:0,5'],
            'ingredients' => ['required', 'string', 'max:1000'],
        ]);
    }

    private function productColumns(array $d): array
    {
        return [
            'name' => $d['name'],
            'subtitle' => strtoupper($d['subtitle']),
            'group' => $d['group'],
            'taste' => $d['taste'],
            'base_price' => $d['basePrice'],
            'stock' => $d['stock'],
            'tag' => $d['tag'],
            'bg' => $d['bg'],
            'bag' => $d['bag'],
            'label' => $d['label'],
            'crunch' => $d['crunch'],
            'spice' => $d['spice'],
            'ingredients' => $d['ingredients'],
            'updated_at' => now(),
        ];
    }

    public function saveProduct(
        Request $request,
        ?int $id = null
    ) {
        $data = $this->productColumns(
            $this->validProduct($request)
        );

        if ($id) {
            $count = DB::table('products')
                ->where('id', $id)
                ->where('is_active', true)
                ->update($data);

            abort_unless($count, 404);
        } else {
            DB::table('products')->insert(
                $data + [
                    'is_active' => true,
                    'created_at' => now(),
                ]
            );
        }

        return response()->json([
            'products' => $this->products()
        ]);
    }

    public function toggleStock(int $id)
    {
        DB::transaction(function () use ($id) {
            $p = DB::table('products')
                ->where('id', $id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            abort_unless($p, 404);

            DB::table('products')
                ->where('id', $id)
                ->update([
                    'stock' => $p->stock > 0 ? 0 : 25,
                    'updated_at' => now(),
                ]);
        });

        return response()->json([
            'products' => $this->products()
        ]);
    }

    public function deleteProduct(int $id)
    {
        DB::table('products')
            ->where('id', $id)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        return response()->json([
            'products' => $this->products()
        ]);
    }

    public function saveSettings(Request $request)
    {
        $d = $request->validate([
            'ribbon' => ['required', 'string', 'max:140'],
            'aboutTitle' => ['required', 'string', 'max:90'],
            'aboutDescription' => [
                'required', 'string', 'max:400'
            ],
            'regShipping' => [
                'required', 'integer', 'min:0', 'max:1000000'
            ],
            'expShipping' => [
                'required', 'integer', 'min:0', 'max:1000000'
            ],
        ]);

        DB::table('store_settings')
            ->where('id', 1)
            ->update([
                'ribbon' => $d['ribbon'],
                'about_title' => $d['aboutTitle'],
                'about_description' => $d['aboutDescription'],
                'reg_shipping' => $d['regShipping'],
                'exp_shipping' => $d['expShipping'],
                'updated_at' => now(),
            ]);

        return response()->json([
            'settings' => $this->settingsData()
        ]);
    }

    private function priceFor(
        $base,
        $weight
    ): int {
        $multiplier = [
            100 => 1,
            250 => 2.2,
            500 => 4,
        ][$weight];

        return (int) round(
            ($base * $multiplier) / 500
        ) * 500;
    }

        public function checkout(Request $request)
    {
        $input = $request->validate([
            'buyer' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'min:9', 'max:18'],
            'city' => ['required', 'string', 'max:70'],
            'address' => ['required', 'string', 'max:300'],
            'courier' => ['required', 'in:Reguler,Express'],
            'method' => [
                'required', 'in:QRIS,Transfer Bank,E-Wallet'
            ],
            'voucher' => ['required', 'boolean'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.key' => [
                'required', 'string', 'max:80'
            ],
            'items.*.qty' => [
                'required', 'integer', 'min:1', 'max:50'
            ],
        ]);

        $result = DB::transaction(function () use ($input) {
            $parsed = [];
            $needs = [];

            foreach ($input['items'] as $item) {
                $key = $item['key'];
                $qty = (int) $item['qty'];

                if (preg_match(
                    '/^(\d+)_(100|250|500)$/',
                    $key,
                    $m
                )) {
                    $ids = [(int) $m[1]];
                    $w = (int) $m[2];
                    $type = 'single';

                } elseif (preg_match(
                    '/^combo_(\d+)_(\d+)_(\d+)$/',
                    $key,
                    $m
                )) {
                    $ids = [
                        (int) $m[1],
                        (int) $m[2],
                        (int) $m[3],
                    ];
                    $w = 100;
                    $type = 'bundle';

                } else {
                    abort(422, 'Format produk tidak valid.');
                }

                foreach ($ids as $id) {
                    $needs[$id] = ($needs[$id] ?? 0) + $qty;
                }

                $parsed[] = [
                    'ids' => $ids,
                    'weight' => $w,
                    'qty' => $qty,
                    'type' => $type,
                ];
            }

            $products = DB::table('products')
                ->whereIn('id', array_keys($needs))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($needs as $id => $qty) {
                $p = $products->get($id);

                if (!$p || !$p->is_active || $p->stock < $qty) {
                    abort(
                        422,
                        'Stok produk berubah. Perbarui keranjang.'
                    );
                }
            }

            $lines = [];
            $subtotal = 0;

            foreach ($parsed as $it) {
                if ($it['type'] === 'single') {
                    $p = $products[$it['ids'][0]];

                    $price = $this->priceFor(
                        $p->base_price,
                        $it['weight']
                    );

                    $name = $p->name;
                    $weight = $it['weight'] . 'g';
                    $pid = $p->id;
                } else {
                    $price = max(
                        0,
                        array_sum(array_map(
                            fn ($id) => $products[$id]->base_price,
                            $it['ids']
                        )) - 6000
                    );

                    $name = 'Paket Kombo 3 Rasa ('
                        . implode(' + ', array_map(
                            fn ($id) => $products[$id]->subtitle,
                            $it['ids']
                        )) . ')';

                    $weight = '3×100g';
                    $pid = null;
                }

                $subtotal += $price * $it['qty'];

                $lines[] = [
                    'product_id' => $pid,
                    'product_name' => $name,
                    'weight_label' => $weight,
                    'bundle_ids' => $it['type'] === 'bundle'
                        ? json_encode($it['ids'])
                        : null,
                    'unit_price' => $price,
                    'quantity' => $it['qty'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $s = $this->setting();

            $discount = $input['voucher']
                ? min(5000, $subtotal)
                : 0;

            $shipping = $input['courier'] === 'Express'
                ? $s->exp_shipping
                : ($subtotal >= 50000 ? 0 : $s->reg_shipping);

            $total = $subtotal - $discount + $shipping;

            $code = 'KRK-'
                . now()->format('ymd')
                . '-'
                . strtoupper(Str::random(10));

            $orderId = DB::table('orders')->insertGetId([
                'order_code' => $code,
                'buyer_name' => $input['buyer'],
                'buyer_phone' => $input['phone'],
                'buyer_city' => $input['city'],
                'buyer_address' => $input['address'],
                'courier' => $input['courier'],
                'payment_method' => $input['method'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_cost' => $shipping,
                'total_amount' => $total,
                'payment_status' => 'pending',
                'status' => 'Menunggu Pembayaran',
                'stock_reserved' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($lines as $line) {
                DB::table('order_items')->insert(
                    $line + ['order_id' => $orderId]
                );
            }

            foreach ($needs as $id => $qty) {
                DB::table('products')
                    ->where('id', $id)
                    ->decrement('stock', $qty);
            }

            return [
                'id' => $orderId,
                'code' => $code,
                'total' => $total,
            ];
        });

        try {
            $this->midtrans();

            $enabled = match ($input['method']) {
                'QRIS' => [
                    'gopay', 'shopeepay', 'other_qris'
                ],
                'Transfer Bank' => [
                    'bank_transfer'
                ],
                default => [
                    'gopay', 'shopeepay', 'ovo', 'dana'
                ],
            };

            $token = Snap::getSnapToken([
                'transaction_details' => [
                    'order_id' => $result['code'],
                    'gross_amount' => $result['total'],
                ],
                'customer_details' => [
                    'first_name' => $input['buyer'],
                    'phone' => $input['phone'],
                ],
                'enabled_payments' => $enabled,
            ]);

            DB::table('orders')
                ->where('id', $result['id'])
                ->update(['snap_token' => $token]);

            return response()->json([
                'code' => $result['code'],
                'total' => $result['total'],
                'token' => $token,
            ]);

        } catch (\Throwable $e) {
            Log::error('Midtrans: ' . $e->getMessage());

            $this->applyPayment(
                $result['code'],
                'failure'
            );

            return response()->json([
                'message' => 'Gagal membuka pembayaran Midtrans. Periksa konfigurasi Sandbox.'
            ], 502);
        }
    }

        private function applyPayment(
        string $code,
        string $status,
        ?string $fraud = null
    ): void {
        DB::transaction(function () use ($code, $status, $fraud) {
            $order = DB::table('orders')
                ->where('order_code', $code)
                ->lockForUpdate()
                ->first();

            if (!$order) return;

            // Pesanan yang sudah dibayar tidak diubah oleh
            // notifikasi gagal atau kedaluwarsa yang datang belakangan.
            if ($order->payment_status === 'paid') return;

            $paid = (
                $status === 'settlement'
                || $status === 'capture'
            ) && (
                $fraud === null
                || $fraud === 'accept'
            );

            $failed = in_array($status, [
                'deny', 'expire', 'cancel', 'failure'
            ], true);

            if ($paid) {
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update([
                        'payment_status' => 'paid',
                        'status' => 'Sedang Dikemas',
                        'paid_at' => now(),
                        'updated_at' => now(),
                    ]);

            } elseif ($failed) {
                if ($order->stock_reserved) {
                    $items = DB::table('order_items')
                        ->where('order_id', $order->id)
                        ->get();

                    foreach ($items as $item) {
                        $ids = $item->bundle_ids
                            ? json_decode($item->bundle_ids, true)
                            : [$item->product_id];

                        foreach ($ids as $id) {
                            DB::table('products')
                                ->where('id', $id)
                                ->increment('stock', $item->quantity);
                        }
                    }
                }

                DB::table('orders')
                    ->where('id', $order->id)
                    ->update([
                        'stock_reserved' => false,
                        'payment_status' => 'failed',
                        'status' => 'Dibatalkan',
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    public function checkPayment(string $code)
    {
        $order = DB::table('orders')
            ->where('order_code', $code)
            ->first();

        abort_unless($order, 404);

        if ($order->payment_status === 'pending') {
            try {
                $this->midtrans();

                $r = (array) Transaction::status($code);

                $this->applyPayment(
                    $code,
                    (string) ($r['transaction_status'] ?? 'pending'),
                    $r['fraud_status'] ?? null
                );

            } catch (\Throwable $e) {
                // Midtrans bisa mengembalikan 404 jika pembeli
                // belum memilih metode di halaman Snap.
            }
        }

        $current = DB::table('orders')
            ->where('order_code', $code)
            ->first();

        return response()->json([
            'code' => $code,
            'paymentStatus' => $current->payment_status,
            'status' => $current->status,
        ]);
    }

    public function notification(Request $request)
    {
        $p = $request->all();

        $code = (string) ($p['order_id'] ?? '');

        $signature = hash(
            'sha512',
            $code
                . ($p['status_code'] ?? '')
                . ($p['gross_amount'] ?? '')
                . config('services.midtrans.server_key')
        );

        if (
            !isset($p['signature_key'])
            || !hash_equals(
                $signature,
                (string) $p['signature_key']
            )
        ) {
            abort(403);
        }

        $order = DB::table('orders')
            ->where('order_code', $code)
            ->first();

        abort_unless(
            $order
                && (int) round((float) ($p['gross_amount'] ?? 0))
                === (int) $order->total_amount,
            400
        );

        $this->applyPayment(
            $code,
            (string) ($p['transaction_status'] ?? 'pending'),
            $p['fraud_status'] ?? null
        );

        return response()->json(['ok' => true]);
    }

    public function changeOrder(Request $request, string $code)
    {
        $d = $request->validate([
            'status' => [
                'required',
                'in:Sedang Dikemas,Dikirim,Selesai'
            ],
        ]);

        $order = DB::table('orders')
            ->where('order_code', $code)
            ->first();

        abort_unless($order, 404);

        abort_unless(
            $order->payment_status === 'paid',
            422,
            'Pesanan belum dibayar.'
        );

        DB::table('orders')
            ->where('id', $order->id)
            ->update([
                'status' => $d['status'],
                'updated_at' => now(),
            ]);

        return response()->json(['ok' => true]);
    }
}