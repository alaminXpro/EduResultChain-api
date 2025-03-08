<?php

namespace Vanguard\Services;

use Vanguard\Services\Interfaces\PhoneVerificationServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class MockPhoneVerificationService implements PhoneVerificationServiceInterface
{
    /**
     * Generate and send a verification code to a student.
     *
     * @param string $registrationNumber
     * @return array
     */
    public function generateVerificationCode(string $registrationNumber)
    {
        try {
            // Find the student
            $student = DB::table('students')
                ->where('registration_number', $registrationNumber)
                ->first();
            
            if (!$student) {
                throw new \Exception('Student not found with this registration number.');
            }
            
            // In mock mode, always use "123456" as the verification code
            $code = '123456';
            
            // Store the verification code in the database
            $this->storeVerificationCode($registrationNumber, $code);
            
            // Send verification code via email
            $this->sendVerificationEmail($student, $code);
            
            // Log the code for easier testing
            Log::info('Verification code for ' . $registrationNumber . ': ' . $code);
            
            // Create a PhoneVerification-like object to return
            $phoneVerification = new \stdClass();
            $phoneVerification->registration_number = $registrationNumber;
            $phoneVerification->phone_number = $student->phone_number;
            $phoneVerification->verification_code = $code;
            $phoneVerification->verified = false;
            $phoneVerification->verified_at = null;
            $phoneVerification->code_expires_at = now()->addMinutes(30);
            
            return $phoneVerification;
        } catch (Exception $e) {
            Log::error('Mock Phone Verification Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify a phone number using the provided verification code.
     *
     * @param string $registrationNumber
     * @param string $verificationCode
     * @return bool
     */
    public function verifyPhone(string $registrationNumber, string $verificationCode)
    {
        try {
            // In mock mode, we'll check for "123456" to simulate the real flow
            if ($verificationCode !== '123456') {
                return false;
            }
            
            // Mark the phone as verified
            $this->markPhoneAsVerified($registrationNumber);
            
            Log::info('Mock Phone Verification: Verified phone for ' . $registrationNumber);
            
            return true;
        } catch (Exception $e) {
            Log::error('Mock Phone Verification Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a phone number is verified.
     *
     * @param string $registrationNumber
     * @return bool
     */
    public function isPhoneVerified(string $registrationNumber)
    {
        try {
            $verification = DB::table('phone_verifications')
                ->where('registration_number', $registrationNumber)
                ->first();
            
            return $verification && $verification->verified;
        } catch (Exception $e) {
            Log::error('Mock Phone Verification Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get verification status for a registration number.
     *
     * @param string $registrationNumber
     * @return array
     */
    public function getVerificationStatus(string $registrationNumber)
    {
        try {
            $verification = DB::table('phone_verifications')
                ->where('registration_number', $registrationNumber)
                ->first();
            
            if (!$verification) {
                return [
                    'verified' => false,
                    'phone_number' => null,
                    'last_verification_attempt' => null,
                ];
            }
            
            return [
                'verified' => (bool) $verification->verified,
                'phone_number' => $this->maskPhoneNumber($verification->phone_number ?? ''),
                'last_verification_attempt' => $verification->updated_at,
                'verified_at' => $verification->verified_at,
            ];
        } catch (Exception $e) {
            Log::error('Mock Phone Verification Error: ' . $e->getMessage());
            
            return [
                'verified' => false,
                'phone_number' => null,
                'last_verification_attempt' => null,
            ];
        }
    }

    /**
     * Mask a phone number for privacy.
     *
     * @param string $phoneNumber
     * @return string
     */
    private function maskPhoneNumber(string $phoneNumber)
    {
        // Keep first 3 and last 2 digits visible, mask the rest
        $length = strlen($phoneNumber);
        if ($length <= 5) {
            return $phoneNumber; // Too short to mask effectively
        }
        
        $visibleStart = 3;
        $visibleEnd = 2;
        
        $maskedPart = str_repeat('*', $length - $visibleStart - $visibleEnd);
        
        return substr($phoneNumber, 0, $visibleStart) . $maskedPart . substr($phoneNumber, -$visibleEnd);
    }

    /**
     * Store verification code in the database.
     *
     * @param string $registrationNumber
     * @param string $code
     * @return void
     */
    private function storeVerificationCode(string $registrationNumber, string $code): void
    {
        // Check if a record already exists
        $verification = DB::table('phone_verifications')
            ->where('registration_number', $registrationNumber)
            ->first();
        
        $now = now();
        
        if ($verification) {
            // Update existing record
            DB::table('phone_verifications')
                ->where('registration_number', $registrationNumber)
                ->update([
                    'verification_code' => $code,
                    'code_expires_at' => $now->addMinutes(30),
                    'updated_at' => $now
                ]);
        } else {
            // Get student phone number
            $student = DB::table('students')
                ->where('registration_number', $registrationNumber)
                ->first();
                
            // Create new record
            DB::table('phone_verifications')
                ->insert([
                    'registration_number' => $registrationNumber,
                    'phone_number' => $student->phone_number,
                    'verification_code' => $code,
                    'code_expires_at' => $now->addMinutes(30),
                    'verified' => false,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
        }
    }

    /**
     * Mark phone as verified in the database.
     *
     * @param string $registrationNumber
     * @return void
     */
    private function markPhoneAsVerified(string $registrationNumber): void
    {
        $now = now();
        
        // Check if a record already exists
        $verification = DB::table('phone_verifications')
            ->where('registration_number', $registrationNumber)
            ->first();
        
        if ($verification) {
            // Update existing record
            DB::table('phone_verifications')
                ->where('registration_number', $registrationNumber)
                ->update([
                    'verified' => true,
                    'verified_at' => $now,
                    'updated_at' => $now
                ]);
        } else {
            // Get student phone number
            $student = DB::table('students')
                ->where('registration_number', $registrationNumber)
                ->first();
                
            // Create new record (should not happen in normal flow)
            DB::table('phone_verifications')
                ->insert([
                    'registration_number' => $registrationNumber,
                    'phone_number' => $student->phone_number,
                    'verification_code' => '123456', // Default mock code
                    'code_expires_at' => $now->addMinutes(30),
                    'verified' => true,
                    'verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
        }
    }

    /**
     * Send verification code via email.
     *
     * @param object $student
     * @param string $code
     * @return bool
     */
    private function sendVerificationEmail($student, $code)
    {
        if (empty($student->email)) {
            Log::info("No email found for student with registration number {$student->registration_number}");
            return false;
        }
        
        // Find the user associated with this student
        $user = DB::table('users')
            ->where('email', $student->email)
            ->first();
            
        if (!$user) {
            Log::info("No user found for email {$student->email}");
            return false;
        }
        
        // Create email content
        $subject = "Your EduResultChain Verification Code";
        $message = "Your verification code for EduResultChain is: {$code}. This code will expire in 30 minutes.";
        
        // Send email
        try {
            \Mail::raw($message, function ($mail) use ($user, $subject) {
                $mail->to($user->email)
                    ->subject($subject);
            });
            
            Log::info("Verification code email sent to {$user->email}: {$code}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send verification email: " . $e->getMessage());
            return false;
        }
    }
} 