<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class RefundController extends Controller
{
    use Traits\HasCrudMethods;

    public function postCreateBatchRefund()
    {
        $input = Request::all();

        $response = $this->service()->createBatchRefund($input);

        return ApiResponse::json($response);
    }

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

    public function getRefundFee()
    {
        $input = Request::all();

        $fee = $this->service()->fetchRefundFee($input);

        return ApiResponse::json($fee);
    }

    public function generateRefunds()
    {
        $input = Request::all();

        $refundExcel = $this->service()->getRefundsFile($input);

        return ApiResponse::json($refundExcel);
    }

    public function postRefundVerifyMultiple($ids)
    {
        $data = $this->service()->verifyMultiple($ids);

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
     *
     * @return array
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

    public function postRefundRetryBulk()
    {
        $input = Request::all();

        $response = $this->service()->retryBulk($input);

        return ApiResponse::json($response);
    }

    public function postRefundRetryBulkViaFta()
    {
        $input = Request::all();

        $response = $this->service()->retryBulkViaFta($input);

        return ApiResponse::json($response);
    }

    public function postRefundDirectRetryBulk()
    {
        $input = Request::all();

        $response = $this->service()->directRetryBulk($input);

        return ApiResponse::json($response);
    }

    public function postRetryScroogeRefundsWithoutVerify()
    {
        $input = Request::all();

        $response = $this->service()->retryScroogeRefundsWithoutVerify($input);

        return ApiResponse::json($response);
    }

    public function postRefundVerify(string $id)
    {
        $response = $this->service()->verify($id);

        return ApiResponse::json($response);
    }

    public function updateScroogeRefundStatus(string $id)
    {
        $input = Request::all();

        $data = $this->service()->updateScroogeRefundStatus($id, $input);

        return ApiResponse::json($data);
    }

    public function postGatewayRefundCall(string $id)
    {
        $input = Request::all();

        $response = $this->service()->makeGatewayRefundCall($id, $input);

        return ApiResponse::json($response);
    }

    public function postGatewayVerifyRefundCall(string $id)
    {
        $input = Request::all();

        $response = $this->service()->makeGatewayVerifyRefundCall($id, $input);

        return ApiResponse::json($response);
    }

    public function postScroogeVerifyRefundCall(string $id)
    {
        $input = Request::all();

        $response = $this->service()->makeScroogeVerifyRefundCall($id, $input);

        return ApiResponse::json($response);
    }

    public function scroogeRefundCreate(string $id)
    {
        $response = $this->service()->createScroogeRefund($id);

        return ApiResponse::json($response);
    }

    public function getRefundEntity($id)
    {
        $refund = $this->service()->fetchEntity($id);

        return ApiResponse::json($refund);
    }

    public function scroogeRefundCreateBulk()
    {
        $input = Request::all();

        $response = $this->service()->createScroogeRefundBulk($input);

        return ApiResponse::json($response);
    }

    public function putRefundStatus($id)
    {
        $input = Request::all();

        $data = $this->service()->editStatus($id, $input);

        return ApiResponse::json($data);
    }

    public function putRefundMarkProcessedBulk()
    {
        $input = Request::all();

        $data = $this->service()->markProcessedBulk($input);

        return ApiResponse::json($data);
    }

    public function getRefundDetailsForCustomer()
    {
        $input = Request::all();

        $data = $this->service()->fetchRefundDetailsForCustomer($input);

        return ApiResponse::json($data);
    }

    public function getRefundsDetailsForCustomer()
    {
        $input = Request::all();

        $data = $this->service()->fetchRefundsDetailsForCustomer($input);

        return ApiResponse::json($data);
    }

    public function updateProcessedAt()
    {
        $input = Request::all();

        $data = $this->service()->updateProcessedAt($input);

        return ApiResponse::json($data);
    }

    public function bulkUpdateRefundsReference1()
    {
        $input = Request::all();

        $data = $this->service()->bulkUpdateRefundsReference1($input);

        return ApiResponse::json($data);
    }

    public function scroogeRefundVerifyBulk()
    {
        $input = Request::all();

        $data = $this->service()->verifyScroogeRefundsBulk($input);

        return ApiResponse::json($data);
    }

    public function scroogeFetchEntities()
    {
        $input = Request::all();

        $data = $this->service()->scroogeFetchEntities($input);

        return ApiResponse::json($data);
    }

    public function postVerifyRefundsBulk()
    {
        $input = Request::all();

        $data = $this->service()->verifyRefundsInBulk($input);

        return ApiResponse::json($data);
    }

    public function setUnprocessedRefundsConfig()
    {
        $input = Request::all();

        $data = $this->service()->setUnprocessedRefundsConfig($input);

        return ApiResponse::json($data);
    }

    public function cancelRefundsBatch(string $batchId)
    {
        $this->service()->cancelRefundsBatch($batchId);

        return ApiResponse::json([]);
    }
}
