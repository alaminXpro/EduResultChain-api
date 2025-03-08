<?php

namespace Vanguard\Http\Requests\ResultRevalidation;

use Illuminate\Foundation\Http\FormRequest;

class CreateRevalidationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Allow students, institutions, board users, and admins to create revalidation requests
        return auth()->check() && (
            auth()->user()->hasRole('student') ||
            auth()->user()->hasRole('institution') ||
            auth()->user()->hasRole('board') ||
            auth()->user()->hasRole('admin')
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'roll_number' => 'required|string|exists:form_fillups,roll_number',
            'subject_id' => 'required|integer|exists:subjects,subject_id',
            'reason' => 'required|string|min:10|max:500',
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
            'roll_number.required' => 'Roll number is required.',
            'roll_number.exists' => 'No form fillup found with this roll number.',
            'subject_id.required' => 'Subject is required.',
            'subject_id.exists' => 'Invalid subject selected.',
            'reason.required' => 'Reason for revalidation is required.',
            'reason.min' => 'Reason must be at least 10 characters.',
            'reason.max' => 'Reason cannot exceed 500 characters.',
        ];
    }
} 