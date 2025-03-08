<?php

namespace Vanguard\Http\Requests\PhoneVerification;

use Illuminate\Foundation\Http\FormRequest;

class GenerateCodeRequest extends FormRequest
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
        ];
    }
} 