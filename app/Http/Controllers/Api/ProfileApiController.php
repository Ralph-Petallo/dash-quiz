<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProfileApiController extends Controller
{
    // Update user profile information
    public function uploadPhoto(Request $request)
    {
        if (! $request->hasFile('photo')) {
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

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $file = $request->file('photo');

        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();

        // Store file properly
        $file->storeAs('public/images/profiles', $filename);

        // 🗑 Delete old photo if exists
        if ($user->profile_photo) {
            Storage::delete('public/images/profiles/'.$user->profile_photo);
        }

        //  Update user
        $user->update([
            'profile_photo' => $filename,
            'active_status' => 1,
            'last_activity' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile photo updated successfully',
            'new_photo' => $filename,
            'new_photo_url' => asset('storage/images/profiles/'.$filename),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:dasher,email,'.$user->id,
            'password' => 'nullable|min:6|confirmed',
        ], [
            'password.min' => 'Password must be at least 6 characters.',
        ]);

        // Only hash & update password if it was provided
        if (! empty($validated['password'])) {
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
        $user = $request->user();
        $profilePhoto = $user->profile_photo;

        if (
            $profilePhoto &&
            basename($profilePhoto) === $profilePhoto &&
            ! filter_var($profilePhoto, FILTER_VALIDATE_URL)
        ) {
            $photoPath = 'images/profiles/'.$profilePhoto;
            $publicDisk = Storage::disk('public');

            if ($publicDisk->exists($photoPath) && ! $publicDisk->delete($photoPath)) {
                throw new RuntimeException('Unable to delete the profile photo.');
            }
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Account deleted successfully.',
        ]);
    }
}
