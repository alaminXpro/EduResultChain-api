<?php

namespace Vanguard\Http\Controllers\Api;

use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Requests\Subject\StoreSubjectRequest;
use Vanguard\Http\Requests\Subject\UpdateSubjectRequest;
use Vanguard\Http\Resources\SubjectResource;
use Vanguard\Http\Resources\SubjectCollection;
use Vanguard\Services\SubjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends ApiController
{
    /**
     * @var SubjectService
     */
    protected $subjectService;

    /**
     * Create a new controller instance.
     *
     * @param SubjectService $subjectService
     * @return void
     */
    public function __construct(SubjectService $subjectService)
    {
        $this->subjectService = $subjectService;
    }

    /**
     * Display a listing of subjects.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Build filters from query parameters
        $filters = [];
        
        // Only add filters that are actually provided in the query string
        foreach (['subject_name', 'subject_category', 'per_page'] as $field) {
            if ($request->query($field) !== null) {
                $filters[$field] = $request->query($field);
            }
        }

        $subjects = $this->subjectService->getAllSubjects($filters);

        return $this->respondWithArray([
            'success' => true,
            'data' => $subjects,
        ]);
    }

    /**
     * Store a newly created subject.
     *
     * @param StoreSubjectRequest $request
     * @return JsonResponse
     */
    public function store(StoreSubjectRequest $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('subjects.manage')) {
            return $this->errorForbidden('You do not have permission to create subjects.');
        }

        try {
            $subject = $this->subjectService->createSubject($request->validated());

            return $this->respondWithArray([
                'success' => true,
                'message' => 'Subject created successfully.',
                'data' => new SubjectResource($subject),
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Display the specified subject.
     *
     * @param int $subjectId
     * @return JsonResponse
     */
    public function show(int $subjectId): JsonResponse
    {
        try {
            $subject = $this->subjectService->getSubject($subjectId);

            return $this->respondWithArray([
                'success' => true,
                'data' => new SubjectResource($subject),
            ]);
        } catch (\Exception $e) {
            return $this->errorNotFound('Subject not found.');
        }
    }

    /**
     * Update the specified subject.
     *
     * @param UpdateSubjectRequest $request
     * @param int $subjectId
     * @return JsonResponse
     */
    public function update(UpdateSubjectRequest $request, int $subjectId): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('subjects.manage')) {
            return $this->errorForbidden('You do not have permission to update subjects.');
        }

        try {
            $subject = $this->subjectService->updateSubject($subjectId, $request->validated());

            return $this->respondWithArray([
                'success' => true,
                'message' => 'Subject updated successfully.',
                'data' => new SubjectResource($subject),
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Remove the specified subject.
     *
     * @param int $subjectId
     * @return JsonResponse
     */
    public function destroy(int $subjectId): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('subjects.manage')) {
            return $this->errorForbidden('You do not have permission to delete subjects.');
        }

        try {
            $this->subjectService->deleteSubject($subjectId);

            return $this->respondWithArray([
                'success' => true,
                'message' => 'Subject deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Get subjects by category.
     *
     * @param string $category
     * @return JsonResponse
     */
    public function getByCategory(string $category): JsonResponse
    {
        try {
            $subjects = $this->subjectService->getSubjectsByCategory($category);

            return $this->respondWithArray([
                'success' => true,
                'data' => SubjectResource::collection($subjects),
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }
} 