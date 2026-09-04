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
    Schema::create('categories', function (Blueprint $table) {
      $table->id();
      $table->string('code');
      $table->integer('parent_id')->nullable();
      $table->string('name_de');
      $table->string('name_fr');
      $table->string('name_it');
      $table->string('desc_de');
      $table->string('desc_fr');
      $table->string('desc_it');
      $table->string('img');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('categories');
  }
};
