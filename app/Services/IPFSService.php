<?php

namespace Vanguard\Services;

use Vanguard\Models\Result;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class IPFSService
{
    /**
     * IPFS API endpoint
     * 
     * @var string
     */
    protected $ipfsEndpoint;

    /**
     * Create a new service instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->ipfsEndpoint = config('services.ipfs.endpoint', 'http://localhost:5001');
    }

    /**
     * Store result data on IPFS and return the hash
     *
     * @param Result $result
     * @return string
     * @throws Exception
     */
    public function storeResultData(Result $result): string
    {
        try {
            // Prepare result data for IPFS storage
            $resultData = $this->prepareResultDataForStorage($result);
            
            // Store on IPFS
            $response = Http::post($this->ipfsEndpoint . '/api/v0/add', [
                'file' => json_encode($resultData)
            ]);
            
            if (!$response->successful()) {
                throw new Exception('Failed to store result on IPFS: ' . $response->body());
            }
            
            $responseData = $response->json();
            return $responseData['Hash'] ?? '';
        } catch (Exception $e) {
            Log::error('IPFS Storage Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieve result data from IPFS
     *
     * @param string $ipfsHash
     * @return array|null
     */
    public function retrieveResultData(string $ipfsHash): ?array
    {
        try {
            // Get data from IPFS
            $response = Http::get($this->ipfsEndpoint . '/api/v0/cat?arg=' . $ipfsHash);
            
            if (!$response->successful()) {
                Log::error('Failed to retrieve data from IPFS: ' . $response->body());
                return null;
            }
            
            return json_decode($response->body(), true);
        } catch (Exception $e) {
            Log::error('IPFS Retrieval Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Store batch results on IPFS
     *
     * @param array $results Collection of Result models
     * @return array Array of result IDs mapped to their IPFS hashes
     */
    public function storeBatchResults(array $results): array
    {
        $hashMap = [];
        
        foreach ($results as $result) {
            try {
                $hash = $this->storeResultData($result);
                $hashMap[$result->result_id] = $hash;
            } catch (Exception $e) {
                Log::error('Failed to store result ' . $result->result_id . ' on IPFS: ' . $e->getMessage());
                $hashMap[$result->result_id] = null;
            }
        }
        
        return $hashMap;
    }

    /**
     * Verify a result against its IPFS hash
     *
     * @param Result $result
     * @param string $ipfsHash
     * @return bool
     */
    public function verifyResultIntegrity(Result $result, string $ipfsHash): bool
    {
        try {
            $storedData = $this->retrieveResultData($ipfsHash);
            
            if (!$storedData) {
                return false;
            }
            
            $currentData = $this->prepareResultDataForStorage($result);
            
            // Compare stored data with current data
            return $this->compareResultData($storedData, $currentData);
        } catch (Exception $e) {
            Log::error('Result Verification Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Prepare result data for storage
     *
     * @param Result $result
     * @return array
     */
    protected function prepareResultDataForStorage(Result $result): array
    {
        // Load the form fillup and exam marks with necessary relationships
        $result->load([
            'formFillup', 
            'formFillup.student', 
            'formFillup.examMarks.subject',
            'formFillup.institution',
            'formFillup.board'
        ]);
        
        // Get institution and board information from User table
        $institutionUser = $result->formFillup->institution;
        $boardUser = $result->formFillup->board;
        
        $institutionName = $institutionUser ? 
            ($institutionUser->first_name . ' ' . $institutionUser->last_name) : 'Unknown';
        
        $boardName = $boardUser ? 
            ($boardUser->first_name . ' ' . $boardUser->last_name) : 'Unknown';
        
        // If institution name is just a space (when first_name and last_name are empty)
        // try to use username instead
        if (trim($institutionName) === '') {
            $institutionName = $institutionUser->username ?? 'Unknown';
        }
        
        // If board name is just a space (when first_name and last_name are empty)
        // try to use username instead
        if (trim($boardName) === '') {
            $boardName = $boardUser->username ?? 'Unknown';
        }
        
        return [
            'result_id' => $result->result_id,
            'roll_number' => $result->roll_number,
            'exam_name' => $result->exam_name,
            'session' => $result->session,
            'gpa' => $result->gpa,
            'grade' => $result->grade,
            'total_marks' => $result->total_marks,
            'status' => $result->status,
            'student' => [
                'registration_number' => $result->formFillup->registration_number,
                'name' => $result->formFillup->student->first_name . ' ' . $result->formFillup->student->last_name,
                'father_name' => $result->formFillup->student->father_name,
                'mother_name' => $result->formFillup->student->mother_name,
                'date_of_birth' => $result->formFillup->student->date_of_birth,
            ],
            'institution' => [
                'id' => $result->formFillup->institution_id,
                'name' => $institutionName,
            ],
            'board' => [
                'id' => $result->formFillup->board_id,
                'name' => $boardName,
            ],
            'subject_marks' => $result->formFillup->examMarks->map(function ($mark) {
                return [
                    'subject_id' => $mark->subject_id,
                    'subject_name' => $mark->subject->subject_name,
                    'subject_category' => $mark->subject->subject_category,
                    'marks_obtained' => $mark->marks_obtained
                ];
            })->toArray(),
            'timestamp' => $result->updated_at->timestamp,
            'hash_version' => '1.0'
        ];
    }

    /**
     * Compare result data for verification
     *
     * @param array $storedData
     * @param array $currentData
     * @return bool
     */
    protected function compareResultData(array $storedData, array $currentData): bool
    {
        // Compare critical fields
        $criticalFields = [
            'result_id', 'roll_number', 'exam_name', 'session', 
            'gpa', 'grade', 'total_marks', 'status'
        ];
        
        foreach ($criticalFields as $field) {
            if (!isset($storedData[$field]) || !isset($currentData[$field]) || 
                $storedData[$field] !== $currentData[$field]) {
                return false;
            }
        }
        
        // Compare subject marks
        if (count($storedData['subject_marks']) !== count($currentData['subject_marks'])) {
            return false;
        }
        
        // Sort both arrays to ensure consistent comparison
        $sortFn = function ($a, $b) {
            return $a['subject_id'] <=> $b['subject_id'];
        };
        
        usort($storedData['subject_marks'], $sortFn);
        usort($currentData['subject_marks'], $sortFn);
        
        for ($i = 0; $i < count($storedData['subject_marks']); $i++) {
            if ($storedData['subject_marks'][$i]['subject_id'] !== $currentData['subject_marks'][$i]['subject_id'] ||
                $storedData['subject_marks'][$i]['marks_obtained'] !== $currentData['subject_marks'][$i]['marks_obtained']) {
                return false;
            }
        }
        
        return true;
    }
} 