<?php

namespace Vanguard\Http\Requests\Student;

use Vanguard\Http\Requests\Request;

class StoreStudentRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->user()->hasPermission('students.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'date_of_birth' => 'required|date',
            'father_name' => 'required|string|max:100',
            'mother_name' => 'required|string|max:100',
            'phone_number' => 'required|string|unique:students,phone_number',
            'email' => 'nullable|email|max:100',
            'permanent_address' => 'required|string|max:255',
            'image' => 'nullable|string' // Base64 encoded image
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
            'phone_number.required' => 'Phone number is required.',
            'phone_number.unique' => 'This phone number is already in use.',
        ];
    }
} 