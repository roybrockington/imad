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
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn([
                // Manufacturer fields
                'mfr', 'mfr_address', 'mfr_city', 'mfr_country', 'mfr_postcode',
                'mfr_web', 'mfr_email', 'mfr_tel', 'mfr_fax',
                // Importer fields
                'imp', 'imp_address', 'imp_city', 'imp_country', 'imp_postcode',
                'imp_web', 'imp_email', 'imp_tel', 'imp_fax',
                // Office fields
                'off', 'off_address', 'off_city', 'off_country', 'off_postcode',
                'off_web', 'off_email', 'off_tel', 'off_fax',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            // Manufacturer fields
            $table->string('mfr')->nullable();
            $table->string('mfr_address')->nullable();
            $table->string('mfr_city')->nullable();
            $table->string('mfr_country')->nullable();
            $table->string('mfr_postcode')->nullable();
            $table->string('mfr_web')->nullable();
            $table->string('mfr_email')->nullable();
            $table->string('mfr_tel')->nullable();
            $table->string('mfr_fax')->nullable();

            // Importer fields
            $table->string('imp')->nullable();
            $table->string('imp_address')->nullable();
            $table->string('imp_city')->nullable();
            $table->string('imp_country')->nullable();
            $table->string('imp_postcode')->nullable();
            $table->string('imp_web')->nullable();
            $table->string('imp_email')->nullable();
            $table->string('imp_tel')->nullable();
            $table->string('imp_fax')->nullable();

            // Office fields
            $table->string('off')->nullable();
            $table->string('off_address')->nullable();
            $table->string('off_city')->nullable();
            $table->string('off_country')->nullable();
            $table->string('off_postcode')->nullable();
            $table->string('off_web')->nullable();
            $table->string('off_email')->nullable();
            $table->string('off_tel')->nullable();
            $table->string('off_fax')->nullable();
        });
    }
};
