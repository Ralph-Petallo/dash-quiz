<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Dasher;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/*
 *
 *
 * Currently now working on brave browser and safari, but working on chrome and firefox.
 *
 */
class EmailVerificationController extends Controller
{
    /**
     * Opened from the email link.
     *
     * Renders a server-side result page, so the outcome shows even when the
     * Vue bundle cannot load on the device.
     */
    public function verify(Request $request, int $id, string $hash)
    {
        if (!$request->hasValidSignature()) {
            logger()->warning('Invalid verification signature', [
                'host' => $request->getHost(),
                'scheme' => $request->getScheme(),
                'dasher_id' => $id,
            ]);

            return $this->resultPage('invalid');
        }

        $dasher = Dasher::find($id);

        if (!$dasher || !hash_equals(sha1($dasher->getEmailForVerification()), $hash)) {
            return $this->resultPage('invalid');
        }

        if (!$dasher->hasVerifiedEmail()) {
            $dasher->markEmailAsVerified();
            event(new Verified($dasher));
        }

        // Already signed in on this browser: go straight to the dashboard.
        if (auth('dasher')->check() && (int) auth('dasher')->id() === (int) $dasher->id) {
            return redirect('/user');
        }

        return $this->resultPage('success');
    }

    /**
     * Public resend. Returns the same message whether or not the account exists,
     * so the endpoint cannot be used to check which emails are registered.
     */
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

    /**
     * Renders the Blade result page. Uses the same status values in both branches.
     */
    private function resultPage(string $status): Response
    {
        return response()
            ->view('email-verified', [
                'status' => $status,
                'loginUrl' => url('/'),
                // Points to the SPA's resend screen when the bundle loads,
                // and falls back to the login page otherwise.
                'resendUrl' => url('/register?step=resend'),
            ])
            ->header('Cache-Control', 'no-store, private');
    }
}