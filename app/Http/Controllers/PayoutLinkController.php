<?php

namespace RZP\Http\Controllers;

use ApiResponse;

class PayoutLinkController extends Controller
{
    use Traits\HasCrudMethods;

    public function update(string $id)
    {
        return ApiResponse::json('Not Supported');
    }

    public function delete(string $id)
    {
        return ApiResponse::json('Not Supported');
    }

    public function getStatus(string $payoutLinkId)
    {
        $response = $this->service()->getStatus($payoutLinkId, $this->input);

        $response = ApiResponse::json($response);

        $this->addCorsHeaders($response);

        return $response;
    }

    public function allowCors()
    {
        $response = ApiResponse::json([]);

        $this->addCorsHeaders($response);

        return $response;
    }

    /**
     * This is a POST request because,
     * 1. It takes a TOKEN which should be sent in the Body and not URL Param
     * 2. Browsers cannot send Body in a GET request
     * @param string $payoutLinkId
     * @return mixed
     */
    public function getFundAccountsOfContact(string $payoutLinkId)
    {
        $response = $this->service()->getFundAccountsOfContact($payoutLinkId, $this->input);

        $response = ApiResponse::json($response);

        $this->addCorsHeaders($response);

        return $response;
    }

    /**
     * Route to update the merchant level settings for payout links
     * @param $merchantId
     * @return
     */
    public function updateSettings($merchantId = null)
    {
        $response = $this->service()->updateSettings($this->input, $merchantId);

        return ApiResponse::json($response);
    }

    /**
     * Route to get the merchant level settings for payout links
     * @param $merchantId
     * @return
     */
    public function getSettings($merchantId = null)
    {
        $response = $this->service()->getSettings($merchantId, $this->input);

        return ApiResponse::json($response);
    }

    /**
     * This api call will take the fund-account details, and initiate the payout
     * @param string $payoutLinkId
     * @return array
     */
    public function initiate(string $payoutLinkId)
    {
        $response = $this->service()->initiate($payoutLinkId, $this->input);

        $response = ApiResponse::json($response);

        $this->addCorsHeaders($response);

        return $response;
    }

    public function resendNotification(string $payoutLinkId)
    {
        $this->service()->resendNotification($payoutLinkId, $this->input);

        return ApiResponse::json();
    }

    public function generateAndSendCustomerOtp(string $payoutLinkId)
    {
        $response = $this->service()->generateAndSendCustomerOtp($payoutLinkId, $this->input);

        $response = ApiResponse::json($response);

        $this->addCorsHeaders($response);

        return $response;
    }

    public function verifyCustomerOtp(string $payoutLinkId)
    {
        $response = $this->service()->verifyCustomerOtp($payoutLinkId, $this->input);

        $response = ApiResponse::json($response);

        $this->addCorsHeaders($response);

        return $response;
    }

    public function viewHostedPage($payoutLinkId)
    {
        $response = $this->service()->viewHostedPage($payoutLinkId);

        return $response;
    }

    public function onBoardingStatus()
    {
        $response = $this->service()
                         ->onBoardingStatus($this->input);

        return ApiResponse::json($response);
    }

    public function summary()
    {
        $response = $this->service()
                         ->summary($this->input);

        return ApiResponse::json($response);
    }

    public function cancel(string $payoutLinkId)
    {
        $data = $this->service()->cancel($payoutLinkId);

        return ApiResponse::json($data);
    }

    public function pullPayoutStatus(string $payoutLinkId)
    {
        $response =$this->service()->pullBulkPayoutStatus($payoutLinkId, $this->input);

        return ApiResponse::json($response);
    }
    /*
     * piggybacking on API's Mailgun integration used by PL microservice
     */
    public function sendEmailInternal()
    {
        $response = $this->service()->sendEmailInternal($this->input);

        return ApiResponse::json($response);
    }

    private function addCorsHeaders(& $response)
    {
        $response->headers->set('Access-Control-Allow-Origin', $this->config['applications.payout_links.url']);

        $response->headers->set('Access-Control-Allow-Credentials' , 'true');

        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
    }

    public function get(string $id)
    {
        $entity = $this->service()->fetchMerchantSpecific($id, $this->input);

        return ApiResponse::json($entity);
    }

    public function list()
    {
        $entities = $this->service()->fetchMultipleMerchantSpecific($this->input);

        return ApiResponse::json($entities);
    }

    public function adminActions()
    {
        return $this->service()->adminActions($this->input);
    }

    /**
     * Internally calls Payout Links MicroService to process the rows
     * Batch MicroService calls this endpoint with the data to process
     * @return mixed
     */
    public function processBatch()
    {
        $response = $this->app['payout-links']->processBatch($this->input);

        return ApiResponse::json($response);
    }

    public function getBatchSummary($batchId)
    {
        $response = $this->service()->getBatchSummary($batchId);

        return ApiResponse::json($response);
    }

    public function bulkResendNotification()
    {
        $response = $this->service()->bulkResendNotification($this->input);

        return ApiResponse::json($response);
    }

    /**
     * Used by dashboard to create a new Batch
     * internally calls Batch MicroService to process the input file
     * @return mixed
     */
    public function createBatch()
    {
        $response = $this->app['payout-links']->createBatch($this->input, $this->ba->getMerchant(), $this->ba->getUser());

        return ApiResponse::json($response);
    }
}
