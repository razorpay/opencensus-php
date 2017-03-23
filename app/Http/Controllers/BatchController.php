<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Models\Batch;
use Request;
use View;

class BatchController extends Controller
{
    public function createBatch()
    {
        $input = Request::all();

        $result = $this->serviCe('batch')->createBatch($input);

        return ApiResponse::json($result);
    }

    public function getBatches()
    {
        $input = Request::all();

        $result = $this->serviCe('batch')->fetchMultiple($input);

        return ApiResponse::json($result);
    }

    public function getBatchById($id)
    {
        $result = $this->serviCe('batch')->getBatchById($id);

        return ApiResponse::json($result);
    }

    public function processBatches()
    {
        $result = $this->serviCe('batch')->processBatches();

        return ApiResponse::json($result);
    }

    public function retryBatch($id)
    {
        $result = $this->serviCe('batch')->retryBatch($id);

        return ApiResponse::json($result);
    }

    public function downloadBatch($id)
    {
        $result = $this->serviCe('batch')->downloadBatch($id);

        return ApiResponse::json($result);
    }
}
