<?php

namespace Vanguard\Services;

use Vanguard\Models\ResultRevalidationRequest;
use Vanguard\Models\ExamMark;
use Vanguard\Models\FormFillup;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ResultRevalidationService
{
    /**
     * @var ExamMarkService
     */
    protected $examMarkService;

    /**
     * Create a new service instance.
     *
     * @param ExamMarkService $examMarkService
     * @return void
     */
    public function __construct(ExamMarkService $examMarkService)
    {
        $this->examMarkService = $examMarkService;
    }

    /**
     * Create a new revalidation request.
     *
     * @param array $data
     * @return ResultRevalidationRequest
     */
    public function createRevalidationRequest(array $data)
    {
        // Validate form fillup exists
        $formFillup = FormFillup::where('roll_number', $data['roll_number'])->first();
        if (!$formFillup) {
            throw new ModelNotFoundException('Form fillup not found for the given roll number.');
        }

        // Validate exam mark exists
        $examMark = ExamMark::where('roll_number', $data['roll_number'])
            ->where('subject_id', $data['subject_id'])
            ->first();
            
        if (!$examMark) {
            throw new ModelNotFoundException('Exam mark not found for the given roll number and subject.');
        }

        // Set the original marks and requested by user
        $data['original_marks'] = $examMark->marks_obtained;
        $data['requested_by'] = Auth::id();
        $data['status'] = 'Pending';

        // Begin transaction
        return DB::transaction(function () use ($data) {
            // Create the revalidation request
            return ResultRevalidationRequest::create($data);
        });
    }

    /**
     * Review a revalidation request.
     *
     * @param int $requestId
     * @param array $data
     * @return ResultRevalidationRequest
     */
    public function reviewRevalidationRequest(int $requestId, array $data)
    {
        // Find the revalidation request
        $revalidationRequest = ResultRevalidationRequest::findOrFail($requestId);
        
        // Validate status
        if (!in_array($data['status'], ['Approved', 'Rejected'])) {
            throw new \InvalidArgumentException('Status must be either Approved or Rejected.');
        }
        
        // Set the reviewer and review time
        $data['reviewed_by'] = Auth::id();
        $data['reviewed_at'] = Carbon::now();

        // Begin transaction
        return DB::transaction(function () use ($revalidationRequest, $data) {
            // If original_marks is not set, retrieve it from the exam mark
            if (!$revalidationRequest->original_marks) {
                $examMark = ExamMark::where('roll_number', $revalidationRequest->roll_number)
                    ->where('subject_id', $revalidationRequest->subject_id)
                    ->first();
                
                if ($examMark) {
                    $revalidationRequest->original_marks = $examMark->marks_obtained;
                    $revalidationRequest->save();
                }
            }
            
            // Update the revalidation request
            $revalidationRequest->update($data);
            
            // If approved and updated_marks is provided, update the exam mark
            if ($data['status'] === 'Approved' && isset($data['updated_marks'])) {
                // Find the exam mark
                $examMark = ExamMark::where('roll_number', $revalidationRequest->roll_number)
                    ->where('subject_id', $revalidationRequest->subject_id)
                    ->first();
                
                if ($examMark) {
                    // Update the exam mark with the new marks
                    $this->examMarkService->updateExamMark($examMark->detail_id, [
                        'marks_obtained' => $data['updated_marks']
                    ]);
                }
            }
            
            return $revalidationRequest->fresh();
        });
    }

    /**
     * Get a revalidation request by ID.
     *
     * @param int $requestId
     * @return ResultRevalidationRequest
     */
    public function getRevalidationRequest(int $requestId)
    {
        return ResultRevalidationRequest::with([
                'formFillup', 
                'formFillup.student', 
                'subject', 
                'requestedBy', 
                'reviewedBy'
            ])
            ->findOrFail($requestId);
    }

    /**
     * Get all revalidation requests with optional filtering.
     *
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllRevalidationRequests(array $filters = [])
    {
        $query = ResultRevalidationRequest::with([
            'formFillup', 
            'formFillup.student', 
            'subject', 
            'requestedBy', 
            'reviewedBy'
        ]);
        
        // Apply filters
        if (isset($filters['roll_number'])) {
            $query->where('roll_number', $filters['roll_number']);
        }
        
        if (isset($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['institution_id'])) {
            $query->whereHas('formFillup', function ($q) use ($filters) {
                $q->where('institution_id', $filters['institution_id']);
            });
        }
        
        if (isset($filters['exam_name'])) {
            $query->whereHas('formFillup', function ($q) use ($filters) {
                $q->where('exam_name', $filters['exam_name']);
            });
        }
        
        if (isset($filters['session'])) {
            $query->whereHas('formFillup', function ($q) use ($filters) {
                $q->where('session', $filters['session']);
            });
        }
        
        // Default sort by created_at desc
        $query->orderBy('created_at', 'desc');
        
        // Paginate results
        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    /**
     * Get revalidation statistics.
     *
     * @param string $examName
     * @param string $session
     * @return array
     */
    public function getRevalidationStatistics(string $examName, string $session)
    {
        $totalRequests = ResultRevalidationRequest::whereHas('formFillup', function ($q) use ($examName, $session) {
                $q->where('exam_name', $examName)
                  ->where('session', $session);
            })
            ->count();
            
        $pendingRequests = ResultRevalidationRequest::whereHas('formFillup', function ($q) use ($examName, $session) {
                $q->where('exam_name', $examName)
                  ->where('session', $session);
            })
            ->where('status', 'Pending')
            ->count();
            
        $approvedRequests = ResultRevalidationRequest::whereHas('formFillup', function ($q) use ($examName, $session) {
                $q->where('exam_name', $examName)
                  ->where('session', $session);
            })
            ->where('status', 'Approved')
            ->count();
            
        $rejectedRequests = ResultRevalidationRequest::whereHas('formFillup', function ($q) use ($examName, $session) {
                $q->where('exam_name', $examName)
                  ->where('session', $session);
            })
            ->where('status', 'Rejected')
            ->count();
            
        $marksIncreased = ResultRevalidationRequest::whereHas('formFillup', function ($q) use ($examName, $session) {
                $q->where('exam_name', $examName)
                  ->where('session', $session);
            })
            ->where('status', 'Approved')
            ->whereRaw('updated_marks > original_marks')
            ->count();
            
        $marksDecreased = ResultRevalidationRequest::whereHas('formFillup', function ($q) use ($examName, $session) {
                $q->where('exam_name', $examName)
                  ->where('session', $session);
            })
            ->where('status', 'Approved')
            ->whereRaw('updated_marks < original_marks')
            ->count();
            
        $noChange = ResultRevalidationRequest::whereHas('formFillup', function ($q) use ($examName, $session) {
                $q->where('exam_name', $examName)
                  ->where('session', $session);
            })
            ->where('status', 'Approved')
            ->whereRaw('updated_marks = original_marks')
            ->count();
            
        return [
            'total_requests' => $totalRequests,
            'pending_requests' => $pendingRequests,
            'approved_requests' => $approvedRequests,
            'rejected_requests' => $rejectedRequests,
            'marks_increased' => $marksIncreased,
            'marks_decreased' => $marksDecreased,
            'no_change' => $noChange,
            'approval_rate' => $totalRequests > 0 ? round(($approvedRequests / $totalRequests) * 100, 2) : 0,
        ];
    }

    /**
     * Get overall revalidation statistics.
     *
     * @return array
     */
    public function getStatistics()
    {
        $totalRequests = ResultRevalidationRequest::count();
            
        $pendingRequests = ResultRevalidationRequest::where('status', 'Pending')->count();
            
        $approvedRequests = ResultRevalidationRequest::where('status', 'Approved')->count();
            
        $rejectedRequests = ResultRevalidationRequest::where('status', 'Rejected')->count();
            
        $marksIncreased = ResultRevalidationRequest::where('status', 'Approved')
            ->whereRaw('updated_marks > original_marks')
            ->count();
            
        $marksDecreased = ResultRevalidationRequest::where('status', 'Approved')
            ->whereRaw('updated_marks < original_marks')
            ->count();
            
        $noChange = ResultRevalidationRequest::where('status', 'Approved')
            ->whereRaw('updated_marks = original_marks')
            ->count();
            
        // Get statistics by exam
        $examStats = DB::table('result_revalidation_requests')
            ->join('form_fillups', 'result_revalidation_requests.roll_number', '=', 'form_fillups.roll_number')
            ->select(
                'form_fillups.exam_name',
                'form_fillups.session',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN result_revalidation_requests.status = "Pending" THEN 1 ELSE 0 END) as pending'),
                DB::raw('SUM(CASE WHEN result_revalidation_requests.status = "Approved" THEN 1 ELSE 0 END) as approved'),
                DB::raw('SUM(CASE WHEN result_revalidation_requests.status = "Rejected" THEN 1 ELSE 0 END) as rejected')
            )
            ->groupBy('form_fillups.exam_name', 'form_fillups.session')
            ->orderBy('form_fillups.session', 'desc')
            ->orderBy('form_fillups.exam_name', 'asc')
            ->get();
            
        return [
            'total' => $totalRequests,
            'pending' => $pendingRequests,
            'approved' => $approvedRequests,
            'rejected' => $rejectedRequests,
            'marks_increased' => $marksIncreased,
            'marks_decreased' => $marksDecreased,
            'no_change' => $noChange,
            'by_exam' => $examStats
        ];
    }
} 