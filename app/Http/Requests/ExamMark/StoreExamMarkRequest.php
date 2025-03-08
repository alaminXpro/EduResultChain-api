<?php

namespace Vanguard\Http\Requests\ExamMark;

use Vanguard\Http\Requests\Request;

class StoreExamMarkRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->user()->hasPermission('exam.marks.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'roll_number' => 'required|string|exists:form_fillups,roll_number',
            'subject_id' => 'required|integer|exists:subjects,subject_id',
            'marks_obtained' => 'required|numeric|min:0|max:100',
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
            'roll_number.exists' => 'The student roll number is invalid or not registered for an exam.',
            'subject_id.exists' => 'The selected subject is invalid.',
            'marks_obtained.min' => 'The marks obtained must be at least 0.',
            'marks_obtained.max' => 'The marks obtained cannot be greater than 100.',
        ];
    }
} 