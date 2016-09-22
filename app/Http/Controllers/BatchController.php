<?php

namespace RZP\Http\Controllers;

use RZP\Http\ApiResponse;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use Request;
use View;

class BatchController extends Controller
{
    protected $batchService;

    public function __construct()
    {
        parent::__construct();

        $this->batchService = new Batch\Service();
    }

    public function createBatch()
    {
        $input = Request::all();

        $result = $this->batchService->uploadBatchFile($input);

        return ApiResponse::json($result);
    }

    public function getBatches()
    {
        $input = Request::all();

        $result = $this->batchService->getBatchFiles($input);

        return ApiResponse::json($result);
    }

    public function getBatchById($id)
    {
        $result = $this->batchService->getBatchById($id);

        return ApiResponse::json($result);
    }

    public function processBatches()
    {
        $result = $this->batchService->processBatchFiles();

        return ApiResponse::json($result);
    }

    public function retryBatch($id)
    {
        $result = $this->batchService->retryBatchFile($id);

        return ApiResponse::json($result);
    }

    public function downloadBatchFile($id)
    {
        $result = $this->batchService->downloadBatchFile($id);

        return ApiResponse::json($result);
    }
}
