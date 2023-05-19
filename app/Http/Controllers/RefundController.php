<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Trace\TraceCode;

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
        $input = Request::all();

        $refunds = $this->service()->fetch($id, $input);

        try
        {
            //Event to be triggered only for PG Merchant Dashboard
            if (($this->ba->isMerchantDashboardApp() === true) and
                ($this->ba->isProductPrimary() === true))
            {
                $this->service()->sendSelfServeSuccessAnalyticsEventToSegmentForFetchingRefundDetailsFromRefundId();
            }
        }

        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::REFUND_SEGMENT_EVENT_PUSH_FAILED, []);
        }

        return ApiResponse::json($refunds);
    }

    public function getRefunds()
    {
        $input = Request::all();

        $refunds = $this->service()->fetchMultiple($input);

        try
        {
            //Event to be triggered only for PG Merchant Dashboard
            if (($this->ba->isMerchantDashboardApp() === true) and
                ($this->ba->isProductPrimary() === true))
            {
                $this->service()->sendSelfServeSuccessAnalyticsEventToSegmentForFetchingRefundDetails($input);
            }
        }

        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::REFUND_SEGMENT_EVENT_PUSH_FAILED, []);
        }

        return ApiResponse::json($refunds);
    }

    public function getRefundFee()
    {
        $input = Request::all();

        $fee = $this->service()->fetchRefundFee($input);

        return ApiResponse::json($fee);
    }

    /**
     * This is almost a duplicate route to getRefundFee, just used by scrooge with internal auth with instant refund mode sent from scrooge.
     */
    public function scroogeFetchRefundFee()
    {
        $input = Request::all();

        $fee = $this->service()->scroogeFetchRefundFee($input);

        return ApiResponse::json($fee);
    }

    /**
     * Does necessary payment update for refund
     * Supports compensatory update
     *
     * returns error if update fails
     *
     * @return mixed
     */
    public function scroogeRefundsPaymentUpdate()
    {
        $input = Request::all();

        $fee = $this->service()->scroogeRefundsPaymentUpdate($input);

        return ApiResponse::json($fee);
    }

    /**
     * Does balance check, transaction create and payment update for refund and returns necessary data
     *
     * @return mixed
     */
    public function scroogeRefundsTransactionCreate()
    {
        $input = Request::all();

        $response = $this->service()->scroogeRefundsTransactionCreate($input);

        return ApiResponse::json($response);
    }

    /**
     * Fetch refund creation data for display logic on FE apps
     *
     * @return mixed
     */
    public function fetchRefundCreationData()
    {
        $input = Request::all();

        $response = $this->service()->fetchRefundCreationData($input);

        return ApiResponse::json($response);
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

    public function postRefundRetry(string $id)
    {
        $input = Request::all();

        $response = $this->service()->retry($id, $input);

        return ApiResponse::json($response);
    }

    /*
     * Support admin action for bulk retrying refunds via FTA to custom sources
     */
    public function retryRefundsViaCustomFundTransfersBatch()
    {
        $input = Request::all();

        $response = $this->service()->retryRefundsViaCustomFundTransfersBatch($input);

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

    public function updateRefund($id)
    {
        $input = Request::all();

        $data = $this->service()->editStatus($id, $input);

        return ApiResponse::json($data);
    }

    public function updateRefundInternal($id)
    {
        $input = Request::all();

        $data = $this->service()->updateRefundInternal($id, $input);

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

        $resp = ApiResponse::json($data);

        $this->addCorsHeaders($resp);

        return $resp;
    }

    public function getRefundsDetailsForCustomer()
    {
        $input = Request::all();

        $data = $this->service()->fetchRefundsDetailsForCustomer($input);

        $resp = ApiResponse::json($data);

        $this->addCorsHeaders($resp);

        return $resp;
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

    public function scroogeFetchEntitiesV2()
    {
        $input = Request::all();

        $data = $this->service()->scroogeFetchEntitiesV2($input);

        return ApiResponse::json($data);
    }

    public function scroogeFetchPublicEntities()
    {
        $input = Request::all();

        $data = $this->service()->scroogeFetchPublicEntities($input);

        return ApiResponse::json($data);
    }

    public function scroogeBackWriteRefund()
    {
        $input = Request::all();

        $data = $this->service()->scroogeBackWriteRefund($input);

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

    private function addCorsHeaders(& $response)
    {
        $response->headers->set('Access-Control-Allow-Credentials' , 'true');

        $response->headers->set('Access-Control-Allow-Headers', '*');
    }

    public function scroogeFetchRefundEmailData()
    {
        $input = Request::all();

        $response = $this->service()->getRefundEmailData($input);

        return ApiResponse::json($response);
    }

    public function reversalCreateForVirtualRefund()
    {
        $input = Request::all();

        $response = $this->service()->createReversalForVirtualRefund($input);

        return ApiResponse::json($response);
    }

    public function scroogeFetchRefundTransactionData()
    {
        $input = Request::all();

        $response = $this->service()->getRefundTransactionData($input);

        return ApiResponse::json($response);
    }
}
