<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Old installation used varchar(12) for courier; "Ambil di toko" is longer.
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'courier')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('courier', 50)->default('Reguler')->change();
            });
        }

        if (!Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table) {
                $table->id();
                $table->string('code', 24)->unique();
                $table->string('description', 120)->nullable();
                $table->enum('type', ['fixed', 'percent']);
                $table->unsignedInteger('value');
                $table->unsignedInteger('min_subtotal')->default(0);
                $table->unsignedInteger('max_discount')->nullable();
                $table->date('starts_on')->nullable();
                $table->date('ends_on')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'promo_code')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('promo_code', 24)->nullable();
            });
        }
    }

    public function down(): void
    {
        // Don't delete promotions/order fields or shrink courier; keeps real data safe.
    }
};
