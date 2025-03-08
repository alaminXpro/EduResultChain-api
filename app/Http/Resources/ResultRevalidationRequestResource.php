<?php

namespace Vanguard\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ResultRevalidationRequestResource extends JsonResource
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
            'id' => $this->id,
            'roll_number' => $this->roll_number,
            'subject' => $this->whenLoaded('subject', function () {
                return [
                    'id' => $this->subject->subject_id,
                    'name' => $this->subject->subject_name,
                    'category' => $this->subject->subject_category,
                ];
            }),
            'student' => $this->whenLoaded('formFillup', function () {
                if ($this->formFillup->student) {
                    return [
                        'registration_number' => $this->formFillup->student->registration_number,
                        'name' => $this->formFillup->student->full_name,
                        'phone_number' => $this->formFillup->student->phone_number,
                    ];
                }
                return null;
            }),
            'exam_details' => $this->whenLoaded('formFillup', function () {
                return [
                    'exam_name' => $this->formFillup->exam_name,
                    'session' => $this->formFillup->session,
                    'group' => $this->formFillup->group,
                    'institution_id' => $this->formFillup->institution_id,
                ];
            }),
            'reason' => $this->reason,
            'status' => $this->status,
            'original_marks' => $this->original_marks,
            'updated_marks' => $this->updated_marks,
            'comments' => $this->comments,
            'requested_by' => $this->whenLoaded('requestedBy', function () {
                return [
                    'id' => $this->requestedBy->id,
                    'name' => $this->requestedBy->name,
                ];
            }),
            'reviewed_by' => $this->whenLoaded('reviewedBy', function () {
                if ($this->reviewedBy) {
                    return [
                        'id' => $this->reviewedBy->id,
                        'name' => $this->reviewedBy->name,
                    ];
                }
                return null;
            }),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'reviewed_at' => $this->reviewed_at ? $this->reviewed_at->toDateTimeString() : null,
        ];
    }
} 