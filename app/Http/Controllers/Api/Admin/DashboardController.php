<?php

namespace Vanguard\Http\Controllers\Api\Admin;

use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Services\ResultService;
use Vanguard\Services\FormFillupService;
use Vanguard\Services\StudentService;
use Vanguard\Services\ResultRevalidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends ApiController
{
    /**
     * @var ResultService
     */
    protected $resultService;

    /**
     * @var FormFillupService
     */
    protected $formFillupService;

    /**
     * @var StudentService
     */
    protected $studentService;

    /**
     * @var ResultRevalidationService
     */
    protected $revalidationService;

    /**
     * Create a new controller instance.
     *
     * @param ResultService $resultService
     * @param FormFillupService $formFillupService
     * @param StudentService $studentService
     * @param ResultRevalidationService $revalidationService
     * @return void
     */
    public function __construct(
        ResultService $resultService,
        FormFillupService $formFillupService,
        StudentService $studentService,
        ResultRevalidationService $revalidationService
    ) {
        $this->resultService = $resultService;
        $this->formFillupService = $formFillupService;
        $this->studentService = $studentService;
        $this->revalidationService = $revalidationService;
    }

    /**
     * Get dashboard summary statistics.
     *
     * @return JsonResponse
     */
    public function getSummary(): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('dashboard.view')) {
            return $this->errorForbidden('You do not have permission to view dashboard statistics.');
        }

        try {
            // Get counts from different tables
            $studentCount = DB::table('students')->count();
            $formFillupCount = DB::table('form_fillups')->count();
            $resultCount = DB::table('results')->count();
            $publishedResultCount = DB::table('results')->where('published', true)->count();
            $pendingRevalidationCount = DB::table('result_revalidation_requests')
                ->where('status', 'pending')
                ->count();
            
            // Get recent activity
            $recentActivity = DB::table('result_histories')
                ->join('users', 'result_histories.modified_by', '=', 'users.id')
                ->select('result_histories.*', 'users.first_name', 'users.last_name')
                ->orderBy('timestamp', 'desc')
                ->limit(10)
                ->get();
            
            return $this->respondWithArray([
                'success' => true,
                'data' => [
                    'counts' => [
                        'students' => $studentCount,
                        'form_fillups' => $formFillupCount,
                        'results' => $resultCount,
                        'published_results' => $publishedResultCount,
                        'pending_revalidations' => $pendingRevalidationCount
                    ],
                    'recent_activity' => $recentActivity
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Get exam statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getExamStatistics(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('dashboard.view')) {
            return $this->errorForbidden('You do not have permission to view exam statistics.');
        }

        try {
            // If exam name and session are not provided, return available exams and sessions
            if (!$request->has('exam_name') || !$request->has('session')) {
                $examsAndSessions = DB::table('form_fillups')
                    ->select('exam_name', 'session')
                    ->distinct()
                    ->orderBy('session', 'desc')
                    ->orderBy('exam_name', 'asc')
                    ->get();
                
                return $this->respondWithArray([
                    'success' => true,
                    'data' => [
                        'available_exams' => $examsAndSessions
                    ]
                ]);
            }
            
            // Validate request
            $request->validate([
                'exam_name' => 'required|string',
                'session' => 'required|string',
            ]);
            
            // Get overall statistics
            $overallStats = $this->resultService->getResultStatistics(
                $request->exam_name,
                $request->session
            );
            
            // Get institution-wise statistics
            $institutionStats = $this->resultService->getInstitutionResultStatistics(
                $request->exam_name,
                $request->session
            );
            
            // Get subject-wise statistics
            $subjectStats = DB::table('exam_marks')
                ->join('form_fillups', 'exam_marks.roll_number', '=', 'form_fillups.roll_number')
                ->join('subjects', 'exam_marks.subject_id', '=', 'subjects.subject_id')
                ->where('form_fillups.exam_name', $request->exam_name)
                ->where('form_fillups.session', $request->session)
                ->select(
                    'subjects.subject_name',
                    'subjects.subject_category',
                    DB::raw('COUNT(*) as total_students'),
                    DB::raw('AVG(exam_marks.marks_obtained) as average_marks'),
                    DB::raw('MAX(exam_marks.marks_obtained) as highest_marks'),
                    DB::raw('MIN(exam_marks.marks_obtained) as lowest_marks')
                )
                ->groupBy('subjects.subject_id', 'subjects.subject_name', 'subjects.subject_category')
                ->get();
            
            return $this->respondWithArray([
                'success' => true,
                'data' => [
                    'overall' => $overallStats,
                    'institutions' => $institutionStats,
                    'subjects' => $subjectStats
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Get revalidation statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRevalidationStatistics(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('dashboard.view')) {
            return $this->errorForbidden('You do not have permission to view revalidation statistics.');
        }

        try {
            // Get overall revalidation statistics
            $stats = $this->revalidationService->getStatistics();
            
            // If exam name and session are provided, get specific statistics
            if ($request->has('exam_name') && $request->has('session')) {
                $examStats = $this->revalidationService->getRevalidationStatistics(
                    $request->exam_name,
                    $request->session
                );
                
                $stats['exam_specific'] = $examStats;
            }
            
            // Get recent revalidation requests
            $recentRequests = DB::table('result_revalidation_requests')
                ->join('form_fillups', 'result_revalidation_requests.roll_number', '=', 'form_fillups.roll_number')
                ->join('subjects', 'result_revalidation_requests.subject_id', '=', 'subjects.subject_id')
                ->leftJoin('users as requesters', 'result_revalidation_requests.requested_by', '=', 'requesters.id')
                ->leftJoin('users as reviewers', 'result_revalidation_requests.reviewed_by', '=', 'reviewers.id')
                ->select(
                    'result_revalidation_requests.*',
                    'form_fillups.exam_name',
                    'form_fillups.session',
                    'subjects.subject_name',
                    'requesters.first_name as requester_first_name',
                    'requesters.last_name as requester_last_name',
                    'reviewers.first_name as reviewer_first_name',
                    'reviewers.last_name as reviewer_last_name'
                )
                ->orderBy('result_revalidation_requests.created_at', 'desc')
                ->limit(10)
                ->get();
            
            return $this->respondWithArray([
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'recent_requests' => $recentRequests
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Get system health statistics.
     *
     * @return JsonResponse
     */
    public function getSystemHealth(): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasRole('Admin')) {
            return $this->errorForbidden('Only administrators can view system health statistics.');
        }

        try {
            // Get database size (MySQL compatible)
            $dbSizeQuery = "SELECT 
                SUM(data_length + index_length) AS size
                FROM information_schema.tables
                WHERE table_schema = DATABASE()";
            
            $dbSize = DB::select($dbSizeQuery)[0]->size ?? 0;
            
            // Get table counts
            $tableCounts = [
                'students' => DB::table('students')->count(),
                'form_fillups' => DB::table('form_fillups')->count(),
                'exam_marks' => DB::table('exam_marks')->count(),
                'results' => DB::table('results')->count(),
                'result_histories' => DB::table('result_histories')->count(),
                'result_revalidation_requests' => DB::table('result_revalidation_requests')->count(),
            ];
            
            // Get recent errors from log (simplified - in a real app, you'd parse the log file)
            $recentErrors = [];
            
            return $this->respondWithArray([
                'success' => true,
                'data' => [
                    'database_size' => [
                        'bytes' => $dbSize,
                        'formatted' => $this->formatBytes($dbSize)
                    ],
                    'table_counts' => $tableCounts,
                    'recent_errors' => $recentErrors,
                    'system_info' => [
                        'php_version' => PHP_VERSION,
                        'laravel_version' => app()->version(),
                        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Format bytes to human-readable format.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
} 