<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->integer('category_id');
            $table->integer('brand_id');
            $table->decimal('ssp_eu', 10, 2);
            $table->decimal('ssp_pl', 10, 2);
            $table->decimal('ssp_cz', 10, 2);
            $table->decimal('trade_eu', 10, 2);
            $table->decimal('trade_pl', 10, 2);
            $table->decimal('trade_cz', 10, 2);
            $table->date('promo_start')->nullable();
            $table->date('promo_end')->nullable();
            $table->integer('qty_break');
            $table->decimal('qty_discount');
            $table->decimal('promo_eu', 10, 2)->nullable();
            $table->decimal('promo_pl', 10, 2);
            $table->decimal('promo_cz', 10, 2);
            $table->string('ean', 13)->nullable();
            $table->boolean('available_for_sale')->default(false);
            $table->boolean('bundle')->default(false);
            $table->boolean('esd')->default(false);
            $table->boolean('published')->default(false);
            $table->timestamps();

            // Indexes for performance
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
