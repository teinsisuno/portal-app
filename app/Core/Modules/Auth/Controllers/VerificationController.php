<?php

namespace App\Core\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerificationController extends Controller
{
    /**
     * Tampilkan halaman "cek email kamu" (notice).
     */
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('dashboard')
            : view('auth.verify-email');
    }

    /**
     * Kirim ulang link verifikasi (rate limited).
     */
    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', 'verification-link-sent');
    }

    /**
     * Verifikasi email via link yang dikirim (URL sudah signed).
     * Middleware 'signed' yang memvalidasi expires & signature.
     */
    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect(route('dashboard'))->with('status', 'email-verified');
    }
}
