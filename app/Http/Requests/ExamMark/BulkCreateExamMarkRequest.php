<?php

namespace Vanguard\Http\Requests\ExamMark;

use Vanguard\Http\Requests\Request;

class BulkCreateExamMarkRequest extends Request
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
            'marks' => 'required|array|min:1',
            'marks.*.roll_number' => 'required|string|exists:form_fillups,roll_number',
            'marks.*.subject_id' => 'required|integer|exists:subjects,subject_id',
            'marks.*.marks_obtained' => 'required|numeric|min:0|max:100',
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
            'marks.required' => 'At least one mark entry is required.',
            'marks.*.roll_number.exists' => 'One or more student roll numbers are invalid or not registered for an exam.',
            'marks.*.subject_id.exists' => 'One or more selected subjects are invalid.',
            'marks.*.marks_obtained.min' => 'Marks obtained must be at least 0.',
            'marks.*.marks_obtained.max' => 'Marks obtained cannot be greater than 100.',
        ];
    }
} 