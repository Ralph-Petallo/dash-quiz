<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\Dasher;
use Illuminate\Support\Facades\Hash;


class GoogleController extends Controller
{
    function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        $googleUser = Socialite::driver('google')->user();

        // Find existing Dasher by Google email
        $user = Dasher::where('email', $googleUser->email ?? '')->first();

        // Create account if it doesn't exist
        if (!$user) {

            $profilePhoto = 'default.png';

            $avatarUrl = $googleUser->avatar ?? $googleUser->getAvatar() ?? null;

            if ($avatarUrl) {
                $image = Http::get($avatarUrl);

                if ($image->successful() && str_starts_with($image->header('Content-Type') ?? '', 'image/')) {
                    $contentType = $image->header('Content-Type');
                    $extension = explode('/', $contentType)[1] ?? 'jpg';
                    $filename = 'google_' . uniqid() . '.' . $extension;

                    Storage::disk('public')->put(
                        'images/profiles/' . $filename,
                        $image->body()
                    );

                    $profilePhoto = $filename;
                }
            }
            $user = Dasher::create([
                'first_name' => $googleUser->user['given_name'] ?? '',
                'last_name' => $googleUser->user['family_name'] ?? '',
                'email' => $googleUser->email ?? '',
                'password' => Hash::make(Str::random(64)), // Generate a random password
                'profile_photo' => $profilePhoto,
                'role' => 'dasher',
            ]);
        }

        // Login using the same guard
        Auth::guard('dasher')->login($user);

        // Regenerate session just like normal login
        $request->session()->regenerate();

        // Update user activity
        $user->update([
            'active_status' => 1,
            'last_activity' => now(),
        ]);

        // Clear dashboard/leaderboard cache
        Cache::forget('dashquiz:dashboard:stats');
        Cache::forget('dashquiz:leaderboard');

        // Mark user as online
        Cache::put(
            "dashquiz:user:{$user->id}:status",
            'online',
            now()->addMinutes(5)
        );

        return redirect('/user');
    }
}
