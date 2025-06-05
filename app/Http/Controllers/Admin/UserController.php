<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class UserController extends Controller
{
    // ... existing code ...

    public function suspend($id)
    {
        $user = User::findOrFail($id);
        $user->suspended_at = now();
        $user->save();
        return redirect()->back()->with('success', 'User suspended successfully.');
    }

    public function unsuspend($id)
    {
        $user = User::findOrFail($id);
        $user->suspended_at = null;
        $user->save();
        return redirect()->back()->with('success', 'User unsuspended successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:root,reseller,user',
        ]);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);
        $user->assignRole($validated['role']);
        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    // ... existing code ...
} 