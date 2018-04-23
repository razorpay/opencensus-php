<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Illuminate\Http\Request;
use RZP\Constants\Entity as E;
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
        $token = $request->input('token');

        $isValid = $this->isValidOneTimeToken($token);

        if ($isValid === false)
        {
            $view = View::make('403');
        }
        else {
            $view = View::make('public.direct_debit_form', [
                'dashboardHost' =>  config("applications.dashboard.url"),
            ]);
        }

        return $view;
    }

    public function submitBatchUploadForm(Request $request)
    {
        $token = $request->input('token');
        $input = $request->all();

        $this->service(E::MERCHANT_REQUEST)->consumeOneTimeToken($token);

        $result =  $this->service()->createBatch($input);

        $view = View::make('public.direct_debit_form_submit', $result);
        return $view;
    }

    public function validateBatchFile(Request $request)
    {
        $input = $request->all();
        $token = $input['token'];

        $this->service(E::MERCHANT_REQUEST)->consumeOneTimeToken($token);
        $response = $this->service()->validateFile($input);

        return ApiResponse::json($response);
    }

    private function isValidOneTimeToken($token)
    {
        return $this->service(E::MERCHANT_REQUEST)->isValidOneTimeToken($token);
    }
}
