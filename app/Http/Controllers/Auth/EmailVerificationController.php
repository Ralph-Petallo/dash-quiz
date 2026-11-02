<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Dasher;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    // Opened from the email link. Always redirects to the SPA so the learner sees a clear result.
    // Opened from the email link. Logged-in dashers go straight to their dashboard;
// everyone else lands on the SPA result page.
    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            return redirect('/email-verified?status=invalid');
        }

        $dasher = Dasher::find($id);

        if (!$dasher || !hash_equals(sha1($dasher->getEmailForVerification()), $hash)) {
            return redirect('/email-verified?status=invalid');
        }

        if (!$dasher->hasVerifiedEmail()) {
            $dasher->markEmailAsVerified();
            event(new Verified($dasher));
        }

        if (auth('dasher')->check() && auth('dasher')->id() === $dasher->id) {
            return redirect('/user');
        }

        return redirect('/email-verified?status=success');
    }

    // Public resend: no login needed. The same message is returned whether or not
    // the account exists, so the endpoint can't be used to check which emails are registered.
    public function resend(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $dasher = Dasher::where('email', mb_strtolower($data['email']))->first();

        if ($dasher && !$dasher->hasVerifiedEmail()) {
            $dasher->sendEmailVerificationNotification();
        }

        return response()->json([
            'message' => 'If that account exists and is not verified yet, a new link has been sent.',
        ]);
    }
}