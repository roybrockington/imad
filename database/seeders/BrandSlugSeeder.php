<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Brand;

class BrandSlugSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Generating slugs for all brands...');

        $brands = Brand::all();
        $updated = 0;
        $skipped = 0;

        foreach ($brands as $brand) {
            // Generate slug from name
            $slug = Str::slug($brand->name);

            // Check if slug already exists and is correct
            if ($brand->slug === $slug) {
                $skipped++;
                continue;
            }

            // Update the slug
            $brand->slug = $slug;
            $brand->save();

            $this->command->line("Updated: {$brand->name} → {$slug}");
            $updated++;
        }

        $this->command->newLine();
        $this->command->info("Slug generation complete!");
        $this->command->info("Updated: {$updated} brands");
        $this->command->info("Skipped: {$skipped} brands (already had correct slugs)");
    }
}
