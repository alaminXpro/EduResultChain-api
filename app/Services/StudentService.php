<?php

namespace Vanguard\Services;

use Vanguard\Models\Student;
use Vanguard\Models\PhoneVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class StudentService
{
    /**
     * Create a new student record.
     *
     * @param array $data
     * @return object|null
     */
    public function createStudent(array $data)
    {
        try {
            DB::beginTransaction();
            
            // Generate registration number with current year prefix
            $currentYear = date('Y');
            $registrationQuery = "
                SELECT COALESCE(
                    MAX(CAST(SUBSTRING(registration_number, 5) AS UNSIGNED)), 
                    0
                ) + 1 as next_reg
                FROM students 
                WHERE registration_number LIKE ?
            ";
            
            $nextReg = DB::selectOne($registrationQuery, [$currentYear . '%'])->next_reg;
            $data['registration_number'] = $currentYear . str_pad($nextReg, 4, '0', STR_PAD_LEFT);
            
            // Create the student
            $insertQuery = "
                INSERT INTO students (
                    registration_number, first_name, last_name, 
                    date_of_birth, father_name, mother_name,
                    phone_number, email, image,
                    permanent_address, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ";
            
            $result = DB::insert($insertQuery, [
                $data['registration_number'],
                $data['first_name'],
                $data['last_name'],
                $data['date_of_birth'],
                $data['father_name'],
                $data['mother_name'],
                $data['phone_number'],
                $data['email'] ?? null,
                $data['image'] ?? null,
                $data['permanent_address']
            ]);

            if (!$result) {
                DB::rollBack();
                Log::error('Failed to insert student record');
                return null;
            }
            
            // Generate a verification code
            $verificationCode = $this->generateVerificationCode();
            
            // Create phone verification record
            $verificationQuery = "
                INSERT INTO phone_verifications (
                    registration_number, phone_number,
                    verification_code, code_expires_at,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, NOW(), NOW())
            ";
            
            $verificationResult = DB::insert($verificationQuery, [
                $data['registration_number'],
                $data['phone_number'],
                $verificationCode,
                now()->addHours(24)
            ]);

            if (!$verificationResult) {
                DB::rollBack();
                Log::error('Failed to create phone verification record');
                return null;
            }
            
            // Get the created student
            $selectQuery = "
                SELECT s.*, pv.verified, pv.verified_at 
                FROM students s
                LEFT JOIN phone_verifications pv ON s.registration_number = pv.registration_number
                WHERE s.registration_number = ?
                LIMIT 1
            ";
            
            $student = DB::selectOne($selectQuery, [$data['registration_number']]);
            
            if (!$student) {
                DB::rollBack();
                Log::error('Failed to retrieve created student');
                return null;
            }

            DB::commit();
            
            // In a real application, you would send the verification code via SMS here
            // For now, we'll just log it
            Log::info("Verification code for student {$data['registration_number']}: {$verificationCode}");
            
            return $student;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create student: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update an existing student record.
     *
     * @param string $registrationNumber
     * @param array $data
     * @return object|null
     */
    public function updateStudent(string $registrationNumber, array $data)
    {
        try {
            DB::beginTransaction();
            
            // Check if student exists and get current phone number
            $existingQuery = "
                SELECT * FROM students 
                WHERE registration_number = ?
                LIMIT 1
            ";
            $existing = DB::selectOne($existingQuery, [$registrationNumber]);
            
            if (!$existing) {
                DB::rollBack();
                return null;
            }
            
            // Check if phone number is being updated
            $phoneChanged = isset($data['phone_number']) && $data['phone_number'] !== $existing->phone_number;
            
            // Build update query dynamically
            $updateFields = [];
            $bindings = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, [
                    'first_name', 'last_name', 'date_of_birth',
                    'father_name', 'mother_name', 'phone_number',
                    'email', 'image', 'permanent_address'
                ])) {
                    $updateFields[] = "{$field} = ?";
                    $bindings[] = $value;
                }
            }
            
            if (!empty($updateFields)) {
                $updateFields[] = "updated_at = NOW()";
                $bindings[] = $registrationNumber;
                
                $updateQuery = "
                    UPDATE students 
                    SET " . implode(', ', $updateFields) . "
                    WHERE registration_number = ?
                ";
                
                DB::update($updateQuery, $bindings);
            }
            
            // If phone number changed, update verification status
            if ($phoneChanged) {
                $verificationCode = $this->generateVerificationCode();
                
                // Check if verification record exists
                $verificationExistsQuery = "
                    SELECT * FROM phone_verifications 
                    WHERE registration_number = ?
                    LIMIT 1
                ";
                $verificationExists = DB::selectOne($verificationExistsQuery, [$registrationNumber]);
                
                if ($verificationExists) {
                    // Update existing verification record
                    $updateVerificationQuery = "
                        UPDATE phone_verifications 
                        SET phone_number = ?,
                            verification_code = ?,
                            verified = false,
                            verified_at = NULL,
                            code_expires_at = ?,
                            updated_at = NOW()
                        WHERE registration_number = ?
                    ";
                    
                    DB::update($updateVerificationQuery, [
                        $data['phone_number'],
                        $verificationCode,
                        now()->addHours(24),
                        $registrationNumber
                    ]);
                } else {
                    // Create new verification record
                    $insertVerificationQuery = "
                        INSERT INTO phone_verifications (
                            registration_number, phone_number,
                            verification_code, code_expires_at,
                            created_at, updated_at
                        ) VALUES (?, ?, ?, ?, NOW(), NOW())
                    ";
                    
                    DB::insert($insertVerificationQuery, [
                        $registrationNumber,
                        $data['phone_number'],
                        $verificationCode,
                        now()->addHours(24)
                    ]);
                }
                
                // In a real application, you would send the verification code via SMS here
                Log::info("New verification code for student {$registrationNumber}: {$verificationCode}");
            }
            
            // Get the updated student
            $selectQuery = "
                SELECT 
                    s.*,
                    pv.verified,
                    pv.verified_at
                FROM students s
                LEFT JOIN phone_verifications pv ON s.registration_number = pv.registration_number
                WHERE s.registration_number = ?
                LIMIT 1
            ";
            
            $student = DB::selectOne($selectQuery, [$registrationNumber]);
            
            DB::commit();
            
            return $student;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update student: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get a student by registration number.
     *
     * @param string $registrationNumber
     * @return object|null
     */
    public function getStudent(string $registrationNumber)
    {
        $query = "
            SELECT 
                s.*,
                pv.verified,
                pv.verified_at
            FROM students s
            LEFT JOIN phone_verifications pv ON s.registration_number = pv.registration_number
            WHERE s.registration_number = ?
            LIMIT 1
        ";
        
        $student = DB::selectOne($query, [$registrationNumber]);
        
        return $student;
    }
    
    /**
     * Get all students with optional filtering.
     *
     * @param array $filters
     * @return array
     */
    public function getAllStudents(array $filters = [])
    {
        // Base query
        $query = "
            SELECT 
                s.*,
                pv.verified,
                pv.verified_at
            FROM students s
            LEFT JOIN phone_verifications pv ON s.registration_number = pv.registration_number
        ";
        
        $whereConditions = [];
        $bindings = [];
        
        // Add filter conditions - the controller should only send filters that are actually provided
        if (isset($filters['first_name'])) {
            $whereConditions[] = "s.first_name LIKE ?";
            $bindings[] = "%{$filters['first_name']}%";
        }
        
        if (isset($filters['last_name'])) {
            $whereConditions[] = "s.last_name LIKE ?";
            $bindings[] = "%{$filters['last_name']}%";
        }
        
        if (isset($filters['date_of_birth'])) {
            $whereConditions[] = "s.date_of_birth = ?";
            $bindings[] = $filters['date_of_birth'];
        }
        
        if (isset($filters['phone_number'])) {
            $whereConditions[] = "s.phone_number = ?";
            $bindings[] = $filters['phone_number'];
        }
        
        if (isset($filters['email'])) {
            $whereConditions[] = "s.email = ?";
            $bindings[] = $filters['email'];
        }
        
        // Add WHERE clause if we have conditions
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        // Add ordering
        $query .= " ORDER BY s.created_at DESC";
        
        // Add pagination if requested
        if (isset($filters['per_page'])) {
            $perPage = (int)$filters['per_page'];
            $page = (int)request()->get('page', 1);
            $offset = ($page - 1) * $perPage;
            
            $query .= " LIMIT ? OFFSET ?";
            $bindings[] = $perPage;
            $bindings[] = $offset;
        }
        
        // Execute the query and return the results
        return DB::select($query, $bindings);
    }
    
    /**
     * Delete a student record.
     *
     * @param string $registrationNumber
     * @return bool
     */
    public function deleteStudent(string $registrationNumber)
    {
        try {
            DB::beginTransaction();
            
            // Check if student exists
            $existingQuery = "SELECT * FROM students WHERE registration_number = ? LIMIT 1";
            $existing = DB::selectOne($existingQuery, [$registrationNumber]);
            
            if (!$existing) {
                DB::rollBack();
                return false;
            }
            
            // Check if student has any form fillups
            $formFillupQuery = "SELECT COUNT(*) as count FROM form_fillups WHERE registration_number = ?";
            $hasFormFillups = DB::selectOne($formFillupQuery, [$registrationNumber])->count > 0;
            
            if ($hasFormFillups) {
                DB::rollBack();
                return false;
            }
            
            // Delete phone verification record first
            $deleteVerificationQuery = "DELETE FROM phone_verifications WHERE registration_number = ?";
            DB::delete($deleteVerificationQuery, [$registrationNumber]);
            
            // Delete the student
            $deleteStudentQuery = "DELETE FROM students WHERE registration_number = ?";
            $deleted = DB::delete($deleteStudentQuery, [$registrationNumber]);
            
            DB::commit();
            
            return $deleted > 0;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete student: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify a student's phone number.
     *
     * @param string $registrationNumber
     * @param string $verificationCode
     * @return array
     */
    public function verifyPhoneNumber(string $registrationNumber, string $verificationCode)
    {
        try {
            DB::beginTransaction();
            
            // Using raw SQL to call the stored procedure
            $result = DB::select('
                CALL verify_phone_number(?, ?, @success, @message);
                SELECT @success as success, @message as message;
            ', [$registrationNumber, $verificationCode]);
            
            DB::commit();
            
            // Extract result from procedure
            $success = $result[0]->success ?? false;
            $message = $result[0]->message ?? 'Unknown error occurred';
            
            return [
                'success' => (bool) $success,
                'message' => $message
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to verify phone number: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'An error occurred during verification'
            ];
        }
    }
    
    /**
     * Generate a random verification code.
     *
     * @return string
     */
    private function generateVerificationCode(): string
    {
        return Str::random(6);
    }
    
    /**
     * Get students with exam results using complex join.
     *
     * @param array $filters
     * @return array
     */
    public function getStudentsWithResults(array $filters = [])
    {
        $examName = $filters['exam_name'] ?? null;
        $session = $filters['session'] ?? null;
        
        // Using raw SQL for complex query with multiple joins
        $query = "
            SELECT 
                s.registration_number,
                s.first_name,
                s.last_name,
                f.roll_number,
                f.exam_name,
                f.session,
                f.group,
                r.gpa,
                r.grade,
                r.total_marks,
                r.status,
                r.published
            FROM 
                students s
            INNER JOIN 
                form_fillups f ON s.registration_number = f.registration_number
            LEFT JOIN 
                results r ON f.roll_number = r.roll_number
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($examName) {
            $query .= " AND f.exam_name = ?";
            $params[] = $examName;
        }
        
        if ($session) {
            $query .= " AND f.session = ?";
            $params[] = $session;
        }
        
        $query .= " ORDER BY s.first_name, s.last_name";
        
        return DB::select($query, $params);
    }
} 