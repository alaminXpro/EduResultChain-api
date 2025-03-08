<?php

namespace Vanguard\Services;

use Vanguard\Models\ExamMark;
use Vanguard\Models\FormFillup;
use Vanguard\Models\Subject;
use Vanguard\Models\Result;
use Vanguard\Models\ResultHistory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExamMarkService
{
    /**
     * @var IPFSService
     */
    protected $ipfsService;

    /**
     * Create a new service instance.
     *
     * @param IPFSService $ipfsService
     * @return void
     */
    public function __construct(IPFSService $ipfsService)
    {
        $this->ipfsService = $ipfsService;
    }

    /**
     * Get all exam marks with optional filters.
     *
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public function getAllExamMarks(array $filters = [])
    {
        $query = "
            SELECT 
                em.*,
                s.subject_name,
                s.subject_code,
                u.first_name as entered_by_name,
                ff.exam_name,
                ff.session
            FROM exam_marks em
            INNER JOIN subjects s ON em.subject_id = s.subject_id
            INNER JOIN form_fillups ff ON em.roll_number = ff.roll_number
            LEFT JOIN users u ON em.entered_by = u.id
            WHERE 1=1
        ";

        $bindings = [];

        if (!empty($filters['roll_number'])) {
            $query .= " AND em.roll_number = ?";
            $bindings[] = $filters['roll_number'];
        }

        if (!empty($filters['subject_id'])) {
            $query .= " AND em.subject_id = ?";
            $bindings[] = $filters['subject_id'];
        }

        $query .= " ORDER BY em.created_at DESC";

        $perPage = $filters['per_page'] ?? 15;
        $page = request()->get('page', 1);
        $offset = ($page - 1) * $perPage;

        $query .= " LIMIT ? OFFSET ?";
        $bindings[] = $perPage;
        $bindings[] = $offset;

        $examMarks = DB::select($query, $bindings);
        
        // Convert each object to an array
        return array_map(function($mark) {
            return (array)$mark;
        }, $examMarks);
    }

    /**
     * Create a new exam mark.
     *
     * @param array $data
     * @return array
     */
    public function createExamMark(array $data)
    {
        // Validate form fillup exists
        $formFillupQuery = "SELECT * FROM form_fillups WHERE roll_number = ? LIMIT 1";
        $formFillup = DB::selectOne($formFillupQuery, [$data['roll_number']]);
        if (!$formFillup) {
            throw new ModelNotFoundException('Form fillup not found for the given roll number.');
        }

        // Validate subject exists
        $subjectQuery = "SELECT * FROM subjects WHERE subject_id = ? LIMIT 1";
        $subject = DB::selectOne($subjectQuery, [$data['subject_id']]);
        if (!$subject) {
            throw new ModelNotFoundException('Subject not found.');
        }

        // Calculate grade and grade point
        $gradeInfo = ExamMark::calculateGradeAndPoint($data['marks_obtained']);
        
        // Begin transaction
        return DB::transaction(function () use ($data, $formFillup, $gradeInfo) {
            // Create the exam mark
            $insertQuery = "
                INSERT INTO exam_marks (
                    roll_number, subject_id, marks_obtained, 
                    grade, grade_point, entered_by, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ";
            
            DB::insert($insertQuery, [
                $data['roll_number'],
                $data['subject_id'],
                $data['marks_obtained'],
                $gradeInfo['grade'],
                $gradeInfo['grade_point'],
                Auth::id()
            ]);
            
            $examMarkId = DB::getPdo()->lastInsertId();
            
            // Get the created exam mark
            $selectQuery = "
                SELECT 
                    em.*,
                    s.subject_name,
                    s.subject_code,
                    u.first_name as entered_by_name,
                    ff.exam_name,
                    ff.session
                FROM exam_marks em
                INNER JOIN subjects s ON em.subject_id = s.subject_id
                INNER JOIN form_fillups ff ON em.roll_number = ff.roll_number
                LEFT JOIN users u ON em.entered_by = u.id
                WHERE em.detail_id = ?
                LIMIT 1
            ";
            
            $examMark = DB::selectOne($selectQuery, [$examMarkId]);
            
            // Update or create result
            $this->updateOrCreateResult((object)$formFillup);
            
            return (array)$examMark;
        });
    }

    /**
     * Update an existing exam mark.
     *
     * @param int $detailId
     * @param array $data
     * @return array
     */
    public function updateExamMark(int $detailId, array $data)
    {
        // Find the exam mark
        $examMarkQuery = "
            SELECT em.*, ff.* 
            FROM exam_marks em
            INNER JOIN form_fillups ff ON em.roll_number = ff.roll_number
            WHERE em.detail_id = ?
            LIMIT 1
        ";
        $examMark = DB::selectOne($examMarkQuery, [$detailId]);
        
        if (!$examMark) {
            throw new ModelNotFoundException('Exam mark not found.');
        }
        
        // Calculate grade and grade point if marks_obtained is provided
        if (isset($data['marks_obtained'])) {
            $gradeInfo = ExamMark::calculateGradeAndPoint($data['marks_obtained']);
            $data['grade'] = $gradeInfo['grade'];
            $data['grade_point'] = $gradeInfo['grade_point'];
        }

        // Begin transaction
        return DB::transaction(function () use ($detailId, $data, $examMark) {
            // Update the exam mark
            $updateQuery = "
                UPDATE exam_marks 
                SET marks_obtained = ?,
                    grade = ?,
                    grade_point = ?,
                    entered_by = ?,
                    updated_at = NOW()
                WHERE detail_id = ?
            ";
            
            DB::update($updateQuery, [
                $data['marks_obtained'],
                $data['grade'],
                $data['grade_point'],
                Auth::id(),
                $detailId
            ]);
            
            // Get the updated exam mark
            $selectQuery = "
                SELECT 
                    em.*,
                    s.subject_name,
                    s.subject_code,
                    u.first_name as entered_by_name,
                    ff.exam_name,
                    ff.session
                FROM exam_marks em
                INNER JOIN subjects s ON em.subject_id = s.subject_id
                INNER JOIN form_fillups ff ON em.roll_number = ff.roll_number
                LEFT JOIN users u ON em.entered_by = u.id
                WHERE em.detail_id = ?
                LIMIT 1
            ";
            
            $updatedExamMark = DB::selectOne($selectQuery, [$detailId]);
            
            // Update or create result
            $this->updateOrCreateResult((object)$examMark);
            
            return (array)$updatedExamMark;
        });
    }

    /**
     * Get an exam mark by detail ID.
     *
     * @param int $detailId
     * @return array
     */
    public function getExamMark(int $detailId)
    {
        $query = "
            SELECT 
                em.*,
                s.subject_name,
                s.subject_code,
                u.first_name as entered_by_name,
                ff.exam_name,
                ff.session
            FROM exam_marks em
            INNER JOIN subjects s ON em.subject_id = s.subject_id
            INNER JOIN form_fillups ff ON em.roll_number = ff.roll_number
            LEFT JOIN users u ON em.entered_by = u.id
            WHERE em.detail_id = ?
            LIMIT 1
        ";
        
        $examMark = DB::selectOne($query, [$detailId]);
        
        if (!$examMark) {
            throw new ModelNotFoundException('Exam mark not found.');
        }
        
        return (array)$examMark;
    }

    /**
     * Get all exam marks for a roll number.
     *
     * @param string $rollNumber
     * @return array
     */
    public function getExamMarksByRollNumber(string $rollNumber)
    {
        // Validate form fillup exists
        $formFillupQuery = "SELECT * FROM form_fillups WHERE roll_number = ? LIMIT 1";
        $formFillup = DB::selectOne($formFillupQuery, [$rollNumber]);
        
        if (!$formFillup) {
            throw new ModelNotFoundException('Form fillup not found for the given roll number.');
        }
        
        $query = "
            SELECT 
                em.*,
                s.subject_name,
                s.subject_code,
                u.first_name as entered_by_name,
                ff.exam_name,
                ff.session
            FROM exam_marks em
            INNER JOIN subjects s ON em.subject_id = s.subject_id
            INNER JOIN form_fillups ff ON em.roll_number = ff.roll_number
            LEFT JOIN users u ON em.entered_by = u.id
            WHERE em.roll_number = ?
            ORDER BY em.created_at DESC
        ";
        
        $examMarks = DB::select($query, [$rollNumber]);
        
        // Convert each object to an array
        return array_map(function($mark) {
            return (array)$mark;
        }, $examMarks);
    }

    /**
     * Delete an exam mark.
     *
     * @param int $detailId
     * @return bool
     */
    public function deleteExamMark(int $detailId)
    {
        // Find the exam mark
        $examMarkQuery = "
            SELECT em.*, ff.* 
            FROM exam_marks em
            INNER JOIN form_fillups ff ON em.roll_number = ff.roll_number
            WHERE em.detail_id = ?
            LIMIT 1
        ";
        $examMark = DB::selectOne($examMarkQuery, [$detailId]);
        
        if (!$examMark) {
            throw new ModelNotFoundException('Exam mark not found.');
        }

        // Begin transaction
        return DB::transaction(function () use ($detailId, $examMark) {
            // Delete the exam mark
            $deleteQuery = "DELETE FROM exam_marks WHERE detail_id = ?";
            $deleted = DB::delete($deleteQuery, [$detailId]);
            
            // Update or create result
            $this->updateOrCreateResult((object)$examMark);
            
            return $deleted;
        });
    }

    /**
     * Bulk create exam marks.
     *
     * @param array $marksData
     * @return array
     */
    public function bulkCreateExamMarks(array $marksData)
    {
        $results = [];
        $rollNumbers = [];

        // Begin transaction
        DB::beginTransaction();

        try {
            $insertQuery = "
                INSERT INTO exam_marks (
                    roll_number, subject_id, marks_obtained, 
                    grade, grade_point, entered_by, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ";
            
            foreach ($marksData as $markData) {
                // Calculate grade and grade point
                $gradeInfo = ExamMark::calculateGradeAndPoint($markData['marks_obtained']);
                
                // Insert the exam mark
                DB::insert($insertQuery, [
                    $markData['roll_number'],
                    $markData['subject_id'],
                    $markData['marks_obtained'],
                    $gradeInfo['grade'],
                    $gradeInfo['grade_point'],
                    Auth::id()
                ]);
                
                $examMarkId = DB::getPdo()->lastInsertId();
                
                // Get the created exam mark
                $selectQuery = "
                    SELECT 
                        em.*,
                        s.subject_name,
                        s.subject_code,
                        u.first_name as entered_by_name,
                        ff.exam_name,
                        ff.session
                    FROM exam_marks em
                    INNER JOIN subjects s ON em.subject_id = s.subject_id
                    INNER JOIN form_fillups ff ON em.roll_number = ff.roll_number
                    LEFT JOIN users u ON em.entered_by = u.id
                    WHERE em.detail_id = ?
                    LIMIT 1
                ";
                
                $examMark = DB::selectOne($selectQuery, [$examMarkId]);
                $results[] = (array)$examMark;
                
                // Track unique roll numbers for result updates
                if (!in_array($markData['roll_number'], $rollNumbers)) {
                    $rollNumbers[] = $markData['roll_number'];
                }
            }
            
            // Update results for all affected roll numbers
            foreach ($rollNumbers as $rollNumber) {
                $formFillupQuery = "SELECT * FROM form_fillups WHERE roll_number = ? LIMIT 1";
                $formFillup = DB::selectOne($formFillupQuery, [$rollNumber]);
                if ($formFillup) {
                    $this->updateOrCreateResult((object)$formFillup);
                }
            }
            
            DB::commit();
            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update or create a result for a form fillup.
     *
     * @param object $formFillup
     * @return array
     */
    private function updateOrCreateResult(object $formFillup)
    {
        // Get all exam marks for this form fillup
        $examMarksQuery = "
            SELECT marks_obtained, grade_point 
            FROM exam_marks 
            WHERE roll_number = ?
        ";
        $examMarks = DB::select($examMarksQuery, [$formFillup->roll_number]);
        
        // Calculate total marks and GPA
        $totalMarks = array_sum(array_column((array)$examMarks, 'marks_obtained'));
        $totalSubjects = count($examMarks);
        
        // Check if any subject has a failing grade
        $hasFailed = false;
        foreach ($examMarks as $mark) {
            if ($mark->grade_point == 0) {
                $hasFailed = true;
                break;
            }
        }
        
        // Calculate GPA (average of grade points if no failures, otherwise 0)
        $gpa = 0;
        if ($totalSubjects > 0 && !$hasFailed) {
            $gpa = round(array_sum(array_column((array)$examMarks, 'grade_point')) / $totalSubjects, 2);
            // Cap at 5.00
            $gpa = min($gpa, 5.00);
        }
        
        // Determine status
        $status = $hasFailed ? 'Fail' : 'Pass';
        
        // Calculate grade based on GPA
        $grade = $this->calculateGrade($gpa);
        
        // Create or update the result
        $resultId = $formFillup->exam_name . '_' . $formFillup->session . '_' . $formFillup->roll_number;
        
        $existingResultQuery = "
            SELECT * FROM results 
            WHERE result_id = ?
            LIMIT 1
        ";
        $existingResult = DB::selectOne($existingResultQuery, [$resultId]);
        
        if ($existingResult) {
            // Check if the result was published
            $wasPublished = $existingResult->published ?? false;
            
            // Create audit log if the result was published
            if ($wasPublished) {
                // Log the change in the result history table
                $historyQuery = "
                    INSERT INTO result_histories (
                        result_id, modified_by, modification_type, 
                        previous_data, new_data, previous_ipfs_hash, new_ipfs_hash, timestamp
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ";
                
                $previousData = json_encode([
                    'gpa' => $existingResult->gpa,
                    'grade' => $existingResult->grade,
                    'total_marks' => $existingResult->total_marks,
                    'status' => $existingResult->status,
                    'published' => $existingResult->published,
                    'published_by' => $existingResult->published_by,
                    'published_at' => $existingResult->published_at
                ]);
                
                $newData = json_encode([
                    'gpa' => $gpa,
                    'grade' => $grade,
                    'total_marks' => $totalMarks,
                    'status' => $status,
                    'published' => false,
                    'published_by' => null,
                    'published_at' => null
                ]);
                
                DB::insert($historyQuery, [
                    $resultId,
                    Auth::id(),
                    'marks_update',
                    $previousData,
                    $newData,
                    $existingResult->ipfs_hash, // Previous IPFS hash
                    null // New IPFS hash will be generated later
                ]);
            }
            
            // Update existing result and unpublish it
            $updateQuery = "
                UPDATE results 
                SET gpa = ?,
                    grade = ?,
                    total_marks = ?,
                    status = ?,
                    published = ?,
                    published_by = ?,
                    published_at = ?,
                    updated_at = NOW()
                WHERE result_id = ?
            ";
            
            DB::update($updateQuery, [
                $gpa,
                $grade,
                $totalMarks,
                $status,
                false, // Unpublish the result
                null,  // Clear published_by
                null,  // Clear published_at
                $resultId
            ]);
            
            // Get the updated result
            $result = Result::where('result_id', $resultId)->first();
            
            // Generate new IPFS hash
            if ($result) {
                try {
                    $newHash = $this->ipfsService->storeResultData($result);
                    
                    // Update the result with the new hash
                    $result->update(['ipfs_hash' => $newHash]);
                    
                    // Create an audit log entry for the hash update
                    ResultHistory::create([
                        'result_id' => $resultId,
                        'modified_by' => Auth::id(),
                        'modification_type' => 'hash_update',
                        'previous_data' => json_encode(['status' => $result->status, 'gpa' => $result->gpa]),
                        'new_data' => json_encode(['status' => $result->status, 'gpa' => $result->gpa]),
                        'previous_ipfs_hash' => $existingResult->ipfs_hash,
                        'new_ipfs_hash' => $newHash,
                        'timestamp' => now()
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to generate IPFS hash for result ' . $resultId . ': ' . $e->getMessage());
                }
            }
            
            $selectQuery = "SELECT * FROM results WHERE result_id = ? LIMIT 1";
            return (array)DB::selectOne($selectQuery, [$resultId]);
        } else {
            // Create new result
            $insertQuery = "
                INSERT INTO results (
                    result_id, roll_number, exam_name, session,
                    gpa, grade, total_marks, status,
                    published, published_by, published_at,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ";
            
            DB::insert($insertQuery, [
                $resultId,
                $formFillup->roll_number,
                $formFillup->exam_name,
                $formFillup->session,
                $gpa,
                $grade,
                $totalMarks,
                $status,
                false, // New results are unpublished by default
                null,  // No publisher
                null   // No publish date
            ]);
            
            // Get the new result
            $result = Result::where('result_id', $resultId)->first();
            
            // Generate initial IPFS hash
            if ($result) {
                try {
                    $newHash = $this->ipfsService->storeResultData($result);
                    
                    // Update the result with the hash
                    $result->update(['ipfs_hash' => $newHash]);
                    
                    // Create an audit log entry for the initial hash
                    ResultHistory::create([
                        'result_id' => $resultId,
                        'modified_by' => Auth::id(),
                        'modification_type' => 'initial_hash',
                        'previous_data' => json_encode(['status' => $result->status, 'gpa' => $result->gpa]),
                        'new_data' => json_encode(['status' => $result->status, 'gpa' => $result->gpa]),
                        'previous_ipfs_hash' => null,
                        'new_ipfs_hash' => $newHash,
                        'timestamp' => now()
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to generate initial IPFS hash for result ' . $resultId . ': ' . $e->getMessage());
                }
            }
            
            $selectQuery = "SELECT * FROM results WHERE result_id = ? LIMIT 1";
            return (array)DB::selectOne($selectQuery, [$resultId]);
        }
    }

    /**
     * Calculate grade based on GPA.
     *
     * @param float $gpa
     * @return string
     */
    private function calculateGrade(float $gpa): string
    {
        if ($gpa == 0) return 'F';
        if ($gpa >= 5.00) return 'A+';
        if ($gpa >= 4.00) return 'A';
        if ($gpa >= 3.50) return 'A-';
        if ($gpa >= 3.00) return 'B+';
        if ($gpa >= 2.50) return 'B';
        if ($gpa >= 2.00) return 'C';
        if ($gpa >= 1.00) return 'D';
        return 'F';
    }
} 