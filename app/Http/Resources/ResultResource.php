<?php

namespace Vanguard\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = $request->user();
        $isAdmin = $user && ($user->hasRole('Admin') || $user->hasRole('Board'));
        
        $data = [
            'result_id' => $this->result_id,
            'roll_number' => $this->roll_number,
            'exam_name' => $this->exam_name,
            'session' => $this->session,
            'total_marks' => $this->total_marks,
            'gpa' => $this->gpa,
            'grade' => $this->grade,
            'status' => $this->status,
            'published' => (bool) $this->published,
            'published_at' => $this->published_at ? $this->published_at->toDateTimeString() : null,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
        
        // Include student information
        if ($this->relationLoaded('formFillup') && $this->formFillup && $this->formFillup->relationLoaded('student')) {
            $data['student'] = [
                'registration_number' => $this->formFillup->registration_number,
                'first_name' => $this->formFillup->student->first_name,
                'last_name' => $this->formFillup->student->last_name,
                'group' => $this->formFillup->group,
            ];
            
            // Include institution information
            if ($this->formFillup->institution_id) {
                $data['institution'] = [
                    'id' => $this->formFillup->institution_id,
                    'name' => $this->formFillup->institution->name ?? null,
                ];
            }
        }
        
        // Include exam marks if loaded
        if ($this->relationLoaded('examMarks')) {
            $data['exam_marks'] = $this->examMarks->map(function ($mark) {
                return [
                    'subject_id' => $mark->subject_id,
                    'subject_name' => $mark->subject->subject_name ?? null,
                    'subject_code' => $mark->subject->subject_code ?? null,
                    'marks_obtained' => $mark->marks_obtained,
                    'grade' => $mark->grade,
                    'grade_point' => $mark->grade_point,
                ];
            });
        }
        
        // Include IPFS hash only for published results
        if ($this->published) {
            $data['ipfs_hash'] = $this->ipfs_hash;
        }
        
        // Include administrative data only for admins and board users
        if ($isAdmin) {
            $data['published_by'] = $this->publishedBy ? [
                'id' => $this->publishedBy->id,
                'name' => $this->publishedBy->name,
            ] : null;
        }
        
        return $data;
    }
} 