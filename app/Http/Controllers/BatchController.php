<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use ApiResponse;

class BatchController extends Controller
{
    public function createBatch()
    {
        $input = Request::all();

        $result = $this->service()->createBatch($input);

        return ApiResponse::json($result);
    }

    public function getBatches()
    {
        $input = Request::all();

        $result = $this->service()->fetchMultiple($input);

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
        $input = Request::all();

        $result = $this->service()->processBatch($id, $input);

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
}
