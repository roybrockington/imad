<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Get all accounts, optionally filtered by region
     */
    public function index(Request $request): JsonResponse
    {
        $query = Account::query();

        // Filter by region if provided
        if ($request->has('region_id') && $request->input('region_id') !== '') {
            $query->where('region_id', $request->input('region_id'));
        }

        $accounts = $query->orderBy('name')->get();
        return response()->json($accounts);
    }

    /**
     * Get a specific account with all related discounts and brand information
     */
    public function show(Account $account): JsonResponse
    {
        // Load the account with its discounts and the related brands
        $account->load([
            'discounts.brand',
            'categoryDiscounts.brand',
            'categoryDiscounts.category',
            'region',
            'country',
            'currency'
        ]);

        return response()->json($account);
    }
}
