<?php

namespace Vanguard\Services;

use Vanguard\Services\Interfaces\PhoneVerificationServiceInterface;
use Vanguard\Models\PhoneVerification;
use Vanguard\Models\Student;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PhoneVerificationService implements PhoneVerificationServiceInterface
{
    /**
     * Generate and send a verification code to a student.
     *
     * @param string $registrationNumber
     * @return PhoneVerification
     */
    public function generateVerificationCode(string $registrationNumber)
    {
        // Find the student
        $student = Student::where('registration_number', $registrationNumber)->first();
        if (!$student) {
            throw new ModelNotFoundException('Student not found with the given registration number.');
        }

        // Generate a random 6-digit code
        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Set expiration time (30 minutes from now)
        $expiresAt = Carbon::now()->addMinutes(30);

        // Create or update the phone verification record
        $phoneVerification = PhoneVerification::updateOrCreate(
            ['registration_number' => $registrationNumber],
            [
                'phone_number' => $student->phone_number,
                'verification_code' => $verificationCode,
                'verified' => false,
                'verified_at' => null,
                'code_expires_at' => $expiresAt,
            ]
        );

        // Send verification code via email
        $this->sendVerificationEmail($student->phone_number, $verificationCode);
        
        // Log the code for development purposes
        \Log::info("Verification code for {$registrationNumber}: {$verificationCode}");

        return $phoneVerification;
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
        // Find the phone verification record
        $phoneVerification = PhoneVerification::where('registration_number', $registrationNumber)
            ->where('verification_code', $verificationCode)
            ->first();

        if (!$phoneVerification) {
            return false;
        }

        // Check if the code has expired
        if ($phoneVerification->isExpired()) {
            return false;
        }

        // Update the verification status
        $phoneVerification->update([
            'verified' => true,
            'verified_at' => Carbon::now(),
        ]);

        return true;
    }

    /**
     * Check if a phone number is verified.
     *
     * @param string $registrationNumber
     * @return bool
     */
    public function isPhoneVerified(string $registrationNumber)
    {
        $phoneVerification = PhoneVerification::where('registration_number', $registrationNumber)->first();
        
        return $phoneVerification && $phoneVerification->verified;
    }

    /**
     * Get verification status for a registration number.
     *
     * @param string $registrationNumber
     * @return array
     */
    public function getVerificationStatus(string $registrationNumber)
    {
        $phoneVerification = PhoneVerification::where('registration_number', $registrationNumber)->first();
        
        if (!$phoneVerification) {
            return [
                'verified' => false,
                'phone_number' => null,
                'last_verification_attempt' => null,
            ];
        }

        return [
            'verified' => (bool) $phoneVerification->verified,
            'phone_number' => $this->maskPhoneNumber($phoneVerification->phone_number),
            'last_verification_attempt' => $phoneVerification->updated_at,
            'verified_at' => $phoneVerification->verified_at,
        ];
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
     * Send verification SMS (placeholder for actual SMS integration).
     *
     * @param string $phoneNumber
     * @param string $verificationCode
     * @return bool
     */
    private function sendVerificationSMS(string $phoneNumber, string $verificationCode)
    {
        // This is a placeholder for actual SMS sending logic
        // In a real implementation, this would integrate with an SMS gateway
        
        // Example message
        $message = "Your EduResultChain verification code is: {$verificationCode}. This code will expire in 30 minutes.";
        
        // Log the message for development purposes
        \Log::info("SMS would be sent to {$phoneNumber}: {$message}");
        
        // For now, we're not sending actual SMS, so we'll just return true
        return true;
    }
    
    /**
     * Send verification code via email.
     *
     * @param string $phoneNumber
     * @param string $verificationCode
     * @return bool
     */
    private function sendVerificationEmail(string $phoneNumber, string $verificationCode)
    {
        // Find the student with this phone number
        $student = \DB::table('students')
            ->where('phone_number', $phoneNumber)
            ->first();
            
        if (!$student || empty($student->email)) {
            \Log::info("No email found for phone number {$phoneNumber}");
            return false;
        }
        
        // Find the user associated with this student
        $user = \DB::table('users')
            ->where('email', $student->email)
            ->first();
            
        if (!$user) {
            \Log::info("No user found for email {$student->email}");
            return false;
        }
        
        // Create email content
        $subject = "Your EduResultChain Verification Code";
        $message = "Your verification code for EduResultChain is: {$verificationCode}. This code will expire in 30 minutes.";
        
        // Send email
        try {
            \Mail::raw($message, function ($mail) use ($user, $subject) {
                $mail->to($user->email)
                    ->subject($subject);
            });
            
            \Log::info("Verification code email sent to {$user->email}");
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to send verification email: " . $e->getMessage());
            return false;
        }
    }
} 