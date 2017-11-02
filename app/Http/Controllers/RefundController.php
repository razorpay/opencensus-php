<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class RefundController extends Controller
{
    public function postRefundCreate()
    {
        $input = Request::all();

        $refund = $this->service()->create($input);

        return ApiResponse::json($refund);
    }

    public function getRefund($id)
    {
        $refunds = $this->service()->fetch($id);

        return ApiResponse::json($refunds);
    }

    public function getRefunds()
    {
        $input = Request::all();

        $refunds = $this->service()->fetchMultiple($input);

        return ApiResponse::json($refunds);
    }

    public function generateRefunds()
    {
        $input = Request::all();

        $refundExcel = $this->service()->getRefundsFile($input);

        return ApiResponse::json($refundExcel);
    }

    public function postRefundVerify($ids)
    {
        $data = $this->service()->verify($ids);

        return ApiResponse::json($data);
    }

    /**
     * Creates transactions for all refunds if not present.
     */
    public function postRefundsTransactions()
    {
        $summary = $this->service()->createMissingTransactions();

        return ApiResponse::json($summary);
    }

    public function postGatewayRefundedTransactions()
    {
        $data = $this->service()->createMissingTransactionsForGatewayRefunded();

        return ApiResponse::json($data);
    }

    public function postManualGatewayRefund($refundIds)
    {
        $data = $this->service()->manualGatewayRefund($refundIds);

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
        $data = $this->service()->createGatewayRefundRecords($gateway);

        return ApiResponse::json($data);
    }

    /**
     * In case a refund is successful on the gateway side and is not able
     * to record on the api side for some reason (like db lock timeout),
     * we use this route to record it on the api side.
     * This can actually get handled by billdesk recon itself.
     * But, in case a refund is made before the money is settled to us,
     * the refund is in cancelled state and not in refunded state. This
     * means that we do not get it in the recon files.
     *
     */
    public function postCreateBilldeskCancelledRefunds()
    {
        $data = $this->service()->createBilldeskCancelledRefunds();

        return ApiResponse::json($data);
    }

    public function postGatewayValidateRefund(string $gateway)
    {
        $data = $this->service()->validateUnknownGatewayRefunds($gateway);

        return ApiResponse::json($data);
    }

    public function postRetryFailedRefunds()
    {
        $input = Request::all();

        $data = $this->service()->retryFailedRefunds($input);

        return ApiResponse::json($data);
    }

    public function postRefundRetry(string $id)
    {
        $input = Request::all();

        $response = $this->service()->retry($id, $input);

        return ApiResponse::json($response);
    }
}
