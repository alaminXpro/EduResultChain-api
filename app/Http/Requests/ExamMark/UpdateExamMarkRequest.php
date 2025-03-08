<?php

namespace Vanguard\Http\Requests\ExamMark;

use Vanguard\Http\Requests\Request;

class UpdateExamMarkRequest extends Request
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
            'marks_obtained.min' => 'The marks obtained must be at least 0.',
            'marks_obtained.max' => 'The marks obtained cannot be greater than 100.',
        ];
    }
} 