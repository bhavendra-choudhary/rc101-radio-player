<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('admin.index');
        }

        return view('admin.auth.login');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $input = $request->all();
        if ($request->has('name') && !$request->has('login')) {
            $input['login'] = $request->input('name');
        } elseif ($request->has('email') && !$request->has('login')) {
            $input['login'] = $request->input('email');
        }

        $validator = Validator::make($input, [
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        $login = $input['login'];
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        $credentials = [
            $fieldType => $login,
            'password' => $request->input('password')
        ];

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            return redirect()->route('admin.index')
                ->with('success', 'Welcome back! You have been successfully logged in.');
        }

        return back()
            ->withErrors([
                'login' => 'The provided credentials do not match our records.',
            ])
            ->withInput($request->except('password'));
    }


    /**
     * Show the registration form.
     */
    public function showRegistrationForm()
    {
        if (Auth::check()) {
            return redirect()->route('admin.index');
        }

        return view('admin.auth.register');
    }

    /**
     * Handle registration request.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'admin_access_code' => 'required|string|min:4',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except('password', 'password_confirmation', 'admin_access_code'));
        }

        // Verify admin access code first
        $adminAccessCode = env('ADMIN_ACCESS_CODE');
        if (!$adminAccessCode || $request->admin_access_code !== $adminAccessCode) {
            return back()
                ->withErrors([
                    'admin_access_code' => 'Invalid admin access code.',
                ])
                ->withInput($request->except('password', 'password_confirmation', 'admin_access_code'));
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect()->route('admin.index')
            ->with('success', 'Registration successful! Welcome to the admin panel.');
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('success', 'You have been successfully logged out.');
    }
}
