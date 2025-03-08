<?php

namespace Vanguard\Services;

use Vanguard\Models\Result;
use Vanguard\Models\ResultHistory;
use Vanguard\Models\FormFillup;
use Vanguard\Models\ExamMark;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Vanguard\Services\IPFSService;

class ResultService
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
     * Get a result by result ID.
     *
     * @param string $resultId
     * @return Result
     */
    public function getResult(string $resultId)
    {
        return Result::with(['formFillup', 'formFillup.student', 'publishedBy', 'examMarks', 'examMarks.subject'])
            ->findOrFail($resultId);
    }

    /**
     * Get a result by roll number.
     *
     * @param string $rollNumber
     * @return Result
     */
    public function getResultByRollNumber(string $rollNumber)
    {
        return Result::with(['formFillup', 'formFillup.student', 'publishedBy', 'examMarks', 'examMarks.subject'])
            ->where('roll_number', $rollNumber)
            ->firstOrFail();
    }

    /**
     * Get all results for a roll number.
     *
     * @param string $rollNumber
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getResultsByRollNumber(string $rollNumber)
    {
        // Log the query for debugging
        \Log::info('Searching for results with roll number: ' . $rollNumber);
        
        $query = Result::with(['formFillup', 'formFillup.student', 'publishedBy', 'examMarks', 'examMarks.subject'])
            ->where('roll_number', $rollNumber);
            
        // Log the SQL query
        \Log::info('SQL query: ' . $query->toSql());
        \Log::info('SQL bindings: ' . json_encode($query->getBindings()));
        
        $results = $query->get();
        
        // Log the result count
        \Log::info('Found ' . $results->count() . ' results for roll number ' . $rollNumber);
        
        return $results;
    }

    /**
     * Get all results with optional filtering.
     *
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllResults(array $filters = [])
    {
        $query = Result::with(['formFillup', 'formFillup.student', 'publishedBy']);
        
        // Apply filters
        if (isset($filters['exam_name'])) {
            $query->where('exam_name', $filters['exam_name']);
        }
        
        if (isset($filters['session'])) {
            $query->where('session', $filters['session']);
        }
        
        if (isset($filters['institution_id'])) {
            $query->whereHas('formFillup', function ($q) use ($filters) {
                $q->where('institution_id', $filters['institution_id']);
            });
        }
        
        if (isset($filters['published'])) {
            // Convert string to boolean for published filter
            $published = filter_var($filters['published'], FILTER_VALIDATE_BOOLEAN);
            $query->where('published', $published);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        // Add search by roll number
        if (isset($filters['roll_number'])) {
            $query->where('roll_number', 'like', '%' . $filters['roll_number'] . '%');
        }
        
        // Add search by student name
        if (isset($filters['student_name'])) {
            $query->whereHas('formFillup.student', function ($q) use ($filters) {
                $q->where('first_name', 'like', '%' . $filters['student_name'] . '%')
                  ->orWhere('last_name', 'like', '%' . $filters['student_name'] . '%');
            });
        }
        
        // Default sort by created_at desc
        $query->orderBy('created_at', 'desc');
        
        // Paginate results
        $perPage = isset($filters['per_page']) ? (int)$filters['per_page'] : 15;
        return $query->paginate($perPage);
    }

    /**
     * Publish results for a specific exam and session.
     *
     * @param array $resultIds
     * @return array Published results
     */
    public function publishResults(array $resultIds)
    {
        // Start a database transaction
        return DB::transaction(function () use ($resultIds) {
            $user = Auth::user();
            $now = Carbon::now();
            $publishedResults = [];
            
            // Format the result IDs for the SQL query
            $placeholders = implode(',', array_map(function($id) {
                return "'" . $id . "'";
            }, $resultIds));
            
            // Use raw SQL to publish results in bulk
            $updateSql = "
                UPDATE results
                SET 
                    published = 1,
                    published_by = ?,
                    published_at = ?
                WHERE 
                    result_id IN ($placeholders)
                    AND published = 0
            ";
            
            // Execute the update
            DB::update($updateSql, [$user->id, $now]);
            
            // Fetch the updated results as Result model objects
            $results = Result::whereIn('result_id', $resultIds)
                ->where('published', 1)
                ->where('published_by', $user->id)
                ->where('published_at', $now)
                ->get();
            
            // Process each published result
            foreach ($results as $result) {
                // Generate IPFS hash for the result
                $resultHash = $this->ipfsService->storeResultData($result);
                
                // Store the previous hash
                $previousHash = $result->ipfs_hash;
                
                // Update the IPFS hash
                $result->update(['ipfs_hash' => $resultHash]);
                
                // Create an audit log entry
                ResultHistory::create([
                    'result_id' => $result->result_id,
                    'modified_by' => $user->id,
                    'modification_type' => 'publication',
                    'previous_data' => json_encode(['published' => false]),
                    'new_data' => json_encode([
                        'published' => true,
                        'published_by' => $user->id,
                        'published_at' => $now
                    ]),
                    'previous_ipfs_hash' => $previousHash,
                    'new_ipfs_hash' => $resultHash,
                    'timestamp' => $now
                ]);
                
                $publishedResults[] = $result;
            }
            
            return $publishedResults;
        });
    }

    /**
     * Unpublish results.
     *
     * @param array $resultIds
     * @return array Unpublished results
     */
    public function unpublishResults(array $resultIds)
    {
        // Start a database transaction
        return DB::transaction(function () use ($resultIds) {
            $user = Auth::user();
            $now = Carbon::now();
            $unpublishedResults = [];
            
            // Format the result IDs for the SQL query
            $placeholders = implode(',', array_map(function($id) {
                return "'" . $id . "'";
            }, $resultIds));
            
            // Get all published results with the given IDs
            $results = Result::whereIn('result_id', $resultIds)
                ->where('published', true)
                ->get();
            
            if ($results->isEmpty()) {
                return [];
            }
            
            // Use raw SQL to unpublish results in bulk
            $updateSql = "
                UPDATE results
                SET 
                    published = 0,
                    published_by = NULL,
                    published_at = NULL
                WHERE 
                    result_id IN ($placeholders)
                    AND published = 1
            ";
            
            // Execute the update
            DB::update($updateSql, []);
            
            // Process each unpublished result for audit
            foreach ($results as $result) {
                // Store the previous state for audit
                $previousData = [
                    'published' => true,
                    'published_at' => $result->published_at,
                    'published_by' => $result->published_by
                ];
                
                // Create an audit log entry
                ResultHistory::create([
                    'result_id' => $result->result_id,
                    'modified_by' => $user->id,
                    'modification_type' => 'unpublication',
                    'previous_data' => json_encode($previousData),
                    'new_data' => json_encode(['published' => false]),
                    'previous_ipfs_hash' => $result->ipfs_hash,
                    'new_ipfs_hash' => $result->ipfs_hash, // Same hash since we're just changing publication status
                    'timestamp' => $now
                ]);
                
                $unpublishedResults[] = $result->refresh();
            }
            
            return $unpublishedResults;
        });
    }

    /**
     * Update result IPFS hashes in batch
     *
     * @param string $examName
     * @param string $session
     * @return int Number of results updated
     */
    public function updateResultHashes(string $examName, string $session)
    {
        // Start a database transaction
        return DB::transaction(function () use ($examName, $session) {
            // Get all results for the exam and session
            $results = Result::where('exam_name', $examName)
                ->where('session', $session)
                ->get();
            
            if ($results->isEmpty()) {
                return 0;
            }
            
            $count = 0;
            $user = Auth::user();
            $now = Carbon::now();
            
            // Generate IPFS hashes for all results
            $hashMap = $this->ipfsService->storeBatchResults($results->all());
            
            // Update each result with its new hash
            foreach ($results as $result) {
                if (isset($hashMap[$result->result_id]) && $hashMap[$result->result_id]) {
                    $previousHash = $result->ipfs_hash;
                    $newHash = $hashMap[$result->result_id];
                    
                    // Update the result
                    $result->update([
                        'ipfs_hash' => $newHash
                    ]);
                    
                    // Create an audit log entry
                    ResultHistory::create([
                        'result_id' => $result->result_id,
                        'modified_by' => $user->id,
                        'modification_type' => 'hash_update',
                        'previous_data' => json_encode(['status' => $result->status, 'gpa' => $result->gpa]),
                        'new_data' => json_encode(['status' => $result->status, 'gpa' => $result->gpa]),
                        'previous_ipfs_hash' => $previousHash,
                        'new_ipfs_hash' => $newHash,
                        'timestamp' => $now
                    ]);
                    
                    $count++;
                }
            }
            
            return $count;
        });
    }

    /**
     * Verify result integrity against IPFS hash
     *
     * @param string $resultId
     * @return array
     */
    public function verifyResultIntegrity(string $resultId)
    {
        try {
            $result = $this->getResult($resultId);
            
            if (!$result->ipfs_hash) {
                return [
                    'verified' => false,
                    'message' => 'No IPFS hash found for this result'
                ];
            }
            
            $isVerified = $this->ipfsService->verifyResultIntegrity($result, $result->ipfs_hash);
            
            return [
                'verified' => $isVerified,
                'message' => $isVerified ? 'Result integrity verified' : 'Result integrity verification failed',
                'result_id' => $resultId,
                'ipfs_hash' => $result->ipfs_hash,
                'timestamp' => now()->toIso8601String()
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'verified' => false,
                'message' => 'Result not found'
            ];
        } catch (\Exception $e) {
            return [
                'verified' => false,
                'message' => 'Error verifying result: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get result statistics for a specific exam and session.
     *
     * @param string $examName
     * @param string $session
     * @return array
     */
    public function getResultStatistics(string $examName, string $session)
    {
        // Log the parameters for debugging
        \Log::info('Getting result statistics for exam: ' . $examName . ', session: ' . $session);
        
        // Use raw SQL for better performance
        $sql = "
            SELECT 
                COUNT(r.result_id) AS total_students,
                SUM(CASE WHEN r.status = 'Pass' THEN 1 ELSE 0 END) AS pass_count,
                SUM(CASE WHEN r.status = 'Fail' THEN 1 ELSE 0 END) AS fail_count,
                ROUND((SUM(CASE WHEN r.status = 'Pass' THEN 1 ELSE 0 END) / NULLIF(COUNT(r.result_id), 0)) * 100, 2) AS pass_percentage,
                AVG(r.gpa) AS average_gpa,
                MAX(r.gpa) AS highest_gpa,
                MIN(CASE WHEN r.status = 'Pass' THEN r.gpa ELSE NULL END) AS lowest_passing_gpa,
                COUNT(DISTINCT f.institution_id) AS total_institutions
            FROM 
                results r
            INNER JOIN form_fillups f ON r.roll_number = f.roll_number
            WHERE 
                r.exam_name = ?
                AND r.session = ?
        ";
        
        // Log the SQL query
        \Log::info('Overall stats SQL: ' . $sql);
        \Log::info('SQL bindings: ' . json_encode([$examName, $session]));
        
        $overallStats = DB::selectOne($sql, [$examName, $session]);
        
        // Log the overall stats
        \Log::info('Overall stats: ' . json_encode($overallStats));
        
        // Get grade distribution
        $gradeSql = "
            SELECT 
                r.grade,
                COUNT(r.result_id) AS count,
                ROUND((COUNT(r.result_id) / NULLIF((SELECT COUNT(*) FROM results WHERE exam_name = ? AND session = ?), 0)) * 100, 2) AS percentage
            FROM 
                results r
            WHERE 
                r.exam_name = ?
                AND r.session = ?
            GROUP BY 
                r.grade
            ORDER BY 
                CASE 
                    WHEN r.grade = 'A+' THEN 1
                    WHEN r.grade = 'A' THEN 2
                    WHEN r.grade = 'A-' THEN 3
                    WHEN r.grade = 'B+' THEN 4
                    WHEN r.grade = 'B' THEN 5
                    WHEN r.grade = 'C+' THEN 6
                    WHEN r.grade = 'C' THEN 7
                    WHEN r.grade = 'D' THEN 8
                    WHEN r.grade = 'F' THEN 9
                    ELSE 10
                END
        ";
        
        // Log the grade SQL query
        \Log::info('Grade distribution SQL: ' . $gradeSql);
        
        $gradeDistribution = DB::select($gradeSql, [$examName, $session, $examName, $session]);
        
        // Log the grade distribution
        \Log::info('Grade distribution count: ' . count($gradeDistribution));
        
        // First, check if the users table has a 'name' column
        $userColumns = Schema::getColumnListing('users');
        \Log::info('User table columns: ' . json_encode($userColumns));
        
        // Determine the correct name column
        $nameColumn = in_array('name', $userColumns) ? 'name' : 
                     (in_array('institution_name', $userColumns) ? 'institution_name' : 
                     (in_array('full_name', $userColumns) ? 'full_name' : 'id'));
        
        \Log::info('Using name column: ' . $nameColumn);
        
        // Get top institutions using Eloquent to avoid SQL syntax issues
        $topInstitutions = DB::table('results as r')
            ->join('form_fillups as f', 'r.roll_number', '=', 'f.roll_number')
            ->join('users as u', 'f.institution_id', '=', 'u.id')
            ->select(
                'f.institution_id',
                "u.{$nameColumn} as institution_name",
                DB::raw('COUNT(r.result_id) as total_students'),
                DB::raw('SUM(CASE WHEN r.status = "Pass" THEN 1 ELSE 0 END) as pass_count'),
                DB::raw('ROUND((SUM(CASE WHEN r.status = "Pass" THEN 1 ELSE 0 END) / NULLIF(COUNT(r.result_id), 0)) * 100, 2) as pass_percentage'),
                DB::raw('AVG(r.gpa) as average_gpa')
            )
            ->where('r.exam_name', $examName)
            ->where('r.session', $session)
            ->groupBy('f.institution_id', "u.{$nameColumn}")
            ->orderBy('pass_percentage', 'desc')
            ->orderBy('average_gpa', 'desc')
            ->limit(10)
            ->get();
        
        // Log the top institutions
        \Log::info('Top institutions count: ' . count($topInstitutions));
        
        // If we couldn't get top institutions, provide a simplified version
        if (count($topInstitutions) === 0) {
            $topInstitutions = DB::table('results as r')
                ->join('form_fillups as f', 'r.roll_number', '=', 'f.roll_number')
                ->select(
                    'f.institution_id',
                    DB::raw('COUNT(r.result_id) as total_students'),
                    DB::raw('SUM(CASE WHEN r.status = "Pass" THEN 1 ELSE 0 END) as pass_count'),
                    DB::raw('ROUND((SUM(CASE WHEN r.status = "Pass" THEN 1 ELSE 0 END) / NULLIF(COUNT(r.result_id), 0)) * 100, 2) as pass_percentage'),
                    DB::raw('AVG(r.gpa) as average_gpa')
                )
                ->where('r.exam_name', $examName)
                ->where('r.session', $session)
                ->groupBy('f.institution_id')
                ->orderBy('pass_percentage', 'desc')
                ->orderBy('average_gpa', 'desc')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    $item->institution_name = "Institution #{$item->institution_id}";
                    return $item;
                });
        }
        
        $result = [
            'overall' => $overallStats,
            'grade_distribution' => $gradeDistribution,
            'top_institutions' => $topInstitutions,
            'subject_performance' => $this->getSubjectPerformance($examName, $session),
            'gender_distribution' => $this->getGenderDistribution($examName, $session),
            'performance_trend' => $this->getPerformanceTrend($examName)
        ];
        
        // Log the final result
        \Log::info('Final statistics result structure: ' . json_encode(array_keys($result)));
        
        return $result;
    }
    
    /**
     * Get subject performance statistics
     * 
     * @param string $examName
     * @param string $session
     * @return array
     */
    private function getSubjectPerformance(string $examName, string $session)
    {
        try {
            $subjectPerformance = DB::table('exam_marks as em')
                ->join('results as r', 'em.roll_number', '=', 'r.roll_number')
                ->join('subjects as s', 'em.subject_id', '=', 's.subject_id')
                ->select(
                    's.subject_id',
                    's.subject_name',
                    DB::raw('COUNT(em.detail_id) as total_students'),
                    DB::raw('AVG(em.marks_obtained) as average_marks'),
                    DB::raw('SUM(CASE WHEN em.grade = "A+" THEN 1 ELSE 0 END) as a_plus_count'),
                    DB::raw('SUM(CASE WHEN em.grade = "A" THEN 1 ELSE 0 END) as a_count'),
                    DB::raw('SUM(CASE WHEN em.grade = "A-" THEN 1 ELSE 0 END) as a_minus_count'),
                    DB::raw('SUM(CASE WHEN em.grade = "B+" THEN 1 ELSE 0 END) as b_plus_count'),
                    DB::raw('SUM(CASE WHEN em.grade = "B" THEN 1 ELSE 0 END) as b_count'),
                    DB::raw('SUM(CASE WHEN em.grade = "C+" THEN 1 ELSE 0 END) as c_plus_count'),
                    DB::raw('SUM(CASE WHEN em.grade = "C" THEN 1 ELSE 0 END) as c_count'),
                    DB::raw('SUM(CASE WHEN em.grade = "D" THEN 1 ELSE 0 END) as d_count'),
                    DB::raw('SUM(CASE WHEN em.grade = "F" THEN 1 ELSE 0 END) as f_count')
                )
                ->where('r.exam_name', $examName)
                ->where('r.session', $session)
                ->groupBy('s.subject_id', 's.subject_name')
                ->orderBy('average_marks', 'desc')
                ->get();
                
            return $subjectPerformance;
        } catch (\Exception $e) {
            \Log::error('Error getting subject performance: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get gender distribution statistics
     * 
     * @param string $examName
     * @param string $session
     * @return array
     */
    private function getGenderDistribution(string $examName, string $session)
    {
        try {
            $genderDistribution = DB::table('results as r')
                ->join('form_fillups as f', 'r.roll_number', '=', 'f.roll_number')
                ->join('students as s', 'f.registration_number', '=', 's.registration_number')
                ->select(
                    's.gender',
                    DB::raw('COUNT(r.result_id) as total'),
                    DB::raw('SUM(CASE WHEN r.status = "Pass" THEN 1 ELSE 0 END) as pass_count'),
                    DB::raw('ROUND((SUM(CASE WHEN r.status = "Pass" THEN 1 ELSE 0 END) / NULLIF(COUNT(r.result_id), 0)) * 100, 2) as pass_percentage'),
                    DB::raw('AVG(r.gpa) as average_gpa')
                )
                ->where('r.exam_name', $examName)
                ->where('r.session', $session)
                ->groupBy('s.gender')
                ->get();
                
            return $genderDistribution;
        } catch (\Exception $e) {
            \Log::error('Error getting gender distribution: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get performance trend over sessions
     * 
     * @param string $examName
     * @return array
     */
    private function getPerformanceTrend(string $examName)
    {
        try {
            $performanceTrend = DB::table('results')
                ->select(
                    'session',
                    DB::raw('COUNT(result_id) as total_students'),
                    DB::raw('SUM(CASE WHEN status = "Pass" THEN 1 ELSE 0 END) as pass_count'),
                    DB::raw('ROUND((SUM(CASE WHEN status = "Pass" THEN 1 ELSE 0 END) / NULLIF(COUNT(result_id), 0)) * 100, 2) as pass_percentage'),
                    DB::raw('AVG(gpa) as average_gpa')
                )
                ->where('exam_name', $examName)
                ->groupBy('session')
                ->orderBy('session', 'desc')
                ->limit(5)
                ->get();
                
            return $performanceTrend;
        } catch (\Exception $e) {
            \Log::error('Error getting performance trend: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get institution-wise result statistics for a specific exam and session.
     *
     * @param string $examName
     * @param string $session
     * @param int|null $institutionId
     * @return \Illuminate\Support\Collection|object|null
     */
    public function getInstitutionResultStatistics(string $examName, string $session, ?int $institutionId = null)
    {
        // Log the parameters for debugging
        \Log::info('Getting institution statistics for exam: ' . $examName . ', session: ' . $session . ', institution: ' . ($institutionId ?? 'all'));
        
        try {
            // First, check if the institution exists
            if ($institutionId) {
                $institution = DB::table('users')->where('id', $institutionId)->first();
                if (!$institution) {
                    \Log::warning('Institution with ID ' . $institutionId . ' does not exist');
                    return null;
                }
                
                // Get the institution name
                $institutionName = $this->getInstitutionName($institution);
                \Log::info('Institution name: ' . $institutionName);
            }
            
            // Check if there are any form fillups for this institution
            if ($institutionId) {
                $formFillupsExist = DB::table('form_fillups')
                    ->where('institution_id', $institutionId)
                    ->where('exam_name', $examName)
                    ->where('session', $session)
                    ->exists();
                    
                if (!$formFillupsExist) {
                    \Log::warning('No form fillups found for institution ' . $institutionId . ' in exam ' . $examName . ', session ' . $session);
                    return null;
                }
            }
            
            $query = DB::table('results as r')
                ->join('form_fillups as f', 'r.roll_number', '=', 'f.roll_number')
                ->select(
                    'f.institution_id',
                    DB::raw('COUNT(*) as total_students'),
                    DB::raw('SUM(CASE WHEN r.status = "Pass" THEN 1 ELSE 0 END) as pass_count'),
                    DB::raw('SUM(CASE WHEN r.status = "Fail" THEN 1 ELSE 0 END) as fail_count'),
                    DB::raw('AVG(r.gpa) as average_gpa'),
                    DB::raw('MAX(r.gpa) as highest_gpa'),
                    DB::raw('MIN(CASE WHEN r.status = "Pass" THEN r.gpa ELSE NULL END) as lowest_passing_gpa'),
                    DB::raw('COUNT(DISTINCT f.group) as total_groups')
                )
                ->where('r.exam_name', $examName)
                ->where('r.session', $session);
                
            // Filter by institution if provided
            if ($institutionId) {
                $query->where('f.institution_id', $institutionId);
            }
            
            // Log the SQL query
            \Log::info('Institution statistics SQL: ' . $query->toSql());
            \Log::info('SQL bindings: ' . json_encode($query->getBindings()));
            
            $institutionStats = $query->groupBy('f.institution_id')
                ->get()
                ->map(function ($item) {
                    // Get the institution name
                    $institution = DB::table('users')->where('id', $item->institution_id)->first();
                    $item->institution_name = $institution ? $this->getInstitutionName($institution) : "Institution #{$item->institution_id}";
                    
                    $item->pass_percentage = $item->total_students > 0 
                        ? round(($item->pass_count / $item->total_students) * 100, 2) 
                        : 0;
                    $item->average_gpa = round($item->average_gpa ?? 0, 2);
                    $item->highest_gpa = round($item->highest_gpa ?? 0, 2);
                    $item->lowest_passing_gpa = round($item->lowest_passing_gpa ?? 0, 2);
                    return $item;
                });
                
            // If we still don't have any stats, return null
            if ($institutionStats->isEmpty()) {
                \Log::warning('No statistics found for institution ' . ($institutionId ?? 'all') . ' in exam ' . $examName . ', session ' . $session);
                return null;
            }
            
            // If we have institution stats for a specific institution, add additional statistics
            if (!$institutionStats->isEmpty() && $institutionId) {
                $stats = $institutionStats->first();
                
                // Set the institution name if we have it
                if (isset($institutionName)) {
                    $stats->institution_name = $institutionName;
                }
                
                $institutionStats = $this->enrichInstitutionStatistics($stats, $examName, $session, $institutionId);
            }
            
            // Log the results
            \Log::info('Institution statistics count: ' . (is_object($institutionStats) ? 1 : count($institutionStats)));
            
            return $institutionStats;
        } catch (\Exception $e) {
            \Log::error('Error getting institution statistics: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            // Return null to indicate no statistics found
            return null;
        }
    }
    
    /**
     * Get the institution name from a user object
     *
     * @param object $institution
     * @return string
     */
    private function getInstitutionName($institution)
    {
        // Try different possible name columns
        if (isset($institution->name)) {
            return $institution->name;
        }
        
        if (isset($institution->institution_name)) {
            return $institution->institution_name;
        }
        
        if (isset($institution->full_name)) {
            return $institution->full_name;
        }
        
        if (isset($institution->username)) {
            return $institution->username;
        }
        
        if (isset($institution->email)) {
            // Extract name from email
            $parts = explode('@', $institution->email);
            return str_replace('.', ' ', ucwords($parts[0]));
        }
        
        // If all else fails, use the ID
        return "Institution #{$institution->id}";
    }

    /**
     * Enrich institution statistics with additional data
     *
     * @param object $stats
     * @param string $examName
     * @param string $session
     * @param int $institutionId
     * @return object
     */
    private function enrichInstitutionStatistics($stats, string $examName, string $session, int $institutionId)
    {
        try {
            // Get group statistics
            $groupStats = DB::table('results as r')
                ->join('form_fillups as f', 'r.roll_number', '=', 'f.roll_number')
                ->select(
                    'f.group',
                    DB::raw('COUNT(*) as total_students'),
                    DB::raw('SUM(CASE WHEN r.status = "Pass" THEN 1 ELSE 0 END) as pass_count'),
                    DB::raw('SUM(CASE WHEN r.status = "Fail" THEN 1 ELSE 0 END) as fail_count'),
                    DB::raw('AVG(r.gpa) as average_gpa')
                )
                ->where('r.exam_name', $examName)
                ->where('r.session', $session)
                ->where('f.institution_id', $institutionId)
                ->groupBy('f.group')
                ->get()
                ->map(function ($item) {
                    $item->pass_percentage = $item->total_students > 0 
                        ? round(($item->pass_count / $item->total_students) * 100, 2) 
                        : 0;
                    $item->average_gpa = round($item->average_gpa ?? 0, 2);
                    return $item;
                });
                
            // Get subject statistics
            $subjectStats = DB::table('exam_marks as em')
                ->join('results as r', 'em.roll_number', '=', 'r.roll_number')
                ->join('form_fillups as f', 'r.roll_number', '=', 'f.roll_number')
                ->join('subjects as s', 'em.subject_id', '=', 's.subject_id')
                ->select(
                    's.subject_id',
                    's.subject_name',
                    DB::raw('COUNT(em.detail_id) as total_students'),
                    DB::raw('AVG(em.marks_obtained) as average_marks'),
                    DB::raw('SUM(CASE WHEN em.grade = "A+" THEN 1 ELSE 0 END) as a_plus_count'),
                    DB::raw('SUM(CASE WHEN em.grade = "A" THEN 1 ELSE 0 END) as a_count'),
                    DB::raw('SUM(CASE WHEN em.grade = "A-" THEN 1 ELSE 0 END) as a_minus_count'),
                    DB::raw('SUM(CASE WHEN em.grade = "B+" THEN 1 ELSE 0 END) as b_plus_count'),
                    DB::raw('SUM(CASE WHEN em.grade = "B" THEN 1 ELSE 0 END) as b_count'),
                    DB::raw('SUM(CASE WHEN em.grade = "C+" THEN 1 ELSE 0 END) as c_plus_count'),
                    DB::raw('SUM(CASE WHEN em.grade = "C" THEN 1 ELSE 0 END) as c_count'),
                    DB::raw('SUM(CASE WHEN em.grade = "D" THEN 1 ELSE 0 END) as d_count'),
                    DB::raw('SUM(CASE WHEN em.grade = "F" THEN 1 ELSE 0 END) as f_count')
                )
                ->where('r.exam_name', $examName)
                ->where('r.session', $session)
                ->where('f.institution_id', $institutionId)
                ->groupBy('s.subject_id', 's.subject_name')
                ->orderBy('average_marks', 'desc')
                ->get()
                ->map(function ($item) {
                    $item->average_marks = round($item->average_marks ?? 0, 2);
                    return $item;
                });
                
            // Get gender statistics
            $genderStats = DB::table('results as r')
                ->join('form_fillups as f', 'r.roll_number', '=', 'f.roll_number')
                ->join('students as s', 'f.registration_number', '=', 's.registration_number')
                ->select(
                    's.gender',
                    DB::raw('COUNT(r.result_id) as total'),
                    DB::raw('SUM(CASE WHEN r.status = "Pass" THEN 1 ELSE 0 END) as pass_count'),
                    DB::raw('AVG(r.gpa) as average_gpa')
                )
                ->where('r.exam_name', $examName)
                ->where('r.session', $session)
                ->where('f.institution_id', $institutionId)
                ->groupBy('s.gender')
                ->get()
                ->map(function ($item) {
                    $item->pass_percentage = $item->total > 0 
                        ? round(($item->pass_count / $item->total) * 100, 2) 
                        : 0;
                    $item->average_gpa = round($item->average_gpa ?? 0, 2);
                    return $item;
                });
                
            // Get top students
            $topStudents = DB::table('results as r')
                ->join('form_fillups as f', 'r.roll_number', '=', 'f.roll_number')
                ->join('students as s', 'f.registration_number', '=', 's.registration_number')
                ->select(
                    'r.roll_number',
                    's.first_name',
                    's.last_name',
                    'f.group',
                    'r.gpa',
                    'r.grade',
                    'r.total_marks'
                )
                ->where('r.exam_name', $examName)
                ->where('r.session', $session)
                ->where('f.institution_id', $institutionId)
                ->orderBy('r.gpa', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    $item->gpa = round($item->gpa ?? 0, 2);
                    return $item;
                });
                
            // Add all statistics to the result
            $stats->group_statistics = $groupStats;
            $stats->subject_statistics = $subjectStats;
            $stats->gender_statistics = $genderStats;
            $stats->top_students = $topStudents;
            
            // Generate insights
            $stats->insights = $this->generateInstitutionInsights($stats);
            
            return $stats;
        } catch (\Exception $e) {
            \Log::error('Error enriching institution statistics: ' . $e->getMessage());
            return $stats;
        }
    }
    
    /**
     * Generate insights for institution statistics
     *
     * @param object $stats
     * @return array
     */
    private function generateInstitutionInsights($stats)
    {
        $insights = [];
        
        // Overall performance insight
        $passPercentage = $stats->pass_percentage ?? 0;
        $averageGpa = $stats->average_gpa ?? 0;
        
        if ($passPercentage >= 90) {
            $insights[] = "Excellent overall performance with {$passPercentage}% pass rate.";
        } elseif ($passPercentage >= 75) {
            $insights[] = "Good overall performance with {$passPercentage}% pass rate.";
        } elseif ($passPercentage >= 60) {
            $insights[] = "Average overall performance with {$passPercentage}% pass rate.";
        } else {
            $insights[] = "Below average overall performance with {$passPercentage}% pass rate.";
        }
        
        $insights[] = "Average GPA across all students is " . number_format($averageGpa, 2) . ".";
        
        // Group insights
        if (isset($stats->group_statistics) && count($stats->group_statistics) > 1) {
            $bestGroup = collect($stats->group_statistics)->sortByDesc('pass_percentage')->first();
            $worstGroup = collect($stats->group_statistics)->sortBy('pass_percentage')->first();
            
            if ($bestGroup && $worstGroup && $bestGroup->group != $worstGroup->group) {
                $insights[] = "The {$bestGroup->group} group has the highest pass rate at {$bestGroup->pass_percentage}%.";
                $insights[] = "The {$worstGroup->group} group has the lowest pass rate at {$worstGroup->pass_percentage}%.";
            }
        }
        
        // Subject insights
        if (isset($stats->subject_statistics) && count($stats->subject_statistics) > 1) {
            $bestSubject = collect($stats->subject_statistics)->sortByDesc('average_marks')->first();
            $worstSubject = collect($stats->subject_statistics)->sortBy('average_marks')->first();
            
            if ($bestSubject && $worstSubject) {
                $insights[] = "Highest average marks in {$bestSubject->subject_name} ({$bestSubject->average_marks}).";
                $insights[] = "Lowest average marks in {$worstSubject->subject_name} ({$worstSubject->average_marks}).";
            }
        }
        
        // Gender insights
        if (isset($stats->gender_statistics) && count($stats->gender_statistics) > 1) {
            $maleData = collect($stats->gender_statistics)->firstWhere('gender', 'Male');
            $femaleData = collect($stats->gender_statistics)->firstWhere('gender', 'Female');
            
            if ($maleData && $femaleData) {
                if ($maleData->pass_percentage > $femaleData->pass_percentage) {
                    $diff = number_format($maleData->pass_percentage - $femaleData->pass_percentage, 2);
                    $insights[] = "Male students have a {$diff}% higher pass rate than female students.";
                } elseif ($femaleData->pass_percentage > $maleData->pass_percentage) {
                    $diff = number_format($femaleData->pass_percentage - $maleData->pass_percentage, 2);
                    $insights[] = "Female students have a {$diff}% higher pass rate than male students.";
                }
            }
        }
        
        // Top student insight
        if (isset($stats->top_students) && count($stats->top_students) > 0) {
            $topStudent = $stats->top_students[0];
            $insights[] = "Top performing student is {$topStudent->first_name} {$topStudent->last_name} with GPA {$topStudent->gpa}.";
        }
        
        return $insights;
    }

    /**
     * Recalculate results for specific roll numbers.
     *
     * @param array $rollNumbers
     * @return array
     */
    public function recalculateResults(array $rollNumbers)
    {
        // Get all form fillups for these roll numbers
        $formFillups = FormFillup::whereIn('roll_number', $rollNumbers)->get();
            
        $recalculatedResults = [];
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            foreach ($formFillups as $formFillup) {
                // Get all exam marks for this form fillup
                $examMarks = ExamMark::where('roll_number', $formFillup->roll_number)->get();
                
                if ($examMarks->isEmpty()) {
                    continue;
                }
                
                // Calculate total marks, GPA, and status
                $totalMarks = $examMarks->sum('marks_obtained');
                $totalSubjects = $examMarks->count();
                $averageGpa = $examMarks->avg('grade_point');
                $failCount = $examMarks->where('grade', 'F')->count();
                
                // Determine status (Pass/Fail)
                $status = $failCount > 0 ? 'Fail' : 'Pass';
                
                // Round GPA to 2 decimal places
                $gpa = round($averageGpa, 2);
                
                // Get or create result
                $resultId = $formFillup->exam_name . '_' . $formFillup->session . '_' . $formFillup->roll_number;
                
                $result = Result::updateOrCreate(
                    ['result_id' => $resultId],
                    [
                        'roll_number' => $formFillup->roll_number,
                        'exam_name' => $formFillup->exam_name,
                        'session' => $formFillup->session,
                        'total_marks' => $totalMarks,
                        'gpa' => $gpa,
                        'grade' => $this->calculateGrade($gpa),
                        'status' => $status,
                        'published' => false,
                        'published_by' => null,
                        'published_at' => null
                    ]
                );
                
                // Create audit log for recalculation
                ResultHistory::create([
                    'result_id' => $resultId,
                    'modified_by' => Auth::id(),
                    'modification_type' => 'recalculation',
                    'previous_data' => json_encode([
                        'total_marks' => $result->getOriginal('total_marks'),
                        'gpa' => $result->getOriginal('gpa'),
                        'grade' => $result->getOriginal('grade'),
                        'status' => $result->getOriginal('status')
                    ]),
                    'new_data' => json_encode([
                        'total_marks' => $totalMarks,
                        'gpa' => $gpa,
                        'grade' => $this->calculateGrade($gpa),
                        'status' => $status
                    ]),
                    'timestamp' => Carbon::now()
                ]);
                
                // Generate new IPFS hash for the updated result
                $previousHash = $result->ipfs_hash;
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
                    'previous_ipfs_hash' => $previousHash,
                    'new_ipfs_hash' => $newHash,
                    'timestamp' => Carbon::now()
                ]);
                
                $recalculatedResults[] = $result->fresh();
            }
            
            DB::commit();
            return $recalculatedResults;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get public result by roll number and registration number using raw SQL.
     *
     * @param string $rollNumber
     * @param string $registrationNumber
     * @param string $examName
     * @param string $session
     * @return array
     */
    public function getPublicResultByRollAndRegistration(string $rollNumber, string $registrationNumber, string $examName, string $session)
    {
        // First check if the result exists and is published
        $resultExists = DB::table('results')
            ->where('roll_number', $rollNumber)
            ->where('exam_name', $examName)
            ->where('session', $session)
            ->first();
            
        if (!$resultExists) {
            throw new ModelNotFoundException("Result not found for roll number: $rollNumber, exam: $examName, session: $session");
        }
        
        if (!$resultExists->published) {
            throw new ModelNotFoundException("Result exists but is not published for roll number: $rollNumber");
        }
        
        // Check if form fillup exists
        $formFillupExists = DB::table('form_fillups')
            ->where('roll_number', $rollNumber)
            ->first();
            
        if (!$formFillupExists) {
            throw new ModelNotFoundException("Form fillup not found for roll number: $rollNumber");
        }
        
        // Check if student exists with the registration number
        $studentExists = DB::table('students')
            ->where('registration_number', $registrationNumber)
            ->first();
            
        if (!$studentExists) {
            throw new ModelNotFoundException("Student not found with registration number: $registrationNumber");
        }
        
        // Check if the registration number matches the form fillup
        if ($formFillupExists->registration_number !== $registrationNumber) {
            throw new ModelNotFoundException("Registration number $registrationNumber does not match the form fillup for roll number $rollNumber");
        }
        
        // Use raw SQL query from complex_queries.sql
        $sql = "
            SELECT 
                r.result_id,
                r.roll_number,
                r.exam_name,
                r.session,
                r.gpa,
                r.grade,
                r.total_marks,
                r.status,
                r.published,
                r.published_at,
                r.ipfs_hash,
                s.registration_number,
                s.first_name,
                s.last_name,
                s.father_name,
                s.mother_name,
                s.date_of_birth,
                f.group,
                f.institution_id,
                CONCAT(i.first_name, ' ', IFNULL(i.last_name, '')) AS institution_name,
                i.address AS institution_address
            FROM 
                results r
            INNER JOIN form_fillups f ON r.roll_number = f.roll_number
            INNER JOIN students s ON f.registration_number = s.registration_number
            INNER JOIN users i ON f.institution_id = i.id
            WHERE 
                r.roll_number = ?
                AND s.registration_number = ?
                AND r.exam_name = ?
                AND r.session = ?
                AND r.published = 1
        ";
        
        $result = DB::select($sql, [$rollNumber, $registrationNumber, $examName, $session]);
        
        if (empty($result)) {
            throw new ModelNotFoundException('No published result found for the provided criteria. This might be due to missing institution data.');
        }
        
        // Get subject-wise marks using another raw SQL query
        $subjectSql = "
            SELECT 
                em.subject_id,
                sub.subject_name,
                sub.subject_category,
                em.marks_obtained,
                em.grade AS subject_grade,
                em.grade_point AS subject_grade_point
            FROM 
                exam_marks em
            INNER JOIN subjects sub ON em.subject_id = sub.subject_id
            WHERE 
                em.roll_number = ?
            ORDER BY
                sub.subject_category, sub.subject_name
        ";
        
        $subjects = DB::select($subjectSql, [$rollNumber]);
        
        // Combine result with subjects
        $resultData = (array) $result[0];
        $resultData['subjects'] = $subjects;
        
        // Verify result integrity
        $verificationResult = $this->verifyResultIntegrity($resultData['result_id']);
        $resultData['verification'] = $verificationResult;
        
        return $resultData;
    }

    /**
     * Update the IPFS hash for a result and log the change
     *
     * @param Result $result
     * @return string The new IPFS hash
     */
    public function updateResultHash(Result $result)
    {
        // Start a database transaction
        return DB::transaction(function () use ($result) {
            $user = Auth::user() ? Auth::user()->id : null;
            $now = Carbon::now();
            
            // Store the previous hash
            $previousHash = $result->ipfs_hash;
            
            // Generate a new IPFS hash
            $newHash = $this->ipfsService->storeResultData($result);
            
            // Update the result with the new hash
            $result->update(['ipfs_hash' => $newHash]);
            
            // Create an audit log entry
            ResultHistory::create([
                'result_id' => $result->result_id,
                'modified_by' => $user,
                'modification_type' => 'hash_update',
                'previous_data' => json_encode(['status' => $result->status, 'gpa' => $result->gpa]),
                'new_data' => json_encode(['status' => $result->status, 'gpa' => $result->gpa]),
                'previous_ipfs_hash' => $previousHash,
                'new_ipfs_hash' => $newHash,
                'timestamp' => $now
            ]);
            
            return $newHash;
        });
    }
} 