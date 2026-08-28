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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('ssp_uk', 10, 2)->nullable()->after('ssp_cz');
            $table->decimal('trade_uk', 10, 2)->nullable()->after('trade_cz');
            $table->integer('qty_break_uk')->nullable()->after('qty_discount');
            $table->decimal('qty_discount_uk', 10, 2)->nullable()->after('qty_break_uk');
            $table->decimal('promo_uk', 10, 2)->nullable()->after('promo_cz');
            $table->integer('stock_uk')->nullable()->after('stock');
            $table->date('eta_uk')->nullable()->after('eta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'ssp_uk',
                'trade_uk',
                'qty_break_uk',
                'qty_discount_uk',
                'promo_uk',
                'stock_uk',
                'eta_uk',
            ]);
        });
    }
};
