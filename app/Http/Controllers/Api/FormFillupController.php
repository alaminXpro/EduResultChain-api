<?php

namespace Vanguard\Http\Controllers\Api;

use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Requests\FormFillup\StoreFormFillupRequest;
use Vanguard\Http\Requests\FormFillup\UpdateFormFillupRequest;
use Vanguard\Http\Resources\FormFillupResource;
use Vanguard\Http\Resources\FormFillupCollection;
use Vanguard\Services\FormFillupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormFillupController extends ApiController
{
    /**
     * @var FormFillupService
     */
    protected $formFillupService;

    /**
     * Create a new controller instance.
     *
     * @param FormFillupService $formFillupService
     * @return void
     */
    public function __construct(FormFillupService $formFillupService)
    {
        $this->formFillupService = $formFillupService;
    }

    /**
     * Display a listing of form fillups.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('form.fillup.manage')) {
            return $this->errorForbidden('You do not have permission to view form fillups.');
        }

        // Build filters from query parameters, not request body
        $filters = [];
        
        // Only add filters that are actually provided in the query string
        foreach (['exam_name', 'session', 'group', 'institution_id', 'per_page'] as $field) {
            if ($request->query($field) && $request->query($field) !== '') {
                $filters[$field] = $request->query($field);
            }
        }

        // Apply user-specific filters
        $user = auth()->user();
        
        if ($user->hasRole('institution')) {
            $filters['institution_id'] = $user->id;
        }

        $formFillups = $this->formFillupService->getAllFormFillups($filters);

        return $this->respondWithArray([
            'success' => true,
            'data' => new FormFillupCollection($formFillups),
        ]);
    }

    /**
     * Store a newly created form fillup.
     *
     * @param StoreFormFillupRequest $request
     * @return JsonResponse
     */
    public function store(StoreFormFillupRequest $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('form.fillup.manage')) {
            return $this->errorForbidden('You do not have permission to create form fillups.');
        }

        try {
            $data = $request->validated();
            
            // If user is an institution, set the institution_id automatically
            if (auth()->user()->hasRole('Institution')) {
                $data['institution_id'] = auth()->id();
            }
            
            // If user is a board, set the board_id automatically
            if (auth()->user()->hasRole('Board')) {
                $data['board_id'] = auth()->id();
            }
            
            $formFillup = $this->formFillupService->createFormFillup($data);

            if (!$formFillup) {
                return $this->errorInternalError('Failed to create form fillup. Please try again.');
            }

            return $this->respondWithArray([
                'success' => true,
                'message' => 'Form fillup created successfully.',
                'data' => new FormFillupResource((object)$formFillup),
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Display the specified form fillup.
     *
     * @param string $rollNumber
     * @return JsonResponse
     */
    public function show(string $rollNumber): JsonResponse
    {
        // Check if user can view this form fillup
        if (!$this->canViewFormFillup($rollNumber)) {
            return $this->errorForbidden('You do not have permission to view this form fillup.');
        }

        try {
            $formFillup = $this->formFillupService->getFormFillup($rollNumber);

            return $this->respondWithArray([
                'success' => true,
                'data' => new FormFillupResource($formFillup),
            ]);
        } catch (\Exception $e) {
            return $this->errorNotFound('Form fillup not found.');
        }
    }

    /**
     * Update the specified form fillup.
     *
     * @param UpdateFormFillupRequest $request
     * @param string $rollNumber
     * @return JsonResponse
     */
    public function update(UpdateFormFillupRequest $request, string $rollNumber): JsonResponse
    {
        // Check if user can edit this form fillup
        if (!$this->canEditFormFillup($rollNumber)) {
            return $this->errorForbidden('You do not have permission to update this form fillup.');
        }

        try {
            $formFillup = $this->formFillupService->updateFormFillup($rollNumber, $request->validated());

            return $this->respondWithArray([
                'success' => true,
                'message' => 'Form fillup updated successfully.',
                'data' => new FormFillupResource($formFillup),
            ]);
        } catch (\Exception $e) {
            // Return a more specific error message
            return $this->errorBadRequest($e->getMessage());
        }
    }

    /**
     * Remove the specified form fillup.
     *
     * @param string $rollNumber
     * @return JsonResponse
     */
    public function destroy(string $rollNumber): JsonResponse
    {
        // Check if user can edit this form fillup
        if (!$this->canEditFormFillup($rollNumber)) {
            return $this->errorForbidden('You do not have permission to delete this form fillup.');
        }

        try {
            $this->formFillupService->deleteFormFillup($rollNumber);

            return $this->respondWithArray([
                'success' => true,
                'message' => 'Form fillup deleted successfully.',
            ]);
        } catch (\Exception $e) {
            // Return a more specific error message
            return $this->errorBadRequest($e->getMessage());
        }
    }

    /**
     * Get institution statistics.
     *
     * @param int $institutionId
     * @param Request $request
     * @return JsonResponse
     */
    public function getInstitutionStatistics(int $institutionId, Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('institution.statistics')) {
            return $this->errorForbidden('You do not have permission to view institution statistics.');
        }

        // Validate request
        $request->validate([
            'exam_name' => 'required|string',
            'session' => 'required|string',
        ]);

        try {
            $statistics = $this->formFillupService->getInstitutionStatistics(
                $institutionId,
                $request->exam_name,
                $request->session
            );

            return $this->respondWithArray([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Get students with missing marks.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStudentsWithMissingMarks(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('form.fillup.manage')) {
            return $this->errorForbidden('You do not have permission to view students with missing marks.');
        }

        // Validate request
        $request->validate([
            'exam_name' => 'required|string',
            'session' => 'required|string',
        ]);

        try {
            $students = $this->formFillupService->getStudentsWithMissingMarks(
                $request->exam_name,
                $request->session
            );

            return $this->respondWithArray([
                'success' => true,
                'data' => $students,
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Check if the authenticated user can view a form fillup.
     *
     * @param string $rollNumber
     * @return bool
     */
    private function canViewFormFillup(string $rollNumber): bool
    {
        $user = auth()->user();

        // Admin can view all form fillups
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Board members can view all form fillups
        if ($user->hasRole('Board') && $user->hasPermission('form.fillup.manage')) {
            return true;
        }

        // Institution can only view their students' form fillups
        if ($user->hasRole('Institution')) {
            try {
                $formFillup = $this->formFillupService->getFormFillup($rollNumber);
                if (!$formFillup) {
                    return false;
                }
                
                // Handle both object and array formats
                $institutionId = is_array($formFillup) ? $formFillup['institution_id'] : $formFillup->institution_id;
                return $institutionId === $user->institution_id;
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Check if the authenticated user can edit a form fillup.
     *
     * @param string $rollNumber
     * @return bool
     */
    private function canEditFormFillup(string $rollNumber): bool
    {
        $user = auth()->user();

        // Admin can edit all form fillups
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Board members can edit all form fillups
        if ($user->hasRole('Board') && $user->hasPermission('form.fillup.manage')) {
            return true;
        }

        // Institution can only edit their students' form fillups
        if ($user->hasRole('Institution') && $user->hasPermission('form.fillup.manage')) {
            try {
                $formFillup = $this->formFillupService->getFormFillup($rollNumber);
                if (!$formFillup) {
                    return false;
                }
                
                // Handle both object and array formats
                $institutionId = is_array($formFillup) ? $formFillup['institution_id'] : $formFillup->institution_id;
                return $institutionId === $user->institution_id;
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }
} 