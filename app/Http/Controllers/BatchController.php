<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use ApiResponse;

class BatchController extends Controller
{
    public function createBatch()
    {
        $result = $this->service()->createBatch($this->input);

        return ApiResponse::json($result);
    }

    public function getBatches()
    {
        $result = $this->service()->fetchMultiple($this->input);

        return ApiResponse::json($result);
    }

    public function getBatchById($id)
    {
        $result = $this->service()->getBatchById($id);

        return ApiResponse::json($result);
    }

    public function processBatches()
    {
        $result = $this->service()->processBatches();

        return ApiResponse::json($result);
    }

    public function processBatch(string $id)
    {
        $result = $this->service()->processBatch($id, $this->input);

        return ApiResponse::json($result);
    }

    /**
     * Ref: Batch/Core::retryBatchOutputFile
     *
     * @param string $id
     *
     * @return ApiResponse
     */
    public function retryBatchOutputFile(string $id)
    {
        $result = $this->service()->retryBatchOutputFile($id);

        return ApiResponse::json($result);
    }

    public function downloadBatch($id)
    {
        $result = $this->service()->downloadBatch($id);

        return ApiResponse::json($result);
    }

    public function validateFile()
    {
        $response = $this->service()->validateFile($this->input);

        return ApiResponse::json($response);
    }

    public function getStatsOfBatch(string $id)
    {
        $response = $this->service()->fetchStatsOfBatch($id);

        return ApiResponse::json($response);
    }
}
