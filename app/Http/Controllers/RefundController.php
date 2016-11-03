<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Models\Payment;
use RZP\Models\Card;
use Request;

class RefundController extends Controller
{
    protected $refund;

    public function __construct()
    {
        parent::__construct();

        $this->refund = new Payment\Refund\Service;
    }

    public function getRefund($id)
    {
        $refunds = $this->refund->fetch($id);

        return ApiResponse::json($refunds);
    }

    public function getRefunds()
    {
        $input = Request::all();

        $refunds = $this->refund->fetchMultiple($input);

        return ApiResponse::json($refunds);
    }

    public function generateNetbankingRefunds()
    {
        $input = Request::all();
        // Just a hack, will be shifted to the /refunds/excel route
        // once properly deployed
        $input['method'] = 'netbanking';

        $refundExcel = $this->refund->getRefundsFile($input);

        return ApiResponse::json($refundExcel);
    }

    public function generateRefunds()
    {
        $input = Request::all();

        $refundExcel = $this->refund->getRefundsFile($input);

        return ApiResponse::json($refundExcel);
    }

    public function postRefundVerify($ids)
    {
        $data = $this->refund->verify($ids);

        return ApiResponse::json($data);
    }

    /**
     * Creates transactions for all refunds if not present.
     */
    public function postRefundsTransactions()
    {
        $summary = $this->refund->createMissingTransactions();

        return ApiResponse::json($summary);
    }

    public function postManualGatewayRefund($refundIds)
    {
        $data = $this->refund->manualGatewayRefund($refundIds);

        return ApiResponse::json($data);
    }
}