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
        Schema::table('countries', function (Blueprint $table) {
            $table->decimal('shipping_eur', 10, 2)->nullable();
            $table->decimal('shipping_czk', 10, 2)->nullable();
            $table->decimal('shipping_gbp', 10, 2)->nullable();
            $table->decimal('shipping_pln', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn(['shipping_eur', 'shipping_czk', 'shipping_gbp', 'shipping_pln']);
        });
    }
};
