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
            $table->decimal('freight_eur', 10, 2)->nullable();
            $table->decimal('freight_czk', 10, 2)->nullable();
            $table->decimal('freight_gbp', 10, 2)->nullable();
            $table->decimal('freight_pln', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn(['freight_eur', 'freight_czk', 'freight_gbp', 'freight_pln']);
        });
    }
};
