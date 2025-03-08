<?php

namespace Vanguard\Http\Controllers\Api;

use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Services\ResultService;
use Vanguard\Services\IPFSService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends ApiController
{
    /**
     * @var ResultService
     */
    protected $resultService;

    /**
     * @var IPFSService
     */
    protected $ipfsService;

    /**
     * Create a new controller instance.
     *
     * @param ResultService $resultService
     * @param IPFSService $ipfsService
     * @return void
     */
    public function __construct(ResultService $resultService, IPFSService $ipfsService)
    {
        $this->resultService = $resultService;
        $this->ipfsService = $ipfsService;
    }

    /**
     * Verify a result's integrity.
     *
     * @param string $resultId
     * @return JsonResponse
     */
    public function verifyResult(string $resultId): JsonResponse
    {
        $verificationResult = $this->resultService->verifyResultIntegrity($resultId);
        
        return $this->respondWithArray([
            'success' => $verificationResult['verified'],
            'data' => $verificationResult
        ]);
    }

    /**
     * Verify a result by roll number.
     *
     * @param string $rollNumber
     * @return JsonResponse
     */
    public function verifyByRollNumber(string $rollNumber): JsonResponse
    {
        try {
            $result = $this->resultService->getResultByRollNumber($rollNumber);
            return $this->verifyResult($result->result_id);
        } catch (\Exception $e) {
            return $this->errorNotFound('Result not found for this roll number.');
        }
    }

    /**
     * Retrieve result data from IPFS.
     *
     * @param string $ipfsHash
     * @return JsonResponse
     */
    public function retrieveFromIPFS(string $ipfsHash): JsonResponse
    {
        $resultData = $this->ipfsService->retrieveResultData($ipfsHash);
        
        if (!$resultData) {
            return $this->errorNotFound('No data found for this IPFS hash.');
        }
        
        // Try to find a result with this hash to provide verification context
        $result = \Vanguard\Models\Result::where('ipfs_hash', $ipfsHash)->first();
        
        $verificationInfo = [
            'hash_exists' => true,
            'is_current_hash' => false,
            'result_found' => false,
            'published' => false,
        ];
        
        // Enhance the IPFS data with correct institution and board names
        if ($result) {
            $verificationInfo['result_found'] = true;
            $verificationInfo['is_current_hash'] = true;
            $verificationInfo['published'] = (bool) $result->published;
            $verificationInfo['result_id'] = $result->result_id;
            $verificationInfo['roll_number'] = $result->roll_number;
            
            // Load the form fillup with institution and board
            $result->load(['formFillup.institution', 'formFillup.board']);
            
            // Update institution name in the IPFS data if it's "Unknown"
            if (isset($resultData['institution']['name']) && $resultData['institution']['name'] === 'Unknown') {
                $institutionUser = $result->formFillup->institution;
                if ($institutionUser) {
                    // Try full name first
                    $fullName = trim($institutionUser->first_name . ' ' . $institutionUser->last_name);
                    if (!empty($fullName)) {
                        $resultData['institution']['name'] = $fullName;
                    } 
                    // If full name is empty, try username
                    else if (!empty($institutionUser->username)) {
                        $resultData['institution']['name'] = $institutionUser->username;
                    }
                }
            }
            
            // Update board name in the IPFS data if it's "Unknown"
            if (isset($resultData['board']['name']) && $resultData['board']['name'] === 'Unknown') {
                $boardUser = $result->formFillup->board;
                if ($boardUser) {
                    // Try full name first
                    $fullName = trim($boardUser->first_name . ' ' . $boardUser->last_name);
                    if (!empty($fullName)) {
                        $resultData['board']['name'] = $fullName;
                    } 
                    // If full name is empty, try username
                    else if (!empty($boardUser->username)) {
                        $resultData['board']['name'] = $boardUser->username;
                    }
                }
            }
            
            if ($result->published) {
                $verificationInfo['published_at'] = $result->published_at ? $result->published_at->format('Y-m-d H:i:s') : null;
                $verificationInfo['published_by_id'] = $result->published_by;
                
                // Get publisher name if available
                if ($result->published_by) {
                    $publisher = \Vanguard\User::find($result->published_by);
                    $verificationInfo['published_by_name'] = $publisher ? 
                        ($publisher->first_name . ' ' . $publisher->last_name) : 'Unknown';
                }
            }
        } else {
            // Check if this hash exists in history
            $history = \Vanguard\Models\ResultHistory::where('new_ipfs_hash', $ipfsHash)
                ->orWhere('previous_ipfs_hash', $ipfsHash)
                ->first();
                
            if ($history) {
                $verificationInfo['is_historical'] = true;
                $verificationInfo['result_id'] = $history->result_id;
                $verificationInfo['modification_type'] = $history->modification_type;
                $verificationInfo['modified_at'] = $history->timestamp ? $history->timestamp->format('Y-m-d H:i:s') : null;
                
                // Get current result to enhance the IPFS data
                $currentResult = \Vanguard\Models\Result::where('result_id', $history->result_id)->first();
                if ($currentResult) {
                    $verificationInfo['current_hash'] = $currentResult->ipfs_hash;
                    
                    // Load the form fillup with institution and board
                    $currentResult->load(['formFillup.institution', 'formFillup.board']);
                    
                    // Update institution name in the IPFS data if it's "Unknown"
                    if (isset($resultData['institution']['name']) && $resultData['institution']['name'] === 'Unknown') {
                        $institutionUser = $currentResult->formFillup->institution;
                        if ($institutionUser) {
                            // Try full name first
                            $fullName = trim($institutionUser->first_name . ' ' . $institutionUser->last_name);
                            if (!empty($fullName)) {
                                $resultData['institution']['name'] = $fullName;
                            } 
                            // If full name is empty, try username
                            else if (!empty($institutionUser->username)) {
                                $resultData['institution']['name'] = $institutionUser->username;
                            }
                        }
                    }
                    
                    // Update board name in the IPFS data if it's "Unknown"
                    if (isset($resultData['board']['name']) && $resultData['board']['name'] === 'Unknown') {
                        $boardUser = $currentResult->formFillup->board;
                        if ($boardUser) {
                            // Try full name first
                            $fullName = trim($boardUser->first_name . ' ' . $boardUser->last_name);
                            if (!empty($fullName)) {
                                $resultData['board']['name'] = $fullName;
                            } 
                            // If full name is empty, try username
                            else if (!empty($boardUser->username)) {
                                $resultData['board']['name'] = $boardUser->username;
                            }
                        }
                    }
                }
            }
        }
        
        // Log the enhanced data
        \Log::info('Enhanced IPFS Data', [
            'hash' => $ipfsHash,
            'institution_name' => $resultData['institution']['name'] ?? 'Not found',
            'board_name' => $resultData['board']['name'] ?? 'Not found'
        ]);
        
        return $this->respondWithArray([
            'success' => true,
            'data' => $resultData,
            'verification' => $verificationInfo
        ]);
    }

    /**
     * Public verification endpoint that doesn't require authentication.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function publicVerify(Request $request): JsonResponse
    {
        $request->validate([
            'roll_number' => 'required_without:result_id|string',
            'result_id' => 'required_without:roll_number|string',
            'exam_name' => 'required|string',
            'session' => 'required|string',
        ]);
        
        try {
            if ($request->has('roll_number')) {
                $result = $this->resultService->getResultByRollNumber($request->roll_number);
            } else {
                $result = $this->resultService->getResult($request->result_id);
            }
            
            // Verify that the result matches the requested exam and session
            if ($result->exam_name !== $request->exam_name || $result->session !== $request->session) {
                return $this->errorNotFound('No matching result found for the provided criteria.');
            }
            
            // Only allow verification of published results
            if (!$result->published) {
                return $this->errorForbidden('This result has not been published yet.');
            }
            
            $verificationResult = $this->resultService->verifyResultIntegrity($result->result_id);
            
            // If the result is verified, enhance the verification data with correct institution and board names
            if ($verificationResult['verified']) {
                // Retrieve the IPFS data
                $ipfsData = $this->ipfsService->retrieveResultData($result->ipfs_hash);
                
                if ($ipfsData) {
                    // Load the form fillup with institution and board
                    $result->load(['formFillup.institution', 'formFillup.board']);
                    
                    // Update institution name in the IPFS data if it's "Unknown"
                    if (isset($ipfsData['institution']['name']) && $ipfsData['institution']['name'] === 'Unknown') {
                        $institutionUser = $result->formFillup->institution;
                        if ($institutionUser) {
                            // Try full name first
                            $fullName = trim($institutionUser->first_name . ' ' . $institutionUser->last_name);
                            if (!empty($fullName)) {
                                $ipfsData['institution']['name'] = $fullName;
                            } 
                            // If full name is empty, try username
                            else if (!empty($institutionUser->username)) {
                                $ipfsData['institution']['name'] = $institutionUser->username;
                            }
                        }
                    }
                    
                    // Update board name in the IPFS data if it's "Unknown"
                    if (isset($ipfsData['board']['name']) && $ipfsData['board']['name'] === 'Unknown') {
                        $boardUser = $result->formFillup->board;
                        if ($boardUser) {
                            // Try full name first
                            $fullName = trim($boardUser->first_name . ' ' . $boardUser->last_name);
                            if (!empty($fullName)) {
                                $ipfsData['board']['name'] = $fullName;
                            } 
                            // If full name is empty, try username
                            else if (!empty($boardUser->username)) {
                                $ipfsData['board']['name'] = $boardUser->username;
                            }
                        }
                    }
                    
                    // Add the enhanced data to the verification result
                    $verificationResult['enhanced_data'] = $ipfsData;
                }
            }
            
            return $this->respondWithArray([
                'success' => $verificationResult['verified'],
                'data' => $verificationResult
            ]);
        } catch (\Exception $e) {
            return $this->errorNotFound('No matching result found for the provided criteria.');
        }
    }

    /**
     * Update IPFS hashes for a batch of results.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateHashes(Request $request): JsonResponse
    {
        // Validate request
        $request->validate([
            'exam_name' => 'required|string',
            'session' => 'required|string',
        ]);

        // Check permissions
        if (!auth()->user()->hasPermission('results.manage')) {
            return $this->errorForbidden('You do not have permission to update result hashes.');
        }

        try {
            $count = $this->resultService->updateResultHashes(
                $request->exam_name,
                $request->session
            );

            return $this->respondWithArray([
                'success' => true,
                'message' => $count . ' result hashes updated successfully.',
            ]);
        } catch (\Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }

    /**
     * Get public result by roll number and registration number.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPublicResult(Request $request): JsonResponse
    {
        $request->validate([
            'roll_number' => 'required|string',
            'registration_number' => 'required|string',
            'exam_name' => 'required|string',
            'session' => 'required|string',
        ]);
        
        try {
            // Use the raw SQL method from ResultService
            $resultData = $this->resultService->getPublicResultByRollAndRegistration(
                $request->roll_number,
                $request->registration_number,
                $request->exam_name,
                $request->session
            );
            
            // If the result has an IPFS hash, retrieve the data and enhance it
            if (isset($resultData['ipfs_hash']) && !empty($resultData['ipfs_hash'])) {
                $ipfsData = $this->ipfsService->retrieveResultData($resultData['ipfs_hash']);
                
                if ($ipfsData) {
                    // Get the result model to load relationships
                    $result = \Vanguard\Models\Result::where('result_id', $resultData['result_id'])->first();
                    
                    if ($result) {
                        // Load the form fillup with institution and board
                        $result->load(['formFillup.institution', 'formFillup.board']);
                        
                        // Update institution name in the IPFS data if it's "Unknown"
                        if (isset($ipfsData['institution']['name']) && $ipfsData['institution']['name'] === 'Unknown') {
                            $institutionUser = $result->formFillup->institution;
                            if ($institutionUser) {
                                // Try full name first
                                $fullName = trim($institutionUser->first_name . ' ' . $institutionUser->last_name);
                                if (!empty($fullName)) {
                                    $ipfsData['institution']['name'] = $fullName;
                                } 
                                // If full name is empty, try username
                                else if (!empty($institutionUser->username)) {
                                    $ipfsData['institution']['name'] = $institutionUser->username;
                                }
                            }
                        }
                        
                        // Update board name in the IPFS data if it's "Unknown"
                        if (isset($ipfsData['board']['name']) && $ipfsData['board']['name'] === 'Unknown') {
                            $boardUser = $result->formFillup->board;
                            if ($boardUser) {
                                // Try full name first
                                $fullName = trim($boardUser->first_name . ' ' . $boardUser->last_name);
                                if (!empty($fullName)) {
                                    $ipfsData['board']['name'] = $fullName;
                                } 
                                // If full name is empty, try username
                                else if (!empty($boardUser->username)) {
                                    $ipfsData['board']['name'] = $boardUser->username;
                                }
                            }
                        }
                        
                        // Add the enhanced IPFS data to the result data
                        $resultData['ipfs_data'] = $ipfsData;
                    }
                }
            }
            
            return $this->respondWithArray([
                'success' => true,
                'data' => $resultData
            ]);
        } catch (\Exception $e) {
            // Log the error and parameters for debugging
            \Log::error('Public result fetch failed', [
                'error' => $e->getMessage(),
                'roll_number' => $request->roll_number,
                'registration_number' => $request->registration_number,
                'exam_name' => $request->exam_name,
                'session' => $request->session
            ]);
            
            return $this->errorNotFound('No matching result found for the provided criteria.');
        }
    }

    /**
     * Verify a marksheet using its IPFS hash and result ID.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyMarksheet(Request $request): JsonResponse
    {
        $request->validate([
            'hash' => 'required|string',
            'result_id' => 'required|string',
        ]);
        
        try {
            // Get the result from the database
            $result = \Vanguard\Models\Result::where('result_id', $request->result_id)->first();
            
            if (!$result) {
                return $this->respondWithArray([
                    'success' => false,
                    'message' => 'Result not found',
                    'verification_status' => 'INVALID'
                ]);
            }
            
            // Check if the result is published
            if (!$result->published) {
                return $this->respondWithArray([
                    'success' => false,
                    'message' => 'This result has not been published officially',
                    'verification_status' => 'UNPUBLISHED'
                ]);
            }
            
            // Verify the hash against the provided hash
            $providedHash = $request->hash;
            $storedHash = $result->ipfs_hash;
            
            if ($providedHash !== $storedHash) {
                // The hash on the marksheet doesn't match the current hash in the database
                
                // Check if this hash exists in the result history
                $historyRecord = \Vanguard\Models\ResultHistory::where(function($query) use ($providedHash) {
                        $query->where('new_ipfs_hash', $providedHash)
                              ->orWhere('previous_ipfs_hash', $providedHash);
                    })
                    ->where('result_id', $result->result_id)
                    ->first();
                
                if ($historyRecord) {
                    // This was a valid hash at some point, but the result has been updated
                    return $this->respondWithArray([
                        'success' => false,
                        'message' => 'This marksheet was valid but has been updated since issuance',
                        'verification_status' => 'OUTDATED',
                        'last_updated' => $historyRecord->timestamp->format('Y-m-d H:i:s'),
                        'modification_type' => $historyRecord->modification_type,
                        'current_hash' => $storedHash
                    ]);
                }
                
                // The hash doesn't exist in our system at all
                return $this->respondWithArray([
                    'success' => false,
                    'message' => 'Invalid hash. This marksheet cannot be verified.',
                    'verification_status' => 'INVALID'
                ]);
            }
            
            // Retrieve the data from IPFS to display
            $ipfsData = $this->ipfsService->retrieveResultData($storedHash);
            
            if (!$ipfsData) {
                return $this->respondWithArray([
                    'success' => false,
                    'message' => 'Could not retrieve data from blockchain storage',
                    'verification_status' => 'ERROR'
                ]);
            }
            
            // Enhance the IPFS data with correct institution and board names
            // Load the form fillup with institution and board
            $result->load(['formFillup.institution', 'formFillup.board']);
            
            // Update institution name in the IPFS data if it's "Unknown"
            if (isset($ipfsData['institution']['name']) && $ipfsData['institution']['name'] === 'Unknown') {
                $institutionUser = $result->formFillup->institution;
                if ($institutionUser) {
                    // Try full name first
                    $fullName = trim($institutionUser->first_name . ' ' . $institutionUser->last_name);
                    if (!empty($fullName)) {
                        $ipfsData['institution']['name'] = $fullName;
                    } 
                    // If full name is empty, try username
                    else if (!empty($institutionUser->username)) {
                        $ipfsData['institution']['name'] = $institutionUser->username;
                    }
                }
            }
            
            // Update board name in the IPFS data if it's "Unknown"
            if (isset($ipfsData['board']['name']) && $ipfsData['board']['name'] === 'Unknown') {
                $boardUser = $result->formFillup->board;
                if ($boardUser) {
                    // Try full name first
                    $fullName = trim($boardUser->first_name . ' ' . $boardUser->last_name);
                    if (!empty($fullName)) {
                        $ipfsData['board']['name'] = $fullName;
                    } 
                    // If full name is empty, try username
                    else if (!empty($boardUser->username)) {
                        $ipfsData['board']['name'] = $boardUser->username;
                    }
                }
            }
            
            // Success! The marksheet is verified
            return $this->respondWithArray([
                'success' => true,
                'message' => 'This marksheet is authentic and matches our records',
                'verification_status' => 'VERIFIED',
                'data' => $ipfsData,
                'published_at' => $result->published_at->format('Y-m-d H:i:s'),
                'published_by' => $result->publishedBy ? 
                    ($result->publishedBy->first_name . ' ' . $result->publishedBy->last_name) : 'System'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Verification error: ' . $e->getMessage());
            return $this->respondWithArray([
                'success' => false,
                'message' => 'An error occurred during verification',
                'verification_status' => 'ERROR'
            ]);
        }
    }

    /**
     * Generate a QR code for marksheet verification.
     *
     * @param string $resultId
     * @return JsonResponse
     */
    public function generateQRCode(string $resultId): JsonResponse
    {
        try {
            $result = \Vanguard\Models\Result::where('result_id', $resultId)->first();
            
            if (!$result) {
                return $this->errorNotFound('Result not found.');
            }
            
            // Check if the result is published
            if (!$result->published) {
                return $this->errorForbidden('Cannot generate QR code for unpublished result.');
            }
            
            // Generate QR code data
            $qrCodeData = $this->resultService->generateVerificationQRCode($result);
            
            return $this->respondWithArray([
                'success' => true,
                'data' => $qrCodeData,
                'message' => 'QR code data generated successfully.'
            ]);
        } catch (\Exception $e) {
            \Log::error('QR Code Generation Error: ' . $e->getMessage());
            return $this->errorInternalError('Failed to generate QR code: ' . $e->getMessage());
        }
    }
} 