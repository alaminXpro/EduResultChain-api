<?php

namespace Vanguard\Http\Controllers\Api\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Requests\Auth\ApiVerifyEmailRequest;

class VerificationController extends ApiController
{
    public function __construct()
    {
        $this->middleware('throttle:6,1')->only('resend');
    }

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @throws AuthorizationException
     */
    public function verify(ApiVerifyEmailRequest $request): JsonResponse
    {
        if (! setting('reg_email_confirmation')) {
            return $this->errorNotFound();
        }

        $this->verifySignature($request);

        if ($request->user()->hasVerifiedEmail()) {
            return $this->emailAlreadyVerifiedResponse();
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return $this->respondWithSuccess();
    }

    /**
     * Verify request signature.
     *
     * @throws AuthorizationException
     */
    private function verifySignature(ApiVerifyEmailRequest $baseRequest): void
    {
        $request = Request::create(
            route('verification.verify', $baseRequest->only('id', 'hash')),
            Request::METHOD_GET,
            $baseRequest->only('expires', 'signature')
        );

        if (! $request->hasValidSignature()) {
            throw new InvalidSignatureException;
        }

        if (! hash_equals((string) $baseRequest->id, (string) auth()->user()->getKey())) {
            throw new AuthorizationException;
        }

        if (! hash_equals((string) $baseRequest->hash, sha1(auth()->user()->getEmailForVerification()))) {
            throw new AuthorizationException;
        }
    }

    protected function emailAlreadyVerifiedResponse(): JsonResponse
    {
        return $this->setStatusCode(Response::HTTP_BAD_REQUEST)
            ->respondWithError(__('E-Mail already verified.'));
    }

    /**
     * Resend the email verification notification.
     */
    public function resend(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->emailAlreadyVerifiedResponse();
        }

        $request->user()->sendEmailVerificationNotification();

        return $this->respondWithSuccess(Response::HTTP_ACCEPTED);
    }
}
