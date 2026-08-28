<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewUserRegistered;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle login request
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::with('account.region', 'account.country', 'account.currency', 'account.discounts', 'account.categoryDiscounts')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke all existing tokens for this user
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_id' => $user->account_id,
                'account' => $user->account,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke current user's token
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get current authenticated user
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user()->load('account.region', 'account.country', 'account.currency', 'account.discounts', 'account.categoryDiscounts');
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'account_id' => $user->account_id,
            'account' => $user->account,
            'roles' => $user->getRoleNames(),
        ]);
    }

    /**
     * Handle registration request
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'turnstile_token' => 'required|string',
        ]);

        // Verify Turnstile token
        $turnstileSecret = config('services.turnstile.secret_key');
        if (!$turnstileSecret) {
            return response()->json([
                'message' => 'Turnstile verification is not configured.',
            ], 500);
        }

        $verifyResponse = \Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => $turnstileSecret,
            'response' => $validated['turnstile_token'],
            'remoteip' => $request->ip(),
        ]);

        $result = $verifyResponse->json();

        if (!$result || !($result['success'] ?? false)) {
            return response()->json([
                'message' => 'Verification failed. Please try again.',
                'errors' => [
                    'turnstile_token' => ['Human verification failed.']
                ]
            ], 422);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'company' => $validated['company'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'account_id' => null, // Will be assigned by admin
        ]);

        // Create token for the new user
        $token = $user->createToken('customer-token')->plainTextToken;

        // Assign Customer role by default
        $user->assignRole('Customer');

        // Send notification to admin email
        try {
            $staffEmail = config('mail.staff_email');
            if ($staffEmail) {
                Notification::route('mail', $staffEmail)
                    ->notify(new NewUserRegistered($user));
            } else {
                \Log::warning('MAIL_STAFF email not configured - new user notification not sent', [
                    'user_id' => $user->id,
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail registration
            \Log::error('Failed to send new user notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_id' => $user->account_id,
                'roles' => $user->getRoleNames(),
            ],
            'message' => 'Registration successful. Your account is pending approval.',
        ], 201);
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Don't reveal if email exists or not for security
            return response()->json([
                'message' => 'If an account exists with that email, a password reset link has been sent.',
            ]);
        }

        // Generate reset token
        $token = Str::random(60);

        // Store token in password_resets table
        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Send email notification
        try {
            $user->notify(new ResetPasswordNotification($token));
        } catch (\Exception $e) {
            \Log::error('Failed to send password reset email', [
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'message' => 'If an account exists with that email, a password reset link has been sent.',
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Find the token record
        $resetRecord = \DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord) {
            return response()->json([
                'message' => 'Invalid or expired reset token.',
            ], 400);
        }

        // Check if token matches
        if (!Hash::check($request->token, $resetRecord->token)) {
            return response()->json([
                'message' => 'Invalid or expired reset token.',
            ], 400);
        }

        // Check if token is expired (60 minutes)
        $createdAt = \Carbon\Carbon::parse($resetRecord->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            // Delete expired token
            \DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'message' => 'Reset token has expired. Please request a new one.',
            ], 400);
        }

        // Find user and update password
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the token
        \DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Password has been reset successfully. You can now log in with your new password.',
        ]);
    }
}
