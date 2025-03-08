<?php

namespace Vanguard\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StatisticsResource extends JsonResource
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
        // The resource is already an array, so we just need to ensure
        // all values are properly formatted
        $data = $this->resource;
        
        // Format overall statistics
        if (isset($data['overall'])) {
            $overall = $data['overall'];
            $data['overall'] = [
                'total_students' => (int) ($overall->total_students ?? 0),
                'pass_count' => (int) ($overall->pass_count ?? 0),
                'fail_count' => (int) ($overall->fail_count ?? 0),
                'pass_percentage' => round((float) ($overall->pass_percentage ?? 0), 2),
                'average_gpa' => round((float) ($overall->average_gpa ?? 0), 2),
                'highest_gpa' => round((float) ($overall->highest_gpa ?? 0), 2),
                'lowest_passing_gpa' => round((float) ($overall->lowest_passing_gpa ?? 0), 2),
                'total_institutions' => (int) ($overall->total_institutions ?? 0),
            ];
        }
        
        // Format grade distribution
        if (isset($data['grade_distribution'])) {
            $data['grade_distribution'] = collect($data['grade_distribution'])->map(function ($item) {
                return [
                    'grade' => $item->grade ?? 'Unknown',
                    'count' => (int) ($item->count ?? 0),
                    'percentage' => round((float) ($item->percentage ?? 0), 2),
                ];
            })->values()->toArray();
        }
        
        // Format top institutions
        if (isset($data['top_institutions'])) {
            $data['top_institutions'] = collect($data['top_institutions'])->map(function ($item) {
                return [
                    'institution_id' => (int) ($item->institution_id ?? 0),
                    'institution_name' => $item->institution_name ?? 'Unknown',
                    'total_students' => (int) ($item->total_students ?? 0),
                    'pass_count' => (int) ($item->pass_count ?? 0),
                    'pass_percentage' => round((float) ($item->pass_percentage ?? 0), 2),
                    'average_gpa' => round((float) ($item->average_gpa ?? 0), 2),
                ];
            })->values()->toArray();
        }
        
        // Format subject performance
        if (isset($data['subject_performance'])) {
            $data['subject_performance'] = collect($data['subject_performance'])->map(function ($item) {
                return [
                    'subject_id' => (int) ($item->subject_id ?? 0),
                    'subject_name' => $item->subject_name ?? 'Unknown',
                    'total_students' => (int) ($item->total_students ?? 0),
                    'average_marks' => round((float) ($item->average_marks ?? 0), 2),
                    'grade_distribution' => [
                        'A+' => (int) ($item->a_plus_count ?? 0),
                        'A' => (int) ($item->a_count ?? 0),
                        'A-' => (int) ($item->a_minus_count ?? 0),
                        'B+' => (int) ($item->b_plus_count ?? 0),
                        'B' => (int) ($item->b_count ?? 0),
                        'C+' => (int) ($item->c_plus_count ?? 0),
                        'C' => (int) ($item->c_count ?? 0),
                        'D' => (int) ($item->d_count ?? 0),
                        'F' => (int) ($item->f_count ?? 0),
                    ],
                ];
            })->values()->toArray();
        }
        
        // Format gender distribution
        if (isset($data['gender_distribution'])) {
            $data['gender_distribution'] = collect($data['gender_distribution'])->map(function ($item) {
                return [
                    'gender' => $item->gender ?? 'Unknown',
                    'total' => (int) ($item->total ?? 0),
                    'pass_count' => (int) ($item->pass_count ?? 0),
                    'pass_percentage' => round((float) ($item->pass_percentage ?? 0), 2),
                    'average_gpa' => round((float) ($item->average_gpa ?? 0), 2),
                ];
            })->values()->toArray();
        }
        
        // Format performance trend
        if (isset($data['performance_trend'])) {
            $data['performance_trend'] = collect($data['performance_trend'])->map(function ($item) {
                return [
                    'session' => $item->session ?? '',
                    'total_students' => (int) ($item->total_students ?? 0),
                    'pass_count' => (int) ($item->pass_count ?? 0),
                    'pass_percentage' => round((float) ($item->pass_percentage ?? 0), 2),
                    'average_gpa' => round((float) ($item->average_gpa ?? 0), 2),
                ];
            })->values()->toArray();
        }
        
        // Add summary insights
        $data['insights'] = $this->generateInsights($data);
        
        return $data;
    }
    
    /**
     * Generate insights from the statistics data
     * 
     * @param array $data
     * @return array
     */
    private function generateInsights($data)
    {
        $insights = [];
        
        // Overall performance insight
        if (isset($data['overall'])) {
            $passPercentage = $data['overall']['pass_percentage'] ?? 0;
            $averageGpa = $data['overall']['average_gpa'] ?? 0;
            
            if ($passPercentage >= 90) {
                $insights[] = "Excellent overall performance with {$passPercentage}% pass rate.";
            } elseif ($passPercentage >= 75) {
                $insights[] = "Good overall performance with {$passPercentage}% pass rate.";
            } elseif ($passPercentage >= 60) {
                $insights[] = "Average overall performance with {$passPercentage}% pass rate.";
            } else {
                $insights[] = "Below average overall performance with {$passPercentage}% pass rate.";
            }
            
            $insights[] = "Average GPA across all students is " . number_format($averageGpa, 2) . ".";
        }
        
        // Subject performance insights
        if (isset($data['subject_performance']) && count($data['subject_performance']) > 0) {
            // Sort by average marks
            $subjectPerformance = collect($data['subject_performance']);
            
            // Get best and worst subjects
            $bestSubject = $subjectPerformance->sortByDesc('average_marks')->first();
            $worstSubject = $subjectPerformance->sortBy('average_marks')->first();
            
            if ($bestSubject && $worstSubject) {
                $bestSubjectName = $bestSubject['subject_name'] ?? 'Unknown';
                $bestSubjectMarks = $bestSubject['average_marks'] ?? 0;
                $worstSubjectName = $worstSubject['subject_name'] ?? 'Unknown';
                $worstSubjectMarks = $worstSubject['average_marks'] ?? 0;
                
                $insights[] = "Highest average marks in {$bestSubjectName} (" . number_format($bestSubjectMarks, 2) . ").";
                $insights[] = "Lowest average marks in {$worstSubjectName} (" . number_format($worstSubjectMarks, 2) . ").";
            }
        }
        
        // Gender insights
        if (isset($data['gender_distribution']) && count($data['gender_distribution']) > 1) {
            $genderDistribution = collect($data['gender_distribution']);
            
            $maleData = $genderDistribution->firstWhere('gender', 'Male');
            $femaleData = $genderDistribution->firstWhere('gender', 'Female');
            
            if ($maleData && $femaleData) {
                $malePassPercentage = $maleData['pass_percentage'] ?? 0;
                $femalePassPercentage = $femaleData['pass_percentage'] ?? 0;
                
                if ($malePassPercentage > $femalePassPercentage) {
                    $diff = number_format($malePassPercentage - $femalePassPercentage, 2);
                    $insights[] = "Male students have a {$diff}% higher pass rate than female students.";
                } elseif ($femalePassPercentage > $malePassPercentage) {
                    $diff = number_format($femalePassPercentage - $malePassPercentage, 2);
                    $insights[] = "Female students have a {$diff}% higher pass rate than male students.";
                }
            }
        }
        
        // Performance trend insights
        if (isset($data['performance_trend']) && count($data['performance_trend']) > 1) {
            $performanceTrend = $data['performance_trend'];
            
            $currentSession = $performanceTrend[0] ?? null;
            $previousSession = $performanceTrend[1] ?? null;
            
            if ($currentSession && $previousSession) {
                $currentPassPercentage = $currentSession['pass_percentage'] ?? 0;
                $previousPassPercentage = $previousSession['pass_percentage'] ?? 0;
                $currentGpa = $currentSession['average_gpa'] ?? 0;
                $previousGpa = $previousSession['average_gpa'] ?? 0;
                
                $passRateDiff = $currentPassPercentage - $previousPassPercentage;
                $gpaRateDiff = $currentGpa - $previousGpa;
                
                if ($passRateDiff > 0) {
                    $insights[] = "Pass rate increased by " . number_format(abs($passRateDiff), 2) . "% compared to previous session.";
                } elseif ($passRateDiff < 0) {
                    $insights[] = "Pass rate decreased by " . number_format(abs($passRateDiff), 2) . "% compared to previous session.";
                }
                
                if ($gpaRateDiff > 0) {
                    $insights[] = "Average GPA increased by " . number_format(abs($gpaRateDiff), 2) . " compared to previous session.";
                } elseif ($gpaRateDiff < 0) {
                    $insights[] = "Average GPA decreased by " . number_format(abs($gpaRateDiff), 2) . " compared to previous session.";
                }
            }
        }
        
        return $insights;
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