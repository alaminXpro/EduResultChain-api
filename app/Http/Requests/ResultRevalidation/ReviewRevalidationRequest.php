<?php

namespace Vanguard\Http\Requests\ResultRevalidation;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRevalidationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Only board users and admins can review revalidation requests
        return auth()->check() && (
            auth()->user()->hasRole('Board') ||
            auth()->user()->hasRole('Admin') ||
            auth()->user()->hasPermission('revalidation.manage')
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
            'status' => 'required|string|in:Approved,Rejected',
            'comments' => 'nullable|string|max:500',
            'updated_marks' => 'required_if:status,Approved|nullable|numeric|min:0|max:100',
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
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be either Approved or Rejected.',
            'comments.max' => 'Comments cannot exceed 500 characters.',
            'updated_marks.required_if' => 'Updated marks are required when approving a revalidation request.',
            'updated_marks.numeric' => 'Updated marks must be a number.',
            'updated_marks.min' => 'Updated marks cannot be negative.',
            'updated_marks.max' => 'Updated marks cannot exceed 100.',
        ];
    }
} 