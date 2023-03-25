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

    public function postReverseAdjustments()
    {
        $input = Request::all();

        $data = $this->service()->postReverseAdjustments($input);

        return ApiResponse::json($data);
    }

    public function postAdjustmentBatch()
    {
        $input = Request::all();

        $data = $this->service()->addAdjustmentBatch($input);

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
        $input = Request::all();

        $data = $this->service()->splitAdjustments($input);

        return ApiResponse::json($data);
    }

    public function subBalanceAdjustment()
    {
        $input = Request::all();

        $data = $this->service()->subBalanceAdjustment($input);

        return ApiResponse::json($data);
    }

    /**
     * will be used to create adjustment transaction as fallback
     * if ledger reverse-shadow mode enabled
     *
     * @return ApiResponse
     */
    public function adjustmentsTransactionCreate()
    {
        $input = Request::all();

        $data = $this->service()->createAdjustmentInTransaction($input);

        return ApiResponse::json($data);
    }
}
