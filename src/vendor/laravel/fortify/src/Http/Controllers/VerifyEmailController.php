<?php

namespace Laravel\Fortify\Http\Controllers;

use Illuminate\Auth\Events\Verified;
use Illuminate\Routing\Controller;
use Laravel\Fortify\Contracts\VerifyEmailResponse;
use Laravel\Fortify\Http\Requests\VerifyEmailRequest;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Laravel\Fortify\Http\Requests\VerifyEmailRequest  $request
     * @return \Laravel\Fortify\Contracts\VerifyEmailResponse
     */
    public function __invoke(VerifyEmailRequest $request)
    {
        $user = \App\Models\User::find($request->route('id'));

        if (!$user) {
            return redirect('/login')->withErrors(['email' => '認証されていないユーザーです。']);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect('/attendance');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            Auth::login($user);
            return redirect('/attendance');
        }

        return app(VerifyEmailResponse::class);
    }
}
