<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class AdjustmentController extends Controller
{
    public function getAdjustment($id)
    {
        $data = $this->service()->getAdjustment($id);

        return ApiResponse::json($data);
    }

    public function getAdjustments()
    {
        $input = Request::all();

        $data = $this->service()->getAdjustments($input);

        return ApiResponse::json($data);
    }

    public function postAdjustment()
    {
        $input = Request::all();

        $data = $this->service()->addAdjustment($input);

        return ApiResponse::json($data);
    }

    public function postFeesAdjustment()
    {
        $input = Request::all();

        $data = $this->service()->addFeesAdjustment($input);

        return ApiResponse::json($data);
    }

    public function postReverseAdjustments()
    {
        $input = Request::all();

        $data = $this->service()->postReverseAdjustments($input);

        return ApiResponse::json($data);
    }

    public function postMultipleAdjustments()
    {
        $input = Request::all();

        $data = $this->service()->addMultipleAdjustment($input);

        return ApiResponse::json($data);
    }
}
