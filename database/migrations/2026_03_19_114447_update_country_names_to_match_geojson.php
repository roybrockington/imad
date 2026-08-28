<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update country names to match the GeoJSON data used in the sales area map
        DB::table('countries')->where('name', 'Bosnia & Herzegovina')->update(['name' => 'Bosnia']);
        DB::table('countries')->where('name', 'Czech Republic')->update(['name' => 'Czechia']);
        DB::table('countries')->where('name', 'North Macedonia')->update(['name' => 'Macedonia']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original names
        DB::table('countries')->where('name', 'Bosnia')->update(['name' => 'Bosnia & Herzegovina']);
        DB::table('countries')->where('name', 'Czechia')->update(['name' => 'Czech Republic']);
        DB::table('countries')->where('name', 'Macedonia')->update(['name' => 'North Macedonia']);
    }
};
