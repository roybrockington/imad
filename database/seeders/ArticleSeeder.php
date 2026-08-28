<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleXMLElement;

class ArticleSeeder extends Seeder
{
    /**
     * Mapping of WordPress authors to system users.
     */
    private array $authorMapping = [];

    /**
     * Mapping of WordPress categories to Brand IDs.
     */
    private array $brandMapping = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $xmlPath = storage_path('app/wordpress-export.xml');

        if (!file_exists($xmlPath)) {
            $this->command->error('WordPress export file not found at: ' . $xmlPath);
            $this->command->info('Please download the file to: ' . $xmlPath);
            return;
        }

        $this->command->info('Loading WordPress export file...');

        // Load and parse XML
        $xml = simplexml_load_file($xmlPath);
        if (!$xml) {
            $this->command->error('Failed to parse XML file');
            return;
        }

        // Register namespaces
        $namespaces = $xml->getNamespaces(true);

        // Build author mapping
        $this->buildAuthorMapping($xml, $namespaces);

        // Build brand mapping
        $this->buildBrandMapping();

        $this->command->info('Processing articles...');

        $imported = 0;
        $skipped = 0;

        // Process each item in the channel
        foreach ($xml->channel->item as $item) {
            $postType = (string) $item->children($namespaces['wp'])->post_type;
            $status = (string) $item->children($namespaces['wp'])->status;

            // Only import published posts (not pages or other types)
            if ($postType !== 'post' || $status !== 'publish') {
                $skipped++;
                continue;
            }

            try {
                $this->importArticle($item, $namespaces);
                $imported++;
                $this->command->info("Imported: " . (string) $item->title);
            } catch (\Exception $e) {
                $this->command->error("Failed to import: " . (string) $item->title);
                $this->command->error("Error: " . $e->getMessage());
                $skipped++;
            }
        }

        $this->command->info("Import complete!");
        $this->command->info("Imported: {$imported} articles");
        $this->command->info("Skipped: {$skipped} items");
    }

    /**
     * Build mapping of WordPress authors to Laravel users.
     */
    private function buildAuthorMapping(SimpleXMLElement $xml, array $namespaces): void
    {
        $this->command->info('Building author mapping...');

        // Get or create a default admin user
        $defaultUser = User::where('email', 'admin@example.com')->first();

        if (!$defaultUser) {
            $this->command->warn('No admin user found, using first available user');
            $defaultUser = User::first();
        }

        foreach ($xml->channel->children($namespaces['wp'])->author as $author) {
            $wpLogin = (string) $author->children($namespaces['wp'])->author_login;
            $wpEmail = (string) $author->children($namespaces['wp'])->author_email;
            $wpDisplayName = (string) $author->children($namespaces['wp'])->author_display_name;

            // Try to find matching user by email
            $user = User::where('email', $wpEmail)->first();

            if (!$user) {
                // Use default user if no match found
                $user = $defaultUser;
                $this->command->warn("No user found for {$wpDisplayName} ({$wpEmail}), using default");
            }

            $this->authorMapping[$wpLogin] = $user->id;
            $this->command->info("Mapped {$wpLogin} -> {$user->name}");
        }
    }

    /**
     * Build mapping of WordPress categories to Brand IDs.
     */
    private function buildBrandMapping(): void
    {
        $this->command->info('Building brand mapping...');

        $brands = Brand::all();

        foreach ($brands as $brand) {
            // Create mapping using lowercase brand name
            $key = strtolower($brand->name);
            $this->brandMapping[$key] = $brand->id;
        }

        $this->command->info('Mapped ' . count($this->brandMapping) . ' brands');
    }

    /**
     * Import a single article from WordPress.
     */
    private function importArticle(SimpleXMLElement $item, array $namespaces): void
    {
        $wp = $namespaces['wp'];
        $content = $namespaces['content'];
        $dc = $namespaces['dc'];

        // Extract basic fields
        $title = (string) $item->title;
        $slug = (string) $item->children($wp)->post_name;
        $body = (string) $item->children($content)->encoded;
        $excerpt = (string) $item->children('excerpt', true)->encoded;
        $pubDate = (string) $item->pubDate;
        $creator = (string) $item->children($dc)->creator;

        // Clean up HTML content
        $body = $this->cleanContent($body);
        $excerpt = $this->cleanContent($excerpt);

        // Get author ID
        $authorId = $this->authorMapping[$creator] ?? User::first()->id;

        // Check if article already exists
        $existingArticle = Article::where('slug', $slug)->first();
        if ($existingArticle) {
            $this->command->warn("Article with slug '{$slug}' already exists, skipping...");
            return;
        }

        // Create article
        $article = Article::create([
            'title' => $title,
            'slug' => $slug ?: Str::slug($title),
            'excerpt' => !empty($excerpt) ? $excerpt : $this->generateExcerpt($body),
            'body' => $body,
            'published' => true,
            'published_at' => $pubDate ? date('Y-m-d H:i:s', strtotime($pubDate)) : now(),
            'author_id' => $authorId,
        ]);

        // Extract and associate brands from categories
        $brandIds = [];
        foreach ($item->category as $category) {
            $categoryName = strtolower((string) $category);

            // Check if this category matches a brand
            if (isset($this->brandMapping[$categoryName])) {
                $brandIds[] = $this->brandMapping[$categoryName];
            }
        }

        // Attach brands to article
        if (!empty($brandIds)) {
            $article->brands()->attach(array_unique($brandIds));
        }
    }

    /**
     * Clean HTML content from WordPress.
     */
    private function cleanContent(string $content): string
    {
        if (empty($content)) {
            return '';
        }

        // Decode HTML entities
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove WordPress-specific shortcodes (basic cleanup)
        $content = preg_replace('/\[.*?\]/', '', $content);

        return trim($content);
    }

    /**
     * Generate an excerpt from the body content.
     */
    private function generateExcerpt(string $body, int $length = 200): string
    {
        // Strip HTML tags
        $text = strip_tags($body);

        // Truncate to desired length
        if (strlen($text) > $length) {
            $text = substr($text, 0, $length);
            $text = substr($text, 0, strrpos($text, ' ')) . '...';
        }

        return $text;
    }
}
