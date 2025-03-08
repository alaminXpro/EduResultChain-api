<?php

namespace Vanguard\Http\Controllers\Api;

use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Requests\ExamMark\StoreExamMarkRequest;
use Vanguard\Http\Requests\ExamMark\UpdateExamMarkRequest;
use Vanguard\Http\Requests\ExamMark\BulkCreateExamMarkRequest;
use Vanguard\Http\Resources\ExamMarkResource;
use Vanguard\Http\Resources\ExamMarkCollection;
use Vanguard\Services\ExamMarkService;
use Vanguard\Services\FormFillupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamMarkController extends ApiController
{
    /**
     * @var ExamMarkService
     */
    protected $examMarkService;

    /**
     * @var FormFillupService
     */
    protected $formFillupService;

    /**
     * Create a new controller instance.
     *
     * @param ExamMarkService $examMarkService
     * @param FormFillupService $formFillupService
     * @return void
     */
    public function __construct(ExamMarkService $examMarkService, FormFillupService $formFillupService)
    {
        $this->examMarkService = $examMarkService;
        $this->formFillupService = $formFillupService;
    }

    /**
     * Display a listing of exam marks.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('exam.marks.manage')) {
            return $this->errorForbidden('You do not have permission to view exam marks.');
        }

        // Build filters from request
        $filters = $request->only([
            'roll_number',
            'subject_id',
            'per_page',
        ]);

        $examMarks = $this->examMarkService->getAllExamMarks($filters);

        return $this->respondWithArray([
            'success' => true,
            'data' => new ExamMarkCollection($examMarks),
        ]);
    }

    /**
     * Store a newly created exam mark.
     *
     * @param StoreExamMarkRequest $request
     * @return JsonResponse
     */
    public function store(StoreExamMarkRequest $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('exam.marks.manage')) {
            return $this->errorForbidden('You do not have permission to create exam marks.');
        }

        try {
            $examMark = $this->examMarkService->createExamMark($request->validated());

            return $this->respondWithArray([
                'success' => true,
                'message' => 'Exam mark created successfully.',
                'data' => new ExamMarkResource($examMark),
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Display the specified exam mark.
     *
     * @param int $detailId
     * @return JsonResponse
     */
    public function show(int $detailId): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('exam.marks.manage')) {
            return $this->errorForbidden('You do not have permission to view exam marks.');
        }

        try {
            $examMark = $this->examMarkService->getExamMark($detailId);

            return $this->respondWithArray([
                'success' => true,
                'data' => new ExamMarkResource($examMark),
            ]);
        } catch (\Exception $e) {
            return $this->errorNotFound('Exam mark not found.');
        }
    }

    /**
     * Update the specified exam mark.
     *
     * @param UpdateExamMarkRequest $request
     * @param int $detailId
     * @return JsonResponse
     */
    public function update(UpdateExamMarkRequest $request, int $detailId): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('exam.marks.manage')) {
            return $this->errorForbidden('You do not have permission to update exam marks.');
        }

        try {
            $examMark = $this->examMarkService->updateExamMark($detailId, $request->validated());

            return $this->respondWithArray([
                'success' => true,
                'message' => 'Exam mark updated successfully.',
                'data' => new ExamMarkResource($examMark),
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Remove the specified exam mark.
     *
     * @param int $detailId
     * @return JsonResponse
     */
    public function destroy(int $detailId): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('exam.marks.manage')) {
            return $this->errorForbidden('You do not have permission to delete exam marks.');
        }

        try {
            $this->examMarkService->deleteExamMark($detailId);

            return $this->respondWithArray([
                'success' => true,
                'message' => 'Exam mark deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Get exam marks by roll number.
     *
     * @param string $rollNumber
     * @return JsonResponse
     */
    public function getByRollNumber(string $rollNumber): JsonResponse
    {
        // Check if user can view this student's marks
        if (!$this->canViewStudentMarks($rollNumber)) {
            return $this->errorForbidden('You do not have permission to view this student\'s marks.');
        }

        try {
            $examMarks = $this->examMarkService->getExamMarksByRollNumber($rollNumber);

            return $this->respondWithArray([
                'success' => true,
                'data' => ExamMarkResource::collection($examMarks),
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Bulk create exam marks.
     *
     * @param BulkCreateExamMarkRequest $request
     * @return JsonResponse
     */
    public function bulkCreate(BulkCreateExamMarkRequest $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('exam.marks.manage')) {
            return $this->errorForbidden('You do not have permission to create exam marks.');
        }

        try {
            $examMarks = $this->examMarkService->bulkCreateExamMarks($request->validated()['marks']);

            return $this->respondWithArray([
                'success' => true,
                'message' => count($examMarks) . ' exam marks created successfully.',
                'data' => ExamMarkResource::collection($examMarks),
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Check if the authenticated user can view a student's marks.
     *
     * @param string $rollNumber
     * @return bool
     */
    private function canViewStudentMarks(string $rollNumber): bool
    {
        $user = auth()->user();

        // Admin can view all marks
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Board members can view all marks
        if ($user->hasRole('Board') && $user->hasPermission('exam.marks.manage')) {
            return true;
        }

        // Institution can only view their students' marks
        if ($user->hasRole('Institution')) {
            try {
                $formFillup = $this->formFillupService->getFormFillup($rollNumber);
                return $formFillup->institution_id === $user->institution_id;
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }
} 