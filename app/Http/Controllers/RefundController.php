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

        $this->refund = new Payment\Refund\Service();
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

    /**
     * This is a little similar to manual gateway refund and verify refund (a combination).
     *
     * In this route, we get all the refunds which have been timed out. We call verify on the gateway
     * to find out whether the refund was done successfully. If it has, we record the refund on gateway. If it has
     * not, we just notify on slack and move on.
     * We DO NOT call refund on the gateway. (That's why we don't use verifyRefund/manualRefund)
     *
     * Two basic checks which we would have here:
     * - The refund on api side has a corresponding transaction.
     * - No refund entity created on the gateway side.
     *
     * @param $gateway
     */
    public function postGatewayRefundRecord($gateway)
    {
        $data = $this->refund->createGatewayRefundRecords($gateway);

        sd($data);
        
        return ApiResponse::json($data);
    }
}