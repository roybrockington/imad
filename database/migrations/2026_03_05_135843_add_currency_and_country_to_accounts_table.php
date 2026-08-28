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
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('discount')->constrained();
            $table->foreignId('country_id')->nullable()->after('currency_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropForeign(['country_id']);
            $table->dropColumn(['currency_id', 'country_id']);
        });
    }
};
