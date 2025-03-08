<?php

namespace Vanguard\Http\Requests\PhoneVerification;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPhoneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'registration_number' => 'required|string|exists:students,registration_number',
            'verification_code' => 'required|string|size:6',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'registration_number.required' => 'Registration number is required.',
            'registration_number.exists' => 'No student found with this registration number.',
            'verification_code.required' => 'Verification code is required.',
            'verification_code.size' => 'Verification code must be 6 digits.',
        ];
    }
} 