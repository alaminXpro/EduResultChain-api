<?php

namespace Vanguard\Services;

use Vanguard\Models\Result;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class MockIPFSService extends IPFSService
{
    /**
     * Local storage path for mock IPFS data
     * 
     * @var string
     */
    protected $storagePath = 'mock_ipfs';

    /**
     * Store result data locally and return a mock hash
     *
     * @param Result $result
     * @return string
     * @throws Exception
     */
    public function storeResultData(Result $result): string
    {
        try {
            // Prepare result data for storage
            $resultData = $this->prepareResultDataForStorage($result);
            
            // Generate a mock hash based on the result ID and timestamp
            $mockHash = 'mock_' . md5($result->result_id . time());
            
            // Store the data in local storage
            Storage::put(
                $this->storagePath . '/' . $mockHash . '.json', 
                json_encode($resultData, JSON_PRETTY_PRINT)
            );
            
            Log::info('Mock IPFS: Stored result ' . $result->result_id . ' with hash ' . $mockHash);
            
            return $mockHash;
        } catch (Exception $e) {
            Log::error('Mock IPFS Storage Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieve result data from local storage
     *
     * @param string $ipfsHash
     * @return array|null
     */
    public function retrieveResultData(string $ipfsHash): ?array
    {
        try {
            // Check if the file exists in local storage
            $path = $this->storagePath . '/' . $ipfsHash . '.json';
            
            if (!Storage::exists($path)) {
                Log::warning('Mock IPFS: Hash not found: ' . $ipfsHash);
                return null;
            }
            
            // Get the data from local storage
            $data = Storage::get($path);
            $resultData = json_decode($data, true);
            
            Log::info('Mock IPFS: Retrieved data for hash ' . $ipfsHash);
            
            return $resultData;
        } catch (Exception $e) {
            Log::error('Mock IPFS Retrieval Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Store batch results locally
     *
     * @param array $results Collection of Result models
     * @return array Array of result IDs mapped to their mock IPFS hashes
     */
    public function storeBatchResults(array $results): array
    {
        $hashMap = [];
        
        foreach ($results as $result) {
            try {
                $hash = $this->storeResultData($result);
                $hashMap[$result->result_id] = $hash;
            } catch (Exception $e) {
                Log::error('Failed to store result ' . $result->result_id . ' in mock IPFS: ' . $e->getMessage());
                $hashMap[$result->result_id] = null;
            }
        }
        
        return $hashMap;
    }

    /**
     * Verify a result against its mock IPFS hash
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
            Log::error('Mock Result Verification Error: ' . $e->getMessage());
            return false;
        }
    }
} 