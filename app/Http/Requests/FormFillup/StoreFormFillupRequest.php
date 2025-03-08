<?php

namespace Vanguard\Http\Requests\FormFillup;

use Vanguard\Http\Requests\Request;

class StoreFormFillupRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->user()->hasPermission('form.fillup.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'registration_number' => 'required|string|exists:students,registration_number',
            'exam_name' => 'required|string|in:SSC,HSC',
            'session' => 'required|string|regex:/^\d{4}$/', // Year format: YYYY
            'group' => 'required|string|in:Science,Commerce,Arts',
            'board_id' => 'required|exists:users,id',
            'institution_id' => 'required|exists:users,id'
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
            'registration_number.exists' => 'The student registration number is invalid.',
            'exam_name.in' => 'The exam name must be either SSC or HSC.',
            'session.regex' => 'The session must be a valid year (YYYY).',
            'group.in' => 'The group must be either Science, Commerce, or Arts.',
            'board_id.exists' => 'The selected board is invalid.',
            'institution_id.exists' => 'The selected institution is invalid.'
        ];
    }
} 