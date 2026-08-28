<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\OrderPlaced;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Create a new order
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'account_id' => 'nullable|exists:accounts,id',
            'address_id' => 'nullable|exists:addresses,id',
            'po' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|string',
            'items.*.currency' => 'required|string|size:3',
            'items.*.product_code' => 'required|string',
            'items.*.product_name' => 'required|string',
            'total' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Calculate insurance if account has insurance percentage
            $insurance = 0;
            if ($request->account_id) {
                $account = \App\Models\Account::find($request->account_id);
                if ($account && $account->insurance > 0) {
                    // Calculate insurance as percentage of total
                    $insurance = ($request->total * $account->insurance) / 100;
                }
            }

            // Calculate shipping charge
            $shipping = 0;
            $account = $request->account_id ? \App\Models\Account::find($request->account_id) : null;

            // Only charge shipping if account doesn't have free shipping
            if (!$account || !$account->freeShipping) {
                // Get shipping address and country
                if ($request->address_id) {
                    $address = \App\Models\Address::find($request->address_id);
                    if ($address && $address->country) {
                        // Find country by code or name
                        $country = \App\Models\Country::where('code', $address->country)
                            ->orWhere('name', $address->country)
                            ->first();

                        if ($country) {
                            // Get shipping cost based on currency
                            $currencyLower = strtolower($request->currency);
                            $shippingColumn = 'shipping_' . $currencyLower;
                            if (property_exists($country, $shippingColumn) || isset($country->$shippingColumn)) {
                                $shipping = $country->$shippingColumn ?? 0;
                            }
                        }
                    }
                }
            }

            // Calculate freight charge
            $freight = 0;

            // Check if any product in the order has freight = true
            $hasFreightProduct = false;
            foreach ($request->items as $item) {
                $product = \App\Models\Product::find($item['product_id']);
                if ($product && $product->freight) {
                    $hasFreightProduct = true;
                    break;
                }
            }

            // If there's a freight product, apply the freight charge once
            if ($hasFreightProduct && $request->address_id) {
                $address = \App\Models\Address::find($request->address_id);
                if ($address && $address->country) {
                    // Find country by code or name
                    $country = \App\Models\Country::where('code', $address->country)
                        ->orWhere('name', $address->country)
                        ->first();

                    if ($country) {
                        // Get freight cost based on currency
                        $currencyLower = strtolower($request->currency);
                        $freightColumn = 'freight_' . $currencyLower;
                        if (property_exists($country, $freightColumn) || isset($country->$freightColumn)) {
                            $freight = $country->$freightColumn ?? 0;
                        }
                    }
                }
            }

            // Create the order
            $order = Order::create([
                'user_id' => $request->user_id,
                'account_id' => $request->account_id,
                'address_id' => $request->address_id,
                'reference' => '',
                'po' => $request->po,
                'notes' => $request->notes,
                'total' => $request->total,
                'insurance' => $insurance,
                'shipping' => $shipping,
                'freight' => $freight,
                'currency' => $request->currency,
                'status' => 'pending'
            ]);

            // Create order items
            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'currency' => $item['currency'],
                    'product_code' => $item['product_code'],
                    'product_name' => $item['product_name']
                ]);
            }

            DB::commit();

            // Load relationships for response
            $order->load(['items', 'user', 'account.country', 'account.term', 'address']);

            // Determine locale based on account country code
            $locale = 'en'; // Default to English
            if ($order->account?->country?->code === 'DE') {
                $locale = 'de';
            }

            // Send order confirmation email
            try {
                $order->user->notify(new OrderPlaced($order, $locale));
            } catch (\Exception $e) {
                // Log email error but don't fail the order creation
                \Log::error('Failed to send order confirmation email', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'message' => 'Order created successfully',
                'id' => $order->id,
                'order' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order by ID
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $order = Order::with(['items.product.description', 'user', 'account.region', 'address'])
            ->findOrFail($id);

        // Check if user has permission to view this order
        // Admin/Staff can view all orders
        if (!$user->hasRole(['Admin', 'Staff'])) {
            // Regular users can only view orders from their account or their own orders
            if ($user->account_id) {
                // User belongs to an account - check if order belongs to same account
                if ($order->account_id !== $user->account_id) {
                    return response()->json([
                        'message' => 'You do not have permission to view this order'
                    ], 403);
                }
            } else {
                // User doesn't belong to an account - check if it's their personal order
                if ($order->user_id !== $user->id) {
                    return response()->json([
                        'message' => 'You do not have permission to view this order'
                    ], 403);
                }
            }
        }

        return response()->json($order);
    }

    /**
     * Get user's orders
     */
    public function userOrders(Request $request): JsonResponse
    {
        $user = $request->user();

        // If user has an account, get all orders for that account
        // Otherwise, just get their personal orders
        $query = Order::with(['items.product', 'account.region', 'user', 'address']);

        if ($user->account_id) {
            $query->where('account_id', $user->account_id);
        } else {
            $query->where('user_id', $user->id);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($orders);
    }

    /**
     * Get all orders (admin/staff only)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['items', 'user', 'account.region', 'address']);

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Search by order ID, reference, or account name
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhereHas('account', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('email', 'like', "%{$search}%");
                  });
            });
        }

        // Sort by created date (newest first by default)
        $query->orderBy('created_at', 'desc');

        $orders = $query->paginate($request->get('per_page', 15));

        return response()->json($orders);
    }

    /**
     * Update order status (admin/staff only)
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::findOrFail($id);
        $order->status = $request->status;
        $order->save();

        $order->load(['items', 'user', 'account', 'address']);

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order
        ]);
    }
}
