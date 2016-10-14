<?php

namespace App\Http\Controllers;

use Input;
use Response;
use App\Api;
use App\Http\AppResponse;

class BatchController extends Controller
{
    public function uploadBatchFile($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $response) = (new Api\Service)->uploadBatchFile($mode, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    public function fetchMultipleBatches($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $response) = (new Api\Service)->fetchMultipleBatches($mode, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    public function fetchBatchById($mode, $id)
    {
        $this->checkMode($mode);

        list($error, $response) = (new Api\Service)->fetchBatchById($mode, $id);

        return AppResponse::jsonResponse($error, $response);
    }

    public function downloadBatchFile($mode, $id)
    {
        $this->checkMode($mode);

        list($error, $response) = (new Api\Service)->downloadBatchFile($mode, $id);

        return AppResponse::jsonResponse($error, $response);
    }

    public function retryBatchFile($mode, $id)
    {
        $this->checkMode($mode);

        list($error, $response) = (new Api\Service)->retryBatchFile($mode, $id);

        return AppResponse::jsonResponse($error, $response);
    }
}
