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
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('caption_en')->nullable();
            $table->text('caption_de')->nullable();
            $table->text('caption_nl')->nullable();
            $table->text('caption_pl')->nullable();
            $table->text('caption_fr')->nullable();
            $table->string('background')->nullable();
            $table->string('video')->nullable();
            $table->string('link')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slides');
    }
};
