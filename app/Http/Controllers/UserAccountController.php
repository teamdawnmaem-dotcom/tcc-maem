<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserAccountController extends Controller
{
    // Show all users (for Admin)
 
    
    
    public function index()
    {
        $users = User::all();
        return view('admin.user-account-management', compact('users'));

        
    }

    // Store new Checker/Dept Head user
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string|max:50|unique:tbl_user,user_id',
            'user_role' => 'required|string|max:50',
            'user_department' => 'required|string|max:50',
            'user_lname' => 'required|string|max:50',
            'user_fname' => 'required|string|max:50',
            'username' => 'required|string|max:50|unique:tbl_user,username',
            'user_password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'user_id' => $request->user_id,
            'user_role' => $request->user_role,
            'user_department' => $request->user_department,
            'user_lname' => $request->user_lname,
            'user_fname' => $request->user_fname,
            'username' => $request->username,
            'user_password' => Hash::make($request->user_password),
        ]);

        // Log the action
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'CREATE',
            'logs_description' => 'Created new user account: ' . $user->username,
            'logs_module' => 'User Management',
        ]);

        return redirect()->route('admin.user.account.management')->with('success', 'User created successfully!');
    }

    // Update user info
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'user_role' => 'required|string|max:50',
            'user_department' => 'required|string|max:50',
            'user_lname' => 'required|string|max:50',
            'user_fname' => 'required|string|max:50',
            'username' => 'required|string|max:50|unique:tbl_user,username,' . $id . ',user_id',
            'user_password' => 'nullable|string|min:8',
        ]);

        $user->update([ 
            'user_role' => $request->user_role,
            'user_department' => $request->user_department,
            'user_lname' => $request->user_lname,
            'user_fname' => $request->user_fname,
            'username' => $request->username,
            'user_password' => $request->filled('user_password')
                ? Hash::make($request->user_password)
                : $user->user_password,
        ]);

        // Log the action
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'UPDATE',
            'logs_description' => 'Updated user account: ' . $user->username,
            'logs_module' => 'User Management',
        ]);

        return redirect()->route('admin.user.account.management')->with('success', 'User updated successfully!');
    }

    // Account Setting
public function AdminUpdateAccountSettings(Request $request)
{
    $user = auth()->user();

    $request->validate([
        'user_fname' => 'required|string|max:50',
        'user_lname' => 'required|string|max:50',
        'username' => 'required|string|max:50|unique:tbl_user,username,' . $user->user_id . ',user_id',
        'oldPassword' => 'nullable|string',
        'newPassword' => 'nullable|string|min:8',
        'confirmPassword' => 'nullable|string|min:8',
    ]);

    // If old password field is filled, validate it
    if ($request->filled('oldPassword')) {
        if (!Hash::check($request->oldPassword, $user->user_password)) {
            return back()->withErrors(['oldPassword' => 'Old password is incorrect']);
        }

        // Check if new password and confirm password match
        if ($request->newPassword !== $request->confirmPassword) {
            return back()->withErrors(['confirmPassword' => 'New password and confirm password do not match']);
        }

        //  Update password
        $user->user_password = Hash::make($request->newPassword);
    }

    // Update other account info
    $user->update([
        'user_fname' => $request->user_fname,
        'user_lname' => $request->user_lname,
        'username' => $request->username,
        'user_password' => $user->user_password, // keep existing or updated one
    ]);

    // Log the action
    ActivityLog::create([
        'user_id' => $user->user_id,
        'logs_action' => 'UPDATE',
        'logs_description' => 'Updated account settings',
        'logs_module' => 'Account Settings',
    ]);

    return redirect()->back()->with('success', 'Account settings updated successfully!');
}


    // Delete user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $username = $user->username;
        $user->delete();

        // Log the action
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'DELETE',
            'logs_description' => 'Deleted user account: ' . $username,
            'logs_module' => 'User Management',
        ]);

        return redirect()->route('admin.user.account.management')->with('success', 'User deleted successfully!');
    }
}
