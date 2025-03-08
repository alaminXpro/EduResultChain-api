<?php

namespace Vanguard\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InstitutionStatisticsResource extends JsonResource
{
    /**
     * Indicates if the resource's collection keys should be preserved.
     *
     * @var bool
     */
    public $preserveKeys = true;
    
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Handle null resource
        if ($this->resource === null) {
            return [
                'message' => 'No statistics available for this institution.'
            ];
        }
        
        // Check if we have a single object or a collection
        if (is_object($this->resource)) {
            return $this->formatSingleInstitution($this->resource);
        }
        
        // Format the institution statistics collection
        return collect($this->resource)->map(function ($item) {
            return [
                'institution_id' => (int) ($item->institution_id ?? 0),
                'institution_name' => $item->institution_name ?? 'Unknown',
                'total_students' => (int) ($item->total_students ?? 0),
                'pass_count' => (int) ($item->pass_count ?? 0),
                'fail_count' => (int) ($item->fail_count ?? 0),
                'pass_percentage' => round((float) ($item->pass_percentage ?? 0), 2),
                'average_gpa' => round((float) ($item->average_gpa ?? 0), 2),
            ];
        })->values()->toArray();
    }
    
    /**
     * Format a single institution's statistics
     *
     * @param object $item
     * @return array
     */
    private function formatSingleInstitution($item)
    {
        $result = [
            'institution_id' => (int) ($item->institution_id ?? 0),
            'institution_name' => $item->institution_name ?? 'Unknown',
            'total_students' => (int) ($item->total_students ?? 0),
            'pass_count' => (int) ($item->pass_count ?? 0),
            'fail_count' => (int) ($item->fail_count ?? 0),
            'pass_percentage' => round((float) ($item->pass_percentage ?? 0), 2),
            'average_gpa' => round((float) ($item->average_gpa ?? 0), 2),
            'highest_gpa' => round((float) ($item->highest_gpa ?? 0), 2),
            'lowest_passing_gpa' => round((float) ($item->lowest_passing_gpa ?? 0), 2),
            'total_groups' => (int) ($item->total_groups ?? 0),
        ];
        
        // Add group statistics if available
        if (isset($item->group_statistics)) {
            $result['group_statistics'] = collect($item->group_statistics)->map(function ($group) {
                return [
                    'group' => $group->group ?? 'Unknown',
                    'total_students' => (int) ($group->total_students ?? 0),
                    'pass_count' => (int) ($group->pass_count ?? 0),
                    'fail_count' => (int) ($group->fail_count ?? 0),
                    'pass_percentage' => round((float) ($group->pass_percentage ?? 0), 2),
                    'average_gpa' => round((float) ($group->average_gpa ?? 0), 2),
                ];
            })->values()->toArray();
        }
        
        // Add subject statistics if available
        if (isset($item->subject_statistics)) {
            $result['subject_statistics'] = collect($item->subject_statistics)->map(function ($subject) {
                return [
                    'subject_id' => (int) ($subject->subject_id ?? 0),
                    'subject_name' => $subject->subject_name ?? 'Unknown',
                    'total_students' => (int) ($subject->total_students ?? 0),
                    'average_marks' => round((float) ($subject->average_marks ?? 0), 2),
                    'grade_distribution' => [
                        'A+' => (int) ($subject->a_plus_count ?? 0),
                        'A' => (int) ($subject->a_count ?? 0),
                        'A-' => (int) ($subject->a_minus_count ?? 0),
                        'B+' => (int) ($subject->b_plus_count ?? 0),
                        'B' => (int) ($subject->b_count ?? 0),
                        'C+' => (int) ($subject->c_plus_count ?? 0),
                        'C' => (int) ($subject->c_count ?? 0),
                        'D' => (int) ($subject->d_count ?? 0),
                        'F' => (int) ($subject->f_count ?? 0),
                    ],
                ];
            })->values()->toArray();
        }
        
        // Add gender statistics if available
        if (isset($item->gender_statistics)) {
            $result['gender_statistics'] = collect($item->gender_statistics)->map(function ($gender) {
                return [
                    'gender' => $gender->gender ?? 'Unknown',
                    'total' => (int) ($gender->total ?? 0),
                    'pass_count' => (int) ($gender->pass_count ?? 0),
                    'pass_percentage' => round((float) ($gender->pass_percentage ?? 0), 2),
                    'average_gpa' => round((float) ($gender->average_gpa ?? 0), 2),
                ];
            })->values()->toArray();
        }
        
        // Add top students if available
        if (isset($item->top_students)) {
            $result['top_students'] = collect($item->top_students)->map(function ($student) {
                return [
                    'roll_number' => $student->roll_number ?? '',
                    'name' => trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')),
                    'group' => $student->group ?? '',
                    'gpa' => round((float) ($student->gpa ?? 0), 2),
                    'grade' => $student->grade ?? '',
                    'total_marks' => (int) ($student->total_marks ?? 0),
                ];
            })->values()->toArray();
        }
        
        // Add insights if available
        if (isset($item->insights)) {
            $result['insights'] = $item->insights;
        }
        
        return $result;
    }
    
    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->resource = $resource;
    }
} 