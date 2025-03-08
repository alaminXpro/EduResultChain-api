<?php

namespace Vanguard\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamMarkResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Handle both object and array access
        $resource = is_array($this->resource) ? $this->resource : $this->resource->toArray();
        
        return [
            'detail_id' => $resource['detail_id'] ?? $resource['id'] ?? null,
            'roll_number' => $resource['roll_number'] ?? null,
            'subject_id' => $resource['subject_id'] ?? null,
            'subject_name' => $resource['subject_name'] ?? null,
            'subject_code' => $resource['subject_code'] ?? null,
            'marks_obtained' => $resource['marks_obtained'] ?? null,
            'grade' => $resource['grade'] ?? null,
            'grade_point' => $resource['grade_point'] ?? null,
            'exam_name' => $resource['exam_name'] ?? null,
            'session' => $resource['session'] ?? null,
            'entered_by' => $resource['entered_by'] ?? null,
            'entered_by_name' => $resource['entered_by_name'] ?? null,
            'created_at' => $resource['created_at'] ?? null,
            'updated_at' => $resource['updated_at'] ?? null,
        ];
    }
} 