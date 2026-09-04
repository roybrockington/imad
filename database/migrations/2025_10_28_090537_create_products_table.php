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
      $table->decimal('ssp_ch', 10, 2);
      $table->decimal('trade_ch', 10, 2);
      $table->date('promo_start')->nullable();
      $table->date('promo_end')->nullable();
      $table->integer('qty_break');
      $table->decimal('qty_discount');
      $table->decimal('promo_ch', 10, 2)->nullable();
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
