<?php

namespace Vanguard\Http\Controllers\Web\Auth;

use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Vanguard\Http\Controllers\Controller;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * {@inheritDoc}
     */
    protected function sendResetLinkResponse(Request $request, $response): RedirectResponse
    {
        return back()->with('success', trans($response));
    }

    /**
     * {@inheritDoc}
     */
    protected function sendResetLinkFailedResponse(Request $request, $response): RedirectResponse
    {
        $messages = ['email' => trans($response)];
        $httpResponse = back()->withInput($request->only('email'));

        return $response === PasswordBroker::INVALID_USER
            ? $httpResponse->withSuccess($messages)
            : $httpResponse->withErrors($messages);
    }
}
