<?php

namespace Vanguard\Services\Interfaces;

interface PhoneVerificationServiceInterface
{
    /**
     * Generate and send a verification code to a student.
     *
     * @param string $registrationNumber
     * @return mixed
     */
    public function generateVerificationCode(string $registrationNumber);

    /**
     * Verify a phone number using the provided verification code.
     *
     * @param string $registrationNumber
     * @param string $verificationCode
     * @return bool
     */
    public function verifyPhone(string $registrationNumber, string $verificationCode);

    /**
     * Check if a phone number is verified.
     *
     * @param string $registrationNumber
     * @return bool
     */
    public function isPhoneVerified(string $registrationNumber);

    /**
     * Get verification status for a registration number.
     *
     * @param string $registrationNumber
     * @return array
     */
    public function getVerificationStatus(string $registrationNumber);
} 