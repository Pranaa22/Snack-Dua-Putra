<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_admin')->default(false);
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('name', 60);
            $t->string('subtitle', 14);
            $t->string('group', 12);
            $t->string('taste', 110);
            $t->unsignedInteger('base_price');
            $t->unsignedInteger('stock')->default(0);
            $t->string('tag', 20);

            // Warna kemasan SVG
            $t->string('bg', 7);
            $t->string('bag', 7);
            $t->string('label', 7);

            // Tambahan untuk Detail Produk
            $t->unsignedTinyInteger('crunch')->default(5);
            $t->unsignedTinyInteger('spice')->default(0);
            $t->text('ingredients');

            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('store_settings', function (Blueprint $t) {
            $t->id();
            $t->string('ribbon', 140);
            $t->string('about_title', 90);
            $t->string('about_description', 400);
            $t->unsignedInteger('reg_shipping')->default(12000);
            $t->unsignedInteger('exp_shipping')->default(22000);
            $t->timestamps();
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('order_code', 50)->unique();
            $t->string('buyer_name', 80);
            $t->string('buyer_phone', 18);
            $t->string('buyer_city', 70);
            $t->string('buyer_address', 300);
            $t->string('courier', 12);
            $t->string('payment_method', 30);

            $t->unsignedInteger('subtotal');
            $t->unsignedInteger('discount')->default(0);
            $t->unsignedInteger('shipping_cost')->default(0);
            $t->unsignedInteger('total_amount');

            $t->string('payment_status', 20)->default('pending');
            $t->string('status', 40)->default('Menunggu Pembayaran');
            $t->boolean('stock_reserved')->default(true);
            $t->text('snap_token')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });

        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')
                ->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')
                ->nullable()->constrained()->nullOnDelete();

            $t->string('product_name');
            $t->string('weight_label', 15);
            $t->json('bundle_ids')->nullable();
            $t->unsignedInteger('unit_price');
            $t->unsignedInteger('quantity');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('store_settings');
        Schema::dropIfExists('products');

        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('is_admin');
        });
    }
};