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
        Schema::create('product_descriptions', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->string('name1_de');
            $table->string('name1_en');
            $table->string('name1_pl');
            $table->string('name1_fr');
            $table->string('name1_nl');
            $table->string('name2_de');
            $table->string('name2_en');
            $table->string('name2_pl');
            $table->string('name2_fr');
            $table->string('name2_nl');
            $table->text('text1_de')->nullable();
            $table->text('text1_en')->nullable();
            $table->text('text1_pl')->nullable();
            $table->text('text1_fr')->nullable();
            $table->text('text1_nl')->nullable();
            $table->text('text2_de')->nullable();
            $table->text('text2_en')->nullable();
            $table->text('text2_pl')->nullable();
            $table->text('text2_fr')->nullable();
            $table->text('text2_nl')->nullable();
            $table->string('image1')->nullable();
            $table->string('image2')->nullable();
            $table->string('image3')->nullable();
            $table->string('image4')->nullable();
            $table->string('image5')->nullable();
            $table->string('image6')->nullable();
            $table->string('alt1')->nullable();
            $table->string('alt2')->nullable();
            $table->string('alt3')->nullable();
            $table->string('alt4')->nullable();
            $table->string('alt5')->nullable();
            $table->string('alt6')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_descriptions');
    }
};
