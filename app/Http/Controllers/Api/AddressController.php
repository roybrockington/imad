<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    /**
     * Get addresses for the authenticated user's account
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->account_id) {
            return response()->json([
                'message' => 'User does not have an associated account',
                'addresses' => []
            ], 200);
        }

        $addresses = Address::where('account_id', $user->account_id)
            ->orderByDesc('default')
            ->orderBy('name1')
            ->get();

        return response()->json($addresses);
    }
}
