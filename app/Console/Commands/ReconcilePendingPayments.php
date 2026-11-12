<?php
namespace App\Console\Commands;

use App\Services\KriukCommerce;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Transaction;

class ReconcilePendingPayments extends Command
{
    protected $signature = 'kriuk:reconcile';
    protected $description = 'Sinkronisasi pembayaran tertunda dan lepaskan reservasi kedaluwarsa';

    public function handle(): int
    {
        $server=config('kriuk.midtrans_server_key');
        if (!$server) {$this->warn('Midtrans Server Key belum ada.');return self::SUCCESS;}
        MidtransConfig::$serverKey=$server;
        MidtransConfig::$isProduction=(bool)config('kriuk.midtrans_production');
        $orders=DB::table('orders')->where('channel','online')->where('payment_status','pending')
            ->where('created_at','<',now()->subMinutes(3))->orderBy('id')->limit(100)->get();
        foreach($orders as $o){
            try {
                $r=(array)Transaction::status($o->order_code);
                if ((int)round((float)($r['gross_amount']??-1))===(int)$o->total_amount) {
                    KriukCommerce::settle($o->order_code,(string)($r['transaction_status']??'pending'),$r['fraud_status']??null);
                }
            }catch(\Throwable $e){
                // Some Snap transactions return 404 before the customer selects a payment method.
                // After the configured 60-minute Snap lifetime plus buffer, release unused stock.
                if ($o->created_at < now()->subMinutes(75)->toDateTimeString()) {
                    KriukCommerce::settle($o->order_code,'expire');
                } else Log::info('Status Midtrans belum siap',['code'=>$o->order_code]);
            }
        }
        $this->info('Pemeriksaan transaksi tertunda selesai.');
        return self::SUCCESS;
    }
}
