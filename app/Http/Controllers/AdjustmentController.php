<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Exception;

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

    public function splitAdjustments()
    {
        if (Request::hasFile('file') === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Input does not contain the excel file to be processed'
            );
        }

        $file = Request::file('file');

        $data = $this->service()->splitAdjustments($file);

        return ApiResponse::json($data);
    }
}
