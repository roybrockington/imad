<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $query = Product::query()
            ->select('products.*')
            ->where('products.published', true)
            ->visibleTo($user);

        // Filter by availability
        if ($request->has('available_for_sale')) {
            $query->where('products.available_for_sale', $request->boolean('available_for_sale'));
        }

        // Search by name, code, brand, or product descriptions
        if ($request->has('search')) {
            $search = $request->input('search');

            // Split search into individual words for better matching
            $searchTerms = array_filter(explode(' ', $search));

            // Use JOINs instead of whereHas for better performance
            $query->leftJoin('product_descriptions', 'products.id', '=', 'product_descriptions.product_id')
                  ->leftJoin('brands', 'products.brand_id', '=', 'brands.id');

            // If multiple words, each word must match somewhere (AND logic)
            // This allows "dixon cornerstone" to match brand "Dixon" AND product name containing "cornerstone"
            foreach ($searchTerms as $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('products.name', 'like', "%{$term}%")
                      ->orWhere('products.code', 'like', "%{$term}%")
                      ->orWhere('products.mpn', 'like', "%{$term}%")
                      ->orWhere('brands.name', 'like', "%{$term}%")
                      ->orWhere('product_descriptions.name1_en', 'like', "%{$term}%")
                      ->orWhere('product_descriptions.name2_en', 'like', "%{$term}%")
                      ->orWhere('product_descriptions.name1_de', 'like', "%{$term}%")
                      ->orWhere('product_descriptions.name2_de', 'like', "%{$term}%")
                      ->orWhere('product_descriptions.name1_fr', 'like', "%{$term}%")
                      ->orWhere('product_descriptions.name2_fr', 'like', "%{$term}%")
                      ->orWhere('product_descriptions.name1_nl', 'like', "%{$term}%")
                      ->orWhere('product_descriptions.name2_nl', 'like', "%{$term}%")
                      ->orWhere('product_descriptions.name1_pl', 'like', "%{$term}%")
                      ->orWhere('product_descriptions.name2_pl', 'like', "%{$term}%");
                });
            }
        }

        // Load relationships after query optimization
        $query->with(['description', 'brand']);

        // Support limiting results (for autocomplete)
        $limit = $request->input('limit', 15);

        if ($request->has('limit')) {
            $products = $query->orderBy('products.name')->limit($limit)->get();
            return response()->json($products);
        }

        $products = $query->orderBy('products.name')->paginate(20);

        return response()->json($products);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): JsonResponse
    {
        // Load relationships
        $product->load(['description', 'brand', 'category', 'manufacturerSupplier', 'officeSupplier', 'importerSupplier']);

        return response()->json($product);
    }

    /**
     * Display a product by brand name and product name.
     */
    public function showByBrandAndName(Request $request, string $brandName, string $productName): JsonResponse
    {
        $user = $request->user('sanctum');

        // Find the brand by slug - use database query instead of loading all
        $brand = \App\Models\Brand::where('slug', strtolower($brandName))->first();

        if (!$brand) {
            abort(404, 'Brand not found');
        }

        // Load only products for this brand with minimal data first
        $potentialProducts = Product::with(['description'])
            ->where('published', true)
            ->where('brand_id', $brand->id)
            ->whereHas('description') // Must have description
            ->visibleTo($user)
            ->get(['id', 'code', 'variant', 'brand_id']);

        // Find the product by matching slugified name1_en
        $matchedProduct = $potentialProducts->first(function ($p) use ($productName) {
            $productNameToSlugify = $p->description->name1_en ?? $p->name ?? null;
            if (!$productNameToSlugify) return false;
            return $this->slugify($productNameToSlugify) === strtolower($productName);
        });

        if (!$matchedProduct) {
            abort(404, 'Product not found');
        }

        // Now load the full product data with all relationships
        $product = Product::with(['description', 'brand', 'category', 'xware' => function($query) {
            $query->where('stock', '>', 0);
        }, 'manufacturerSupplier', 'officeSupplier', 'importerSupplier'])
            ->find($matchedProduct->id);

        if (!$product) {
            abort(404, 'Product not found');
        }

        // Get all variant siblings (products with same variant value), sorted consistently
        $variants = [];
        if ($product->variant) {
            $allVariants = Product::with(['description', 'brand'])
                ->where('variant', $product->variant)
                ->where('published', true)
                ->where('id', '!=', $product->id) // Exclude current product
                ->visibleTo($user)
                ->orderBy('code', 'asc')
                ->get();

            // Map all variants to array format
            $variants = $allVariants->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'code' => $variant->code,
                    'name' => $variant->description->name1_en ?? $variant->name,
                    'variant_name' => $variant->description->name2_en ?? null,
                    'slug' => $this->slugify($variant->description->name2_en ?? $variant->code),
                ];
            })->values();
        }

        $productData = $product->toArray();
        $productData['variants'] = $variants;

        return response()->json($productData);
    }

    /**
     * Display a product by brand name, product name, and variant slug.
     */
    public function showByBrandProductAndVariant(Request $request, string $brandName, string $productName, string $variantSlug): JsonResponse
    {
        $user = $request->user('sanctum');

        // Find the brand by slug - use database query instead of loading all
        $brand = \App\Models\Brand::where('slug', strtolower($brandName))->first();

        if (!$brand) {
            abort(404, 'Brand not found');
        }

        // Since we need to match slugified product names, we still need to load products
        // but we can limit by brand to reduce the dataset significantly
        $potentialProducts = Product::with(['description'])
            ->where('published', true)
            ->where('brand_id', $brand->id)
            ->whereNotNull('variant') // Only products with variants
            ->whereHas('description') // Must have description for name matching
            ->visibleTo($user)
            ->get(['id', 'code', 'variant', 'brand_id']);

        // Find the base product by matching slugified name1_en
        $baseProduct = $potentialProducts->first(function ($product) use ($productName) {
            $productNameToSlugify = $product->description->name1_en ?? null;
            if (!$productNameToSlugify) return false;
            return $this->slugify($productNameToSlugify) === strtolower($productName);
        });

        if (!$baseProduct || !$baseProduct->variant) {
            abort(404, 'Product not found');
        }

        // Now load all variant products in one optimized query
        $variantProducts = Product::with(['description', 'brand', 'category', 'xware' => function($query) {
            $query->where('stock', '>', 0);
        }, 'manufacturerSupplier', 'officeSupplier', 'importerSupplier'])
            ->where('variant', $baseProduct->variant)
            ->where('published', true)
            ->visibleTo($user)
            ->orderBy('code', 'asc')
            ->get();

        // Find the specific variant by matching the variant slug (name2)
        $product = $variantProducts->first(function ($p) use ($variantSlug) {
            $variantName = $p->description->name2_en ?? null;
            if (!$variantName) return false;
            return $this->slugify($variantName) === strtolower($variantSlug);
        });

        if (!$product) {
            abort(404, 'Variant not found');
        }

        // Get all variant siblings (excluding current product)
        $variants = $variantProducts
            ->where('id', '!=', $product->id)
            ->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'code' => $variant->code,
                    'name' => $variant->description->name1_en ?? $variant->name,
                    'variant_name' => $variant->description->name2_en ?? null,
                    'slug' => $this->slugify($variant->description->name2_en ?? $variant->code),
                ];
            })
            ->values();

        // Get the canonical URL (base product without variant)
        $canonicalProductName = $this->slugify($baseProduct->description->name1_en ?? $baseProduct->name);

        $productData = $product->toArray();
        $productData['variants'] = $variants;
        $productData['canonical_url'] = "/{$brandName}/{$canonicalProductName}";

        return response()->json($productData);
    }

    /**
     * Convert a string to a URL-friendly slug (matches frontend implementation)
     */
    private function slugify(string $text): string
    {
        return strtolower(trim(
            preg_replace('/-+/', '-',
                preg_replace('/[^\w\-]+/', '',
                    preg_replace('/\s+/', '-',
                        preg_replace('/[\/\\\\?#%&+]/', '-', trim($text))
                    )
                )
            ), '-'
        ));
    }

    /**
     * Get product by article number (for redirects)
     */
    public function getByArticle(string $article): JsonResponse
    {
        $product = Product::with(['brand', 'description'])
            ->where('article', $article)
            ->first();

        if (!$product) {
            return response()->json([
                'error' => 'Product not found'
            ], 404);
        }

        // Return brand slug and product slug for redirect
        $brandSlug = $product->brand ? $this->slugify($product->brand->name) : null;
        $productSlug = $product->description && $product->description->name1_en
            ? $this->slugify($product->description->name1_en)
            : $this->slugify($product->name);

        return response()->json([
            'brand_slug' => $brandSlug,
            'product_slug' => $productSlug
        ]);
    }

    /**
     * Process CSV import and return products with quantities
     */
    public function processCsvImport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.code' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $results = [
            'found' => [],
            'not_found' => []
        ];

        foreach ($request->items as $item) {
            $product = Product::with(['brand', 'description', 'category'])
                ->where('code', $item['code'])
                ->first();

            if ($product) {
                $results['found'][] = [
                    'product' => $product,
                    'quantity' => $item['quantity']
                ];
            } else {
                $results['not_found'][] = [
                    'code' => $item['code'],
                    'quantity' => $item['quantity']
                ];
            }
        }

        return response()->json($results);
    }

}
