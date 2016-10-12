<?php

namespace App\Http\Controllers;

use Auth;
use App\Api;
use App\Merchant;
use App\Http\AppResponse;
use Input;
use Response;
use Carbon\Carbon;

class BatchController extends Controller
{

    public function uploadBatchFile($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        $response = (new Api\Service)->uploadBatchFile($mode, $input);

        return AppResponse::jsonResponse($response);
    }

    public function fetchMultipleBatches($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        $response = (new Api\Service)->fetchMultipleBatches($mode, $input);

        return AppResponse::jsonResponse($response);
    }

    public function fetchBatchById($mode, $id)
    {
        $this->checkMode($mode);

        $response = (new Api\Service)->fetchBatchById($mode, $id);

        return AppResponse::jsonResponse($response);
    }

    public function downloadBatchFile($mode, $id)
    {
        $this->checkMode($mode);

        $response = (new Api\Service)->downloadRefundFile($mode, $id);

        return AppResponse::jsonResponse($response);
    }

    public function retryBatchFile($mode, $id)
    {
        $this->checkMode($mode);

        $response = (new Api\Service)->retryRefundFile($mode, $id);

        return AppResponse::jsonResponse($response);
    }
}
