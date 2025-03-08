<?php

namespace Vanguard\Services;

use Vanguard\Models\FormFillup;
use Vanguard\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FormFillupService
{
    /**
     * Create a new form fillup.
     *
     * @param array $data
     * @return array|null
     */
    public function createFormFillup(array $data)
    {
        try {
            DB::beginTransaction();
            
            // Check if student exists
            $studentQuery = "SELECT * FROM students WHERE registration_number = ? LIMIT 1";
            $student = DB::selectOne($studentQuery, [$data['registration_number']]);
            
            if (!$student) {
                DB::rollBack();
                Log::error('Student not found with registration number: ' . $data['registration_number']);
                return null;
            }
            
            // Check if student already has a form fillup for this exam and session
            $existingQuery = "
                SELECT * FROM form_fillups 
                WHERE registration_number = ? AND exam_name = ? AND session = ?
                LIMIT 1
            ";
            $existing = DB::selectOne($existingQuery, [
                $data['registration_number'], 
                $data['exam_name'], 
                $data['session']
            ]);
            
            if ($existing) {
                DB::rollBack();
                Log::error('Student already has a form fillup for this exam and session');
                return null;
            }
            
            // Generate roll number with format: YYYYIIISSSS
            // Where YYYY = session year, III = institution ID, SSSS = sequence number
            $session = $data['session']; // e.g., "2024"
            $institutionId = str_pad($data['institution_id'], 3, '0', STR_PAD_LEFT); // e.g., "003"
            
            // Get the next sequence number for this institution, exam, and session
            $sequenceQuery = "
                SELECT COUNT(*) + 1 as next_seq
                FROM form_fillups 
                WHERE exam_name = ? AND session = ? AND institution_id = ?
            ";
            $nextSeq = DB::selectOne($sequenceQuery, [
                $data['exam_name'], 
                $data['session'], 
                $data['institution_id']
            ])->next_seq;
            
            $sequence = str_pad($nextSeq, 4, '0', STR_PAD_LEFT); // e.g., "0001"
            $data['roll_number'] = "{$session}{$institutionId}{$sequence}"; // e.g., "20240030001"
            
            // Create the form fillup
            $insertQuery = "
                INSERT INTO form_fillups (
                    roll_number, registration_number, exam_name, 
                    session, `group`, board_id, institution_id,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ";
            
            $result = DB::insert($insertQuery, [
                $data['roll_number'],
                $data['registration_number'],
                $data['exam_name'],
                $data['session'],
                $data['group'],
                $data['board_id'],
                $data['institution_id']
            ]);
            
            if (!$result) {
                DB::rollBack();
                Log::error('Failed to insert form fillup record');
                return null;
            }
            
            // Get the created form fillup with relationships
            $selectQuery = "
                SELECT 
                    f.*,
                    s.first_name, s.last_name, s.phone_number,
                    b.username as board_name,
                    i.username as institution_name
                FROM form_fillups f
                INNER JOIN students s ON f.registration_number = s.registration_number
                INNER JOIN users b ON f.board_id = b.id
                INNER JOIN users i ON f.institution_id = i.id
                WHERE f.roll_number = ?
                LIMIT 1
            ";
            
            $formFillup = DB::selectOne($selectQuery, [$data['roll_number']]);
            
            if (!$formFillup) {
                DB::rollBack();
                Log::error('Failed to retrieve created form fillup with roll number: ' . $data['roll_number']);
                return null;
            }
            
            DB::commit();
            
            Log::info("Form fillup created with roll number: {$data['roll_number']}");
            
            return (array)$formFillup;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create form fillup: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update a form fillup.
     *
     * @param string $rollNumber
     * @param array $data
     * @return object|null
     */
    public function updateFormFillup(string $rollNumber, array $data)
    {
        try {
            DB::beginTransaction();
            
            // Check if form fillup exists
            $existingQuery = "SELECT * FROM form_fillups WHERE roll_number = ? LIMIT 1";
            $existing = DB::selectOne($existingQuery, [$rollNumber]);
            
            if (!$existing) {
                DB::rollBack();
                throw new \Exception('Form fillup not found with roll number: ' . $rollNumber);
            }
            
            // Define allowed fields
            $allowedFields = ['exam_name', 'session', 'group', 'board_id', 'institution_id'];
            
            // Check for invalid fields
            $invalidFields = array_diff(array_keys($data), $allowedFields);
            if (!empty($invalidFields)) {
                DB::rollBack();
                throw new \Exception('Invalid fields provided: ' . implode(', ', $invalidFields) . '. Allowed fields are: ' . implode(', ', $allowedFields));
            }
            
            // Update the form fillup
            $updateFields = [];
            $updateBindings = [];
            
            foreach ($data as $field => $value) {
                $updateFields[] = "{$field} = ?";
                $updateBindings[] = $value;
            }
            
            if (!empty($updateFields)) {
                $updateFields[] = "updated_at = NOW()";
                
                $updateQuery = "
                    UPDATE form_fillups 
                    SET " . implode(', ', $updateFields) . "
                    WHERE roll_number = ?
                ";
                
                // Add the roll number as the last binding for the WHERE clause
                $updateBindings[] = $rollNumber;
                
                DB::update($updateQuery, $updateBindings);
            } else {
                DB::rollBack();
                throw new \Exception('No valid fields provided for update. Allowed fields are: ' . implode(', ', $allowedFields));
            }
            
            // Get the updated form fillup with relationships
            $selectQuery = "
                SELECT 
                    f.*,
                    s.first_name, s.last_name, s.phone_number,
                    b.username as board_name,
                    i.username as institution_name
                FROM form_fillups f
                INNER JOIN students s ON f.registration_number = s.registration_number
                INNER JOIN users b ON f.board_id = b.id
                INNER JOIN users i ON f.institution_id = i.id
                WHERE f.roll_number = ?
                LIMIT 1
            ";
            
            $formFillup = DB::selectOne($selectQuery, [$rollNumber]);
            
            DB::commit();
            
            return $formFillup;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e; // Re-throw the exception to show the error message to the user
        }
    }
    
    /**
     * Get a form fillup by roll number.
     *
     * @param string $rollNumber
     * @return object|null
     */
    public function getFormFillup(string $rollNumber)
    {
        $query = "
            SELECT 
                f.*,
                s.first_name, s.last_name, s.phone_number,
                b.username as board_name,
                i.username as institution_name
            FROM form_fillups f
            INNER JOIN students s ON f.registration_number = s.registration_number
            INNER JOIN users b ON f.board_id = b.id
            INNER JOIN users i ON f.institution_id = i.id
            WHERE f.roll_number = ?
            LIMIT 1
        ";
        
        $formFillup = DB::selectOne($query, [$rollNumber]);
        
        return $formFillup;
    }
    
    /**
     * Get all form fillups with optional filtering.
     *
     * @param array $filters
     * @return array
     */
    public function getAllFormFillups(array $filters = [])
    {
        $query = "
            SELECT 
                f.*,
                s.first_name, s.last_name, s.phone_number,
                b.username as board_name,
                i.username as institution_name
            FROM form_fillups f
            INNER JOIN students s ON f.registration_number = s.registration_number
            INNER JOIN users b ON f.board_id = b.id
            INNER JOIN users i ON f.institution_id = i.id
            WHERE 1=1
        ";
        
        $bindings = [];
        
        // Apply filters
        if (isset($filters['exam_name'])) {
            $query .= " AND f.exam_name = ?";
            $bindings[] = $filters['exam_name'];
        }
        
        if (isset($filters['session'])) {
            $query .= " AND f.session = ?";
            $bindings[] = $filters['session'];
        }
        
        if (isset($filters['group'])) {
            $query .= " AND f.group = ?";
            $bindings[] = $filters['group'];
        }
        
        if (isset($filters['board_id'])) {
            $query .= " AND f.board_id = ?";
            $bindings[] = $filters['board_id'];
        }
        
        if (isset($filters['institution_id'])) {
            $query .= " AND f.institution_id = ?";
            $bindings[] = $filters['institution_id'];
        }
        
        if (isset($filters['registration_number'])) {
            $query .= " AND f.registration_number = ?";
            $bindings[] = $filters['registration_number'];
        }
        
        if (isset($filters['student_name'])) {
            $query .= " AND (s.first_name LIKE ? OR s.last_name LIKE ?)";
            $bindings[] = '%' . $filters['student_name'] . '%';
            $bindings[] = '%' . $filters['student_name'] . '%';
        }
        
        // Add pagination
        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 15;
        $offset = ($page - 1) * $perPage;
        
        $query .= " ORDER BY f.created_at DESC LIMIT ? OFFSET ?";
        $bindings[] = (int)$perPage;
        $bindings[] = (int)$offset;
        
        $formFillups = DB::select($query, $bindings);
        
        // Return the objects directly, don't convert to arrays
        return $formFillups ?: [];
    }
    
    /**
     * Delete a form fillup.
     *
     * @param string $rollNumber
     * @return bool
     */
    public function deleteFormFillup(string $rollNumber)
    {
        try {
            DB::beginTransaction();
            
            // Check if form fillup exists
            $existingQuery = "SELECT * FROM form_fillups WHERE roll_number = ? LIMIT 1";
            $existing = DB::selectOne($existingQuery, [$rollNumber]);
            
            if (!$existing) {
                DB::rollBack();
                throw new \Exception('Form fillup not found with roll number: ' . $rollNumber);
            }
            
            // Check if form fillup has exam marks
            $marksQuery = "SELECT COUNT(*) as count FROM exam_marks WHERE roll_number = ?";
            $hasMarks = DB::selectOne($marksQuery, [$rollNumber])->count > 0;
            
            if ($hasMarks) {
                DB::rollBack();
                throw new \Exception('Cannot delete form fillup because it has associated exam marks. Please delete the exam marks first.');
            }
            
            // Delete any associated results first
            $deleteResultQuery = "DELETE FROM results WHERE roll_number = ?";
            DB::delete($deleteResultQuery, [$rollNumber]);
            
            // Delete the form fillup
            $deleteFormFillupQuery = "DELETE FROM form_fillups WHERE roll_number = ?";
            $deleted = DB::delete($deleteFormFillupQuery, [$rollNumber]);
            
            if ($deleted === 0) {
                DB::rollBack();
                throw new \Exception('Failed to delete form fillup. No rows were affected.');
            }
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e; // Re-throw the exception to show the error message to the user
        }
    }
    
    /**
     * Get form fillups by institution with statistics.
     *
     * @param int $institutionId
     * @param string $examName
     * @param string $session
     * @return array
     */
    public function getInstitutionStatistics(int $institutionId, string $examName, string $session)
    {
        // First, let's check if there are multiple groups for this institution
        $groupsQuery = "
            SELECT DISTINCT f.group
            FROM form_fillups f
            WHERE 
                f.institution_id = ?
                AND f.exam_name = ?
                AND f.session = ?
        ";
        
        $groups = DB::select($groupsQuery, [$institutionId, $examName, $session]);
        \Log::info('Institution groups: ' . json_encode($groups));
        \Log::info('Number of groups: ' . count($groups));
        
        // Using raw SQL for complex query with aggregate functions and joins
        $query = "
            SELECT 
                f.group,
                COUNT(f.roll_number) AS total_students,
                SUM(CASE WHEN r.status = 'Pass' THEN 1 ELSE 0 END) AS passed_students,
                SUM(CASE WHEN r.status = 'Fail' THEN 1 ELSE 0 END) AS failed_students,
                ROUND((SUM(CASE WHEN r.status = 'Pass' THEN 1 ELSE 0 END) / NULLIF(COUNT(f.roll_number), 0)) * 100, 2) AS pass_percentage,
                AVG(r.gpa) AS average_gpa,
                MAX(r.gpa) AS highest_gpa
            FROM 
                form_fillups f
            LEFT JOIN 
                results r ON f.roll_number = r.roll_number
            WHERE 
                f.institution_id = ?
                AND f.exam_name = ?
                AND f.session = ?
            GROUP BY 
                f.group
            ORDER BY 
                pass_percentage DESC
        ";
        
        $statistics = DB::select($query, [$institutionId, $examName, $session]);
        
        // Log the results for debugging
        \Log::info('Institution statistics results: ' . json_encode($statistics));
        \Log::info('Number of statistics records: ' . count($statistics));
        
        // If there are no statistics but there are groups, create empty statistics for each group
        if (count($statistics) === 0 && count($groups) > 0) {
            $statistics = [];
            foreach ($groups as $group) {
                $statistics[] = (object)[
                    'group' => $group->group,
                    'total_students' => 0,
                    'passed_students' => 0,
                    'failed_students' => 0,
                    'pass_percentage' => 0,
                    'average_gpa' => 0,
                    'highest_gpa' => 0
                ];
            }
        }
        
        return $statistics;
    }
    
    /**
     * Get students with missing marks.
     *
     * @param string $examName
     * @param string $session
     * @return array
     */
    public function getStudentsWithMissingMarks(string $examName, string $session)
    {
        // Using raw SQL for complex query with LEFT JOIN and IS NULL
        $query = "
            SELECT 
                f.roll_number,
                s.first_name,
                s.last_name,
                f.group,
                sub.subject_id,
                sub.subject_name,
                sub.subject_category
            FROM 
                form_fillups f
            INNER JOIN 
                students s ON f.registration_number = s.registration_number
            CROSS JOIN 
                subjects sub
            LEFT JOIN 
                exam_marks em ON f.roll_number = em.roll_number AND sub.subject_id = em.subject_id
            WHERE 
                f.exam_name = ?
                AND f.session = ?
                AND em.detail_id IS NULL
                AND (
                    (f.group = 'Science' AND sub.subject_category IN ('compulsory', 'science'))
                    OR (f.group = 'Commerce' AND sub.subject_category IN ('compulsory', 'commerce'))
                    OR (f.group = 'Arts' AND sub.subject_category IN ('compulsory', 'arts'))
                )
            ORDER BY 
                f.roll_number, sub.subject_name
        ";
        
        return DB::select($query, [$examName, $session]);
    }
} 