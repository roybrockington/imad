<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\JsonResponse;

class CountryController extends Controller
{
    /**
     * Get country by code
     */
    public function show(string $code): JsonResponse
    {
        $country = Country::where('code', $code)->first();

        if (!$country) {
            return response()->json([
                'message' => 'Country not found'
            ], 404);
        }

        return response()->json($country);
    }

    /**
     * Get all countries
     */
    public function index(): JsonResponse
    {
        $countries = Country::all();
        return response()->json($countries);
    }
}
