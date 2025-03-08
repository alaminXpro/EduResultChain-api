<?php

namespace Vanguard\Services;

use Vanguard\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubjectService
{
    /**
     * Create a new subject.
     *
     * @param array $data
     * @return array|null
     */
    public function createSubject(array $data)
    {
        try {
            DB::beginTransaction();
            
            // Create the subject
            $insertQuery = "
                INSERT INTO subjects (
                    subject_name, subject_category, created_at, updated_at
                ) VALUES (?, ?, NOW(), NOW())
            ";
            
            DB::insert($insertQuery, [
                $data['subject_name'],
                $data['subject_category']
            ]);
            
            $subjectId = DB::getPdo()->lastInsertId();
            
            // Get the created subject
            $selectQuery = "
                SELECT * FROM subjects 
                WHERE subject_id = ?
                LIMIT 1
            ";
            
            $subject = DB::selectOne($selectQuery, [$subjectId]);
            
            DB::commit();
            
            return (array)$subject;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create subject: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update an existing subject.
     *
     * @param int $subjectId
     * @param array $data
     * @return array|null
     */
    public function updateSubject(int $subjectId, array $data)
    {
        try {
            DB::beginTransaction();
            
            // Check if subject exists
            $existingQuery = "SELECT * FROM subjects WHERE subject_id = ? LIMIT 1";
            $existing = DB::selectOne($existingQuery, [$subjectId]);
            
            if (!$existing) {
                DB::rollBack();
                return null;
            }
            
            // Update the subject
            $updateQuery = "
                UPDATE subjects 
                SET subject_name = ?,
                    subject_category = ?,
                    updated_at = NOW()
                WHERE subject_id = ?
            ";
            
            DB::update($updateQuery, [
                $data['subject_name'],
                $data['subject_category'],
                $subjectId
            ]);
            
            // Get the updated subject
            $selectQuery = "
                SELECT * FROM subjects 
                WHERE subject_id = ?
                LIMIT 1
            ";
            
            $subject = DB::selectOne($selectQuery, [$subjectId]);
            
            DB::commit();
            
            return (array)$subject;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update subject: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get a subject by ID.
     *
     * @param int $subjectId
     * @return array|null
     */
    public function getSubject(int $subjectId)
    {
        $query = "
            SELECT * FROM subjects 
            WHERE subject_id = ?
            LIMIT 1
        ";
        
        $subject = DB::selectOne($query, [$subjectId]);
        
        return $subject ? (array)$subject : null;
    }
    
    /**
     * Get all subjects with optional filtering.
     *
     * @param array $filters
     * @return array
     */
    public function getAllSubjects(array $filters = [])
    {
        $query = "
            SELECT * FROM subjects 
            WHERE 1=1
        ";
        
        $bindings = [];
        
        // Apply subject_category filter
        if (isset($filters['subject_category']) && $filters['subject_category'] !== '') {
            $query .= " AND subject_category = ?";
            $bindings[] = $filters['subject_category'];
        }
        
        // Apply subject_name filter
        if (isset($filters['subject_name']) && $filters['subject_name'] !== '') {
            $query .= " AND subject_name LIKE ?";
            $bindings[] = '%' . $filters['subject_name'] . '%';
        }
        
        // Default sorting
        $query .= " ORDER BY subject_category ASC, subject_name ASC";
        
        // Add pagination
        $perPage = $filters['per_page'] ?? 15;
        $page = request()->query('page', 1);
        $offset = ($page - 1) * $perPage;
        
        $query .= " LIMIT ? OFFSET ?";
        $bindings[] = (int)$perPage;
        $bindings[] = (int)$offset;
        
        return DB::select($query, $bindings);
    }
    
    /**
     * Delete a subject.
     *
     * @param int $subjectId
     * @return bool
     */
    public function deleteSubject(int $subjectId)
    {
        try {
            DB::beginTransaction();
            
            // Check if subject exists
            $existingQuery = "SELECT * FROM subjects WHERE subject_id = ? LIMIT 1";
            $existing = DB::selectOne($existingQuery, [$subjectId]);
            
            if (!$existing) {
                DB::rollBack();
                return false;
            }
            
            // Check if subject is being used in exam marks
            $usageQuery = "SELECT COUNT(*) as count FROM exam_marks WHERE subject_id = ?";
            $isUsed = DB::selectOne($usageQuery, [$subjectId])->count > 0;
            
            if ($isUsed) {
                DB::rollBack();
                return false;
            }
            
            // Delete the subject
            $deleteQuery = "DELETE FROM subjects WHERE subject_id = ?";
            $deleted = DB::delete($deleteQuery, [$subjectId]);
            
            DB::commit();
            
            return $deleted > 0;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete subject: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get subjects with performance statistics.
     *
     * @param string $examName
     * @param string $session
     * @return array
     */
    public function getSubjectsWithStats(string $examName, string $session)
    {
        // Using raw SQL for complex query with aggregate functions
        $query = "
            SELECT 
                s.subject_id,
                s.subject_name,
                s.subject_category,
                COUNT(em.detail_id) AS total_students,
                AVG(em.marks_obtained) AS average_marks,
                MAX(em.marks_obtained) AS highest_marks,
                MIN(em.marks_obtained) AS lowest_marks,
                SUM(CASE WHEN em.grade_point > 0 THEN 1 ELSE 0 END) AS passed_students,
                SUM(CASE WHEN em.grade_point = 0 THEN 1 ELSE 0 END) AS failed_students,
                ROUND((SUM(CASE WHEN em.grade_point > 0 THEN 1 ELSE 0 END) / COUNT(em.detail_id)) * 100, 2) AS pass_percentage
            FROM 
                subjects s
            LEFT JOIN 
                exam_marks em ON s.subject_id = em.subject_id
            LEFT JOIN 
                form_fillups f ON em.roll_number = f.roll_number
            WHERE 
                f.exam_name = ? AND f.session = ?
            GROUP BY 
                s.subject_id, s.subject_name, s.subject_category
            ORDER BY 
                pass_percentage ASC
        ";
        
        return DB::select($query, [$examName, $session]);
    }
    
    /**
     * Get subjects by category with count of students.
     *
     * @return array
     */
    public function getSubjectsByCategory()
    {
        // Using raw SQL with subquery and grouping
        $query = "
            SELECT 
                s.subject_category,
                COUNT(s.subject_id) AS subject_count,
                (
                    SELECT COUNT(DISTINCT em.roll_number)
                    FROM exam_marks em
                    JOIN subjects s2 ON em.subject_id = s2.subject_id
                    WHERE s2.subject_category = s.subject_category
                ) AS student_count
            FROM 
                subjects s
            GROUP BY 
                s.subject_category
            ORDER BY 
                subject_count DESC
        ";
        
        return DB::select($query);
    }
} 