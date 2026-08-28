<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\User;
use App\Notifications\AccountApproved;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Get all users pending approval (account_id is null, excluding staff and admin)
     */
    public function pendingApprovals(): JsonResponse
    {
        $pendingUsers = User::whereNull('account_id')
            ->with('roles')
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['Staff', 'Admin']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($pendingUsers);
    }

    /**
     * Approve a user by assigning an account_id
     */
    public function approve(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer|exists:accounts,id'
        ]);

        // Check if this is a new approval (account_id was null)
        $wasUnapproved = $user->account_id === null;

        $user->account_id = $validated['account_id'];
        $user->save();

        // Send approval notification if this was a new approval
        if ($wasUnapproved) {
            try {
                $account = Account::with('region')->find($validated['account_id']);
                $user->notify(new AccountApproved($account));
            } catch (\Exception $e) {
                \Log::error('Failed to send account approval notification', [
                    'user_id' => $user->id,
                    'account_id' => $validated['account_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'message' => 'User approved successfully',
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Get all users
     */
    public function index(): JsonResponse
    {
        $users = User::with(['roles', 'account.region'])->orderBy('created_at', 'desc')->get();
        return response()->json($users);
    }

    /**
     * Update user role
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'required|string|in:Admin,Staff,Customer'
        ]);

        // Sync roles - this removes all existing roles and assigns the new one
        $user->syncRoles([$validated['role']]);

        return response()->json([
            'message' => 'User role updated successfully',
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Delete a user
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Update user account
     */
    public function updateAccount(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer|exists:accounts,id'
        ]);

        // Check if this is a new approval (account_id was null)
        $wasUnapproved = $user->account_id === null;

        $user->account_id = $validated['account_id'];
        $user->save();

        // Send approval notification if this was a new approval
        if ($wasUnapproved) {
            try {
                $account = Account::with('region')->find($validated['account_id']);
                $user->notify(new AccountApproved($account));
            } catch (\Exception $e) {
                \Log::error('Failed to send account approval notification', [
                    'user_id' => $user->id,
                    'account_id' => $validated['account_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'message' => 'User account updated successfully',
            'user' => $user->load(['roles', 'account.region'])
        ]);
    }
}
