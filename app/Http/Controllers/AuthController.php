<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

            // Fire the Registered event to send verification email
            event(new Registered($user));

            // Log the user in (they can browse but not book until verified)
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
                'message' => 'Account created! Check your email to verify your account.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => $e->validator->errors()->first(),
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
            return response()->json([
                'error' => $e->validator->errors()->first(),
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
            return redirect()->intended(route('home'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->validator->errors())->withInput();
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

            event(new Registered($user));
            Auth::login($user);
            $request->session()->regenerate();
            return redirect()->route('booking')->with('message', 'Account created! Please verify your email.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->validator->errors())->withInput();
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
        $user = Auth::user();

        if ($user) {
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'email_verified' => !is_null($user->email_verified_at),
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

        // Send verification notification
        $user->notify(new VerifyEmail);

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

        return redirect('/booking?verified=success')->with('message', 'Email verified successfully! You can now book tickets.');
    }
}
