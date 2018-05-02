<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Illuminate\Http\Request;
use RZP\Exception\BadRequestException;
use View;

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

    public function getStats(string $id)
    {
        $response = $this->service()->fetchStatsOfBatch($id);

        return ApiResponse::json($response);
    }

    public function renderBatchUploadForm(Request $request)
    {
        $isValid = $this->service()->validateToken($request->all());

        if ($isValid === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_BATCH_UPLOAD_INVALID_TOKEN);
        }
        else
        {
            $view = View::make('public.direct_debit_form', [
                'dashboardHost' =>  config("applications.dashboard.url"),
            ]);
        }

        return $view;
    }

    public function validateBatchFile(Request $request)
    {
        $input = $request->all();
        $this->service()->consumeToken($input);
        $response = $this->service()->validateFile($input);

        return ApiResponse::json($response);
    }
}
