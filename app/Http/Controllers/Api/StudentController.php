<?php

namespace Vanguard\Http\Controllers\Api;

use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Requests\Student\StoreStudentRequest;
use Vanguard\Http\Requests\Student\UpdateStudentRequest;
use Vanguard\Http\Resources\StudentResource;
use Vanguard\Http\Resources\StudentCollection;
use Vanguard\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends ApiController
{
    /**
     * @var StudentService
     */
    protected $studentService;

    /**
     * Create a new controller instance.
     *
     * @param StudentService $studentService
     * @return void
     */
    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * Display a listing of students.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('students.manage')) {
            return $this->errorForbidden('You do not have permission to view students.');
        }

        // Build filters from query parameters, not request body
        $filters = [];
        
        // Only add filters that are actually provided in the query string
        foreach (['first_name', 'last_name', 'date_of_birth', 'phone_number', 'email', 'per_page'] as $field) {
            if ($request->query($field) && $request->query($field) !== '') {
                $filters[$field] = $request->query($field);
            }
        }

        // Get students from service
        $students = $this->studentService->getAllStudents($filters);
        
        // Return the response using the controller's respondWithArray method
        return $this->respondWithArray([
            'success' => true,
            'data' => $students,
            'meta' => [
                'total' => count($students),
                'per_page' => $request->query('per_page', 15),
                'current_page' => $request->query('page', 1)
            ]
        ]);
    }

    /**
     * Store a newly created student.
     *
     * @param StoreStudentRequest $request
     * @return JsonResponse
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('students.manage')) {
            return $this->errorForbidden('You do not have permission to create students.');
        }

        try {
            $student = $this->studentService->createStudent($request->validated());

            if (!$student) {
                return $this->errorInternalError('Failed to create student. Please try again.');
            }
            
            // Convert to object if it's an array
            if (is_array($student)) {
                $student = (object) $student;
            }

            return $this->respondWithArray([
                'success' => true,
                'message' => 'Student created successfully.',
                'data' => new StudentResource($student),
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Display the specified student.
     *
     * @param string $registrationNumber
     * @return JsonResponse
     */
    public function show(string $registrationNumber): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('students.manage') && 
            !$this->isOwnStudent($registrationNumber)) {
            return $this->errorForbidden('You do not have permission to view this student.');
        }

        try {
            $student = $this->studentService->getStudent($registrationNumber);
            
            if (!$student) {
                return $this->errorNotFound('Student not found.');
            }
            
            // Convert to object if it's an array
            if (is_array($student)) {
                $student = (object) $student;
            }

            return $this->respondWithArray([
                'success' => true,
                'data' => new StudentResource($student),
            ]);
        } catch (\Exception $e) {
            return $this->errorNotFound('Student not found.');
        }
    }

    /**
     * Update the specified student.
     *
     * @param UpdateStudentRequest $request
     * @param string $registrationNumber
     * @return JsonResponse
     */
    public function update(UpdateStudentRequest $request, string $registrationNumber): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('students.manage')) {
            return $this->errorForbidden('You do not have permission to update students.');
        }

        try {
            $student = $this->studentService->updateStudent($registrationNumber, $request->validated());
            
            if (!$student) {
                return $this->errorNotFound('Student not found.');
            }
            
            // Convert to object if it's an array
            if (is_array($student)) {
                $student = (object) $student;
            }

            return $this->respondWithArray([
                'success' => true,
                'message' => 'Student updated successfully.',
                'data' => new StudentResource($student),
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Remove the specified student.
     *
     * @param string $registrationNumber
     * @return JsonResponse
     */
    public function destroy(string $registrationNumber): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('students.manage')) {
            return $this->errorForbidden('You do not have permission to delete students.');
        }

        try {
            $this->studentService->deleteStudent($registrationNumber);

            return $this->respondWithArray([
                'success' => true,
                'message' => 'Student deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Get students with their results.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStudentsWithResults(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->hasPermission('students.manage') && 
            !auth()->user()->hasPermission('results.view')) {
            return $this->errorForbidden('You do not have permission to view student results.');
        }

        // Build filters from request
        $filters = $request->only([
            'exam_name',
            'session',
            'institution_id',
            'status',
            'per_page',
        ]);

        $students = $this->studentService->getStudentsWithResults($filters);

        return $this->respondWithArray([
            'success' => true,
            'data' => $students,
        ]);
    }

    /**
     * Check if the authenticated user is viewing their own student profile.
     *
     * @param string $registrationNumber
     * @return bool
     */
    private function isOwnStudent(string $registrationNumber): bool
    {
        $user = auth()->user();
        
        if ($user->hasRole('student')) {
            $student = $user->student;
            return $student && $student->registration_number === $registrationNumber;
        }
        
        return false;
    }
} 