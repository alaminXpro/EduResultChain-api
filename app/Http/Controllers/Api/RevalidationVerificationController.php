<?php

namespace Vanguard\Http\Controllers\Api;

use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Services\Interfaces\PhoneVerificationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RevalidationVerificationController extends ApiController
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
     * Generate a verification code for revalidation request.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateCode(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'roll_number' => 'required|string|exists:form_fillups,roll_number',
        ]);

        if ($validator->fails()) {
            return $this->errorBadRequest($validator->errors()->first());
        }

        try {
            // Get the student record for this roll number
            $formFillup = DB::table('form_fillups')
                ->where('roll_number', $request->roll_number)
                ->first();
                
            if (!$formFillup) {
                return $this->errorNotFound('Form fillup not found for this roll number.');
            }
            
            // Get the student record
            $student = DB::table('students')
                ->where('registration_number', $formFillup->registration_number)
                ->first();
                
            if (!$student) {
                return $this->errorNotFound('Student not found for this roll number.');
            }
            
            // Check if the student is associated with the authenticated user
            $user = Auth::user();
            
            // For User role, check if the student is associated with the user
            if ($user->hasRole('User')) {
                $studentUser = DB::table('students')
                    ->where('registration_number', $formFillup->registration_number)
                    ->where('email', $user->email)
                    ->first();
                    
                if (!$studentUser) {
                    return $this->errorForbidden('You can only request verification for your own roll number.');
                }
            }
            
            // Generate verification code using the registration number
            $this->phoneVerificationService->generateVerificationCode($formFillup->registration_number);
            
            return $this->respondWithArray([
                'success' => true,
                'message' => 'Verification code has been sent to your registered email address.',
                'status' => $this->phoneVerificationService->getVerificationStatus($formFillup->registration_number),
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Verify a phone number for revalidation request.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyCode(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'roll_number' => 'required|string|exists:form_fillups,roll_number',
            'verification_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return $this->errorBadRequest($validator->errors()->first());
        }

        try {
            // Get the student record for this roll number
            $formFillup = DB::table('form_fillups')
                ->where('roll_number', $request->roll_number)
                ->first();
                
            if (!$formFillup) {
                return $this->errorNotFound('Form fillup not found for this roll number.');
            }
            
            // Check if the student is associated with the authenticated user
            $user = Auth::user();
            
            // For User role, check if the student is associated with the user
            if ($user->hasRole('User')) {
                $student = DB::table('students')
                    ->where('registration_number', $formFillup->registration_number)
                    ->where('email', $user->email)
                    ->first();
                    
                if (!$student) {
                    return $this->errorForbidden('You can only verify your own roll number.');
                }
            }
            
            // Verify the code using the registration number
            $verified = $this->phoneVerificationService->verifyPhone(
                $formFillup->registration_number,
                $request->verification_code
            );
            
            if ($verified) {
                // Store the verification in the session for 10 minutes
                session(['revalidation_verified_' . $request->roll_number => true]);
                session(['revalidation_verified_at_' . $request->roll_number => now()->timestamp]);
                
                // Force session to be saved immediately
                session()->save();
                
                // Also store in database as a backup
                try {
                    DB::table('phone_verifications')
                        ->where('registration_number', $formFillup->registration_number)
                        ->update([
                            'revalidation_verified' => true,
                            'revalidation_verified_at' => now(),
                            'revalidation_expires_at' => now()->addMinutes(10)
                        ]);
                        
                    \Log::info('Updated phone verification in database', [
                        'registration_number' => $formFillup->registration_number
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to update phone verification in database', [
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Debug session values after setting
                \Log::info('Revalidation verification set:', [
                    'roll_number' => $request->roll_number,
                    'session_key' => 'revalidation_verified_' . $request->roll_number,
                    'is_verified' => session('revalidation_verified_' . $request->roll_number),
                    'verified_at' => session('revalidation_verified_at_' . $request->roll_number),
                    'all_session' => session()->all()
                ]);
                
                return $this->respondWithArray([
                    'success' => true,
                    'message' => 'Verification successful. You can now submit your revalidation request.',
                    'status' => $this->phoneVerificationService->getVerificationStatus($formFillup->registration_number),
                    'expires_at' => now()->addMinutes(10)->timestamp,
                ]);
            }
            
            return $this->errorForbidden('Invalid or expired verification code.');
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Check if a roll number is verified for revalidation.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkVerification(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'roll_number' => 'required|string|exists:form_fillups,roll_number',
        ]);

        if ($validator->fails()) {
            return $this->errorBadRequest($validator->errors()->first());
        }

        try {
            // Get the student record for this roll number
            $formFillup = DB::table('form_fillups')
                ->where('roll_number', $request->roll_number)
                ->first();
                
            if (!$formFillup) {
                return $this->errorNotFound('Form fillup not found for this roll number.');
            }
            
            // Check if the student is associated with the authenticated user
            $user = Auth::user();
            
            // For User role, check if the student is associated with the user
            if ($user->hasRole('User')) {
                $student = DB::table('students')
                    ->where('registration_number', $formFillup->registration_number)
                    ->where('email', $user->email)
                    ->first();
                    
                if (!$student) {
                    return $this->errorForbidden('You can only check verification for your own roll number.');
                }
            }
            
            // Check if the roll number is verified in the session
            $isVerified = session('revalidation_verified_' . $request->roll_number, false);
            $verifiedAt = session('revalidation_verified_at_' . $request->roll_number, 0);
            
            // Check if the verification has expired (10 minutes)
            $isExpired = now()->timestamp - $verifiedAt > 600;
            
            if ($isVerified && !$isExpired) {
                return $this->respondWithArray([
                    'success' => true,
                    'verified' => true,
                    'expires_at' => $verifiedAt + 600,
                    'remaining_seconds' => max(0, ($verifiedAt + 600) - now()->timestamp),
                ]);
            }
            
            // If expired, clear the session
            if ($isExpired) {
                session()->forget('revalidation_verified_' . $request->roll_number);
                session()->forget('revalidation_verified_at_' . $request->roll_number);
            }
            
            return $this->respondWithArray([
                'success' => true,
                'verified' => false,
                'message' => 'Verification required before submitting a revalidation request.',
                'status' => $this->phoneVerificationService->getVerificationStatus($formFillup->registration_number),
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }
} 