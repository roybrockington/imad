<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\JsonResponse;

class RegionController extends Controller
{
    /**
     * Get all regions
     */
    public function index(): JsonResponse
    {
        $regions = Region::orderBy('code')->get();
        return response()->json($regions);
    }
}
