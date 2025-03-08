<?php

namespace Vanguard\Http\Controllers\Api;

use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Resources\ResultResource;
use Vanguard\Http\Resources\ResultCollection;
use Vanguard\Http\Resources\StatisticsResource;
use Vanguard\Http\Resources\InstitutionStatisticsResource;
use Vanguard\Services\ResultService;
use Vanguard\Services\FormFillupService;
use Vanguard\Services\Interfaces\PhoneVerificationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResultController extends ApiController
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
     * @var PhoneVerificationServiceInterface
     */
    protected $phoneVerificationService;

    /**
     * Create a new controller instance.
     *
     * @param ResultService $resultService
     * @param FormFillupService $formFillupService
     * @param PhoneVerificationServiceInterface $phoneVerificationService
     * @return void
     */
    public function __construct(
        ResultService $resultService,
        FormFillupService $formFillupService,
        PhoneVerificationServiceInterface $phoneVerificationService
    ) {
        $this->resultService = $resultService;
        $this->formFillupService = $formFillupService;
        $this->phoneVerificationService = $phoneVerificationService;
    }

    /**
     * Display a listing of results.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('results.view') && !auth()->user()->hasRole('Board')) {
            return $this->errorForbidden('You do not have permission to view results.');
        }

        // Build filters from query parameters
        $filters = [];
        
        // Only add filters that are actually provided in the query string
        foreach ([
            'exam_name', 
            'session', 
            'institution_id', 
            'published', 
            'status', 
            'per_page',
            'roll_number',
            'student_name'
        ] as $param) {
            if ($request->query($param) !== null) {
                $filters[$param] = $request->query($param);
            }
        }

        // Apply user-specific filters
        $user = auth()->user();
        
        if ($user->hasRole('Institution')) {
            $filters['institution_id'] = $user->institution_id;
        }

        $results = $this->resultService->getAllResults($filters);
        $resultCollection = new ResultCollection($results);

        // Return the response with the collection
        return $this->respondWithArray([
            'success' => true,
            'data' => $resultCollection->toArray($request),
            'pagination' => $resultCollection->with($request)['pagination'] ?? null,
        ]);
    }

    /**
     * Display the specified result.
     *
     * @param string $resultId
     * @return JsonResponse
     */
    public function show(string $resultId): JsonResponse
    {
        try {
            $result = $this->resultService->getResult($resultId);

            // Check permissions
            if (!$this->canViewResult($result)) {
                return $this->errorForbidden('You do not have permission to view this result.');
            }

            return $this->respondWithArray([
                'success' => true,
                'data' => new ResultResource($result),
            ]);
        } catch (\Exception $e) {
            return $this->errorNotFound('Result not found.');
        }
    }

    /**
     * Get results by roll number.
     *
     * @param string $rollNumber
     * @param Request $request
     * @return JsonResponse
     */
    public function getByRollNumber(string $rollNumber, Request $request): JsonResponse
    {
        try {
            // First check if there are any results for this roll number
            $results = $this->resultService->getResultsByRollNumber($rollNumber);
            
            if ($results->isEmpty()) {
                return $this->errorNotFound('No results found for this roll number.');
            }
            
            // Now check if the form fillup exists and if the user has permission
            try {
                $formFillup = $this->formFillupService->getFormFillup($rollNumber);
                
                // Check permissions
                $user = auth()->user();
                
                // Board and Admin can view all results
                if (!$user->hasRole('Admin') && !$user->hasRole('Board')) {
                    // Institution can only view their students' results
                    if ($user->hasRole('Institution') && $formFillup->institution_id !== $user->institution_id) {
                        return $this->errorForbidden('You do not have permission to view this student\'s results.');
                    }
                }
            } catch (\Exception $e) {
                // If form fillup doesn't exist but results do, allow access for admin/board
                $user = auth()->user();
                if (!$user->hasRole('Admin') && !$user->hasRole('Board')) {
                    return $this->errorForbidden('You do not have permission to view this student\'s results.');
                }
            }
            
            $resultCollection = new ResultCollection($results);
            
            return $this->respondWithArray([
                'success' => true,
                'data' => $resultCollection->toArray($request),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error retrieving results for roll number ' . $rollNumber . ': ' . $e->getMessage());
            return $this->errorInternalError('An error occurred while retrieving results.');
        }
    }

    /**
     * Publish results.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function publish(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('results.publish')) {
            return $this->errorForbidden('You do not have permission to publish results.');
        }

        // Validate request
        $request->validate([
            'result_ids' => 'required|array',
            'result_ids.*' => 'required|string',
        ]);

        try {
            $resultIds = $request->input('result_ids');
            $publishedResults = $this->resultService->publishResults($resultIds);
            $resultCollection = new ResultCollection($publishedResults);
            
            return $this->respondWithArray([
                'success' => true,
                'message' => count($publishedResults) . ' results published successfully.',
                'data' => $resultCollection->toArray($request),
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Unpublish results.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unpublish(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('results.unpublish')) {
            return $this->errorForbidden('You do not have permission to unpublish results.');
        }

        // Validate request
        $request->validate([
            'result_ids' => 'required|array',
            'result_ids.*' => 'required|string',
        ]);

        try {
            $resultIds = $request->input('result_ids');
            $unpublishedResults = $this->resultService->unpublishResults($resultIds);
            $resultCollection = new ResultCollection($unpublishedResults);
            
            return $this->respondWithArray([
                'success' => true,
                'message' => count($unpublishedResults) . ' results unpublished successfully.',
                'data' => $resultCollection->toArray($request),
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Get result statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStatistics(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('results.statistics')) {
            return $this->errorForbidden('You do not have permission to view result statistics.');
        }

        // Validate request
        $request->validate([
            'exam_name' => 'required|string',
            'session' => 'required|string',
        ]);

        try {
            // Log the request parameters
            \Log::info('Statistics request parameters: ' . json_encode($request->all()));
            
            $examName = $request->query('exam_name');
            $session = $request->query('session');
            
            if (!$examName || !$session) {
                return $this->errorBadRequest('Missing required parameters: exam_name and session are required.');
            }
            
            $statistics = $this->resultService->getResultStatistics($examName, $session);
            $statisticsResource = new StatisticsResource($statistics);
            
            return $this->respondWithArray([
                'success' => true,
                'data' => $statisticsResource->toArray($request),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting statistics: ' . $e->getMessage());
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Get institution-specific result statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getInstitutionStatistics(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('institution.statistics')) {
            return $this->errorForbidden('You do not have permission to view institution statistics.');
        }

        // Validate request
        $validator = \Validator::make($request->all(), [
            'exam_name' => 'required|string',
            'session' => 'required|string',
            'institution_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->errorBadRequest($validator->errors()->first());
        }

        try {
            // Log the request parameters
            \Log::info('Institution statistics request parameters: ' . json_encode($request->all()));
            
            $examName = $request->query('exam_name');
            $session = $request->query('session');
            $institutionId = $request->query('institution_id');
            
            if (!$examName || !$session || !$institutionId) {
                return $this->errorBadRequest('Missing required parameters: exam_name, session, and institution_id are required.');
            }
            
            // Check if the user is an institution and can only view their own statistics
            $user = auth()->user();
            if ($user->hasRole('Institution') && $user->id != $institutionId) {
                return $this->errorForbidden('You can only view statistics for your own institution.');
            }
            
            $statistics = $this->resultService->getInstitutionResultStatistics(
                $examName,
                $session,
                (int)$institutionId
            );
            
            // Check if statistics is empty
            if (is_object($statistics) && !isset($statistics->institution_id)) {
                return $this->errorNotFound('No statistics found for this institution.');
            }
            
            if (is_array($statistics) && empty($statistics)) {
                return $this->errorNotFound('No statistics found for this institution.');
            }
            
            if (is_object($statistics) && method_exists($statistics, 'isEmpty') && $statistics->isEmpty()) {
                return $this->errorNotFound('No statistics found for this institution.');
            }
            
            $statisticsResource = new InstitutionStatisticsResource($statistics);
            
            return $this->respondWithArray([
                'success' => true,
                'data' => $statisticsResource->toArray($request),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting institution statistics: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return $this->errorInternalError('An error occurred while retrieving institution statistics.');
        }
    }

    /**
     * Recalculate results.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recalculate(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasRole('Board') && !auth()->user()->hasRole('Admin')) {
            return $this->errorForbidden('You do not have permission to recalculate results.');
        }

        // Validate request
        $request->validate([
            'roll_numbers' => 'required|array',
            'roll_numbers.*' => 'required|string',
        ]);

        try {
            $rollNumbers = $request->input('roll_numbers');
            $recalculatedResults = $this->resultService->recalculateResults($rollNumbers);
            $resultCollection = new ResultCollection($recalculatedResults);
            
            return $this->respondWithArray([
                'success' => true,
                'message' => count($recalculatedResults) . ' results recalculated successfully.',
                'data' => $resultCollection->toArray($request),
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Check if the authenticated user can view a result.
     *
     * @param mixed $result
     * @return bool
     */
    private function canViewResult($result): bool
    {
        $user = auth()->user();

        // Admin can view all results
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Board members can view all results
        if ($user->hasRole('Board')) {
            return true;
        }

        // Institution can only view their students' results
        if ($user->hasRole('Institution')) {
            try {
                $formFillup = $this->formFillupService->getFormFillup($result->roll_number);
                return $formFillup->institution_id === $user->institution_id;
            } catch (\Exception $e) {
                return false;
            }
        }

        // For public access, check if the result is published
        if (!$user) {
            return $result->published;
        }

        return false;
    }
} 