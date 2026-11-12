<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Existing database from earlier installation: customer contact/shipping is optional for walk-in cash sales.
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                // Laravel 12 / MySQL supports change() without requiring a new table.
                $table->string('buyer_phone', 20)->nullable()->change();
                $table->string('buyer_city', 70)->nullable()->change();
                $table->text('buyer_address')->nullable()->change();
            });
        }

        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'image_path')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('image_path', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        // Keep uploaded image references and past orders safe; do not reverse nullable fields.
    }
};
