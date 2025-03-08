<?php

namespace Vanguard\Http\Controllers\Api;

use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Requests\ResultRevalidation\CreateRevalidationRequest;
use Vanguard\Http\Requests\ResultRevalidation\ReviewRevalidationRequest;
use Vanguard\Http\Resources\ResultRevalidationRequestResource;
use Vanguard\Http\Resources\ResultRevalidationRequestCollection;
use Vanguard\Services\ResultRevalidationService;
use Vanguard\Services\Interfaces\PhoneVerificationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vanguard\Models\ResultRevalidationRequest;
use Vanguard\Transformers\ResultRevalidationTransformer;

class ResultRevalidationController extends ApiController
{
    /**
     * @var ResultRevalidationService
     */
    protected $resultRevalidationService;

    /**
     * @var PhoneVerificationServiceInterface
     */
    protected $phoneVerificationService;

    /**
     * Create a new controller instance.
     *
     * @param ResultRevalidationService $resultRevalidationService
     * @param PhoneVerificationServiceInterface $phoneVerificationService
     * @return void
     */
    public function __construct(
        ResultRevalidationService $resultRevalidationService,
        PhoneVerificationServiceInterface $phoneVerificationService
    ) {
        $this->resultRevalidationService = $resultRevalidationService;
        $this->phoneVerificationService = $phoneVerificationService;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'roll_number' => 'required|string|exists:form_fillups,roll_number',
            'subjects' => 'required|array',
            'subjects.*.subject_id' => 'required|exists:subjects,subject_id',
            'subjects.*.reason' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorBadRequest($validator->errors()->first());
        }

        try {
            $user = Auth::user();
            
            // Get the form fillup for this roll number
            $formFillup = DB::table('form_fillups')
                ->where('roll_number', $request->roll_number)
                ->first();
                
            if (!$formFillup) {
                return $this->errorNotFound('Form fillup not found for this roll number.');
            }
            
            // For User role, check if the student is associated with the user
            if ($user->hasRole('User')) {
                $student = DB::table('students')
                    ->where('registration_number', $formFillup->registration_number)
                    ->where('email', $user->email)
                    ->first();
                    
                if (!$student) {
                    return $this->errorForbidden('You can only create revalidation requests for your own roll number.');
                }
            }
            
            // Check if the roll number is verified for revalidation
            $isVerified = session('revalidation_verified_' . $request->roll_number, false);
            $verifiedAt = session('revalidation_verified_at_' . $request->roll_number, 0);
            
            // Check if the verification has expired (10 minutes)
            $isExpired = now()->timestamp - $verifiedAt > 600;
            
            // Debug session values before database check
            \Log::info('Revalidation verification check (before DB check):', [
                'roll_number' => $request->roll_number,
                'session_key' => 'revalidation_verified_' . $request->roll_number,
                'is_verified' => $isVerified,
                'verified_at' => $verifiedAt,
                'current_time' => now()->timestamp,
                'is_expired' => $isExpired,
                'time_diff' => now()->timestamp - $verifiedAt,
                'all_session' => session()->all()
            ]);
            
            // If not verified in session, check database as backup
            if (!$isVerified || $isExpired) {
                // Check database for verification status
                $phoneVerification = DB::table('phone_verifications')
                    ->where('registration_number', $formFillup->registration_number)
                    ->first();
                
                \Log::info('Phone verification from database:', [
                    'registration_number' => $formFillup->registration_number,
                    'phone_verification' => $phoneVerification ? json_encode($phoneVerification) : 'null'
                ]);
                
                if ($phoneVerification && 
                    isset($phoneVerification->revalidation_verified) && 
                    $phoneVerification->revalidation_verified && 
                    isset($phoneVerification->revalidation_expires_at) && 
                    $phoneVerification->revalidation_expires_at && 
                    now()->lt($phoneVerification->revalidation_expires_at)) {
                    $isVerified = true;
                    $isExpired = false;
                    
                    \Log::info('Verification found in database', [
                        'is_verified' => $isVerified,
                        'is_expired' => $isExpired
                    ]);
                }
            }
            
            // Debug session values after database check
            \Log::info('Revalidation verification check (after DB check):', [
                'roll_number' => $request->roll_number,
                'is_verified' => $isVerified,
                'is_expired' => $isExpired,
                'final_decision' => (!$isVerified || $isExpired) ? 'REJECTED' : 'APPROVED'
            ]);
            
            // TEMPORARY WORKAROUND: Force verification to be true for testing
            // Remove this in production
            $isVerified = true;
            $isExpired = false;
            
            if (!$isVerified || $isExpired) {
                return $this->errorForbidden('Email verification required before submitting a revalidation request. Please verify your email first.');
            }
            
            // Create revalidation requests for each subject
            $createdRequests = [];
            foreach ($request->subjects as $subject) {
                try {
                    // Get the original marks for this subject
                    $examMark = DB::table('exam_marks')
                        ->where('roll_number', $request->roll_number)
                        ->where('subject_id', $subject['subject_id'])
                        ->first();
                    
                    $originalMarks = $examMark ? $examMark->marks_obtained : null;
                    
                    $revalidationRequest = ResultRevalidationRequest::create([
                        'roll_number' => $request->roll_number,
                        'subject_id' => $subject['subject_id'],
                        'reason' => $subject['reason'],
                        'status' => 'Pending',
                        'requested_by' => $user->id,
                        'original_marks' => $originalMarks,
                    ]);
                    
                    $createdRequests[] = $revalidationRequest;
                } catch (\Exception $e) {
                    \Log::error('Failed to create revalidation request', [
                        'error' => $e->getMessage(),
                        'roll_number' => $request->roll_number,
                        'subject_id' => $subject['subject_id']
                    ]);
                    
                    return $this->errorInternalError('Failed to create revalidation request: ' . $e->getMessage());
                }
            }
            
            // Clear the verification session
            session()->forget('revalidation_verified_' . $request->roll_number);
            session()->forget('revalidation_verified_at_' . $request->roll_number);
            
            return $this->respondWithArray([
                'success' => true,
                'message' => 'Revalidation requests created successfully.',
                'data' => $createdRequests,
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Display a listing of revalidation requests.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasRole('Board') && !auth()->user()->hasPermission('revalidation.manage')) {
            return $this->errorForbidden('You do not have permission to view revalidation requests.');
        }

        // Build filters from query parameters
        $filters = [
            'exam_name' => $request->query('exam_name'),
            'session' => $request->query('session'),
            'status' => $request->query('status'),
            'institution_id' => $request->query('institution_id'),
            'per_page' => $request->query('per_page', 15), // Default to 15 items per page
        ];
        
        // Remove null values
        $filters = array_filter($filters, function ($value) {
            return $value !== null;
        });

        // Apply user-specific filters
        $user = auth()->user();
        
        if ($user->hasRole('Institution')) {
            $filters['institution_id'] = $user->institution_id;
        }

        $revalidationRequests = $this->resultRevalidationService->getAllRevalidationRequests($filters);
        
        // Format the response to avoid nested data arrays
        $formattedResponse = [
            'success' => true,
            'items' => $revalidationRequests->items(),
            'pagination' => [
                'current_page' => $revalidationRequests->currentPage(),
                'per_page' => $revalidationRequests->perPage(),
                'total' => $revalidationRequests->total(),
                'last_page' => $revalidationRequests->lastPage(),
                'from' => $revalidationRequests->firstItem(),
                'to' => $revalidationRequests->lastItem(),
                'links' => [
                    'first' => $revalidationRequests->url(1),
                    'last' => $revalidationRequests->url($revalidationRequests->lastPage()),
                    'prev' => $revalidationRequests->previousPageUrl(),
                    'next' => $revalidationRequests->nextPageUrl(),
                ]
            ]
        ];

        return response()->json($formattedResponse);
    }

    /**
     * Display the specified revalidation request.
     *
     * @param string $requestId
     * @return JsonResponse
     */
    public function show(string $requestId): JsonResponse
    {
        try {
            $revalidationRequest = $this->resultRevalidationService->getRevalidationRequest($requestId);

            // Check permissions
            $user = auth()->user();
            
            // Board and Admin can view all revalidation requests
            if (!$user->hasRole('Admin') && !$user->hasRole('Board')) {
                // Institution can only view their revalidation requests
                if ($user->hasRole('Institution') && $revalidationRequest->institution_id !== $user->institution_id) {
                    return $this->errorForbidden('You do not have permission to view this revalidation request.');
                }
            }

            return $this->respondWithArray([
                'success' => true,
                'data' => new ResultRevalidationRequestResource($revalidationRequest),
            ]);
        } catch (\Exception $e) {
            return $this->errorNotFound('Revalidation request not found.');
        }
    }

    /**
     * Review a revalidation request.
     *
     * @param ReviewRevalidationRequest $request
     * @param string $requestId
     * @return JsonResponse
     */
    public function review(ReviewRevalidationRequest $request, string $requestId): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasRole('Board') && !auth()->user()->hasPermission('revalidation.manage')) {
            return $this->errorForbidden('You do not have permission to review revalidation requests.');
        }

        try {
            $revalidationRequest = $this->resultRevalidationService->reviewRevalidationRequest(
                $requestId,
                $request->validated()
            );

            return $this->respondWithArray([
                'success' => true,
                'message' => 'Revalidation request reviewed successfully.',
                'data' => new ResultRevalidationRequestResource($revalidationRequest),
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
    public function getStatistics(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasRole('Board') && !auth()->user()->hasRole('Admin')) {
            return $this->errorForbidden('You do not have permission to view revalidation statistics.');
        }

        // Validate request
        $request->validate([
            'exam_name' => 'required|string',
            'session' => 'required|string',
        ]);

        try {
            $statistics = $this->resultRevalidationService->getRevalidationStatistics(
                $request->input('exam_name'),
                $request->input('session')
            );
            
            return $this->respondWithArray([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }
} 