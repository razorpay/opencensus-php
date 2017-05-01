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

        $result = $this->service('batch')->createBatch($input);

        return ApiResponse::json($result);
    }

    public function getBatches()
    {
        $input = Request::all();

        $result = $this->service('batch')->fetchMultiple($input);

        return ApiResponse::json($result);
    }

    public function getBatchById($id)
    {
        $result = $this->service('batch')->getBatchById($id);

        return ApiResponse::json($result);
    }

    public function processBatches()
    {
        $result = $this->service('batch')->processBatches();

        return ApiResponse::json($result);
    }

    public function retryBatch($id)
    {
        $result = $this->service('batch')->retryBatch($id);

        return ApiResponse::json($result);
    }

    public function downloadBatch($id)
    {
        $result = $this->service('batch')->downloadBatch($id);

        return ApiResponse::json($result);
    }
}
