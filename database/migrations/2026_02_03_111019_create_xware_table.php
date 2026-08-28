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
        Schema::create('xware', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->unsignedBigInteger('product_id');
            $table->integer('stock');
            $table->decimal('discount', 8, 2);
            $table->string('type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xware');
    }
};
