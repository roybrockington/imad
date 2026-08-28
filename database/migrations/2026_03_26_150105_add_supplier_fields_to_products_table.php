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
            $table->foreignId('manufacturer_supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('office_supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('importer_supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->string('mpn')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['manufacturer_supplier_id']);
            $table->dropForeign(['office_supplier_id']);
            $table->dropForeign(['importer_supplier_id']);
            $table->dropColumn([
                'manufacturer_supplier_id',
                'office_supplier_id',
                'importer_supplier_id',
                'mpn'
            ]);
        });
    }
};
