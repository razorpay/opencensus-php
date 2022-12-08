<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use Razorpay\Edge\Passport\Passport;

class LedgerController extends Controller
{
    protected $baseLiveUrl;

    protected $baseTestUrl;

    /**
     * HTTP request headers
     *
     * @var array
     */
    protected $headers;

    protected $mode;

    public function __construct()
    {
        parent::__construct();

        $this->baseLiveUrl = $this->config->get('applications.ledger')['url']['live'];
        $this->baseTestUrl = $this->config->get('applications.ledger')['url']['test'];

        $this->headers = Request::header();
    }

    private function addHeaders() {
        $this->mode = $this->app['rzp.mode'];

        // add passport header
        if($this->mode === 'live') {
            $this->headers[Passport::PASSPORT_JWT_V1] = $this->ba->getPassportJwt($this->baseLiveUrl);
        } else {
            $this->headers[Passport::PASSPORT_JWT_V1] = $this->ba->getPassportJwt($this->baseTestUrl);
        }
    }

    public function createAccount()
    {
        $this->addHeaders();

        $response = $this->app['ledger']->createAccount($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createAccountsOnEvent()
    {
        $this->addHeaders();

        $response = $this->app['ledger']->createAccountsOnEvent($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createAccountsInBulk()
    {
        $response = $this->app['ledger']->createAccountsInBulk($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function activateAccount()
    {
        $response = $this->app['ledger']->activateAccount($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function deactivateAccount()
    {
        $response = $this->app['ledger']->deactivateAccount($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function archiveAccount()
    {
        $response = $this->app['ledger']->archiveAccount($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function updateAccount()
    {
        $response = $this->app['ledger']->updateAccount($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function updateAccountByEntitiesAndMerchantID()
    {
        $response = $this->app['ledger']->updateAccountByEntitiesAndMerchantID($this->input, $this->headers, true);
        return ApiResponse::json($response['body'], $response['code']);
    }

    public function updateAccountDetail()
    {
        $response = $this->app['ledger']->updateAccountDetail($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createJournalFromBatch()
    {
        // Add batch id to journal request notes
        if (isset($this->input['notes']) === true)
        {
            $this->input['notes']['batch_id'] = $this->app['request']->header(RequestHeader::X_Batch_Id, null);
        }
        else
        {
            $this->input['notes'] = [
                'batch_id' => $this->app['request']->header(RequestHeader::X_Batch_Id, null)
            ];
        }

        $response = $this->app['ledger']->createJournal($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createJournal()
    {
        $this->addHeaders();

        $response = $this->app['ledger']->createJournal($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchByTransactor()
    {
        $response = $this->app['ledger']->fetchByTransactor($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createLedgerConfig()
    {
        $this->addHeaders();

        $response = $this->app['ledger']->createLedgerConfig($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function UpdateLedgerConfig()
    {
        $response = $this->app['ledger']->updateLedgerConfig($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function DeleteLedgerConfig()
    {
        $response = $this->app['ledger']->deleteLedgerConfig($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function requestGovernor()
    {
        $response = $this->app['ledger']->requestGovernor($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetch()
    {
        $response = $this->app['ledger']->fetch($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchMultiple()
    {
        $this->addHeaders();

        $response = $this->app['ledger']->fetchMultiple($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchFilter()
    {
        $this->addHeaders();

        $response = $this->app['ledger']->fetchFilter($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchAccountFormFieldOptions()
    {
        $this->addHeaders();

        $response = $this->app['ledger']->fetchAccountFormFieldOptions($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchJournalFormFieldOptions()
    {
        $this->addHeaders();

        $response = $this->app['ledger']->fetchJournalFormFieldOptions($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function replayJournalRejectedEvents()
    {
        $response = $this->app['ledger']->replayJournalRejectedEvents($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchLedgerConfigFormFieldOptions()
    {
        $this->addHeaders();

        $response = $this->app['ledger']->fetchLedgerConfigFormFieldOptions($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchAccountTypes()
    {
        $response = $this->app['ledger']->fetchAccountTypes($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchAccountsByEntitiesAndMerchantID()
    {
        $response = $this->app['ledger']->fetchAccountsByEntitiesAndMerchantID($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function deleteMerchants()
    {
        $this->addHeaders();

        $response = $this->app['ledger']->deleteMerchants($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createJournalCron()
    {
        $response = $this->app['ledger']->createJournalCron($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

}
