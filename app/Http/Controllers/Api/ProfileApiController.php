<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Models\Dasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileApiController extends Controller
{

    // Update user profile information
    public function uploadPhoto(Request $request)
    {
        // 🔍 DEBUG FIRST (only temporarily)
        if (!$request->hasFile('photo')) {
            return response()->json([
                'debug' => 'NO FILE RECEIVED',
                'all' => $request->all(),
            ], 422);
        }

        //  Validate AFTER confirming file exists
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $file = $request->file('photo');

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        // Store file properly
        $file->storeAs('public/images/profiles', $filename);

        // 🗑 Delete old photo if exists
        if ($user->profile_photo) {
            Storage::delete('public/images/profiles/' . $user->profile_photo);
        }

        //  Update user
        $user->update([
            'profile_photo' => $filename,
            'active_status' => 1,
            'last_activity' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile photo updated successfully',
            'new_photo' => $filename,
            'new_photo_url' => asset('storage/images/profiles/' . $filename)
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:dasher,email,' . $user->id,
            'password' => 'nullable|min:6|confirmed',
        ], [
            'password.min' => 'Password must be at least 6 characters.',
        ]);

        // Only hash & update password if it was provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => [
                'first_name' => $user->fresh()->first_name,
                'last_name' => $user->fresh()->last_name,
                'email' => $user->fresh()->email,
            ],
        ]);
    }

    // Allow users to delete their own account
    public function selfDeleteAccount(Request $request)
    {
        // Get logged-in user's ID
        $user_id = $request->user()->id;

        // Find the user record
        $user = Dasher::findOrFail($user_id);

        // Delete the user account permanently
        $user->delete();
    }
}