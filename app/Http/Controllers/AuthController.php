<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // Show login form
    public function showLoginForm()
    {
        return view('login'); // resources/views/login.blade.php
    }

    // Handle login
    public function login(Request $request)
    {
        $request->validate([
            'username'    => 'required|string|regex:/^[a-zA-Z0-9_-]+$/',
            'user_password' => 'required|string',
        ], [
            'username.required' => 'Username is required',
            'username.regex' => 'Username can only contain letters, numbers, underscores, and hyphens',
            'user_password.required' => 'Password is required',
        ]);

       // Check if input matches either username OR user_id
        $user = User::where('username', $request->username)
                    ->orWhere('user_id', $request->username)
                    ->first();

        if ($user && Hash::check($request->user_password, $user->user_password)) {
            Auth::login($user);

            // Redirect based on role
            if ($user->user_role === 'Admin') {
                return redirect()->route('admin.dashboard')->with('success', 'Welcome Admin!');
            } 
            
            elseif ($user->user_role === 'Department Head') {
                return redirect()->route('deptHead.dashboard')->with('success', 'Welcome Department Head!');
            }

            elseif ($user->user_role === 'Checker') {
                return redirect()->route('checker.dashboard')->with('success', 'Welcome Checker!');
            }
            
        }

        return back()->withErrors(['login_error' => 'Invalid credentials']);
    }

    // Handle logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logged out successfully');
    }
}
