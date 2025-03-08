<?php

namespace Vanguard\Http\Controllers\Api;

use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Requests\PhoneVerification\GenerateCodeRequest;
use Vanguard\Http\Requests\PhoneVerification\VerifyPhoneRequest;
use Vanguard\Services\Interfaces\PhoneVerificationServiceInterface;
use Illuminate\Http\JsonResponse;

class PhoneVerificationController extends ApiController
{
    /**
     * @var PhoneVerificationServiceInterface
     */
    protected $phoneVerificationService;

    /**
     * Create a new controller instance.
     *
     * @param PhoneVerificationServiceInterface $phoneVerificationService
     * @return void
     */
    public function __construct(PhoneVerificationServiceInterface $phoneVerificationService)
    {
        $this->phoneVerificationService = $phoneVerificationService;
    }

    /**
     * Generate a verification code for a student.
     *
     * @param GenerateCodeRequest $request
     * @return JsonResponse
     */
    public function generateCode(GenerateCodeRequest $request): JsonResponse
    {
        try {
            $this->phoneVerificationService->generateVerificationCode($request->registration_number);
            
            return $this->respondWithArray([
                'success' => true,
                'message' => 'Verification code has been sent to your registered email address.',
                'status' => $this->phoneVerificationService->getVerificationStatus($request->registration_number),
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Verify a phone number using the provided verification code.
     *
     * @param VerifyPhoneRequest $request
     * @return JsonResponse
     */
    public function verifyPhone(VerifyPhoneRequest $request): JsonResponse
    {
        $verified = $this->phoneVerificationService->verifyPhone(
            $request->registration_number,
            $request->verification_code
        );

        if ($verified) {
            return $this->respondWithArray([
                'success' => true,
                'message' => 'Email verification has been successfully completed.',
                'status' => $this->phoneVerificationService->getVerificationStatus($request->registration_number),
            ]);
        }

        return $this->errorForbidden('Invalid or expired verification code.');
    }

    /**
     * Get the verification status for a student.
     *
     * @param string $registrationNumber
     * @return JsonResponse
     */
    public function getStatus(string $registrationNumber): JsonResponse
    {
        return $this->respondWithArray([
            'success' => true,
            'status' => $this->phoneVerificationService->getVerificationStatus($registrationNumber),
        ]);
    }
} 