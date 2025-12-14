<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register new user
     */
    public function signup(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|min:2',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'role' => 'sometimes|in:client,admin',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => strtolower(trim($validated['email'])),
                'password' => $validated['password'],
                'role' => $validated['role'] ?? 'client',
            ]);

            // Try to send verification email but don't block signup if mailer fails
            $verificationSent = true;
            try {
                event(new Registered($user));
            } catch (\Throwable $mailError) {
                $verificationSent = false;
                Log::warning('[Signup] Failed to send verification email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $mailError->getMessage(),
                ]);
            }

            // Log the user in (they can browse but not book until verified)
            Auth::login($user);

            return response()->json([
                'success' => true,
                'verification_email_sent' => $verificationSent,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'email_verified' => !is_null($user->email_verified_at),
                ],
                'message' => $verificationSent
                    ? 'Account created! Check your email to verify your account.'
                    : 'Account created! We could not send the verification email right now. Please request a new verification email after logging in.',
            ]);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $first = null;
            foreach ($errors as $field => $msgs) { $first = $msgs[0] ?? null; break; }
            return response()->json([
                'error' => $first ?? 'Invalid input',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error during signup',
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $email = strtolower(trim($validated['email']));
            $user = User::where('email', $email)->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'error' => 'Invalid credentials',
                ], 401);
            }

            // Log the user in
            Auth::login($user);

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'email_verified' => !is_null($user->email_verified_at),
                ],
            ]);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $first = null;
            foreach ($errors as $field => $msgs) { $first = $msgs[0] ?? null; break; }
            return response()->json([
                'error' => $first ?? 'Invalid input',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error during login',
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Web: Login via HTML form
     */
    public function webLogin(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $email = strtolower(trim($validated['email']));
            $user = User::where('email', $email)->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
            }

            Auth::login($user);
            $request->session()->regenerate();

            // Role-based landing: avoid sending admins back to /booking via intended()
            if (($user->role ?? 'client') === 'admin') {
                return redirect()->route('admin');
            }
            if (in_array(($user->role ?? 'client'), ['staff'])) {
                // Staff/cashier head to staff portal
                return redirect()->route('staff');
            }

            // Default: return to intended page or home
            return redirect()->intended(route('home'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Server error during login'])->withInput();
        }
    }

    /**
     * Web: Signup via HTML form
     */
    public function webSignup(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|min:2',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => strtolower(trim($validated['email'])),
                'password' => $validated['password'],
                'role' => 'client',
            ]);

            $verificationSent = true;
            try {
                event(new Registered($user));
            } catch (\Throwable $mailError) {
                $verificationSent = false;
                Log::warning('[Web Signup] Failed to send verification email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $mailError->getMessage(),
                ]);
            }

            Auth::login($user);
            $request->session()->regenerate();
            $message = $verificationSent
                ? 'Account created! Please verify your email.'
                : 'Account created! Verification email could not be sent right now. After logging in, click "Resend verification" to get the link.';
            return redirect()->route('booking')->with('message', $message);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Server error during signup'])->withInput();
        }
    }

    /**
     * Web: Logout
     */
    public function webLogout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    /**
     * Get current session user
     */
    public function session(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user) {
            return response()->json([
                'user' => [
                    'id' => $user->id ?? null,
                    'name' => $user->name ?? '',
                    'email' => $user->email ?? '',
                    'role' => $user->role ?? 'client',
                    'email_verified' => !is_null($user->email_verified_at ?? null),
                ],
            ]);
        }

        return response()->json([
            'user' => null,
        ]);
    }

    /**
     * Resend email verification notification
     */
    public function resendVerification(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }

        if (!is_null($user->email_verified_at)) {
            return response()->json([
                'error' => 'Email already verified',
            ], 400);
        }

        try {
            // Send verification notification
            $user->notify(new VerifyEmail);
        } catch (\Throwable $e) {
            Log::warning('[Resend Verification] Failed to send email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Could not send verification email right now (mailer issue). Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent!',
        ]);
    }

    /**
     * Mark email as verified
     */
    public function verifyEmail(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return redirect('/login?verified=invalid')->with('error', 'Invalid verification link');
        }

        if (!is_null($user->email_verified_at)) {
            return redirect('/booking?verified=already')->with('message', 'Email already verified!');
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->save();

        event(new \Illuminate\Auth\Events\Verified($user));

        // After verification, route admins away from booking
        if ($user->role === 'admin') {
            return redirect('/admin?verified=success')->with('message', 'Email verified successfully.');
        }
        return redirect('/booking?verified=success')->with('message', 'Email verified successfully! You can now book tickets.');
    }

    /**
     * Send password reset link (web)
     */
    public function sendResetLink(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);
            $status = Password::sendResetLink(['email' => strtolower(trim($request->input('email')))]);

            return back()->with('status', 'If your email exists, we sent a reset link.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not send reset link. Please try again.')->withInput();
        }
    }

    /**
     * Show reset password form (web)
     */
    public function showResetForm(Request $request, string $token)
    {
        // Show a simple reset form; email may be provided via query
        return view('reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    /**
     * Handle password reset submission (web)
     */
    public function doReset(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|string|min:6|confirmed',
            ]);

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->password = $password; // auto-hashed via cast
                    $user->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return redirect()->route('login')->with('status', 'Password reset successfully. Please login.');
            }

            return back()->with('error', 'Reset failed. Please request a new link.')->withInput();
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            return back()->with('error', 'Unexpected error. Please try again.')->withInput();
        }
    }
}
