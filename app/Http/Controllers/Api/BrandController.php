<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Display a listing of brands that have published products.
     * Filters by user's account discount authorization if logged in with Customer role,
     * otherwise filters by country authorization.
     */
    public function index(Request $request): JsonResponse
    {
        // Attempt to authenticate via Sanctum token (even on public route)
        $user = $request->user('sanctum');

        // Check if user is a Customer with account and has brand discounts
        $filterByDiscounts = false;
        $authorizedBrandIds = [];

        if ($user && $user->account_id && $user->hasRole('Customer')) {
            $account = $user->account()->with('discounts')->first();
            if ($account && $account->discounts) {
                // Get brand IDs where auth = true
                $authorizedBrandIds = $account->discounts()
                    ->where('auth', true)
                    ->pluck('brand_id')
                    ->toArray();

                if (!empty($authorizedBrandIds)) {
                    $filterByDiscounts = true;
                }
            }
        }

        // Debug logging
        \Log::info('BrandController::index', [
            'authenticated' => $user ? true : false,
            'user_id' => $user?->id,
            'account_id' => $user?->account_id,
            'filter_by_discounts' => $filterByDiscounts,
            'authorized_brand_ids' => $authorizedBrandIds,
        ]);

        // Base query with published products
        $query = Brand::select(['brands.id', 'brands.code', 'brands.name', 'brands.slug'])
            ->join('products', 'brands.id', '=', 'products.brand_id')
            ->where('products.published', true)
            ->distinct();

        // Filter by account brand authorization if applicable
        if ($filterByDiscounts) {
            $query->whereIn('brands.id', $authorizedBrandIds);
        } else {
            // Fall back to country filtering for non-Customer users
            $countryCode = null;

            // First priority: logged-in user's account country
            if ($user && $user->account) {
                $account = $user->account()->with('country')->first();
                if ($account && $account->country) {
                    $countryCode = $account->country->code;
                }
            }

            // Second priority: country_code query parameter
            if (!$countryCode && $request->has('country_code')) {
                $countryCode = $request->input('country_code');
            }

            // Filter by country authorization if country is determined
            if ($countryCode) {
                $query->whereHas('countries', function ($q) use ($countryCode) {
                    $q->where('code', $countryCode);
                });
            }
        }

        $brands = $query->orderBy('brands.name')->get();

        \Log::info('BrandController::index result', [
            'total_brands' => $brands->count(),
            'filtered_by' => $filterByDiscounts ? 'discounts' : 'country',
        ]);

        return response()->json($brands);
    }

    /**
     * Display all brands with product counts for admin view.
     */
    public function adminIndex(): JsonResponse
    {
        $brands = Brand::select(['brands.id', 'brands.code', 'brands.name', 'brands.slug'])
            ->withCount('products')
            ->orderBy('brands.name')
            ->get();

        return response()->json($brands);
    }

    /**
     * Display the specified brand with its published products.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        // Search by slug directly (no need to convert dashes)
        $brand = Brand::where('slug', $slug)->firstOrFail();

        $user = $request->user('sanctum');
        $selectedCategoryId = $request->input('category_id');
        $selectedCategory = null;
        $childCategories = [];

        // If a category is selected, check if it's a parent and get its children
        if ($selectedCategoryId) {
            $selectedCategory = Category::with(['children' => function ($query) use ($brand, $user) {
                $query->whereHas('products', function ($q) use ($brand, $user) {
                    $q->where('brand_id', $brand->id)
                      ->where('published', true)
                      ->visibleTo($user);
                });
            }])->find($selectedCategoryId);

            if ($selectedCategory && $selectedCategory->parent_id === null) {
                // It's a parent category, get its children that have products for this brand
                $childCategories = $selectedCategory->children;
            } elseif ($selectedCategory && $selectedCategory->parent_id !== null) {
                // It's a child category, get all siblings (children of the same parent)
                $parent = Category::with(['children' => function ($query) use ($brand, $user) {
                    $query->whereHas('products', function ($q) use ($brand, $user) {
                        $q->where('brand_id', $brand->id)
                          ->where('published', true)
                          ->visibleTo($user);
                    });
                }])->find($selectedCategory->parent_id);

                if ($parent) {
                    $childCategories = $parent->children;
                }
            }
        }

        // Get parent categories for this brand with published products
        $categories = $brand->categories();

        // Build products query
        $productsQuery = $brand->products()
            ->with('description')
            ->where('published', true)
            ->visibleTo($user);

        // Filter by category if provided
        if ($selectedCategoryId) {
            if ($selectedCategory && $selectedCategory->parent_id === null && count($childCategories) > 0) {
                // If parent category with children, show products from parent AND all children
                $categoryIds = [$selectedCategoryId];
                foreach ($childCategories as $child) {
                    $categoryIds[] = $child->id;
                }
                $productsQuery->whereIn('category_id', $categoryIds);
            } else {
                // Show products from this specific category only
                $productsQuery->where('category_id', $selectedCategoryId);
            }
        }

        $products = $productsQuery->orderBy('name')->paginate(20);

        return response()->json([
            'brand' => [
                'id' => $brand->id,
                'code' => $brand->code,
                'name' => $brand->name,
                // Description fields
                'description_en' => $brand->description_en,
                'description_de' => $brand->description_de,
                'description_fr' => $brand->description_fr,
                'description_it' => $brand->description_it,
                // Manufacturer fields
                'mfr' => $brand->mfr,
                'mfr_address' => $brand->mfr_address,
                'mfr_city' => $brand->mfr_city,
                'mfr_country' => $brand->mfr_country,
                'mfr_postcode' => $brand->mfr_postcode,
                'mfr_web' => $brand->mfr_web,
                'mfr_email' => $brand->mfr_email,
                'mfr_tel' => $brand->mfr_tel,
                'mfr_fax' => $brand->mfr_fax,
                // Importer fields
                'imp' => $brand->imp,
                'imp_address' => $brand->imp_address,
                'imp_city' => $brand->imp_city,
                'imp_country' => $brand->imp_country,
                'imp_postcode' => $brand->imp_postcode,
                'imp_web' => $brand->imp_web,
                'imp_email' => $brand->imp_email,
                'imp_tel' => $brand->imp_tel,
                'imp_fax' => $brand->imp_fax,
                // Office fields
                'off' => $brand->off,
                'off_address' => $brand->off_address,
                'off_city' => $brand->off_city,
                'off_country' => $brand->off_country,
                'off_postcode' => $brand->off_postcode,
                'off_web' => $brand->off_web,
                'off_email' => $brand->off_email,
                'off_tel' => $brand->off_tel,
                'off_fax' => $brand->off_fax,
            ],
            'categories' => $categories,
            'child_categories' => $childCategories,
            'selected_category' => $selectedCategory,
            'products' => $products,
        ]);
    }

    /**
     * Get territories data for the sales area map.
     * Returns countries with their associated brand codes.
     */
    public function territories(): JsonResponse
    {
        // Get all countries with their brands
        $countries = Country::with(['brands' => function ($query) {
            $query->select('brands.code', 'brands.name')
                  ->orderBy('brands.code');
        }])
        ->orderBy('name')
        ->get();

        // Transform to match the expected format
        $territories = $countries->map(function ($country) {
            return [
                'country' => $country->name,
                'brands' => $country->brands->pluck('code')->toArray(),
            ];
        })->filter(function ($territory) {
            // Only include countries that have at least one brand
            return count($territory['brands']) > 0;
        })->values();

        return response()->json($territories);
    }
}
