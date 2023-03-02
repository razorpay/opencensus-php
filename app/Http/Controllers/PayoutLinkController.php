<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use Redirect;
use ApiResponse;
use RZP\Trace\TraceCode;
use RZP\Constants\Mode;

class PayoutLinkController extends Controller
{
    use Traits\HasCrudMethods;

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
     * Route to approve the workflow pending on user for payout links
     * @param string $payoutLinkId
     * @return
     */
    public function approvePayoutLink(string $payoutLinkId)
    {
        $this->app['payout-links']->approvePayoutLink(
            $payoutLinkId,
            $this->input,
            $this->ba->getMerchant(),
            $this->ba->getUser(),
            $this->ba->getUserRole()
        );

        return ApiResponse::json();
    }

    /**
     * Route to reject the workflow pending on user for payout links
     * @param string $payoutLinkId
     * @return
     */
    public function rejectPayoutLink(string $payoutLinkId)
    {
        $this->app['payout-links']->rejectPayoutLink(
            $payoutLinkId,
            $this->input,
            $this->ba->getMerchant(),
            $this->ba->getUser(),
            $this->ba->getUserRole()
        );

        return ApiResponse::json();
    }

    /**
     * Route to get data of pending payout links on role
     */
    public function workflowSummary()
    {
        $response = $this->app['payout-links']->workflowSummary(
            $this->ba->getMerchant(),
            $this->ba->getUserRole()
        );

        return ApiResponse::json($response);
    }

    /**
     * Route to approve the workflow pending on user for bulk payout links
     * @return
     */
    public function approveBulkPayoutLinks()
    {
        $this->app['payout-links']->approveBulkPayoutLinks(
            $this->input,
            $this->ba->getMerchant(),
            $this->ba->getUser(),
            $this->ba->getUserRole()
        );

        return ApiResponse::json();
    }

    /**
     * Route to reject the workflow pending on user for bulk payout links
     * @return
     */
    public function rejectBulkPayoutLinks()
    {
        $this->app['payout-links']->rejectBulkPayoutLinks(
            $this->input,
            $this->ba->getMerchant(),
            $this->ba->getUser(),
            $this->ba->getUserRole()
        );

        return ApiResponse::json();
    }

    public function approvePayoutLinkOtp(string $payoutLinkId)
    {
        $response = $this->app['payout-links']->approvePayoutLinkOtp(
            $payoutLinkId,
            $this->ba->getMerchant(),
            $this->ba->getUser(),
            $this->ba->getUserRole()
        );

        return ApiResponse::json($response);
    }

    public function approveBulkPayoutLinksOtp()
    {
        $response = $this->app['payout-links']->approveBulkPayoutLinksOtp(
            $this->input,
            $this->ba->getMerchant(),
            $this->ba->getUser(),
            $this->ba->getUserRole()
        );

        return ApiResponse::json($response);
    }

    /**
     * Route for getting signed url of attachment for payout-link
     * @param string $payoutLinkId
     * @param string $fileId
     * @return
     */
    public function getSignedUrl(string $payoutLinkId, string $fileId)
    {
        $response =  $this->app['payout-links']->getSignedUrl(
            $this->ba->getMerchant(),
            $payoutLinkId,
            $fileId
        );

        return ApiResponse::json($response);
    }

    /**
     * Route to upload attachment
     * @return
     */
    public function uploadAttachment()
    {
        $response = $this->app['payout-links']->uploadAttachment(
            $this->ba->getMerchant(),
            $this->input
        );

        return ApiResponse::json($response);
    }

    /**
     * Route to update attachments for payout-link
     * @param string $payoutLinkId
     * @return
     */
    public function updateAttachmentsForPayoutLink(string $payoutLinkId)
    {
        $response = $this->app['payout-links']->updateAttachments(
            $payoutLinkId,
            $this->input
        );

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

        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
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

    public function ownerBulkRejectPayoutLinks()
    {
        return $this->service()->ownerBulkRejectPayoutLinks($this->input);
    }

    public function fetchPendingPayoutLinksSummary()
    {
        return $this->service()->fetchPendingPayoutLinks($this->input);
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

    public function installShopifyApp()
    {
        $redirectUrl = $this->app['payout-links']->getShopifyAppInstallRedirectURI($this->input);

        return Redirect::to($redirectUrl);
    }

    public function uninstallShopifyApp()
    {
        $this->app['payout-links']->uninstallShopifyApp($this->input);
    }

    public function integrateApp()
    {
        $response = $this->app['payout-links']->integrateApp($this->input, $this->ba->getMerchant());

        return ApiResponse::json($response);
    }

    public function fetchShopifyOrderDetails()
    {
        $response = $this->app['payout-links']->fetchShopifyOrderDetails($this->input, $this->ba->getMerchant());

        return ApiResponse::json($response);
    }

    public function integrationDetails()
    {
        $response = $this->app['payout-links']->integrationDetails($this->input, $this->ba->getMerchant());

        return ApiResponse::json($response);
    }

    public function bulkResendNotification()
    {
        $response = $this->service()->bulkResendNotification($this->input);

        return ApiResponse::json($response);
    }

    public function shopifyCustomerRedact()
    {
        $this->trace->info(TraceCode::PAYOUT_LINK_SHOPIFY_CUSTOMER_REDACT, []);
        return;
    }

    public function shopifyShopRedact()
    {
        $this->trace->info(TraceCode::PAYOUT_LINK_SHOPIFY_SHOP_REDACT, []);

        return;
    }

    public function shopifyCustomerDataRequest()
    {
        $this->trace->info(TraceCode::PAYOUT_LINK_SHOPIFY_CUSTOMER_DATA_REQUEST, []);

        return;
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

    public function sendDemoEmailInternal()
    {
        $response = $this->app['payout-links']->sendDemoEmailInternal($this->input);

        return ApiResponse::json($response);
    }

    public function createDemo()
    {
        $response = $this->app['payout-links']->createDemoPayoutLink($this->input);

        $response = ApiResponse::json($response);

        $this->addCorsHeadersFE($response);

        return $response;
    }

    public function viewDemoHostedPage($payoutLinkId)
    {
        $response = $this->app['payout-links']->getDemoHostedPageData($payoutLinkId);

        return View::make('payout_link.customer_hosted', $response);
    }

    public function generateAndSendCustomerOtpDemo(string $payoutLinkId)
    {
        $response = $this->app['payout-links']->generateAndSendCustomerOtpDemo($payoutLinkId, $this->input);

        $response = ApiResponse::json($response);

        $this->addCorsHeaders($response);

        return $response;
    }

    public function verifyCustomerOtpDemo(string $payoutLinkId)
    {

        $response = $this->app['payout-links']->verifyCustomerOtpDemo($payoutLinkId, $this->input);

        $response = ApiResponse::json($response);

        $this->addCorsHeaders($response);

        return $response;
    }

    public function initiateDemo(string $payoutLinkId)
    {
        $response = $this->app['payout-links']->initiateDemo($payoutLinkId, $this->input);

        $response = ApiResponse::json($response);

        $this->addCorsHeaders($response);

        return $response;
    }

    public function allowCorsFE()
    {
        $response = ApiResponse::json([]);

        $this->addCorsHeadersFE($response);

        return $response;
    }

    public function sendReminderCallback(string $reminderEntityId)
    {
        $response = $this->app['payout-links']->sendReminderCallback($reminderEntityId);

        // $response will be containing 2 keys i.e. status_code and response_body
        return ApiResponse::json($response['response_body'], $response['status_code']);
    }

    public function expireCallback(string $reminderEntityId)
    {
        $response = $this->app['payout-links']->expireCallback($reminderEntityId);

        // $response will be containing 2 keys i.e. status_code and response_body
        return ApiResponse::json($response['response_body'], $response['status_code']);
    }

    public function expireCallbackTestMode(string $reminderEntityId)
    {
        $response = $this->app['payout-links']->expireCallback($reminderEntityId, Mode::TEST);

        // $response will be containing 2 keys i.e. status_code and response_body
        return ApiResponse::json($response['response_body'], $response['status_code']);
    }

    public function update(string $payoutLinkId)
    {
        $input = Request::all();

        $response = $this->app['payout-links']->updatePayoutLink($payoutLinkId, $input, $this->ba->getMerchant());

        return ApiResponse::json($response);
    }

    public function expireCronjob()
    {
        $input = Request::all();

        $response = $this->app['payout-links']->expireCronjob();

        return ApiResponse::json($response);
    }

    public function viewHostedPageData($payoutLinkId)
    {
        $response = $this->app['payout-links']->viewHostedPageData($payoutLinkId);

        return $response;
    }

    public function viewDemoHostedPageData($payoutLinkId)
    {
        $response = $this->app['payout-links']->viewDemoHostedPageData($payoutLinkId);

        return $response;
    }

    private function addCorsHeadersFE(& $response)
    {
        $response->headers->set('Access-Control-Allow-Origin', $this->config['applications.payout_links.app_demo_payout_link_fe_endpoint']);

        $response->headers->set('Access-Control-Allow-Credentials' , 'true');

        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
    }
}
