<?php

namespace RZP\Http\Controllers;

use RZP\Http\ApiResponse;
use RZP\Models\Batch;
use Request;
use View;

class BatchController extends Controller
{
    protected $batchService;

    public function __construct()
    {
        parent::__construct();

        $this->batchService = new Batch\Service;
    }

    public function createBatch()
    {
        $input = Request::all();

        $result = $this->batchService->createBatch($input);

        return ApiResponse::json($result);
    }

    public function getBatches()
    {
        $input = Request::all();

        $result = $this->batchService->fetchMultiple($input);

        return ApiResponse::json($result);
    }

    public function getBatchById($id)
    {
        $result = $this->batchService->getBatchById($id);

        return ApiResponse::json($result);
    }

    public function processBatches()
    {
        $result = $this->batchService->processBatches();

        return ApiResponse::json($result);
    }

    public function retryBatch($id)
    {
        $result = $this->batchService->retryBatch($id);

        return ApiResponse::json($result);
    }

    public function downloadBatch($id)
    {
        $result = $this->batchService->downloadBatch($id);

        return ApiResponse::json($result);
    }
}
