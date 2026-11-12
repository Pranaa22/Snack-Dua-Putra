<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $t) {
                $t->id(); $t->string('name', 100); $t->string('subtitle', 30)->default('ORIGINAL');
                $t->string('group', 30)->default('gurih'); $t->text('taste')->nullable();
                $t->unsignedInteger('base_price')->default(0); $t->unsignedInteger('stock')->default(0);
                $t->string('tag', 30)->default('Pilihan'); $t->string('bg', 7)->default('#f7e6cb');
                $t->string('bag', 7)->default('#d96b27'); $t->string('label', 7)->default('#fff3df');
                $t->unsignedTinyInteger('crunch')->default(5); $t->unsignedTinyInteger('spice')->default(0);
                $t->text('ingredients')->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
            });
        }
        foreach (['base_cost'=>'integer','low_stock_threshold'=>'integer','slug'=>'string'] as $col => $type) {
            if (!Schema::hasColumn('products', $col)) {
                Schema::table('products', function (Blueprint $t) use ($col,$type) {
                    if ($col === 'slug') $t->string('slug', 150)->nullable()->unique();
                    else $t->unsignedInteger($col)->default($col === 'low_stock_threshold' ? 10 : 0);
                });
            }
        }
        if (!Schema::hasTable('store_settings')) {
            Schema::create('store_settings', function (Blueprint $t) {
                $t->id(); $t->string('ribbon', 140)->default('Keripik pilihan dengan rasa yang pas untuk setiap suasana');
                $t->string('about_title', 150)->default('Cerita Kami');
                $t->text('about_description')->nullable();
                $t->unsignedInteger('reg_shipping')->default(12000);
                $t->unsignedInteger('exp_shipping')->default(22000); $t->timestamps();
            });
        }
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $t) {
                $t->id(); $t->string('order_code', 50)->unique();
                $t->string('buyer_name', 80); $t->string('buyer_phone', 20)->nullable();
                $t->string('buyer_city', 70)->nullable(); $t->text('buyer_address')->nullable();
                $t->string('courier', 20)->default('Reguler');
                $t->string('payment_method', 40)->default('QRIS');
                $t->unsignedInteger('subtotal'); $t->unsignedInteger('discount')->default(0);
                $t->unsignedInteger('shipping_cost')->default(0);
                $t->unsignedInteger('total_amount');
                $t->string('payment_status', 20)->default('pending');
                $t->string('status', 40)->default('Menunggu Pembayaran');
                $t->boolean('stock_reserved')->default(true);
                $t->text('snap_token')->nullable(); $t->timestamp('paid_at')->nullable(); $t->timestamps();
            });
        }
        foreach (['channel','cash_received','cash_change','receipt_token','cost_total'] as $col) {
            if (!Schema::hasColumn('orders', $col)) {
                Schema::table('orders', function (Blueprint $t) use ($col) {
                    if ($col === 'channel') $t->string('channel', 16)->default('online');
                    elseif ($col === 'receipt_token') $t->string('receipt_token', 80)->nullable()->unique();
                    else $t->unsignedInteger($col)->default(0);
                });
            }
        }
        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $t) {
                $t->id(); $t->foreignId('order_id')->constrained()->cascadeOnDelete();
                $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $t->string('product_name'); $t->string('weight_label', 20);
                $t->json('bundle_ids')->nullable();
                $t->unsignedInteger('unit_price'); $t->unsignedInteger('quantity'); $t->timestamps();
            });
        }
        if (!Schema::hasColumn('order_items', 'unit_cost')) {
            Schema::table('order_items', fn(Blueprint $t) => $t->unsignedInteger('unit_cost')->default(0));
        }
        if (Schema::hasTable('users') && !Schema::hasColumn('users','is_admin')) {
            Schema::table('users', fn(Blueprint $t) => $t->boolean('is_admin')->default(false));
        }
    }
    public function down(): void
    {
        // Additive migration: existing project data is deliberately preserved on rollback.
    }
};
