<?php

namespace Vanguard\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FormFillupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'roll_number' => $this->roll_number,
            'registration_number' => $this->registration_number,
            'exam_name' => $this->exam_name,
            'session' => $this->session,
            'group' => $this->group,
            'board_id' => $this->board_id,
            'institution_id' => $this->institution_id,
            'board_name' => $this->board_name ?? null,
            'institution_name' => $this->institution_name ?? null,
            'student_name' => isset($this->first_name) ? $this->first_name . ' ' . $this->last_name : null,
            'phone_number' => $this->phone_number ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 