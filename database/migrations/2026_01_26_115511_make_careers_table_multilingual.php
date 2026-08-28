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
        Schema::table('careers', function (Blueprint $table) {
            // Add multilingual position columns
            $table->string('position_en')->nullable();
            $table->string('position_de')->nullable();
            $table->string('position_fr')->nullable();
            $table->string('position_nl')->nullable();
            $table->string('position_pl')->nullable();

            // Add multilingual tasks columns
            $table->text('tasks_en')->nullable();
            $table->text('tasks_de')->nullable();
            $table->text('tasks_fr')->nullable();
            $table->text('tasks_nl')->nullable();
            $table->text('tasks_pl')->nullable();

            // Add multilingual profile columns
            $table->text('profile_en')->nullable();
            $table->text('profile_de')->nullable();
            $table->text('profile_fr')->nullable();
            $table->text('profile_nl')->nullable();
            $table->text('profile_pl')->nullable();

            // Add multilingual expectations columns
            $table->text('expectations_en')->nullable();
            $table->text('expectations_de')->nullable();
            $table->text('expectations_fr')->nullable();
            $table->text('expectations_nl')->nullable();
            $table->text('expectations_pl')->nullable();
        });

        // Drop old non-multilingual columns
        Schema::table('careers', function (Blueprint $table) {
            $table->dropColumn(['position', 'tasks', 'profile', 'expectations']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('careers', function (Blueprint $table) {
            // Restore original columns
            $table->string('position');
            $table->text('tasks');
            $table->text('profile');
            $table->text('expectations');
        });

        Schema::table('careers', function (Blueprint $table) {
            // Drop multilingual columns
            $table->dropColumn([
                'position_en', 'position_de', 'position_fr', 'position_nl', 'position_pl',
                'tasks_en', 'tasks_de', 'tasks_fr', 'tasks_nl', 'tasks_pl',
                'profile_en', 'profile_de', 'profile_fr', 'profile_nl', 'profile_pl',
                'expectations_en', 'expectations_de', 'expectations_fr', 'expectations_nl', 'expectations_pl'
            ]);
        });
    }
};
