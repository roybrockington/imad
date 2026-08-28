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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->boolean('invoicing');
            $table->boolean('default');
            $table->string('name1');
            $table->string('name2');
            $table->string('address1');
            $table->string('address2');
            $table->string('city');
            $table->string('postcode');
            $table->string('country');
            $table->string('tel');
            $table->string('email');
            $table->integer('account_id');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
