<?php

namespace RZP\Services\AccountingPayouts;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Http\Request\Requests;
use RZP\Http\Response\StatusCode;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\User\Entity;
use RZP\Trace\TraceCode;

/**
 * This class will be the main file that will talk to
 * Vendor Payment Micro Service for accounting payouts and relay all the responses.
 * This will be as dummy as possible, and will only do conversions
 * Between the Restful API calls and the RPC API calls that the MS understands
 */
class Service
{
    const BASE_PATH                   = 'twirp/accountingpayouts.Accountingpayouts';
    const GET_INTEGRATION_URL         = 'GetIntegrateURL';
    const CASHFLOW_LIST_BANK_ACCOUNTS = 'ListCashFlowBankAccounts';
    const UPDATE_BANK_ACC_MAPPING     = 'UpdateBankAccountMapping';
    const INTEGRATION_APP_INITIATE    = 'IntegrationAppInitiate';
    const INTEGRATION_STATUS          = 'IntegrationStatus';
    const INTEGRATION_STATUS_APP      = 'IntegrationStatusApp';
    const DELETE_INTEGRATION          = 'DeleteIntegration';
    const APP_CREDENTIALS             = 'AppCredentials';
    const SYNC_STATUS_APP             = 'SyncStatusApp';
    const SYNC                        = 'Sync';
    const WAITLIST                    = 'Waitlist';
    const X_APP_MODE                  = 'X-App-Mode';
    const CREATE_INVOICE_FROM_TALLY   = 'CreateInvoiceFromTally';
    const CREATE_TALLY_CONTACTS       = 'CreateTallyContacts';
    const FETCH_SYNC_STATUS           = 'GetSyncStatus';
    const FETCH_TAX_SLABS             = 'GetTaxSlabRatesTally';
    const FETCH_TALLY_INVOICE         = 'FetchTallyInvoice';
    const CANCEL_TALLY_INVOICE        = 'CancelTallyInvoice';
    const FETCH_TALLY_PAYMENTS        = 'FetchTallyPayments';
    const ACKNOWLEDGE_TALLY_PAYMENT   = 'AcknowledgeTallyPayment';
    const INTEGRATE_TALLY             = 'IntegrateTally';
    const DELETE_INTEGRATION_TALLY    = 'DeleteTallyIntegration';
    const GET_DOMAIN                  = 'GetAccountingAppDomains';
    const SET_DOMAIN                  = 'SetAccountingAppDomain';
    const GET_ORGANISATION_INFO       = 'GetOrganisationsAccountingApp';
    const SET_ORGANISATION_INFO       = 'SetOrganisationInfoAccountingApp';
    const GET_TALLY_CASHFLOW_ENTRIES  = 'GetTallyCashFlowEntries';
    const TALLY_ACK_CASHFLOW_ENTRIES  = 'AcknowledgeCashFlowTallyEntries';
    const TALLY_UPDATE_MAPPING        = 'CashFlowUpdateMappingTally';
    const GET_CHART_OF_ACCOUNTS       = 'GetChartOfAccounts';
    const PUT_CHART_OF_ACCOUNTS       = 'PutChartOfAccounts';
    const SYNC_CHART_OF_ACCOUNTS      = 'SyncChartOfAccounts';
    const ADD_OR_UPDATE_SETTINGS      = 'AddOrUpdateSettings';
    const GET_ALL_SETTINGS            = 'GetAllSettings';
    const GET_BANK_STATEMENT_REPORT   = 'BankStatementReport';
    const ZOHO_STATEMENT_SYNC         = 'TriggerZohoBankStatementSync';
    const GET_TALLY_BANK_TRANSACTIONS = 'FetchBankTransactionsTally';
    const ACK_TALLY_BANK_TRANSACTIONS = 'AckBankTransactionsTally';

    const TRIGGER_BANK_STATEMENT_FETCH_CRON     = 'TriggerBankStatementFetchCron';
    const TRIGGER_BANK_STATEMENT_FETCH_MERCHANT = 'TriggerBankStatementFetchMerchant';

    const GET_MERCHANT_BANKING_ACCOUNTS_FOR_TALLY  = 'GetMerchantBankingAccountsForTally';
    const UPDATE_RX_TALLY_LEDGER_MAPPING           = 'UpdateRxTallyLedgerMapping';
    const GET_BANK_TRANSACTIONS_SYNC_STATUS        = 'GetBankTransactionsSyncStatus';
    const CHECK_IF_BANK_MAPPING_REQUIRED           = 'CheckIfBankMappingRequired';

    const X_RAZORPAY_TASKID_HEADER    = 'X-Razorpay-TaskId';
    const X_REQUEST_ID                = 'X-Request-ID';
    const X_MERCHANT_ID               = 'X-Merchant-Id';
    const X_USER_ID                   = 'X-User-Id';
    const X_ORG_ID                    = 'X-Org-Id';

    const TRIGGER_TYPE = 'trigger_type';
    const CRON         = 'cron';
    const MERCHANT_ID  = 'merchant_id';

    protected $app;

    protected $repo;

    protected $trace;

    protected $config;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']['applications.vendor_payments'];

        $this->repo = $app['repo'];
    }

    public function getBankStatementReport(MerchantEntity $merchant = null, array $input = [])
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_BANK_STATEMENT_REPORT);

        $app = array_pull($input, 'app', '');

        return $this->makeRequest($merchant, $url, $input, $app, [], 'POST', MODE::LIVE);
    }


    public function acknowledgeCashFlowEntries(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::TALLY_ACK_CASHFLOW_ENTRIES);

        $app = array_pull($input, 'app', '');

        return $this->makeRequest($merchant, $url, $input, $app, [], 'POST', MODE::LIVE);
    }

    public function updateMappingCashFlowEntries(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::TALLY_UPDATE_MAPPING);

        return $this->makeRequest($merchant, $url, $input, null, [], 'POST', MODE::LIVE);
    }

    public function getCashFlowEntries(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_TALLY_CASHFLOW_ENTRIES);

        return $this->makeRequest($merchant, $url, $input, null, [], 'POST', MODE::LIVE);
    }

    public function getTallyBankTransactions(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_TALLY_BANK_TRANSACTIONS);

        return $this->makeRequest($merchant, $url, $input, null, [], 'POST', MODE::LIVE);
    }

    public function ackTallyBankTransactions(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::ACK_TALLY_BANK_TRANSACTIONS);

        return $this->makeRequest($merchant, $url, $input, null, [], 'POST', MODE::LIVE);
    }

    public function addOrUpdateSettings(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::ADD_OR_UPDATE_SETTINGS);

        $app = array_pull($input, 'app', '');

        return $this->makeRequest($merchant, $url, $input, $app);
    }

    public function getAllSettings(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_ALL_SETTINGS);

        $app = array_pull($input, 'app', '');

        return $this->makeRequest($merchant, $url, [], $app);
    }

    public function updateBAMapping(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::UPDATE_BANK_ACC_MAPPING);

        $app = array_pull($input, 'app', '');

        return $this->makeRequest($merchant, $url, $input, $app);
    }

    public function listCashFlowBA(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::CASHFLOW_LIST_BANK_ACCOUNTS);

        $app = array_pull($input, 'app', '');

        return $this->makeRequest($merchant, $url, $input, $app);
    }

    public function getIntegrationURL(MerchantEntity $merchant, array $input, string $app, Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_INTEGRATION_URL);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        $input["merchant_id"] = $merchant;

        return $this->makeRequest($merchant, $url, $input, $app);
    }

    public function integrationAppInitiate(MerchantEntity $merchant, array $input, string $app, Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::INTEGRATION_APP_INITIATE);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        $input["merchant_id"] = $merchant;

        return $this->makeRequest($merchant, $url, $input, $app);
    }

    public function integrationStatus(MerchantEntity $merchant, array $input, Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::INTEGRATION_STATUS);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        $input["merchant_id"] = $merchant;

        $res = $this->makeRequest($merchant, $url, $input);

        return $res["results"];
    }

    public function integrationStatusApp(MerchantEntity $merchant, array $input, string $app, Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::INTEGRATION_STATUS_APP);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        $input["merchant_id"] = $merchant;

        return $this->makeRequest($merchant, $url, $input, $app);
    }

    public function callback(array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, 'Callback');

        $this->request($url, $input);

        $response = "<script>window.close()</script>";

        return $response;
    }

    public function deleteIntegration(MerchantEntity $merchant, array $input, string $app, Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::DELETE_INTEGRATION);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        $input["merchant_id"] = $merchant;

        return $this->makeRequest($merchant, $url, $input, $app);
    }

    public function appCredentials(MerchantEntity $merchant, array $input, string $app, Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::APP_CREDENTIALS);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        $input["merchant_id"] = $merchant;

        return $this->makeRequest($merchant, $url, $input, $app);
    }

    public function syncStatus(MerchantEntity $merchant, array $input, string $app, Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::SYNC_STATUS_APP);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        $input["merchant_id"] = $merchant;

        return $this->makeRequest($merchant, $url, $input, $app);
    }

    public function sync(MerchantEntity $merchant, array $input, string $app, Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::SYNC);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        $input["merchant_id"] = $merchant;

        return $this->makeRequest($merchant, $url, $input, $app);
    }

    public function syncInternal(MerchantEntity $merchant, array $input, string $app)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::SYNC);

        $input["merchant_id"] = $merchant;

        return $this->makeRequest($merchant, $url, $input, $app);
    }

    public function waitlist(MerchantEntity $merchant, array $input, string $app, Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::WAITLIST);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        $input["merchant_id"] = $merchant;

        return $this->makeRequest($merchant, $url, $input, $app);
    }

    public function createTallyInvoice(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::CREATE_INVOICE_FROM_TALLY);

        return $this->makeRequest($merchant, $url, $input, null, [], 'POST', MODE::LIVE);
    }

    public function createTallyVendors(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::CREATE_TALLY_CONTACTS);

        return $this->makeRequest($merchant, $url, $input, null, [], 'POST', MODE::LIVE);
    }

    public function fetchSyncStatus(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::FETCH_SYNC_STATUS);

        return $this->makeRequest($merchant, $url, $input, null, [], 'POST', MODE::LIVE);
    }

    public function getTaxSlabs(MerchantEntity $merchant)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::FETCH_TAX_SLABS);

        return $this->makeRequest($merchant, $url, [], null, [], 'POST', MODE::LIVE);
    }

    public function fetchTallyInvoice(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::FETCH_TALLY_INVOICE);

        return $this->makeRequest($merchant, $url, $input, null, [], 'POST', MODE::LIVE);
    }

    public function cancelTallyInvoice(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::CANCEL_TALLY_INVOICE);

        return $this->makeRequest($merchant, $url, $input, null, [], 'POST', MODE::LIVE);
    }

    public function fetchTallyPayments(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::FETCH_TALLY_PAYMENTS);

        return $this->makeRequest($merchant, $url, $input, null, [], 'POST', MODE::LIVE);
    }

    public function acknowledgeTallyPayment(MerchantEntity $merchant, string $id, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::ACKNOWLEDGE_TALLY_PAYMENT);

        $input["id"] = $id;

        return $this->makeRequest($merchant, $url, $input, null, [], 'POST', MODE::LIVE);
    }

    public function integrateTally(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::INTEGRATE_TALLY);

        return $this->makeRequest($merchant, $url, $input, null, [], 'POST', MODE::LIVE);
    }

    public function deleteIntegrationTally(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::DELETE_INTEGRATION_TALLY);

        return $this->makeRequest($merchant, $url, $input, null, [], 'POST', MODE::LIVE);
    }

    public function getOrganisationsInfo(MerchantEntity $merchant, string $app)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_ORGANISATION_INFO);

        return $this->makeRequest($merchant, $url, [], $app, [], 'POST', MODE::LIVE);
    }

    public function setOrganisationInfo(MerchantEntity $merchant, string $app, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::SET_ORGANISATION_INFO);

        return $this->makeRequest($merchant, $url, $input, $app, [], 'POST', MODE::LIVE);
    }

    public function getChartOfAccounts(MerchantEntity $merchant, array $input, string $app, Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_CHART_OF_ACCOUNTS);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input, $app);
    }

    public function putChartOfAccounts(MerchantEntity $merchant, array $input, string $app, Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::PUT_CHART_OF_ACCOUNTS);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input, $app);
    }

    public function syncChartOfAccounts(MerchantEntity $merchant, array $input, string $app, Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::SYNC_CHART_OF_ACCOUNTS);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input, $app);
    }

    public function bankStatementFetchTriggerMerchant(MerchantEntity $merchant, array $input)
    {
        $input[self::MERCHANT_ID] = $merchant->getId();

        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::TRIGGER_BANK_STATEMENT_FETCH_MERCHANT);

        return $this->makeRequest(null, $url, $input);
    }

    public function bankStatementFetchTriggerCron()
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::TRIGGER_BANK_STATEMENT_FETCH_CRON);

        return $this->makeRequest(null, $url);
    }

    public function zohoStatementSyncCron()
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::ZOHO_STATEMENT_SYNC);

        return $this->makeRequest(null, $url);
    }

    public function getMerchantBankingAccountsForTally(MerchantEntity $merchant)
    {
        $data[self::MERCHANT_ID] = $merchant->getId();

        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_MERCHANT_BANKING_ACCOUNTS_FOR_TALLY);

        return $this->makeRequest(null, $url, $data);
    }

    public function updateRxTallyLedgerMapping(MerchantEntity $merchant, array $input)
    {
        $input[self::MERCHANT_ID] = $merchant->getId();

        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::UPDATE_RX_TALLY_LEDGER_MAPPING);

        return $this->makeRequest(null, $url, $input);
    }

    public function getBankTransactionsSyncStatus(MerchantEntity $merchant, array $input)
    {
        $input[self::MERCHANT_ID] = $merchant->getId();

        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_BANK_TRANSACTIONS_SYNC_STATUS);

        return $this->makeRequest(null, $url, $input);
    }

    public function checkIfBankMappingRequired(MerchantEntity $merchant)
    {
        $input[self::MERCHANT_ID] = $merchant->getId();

        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::CHECK_IF_BANK_MAPPING_REQUIRED);

        return $this->makeRequest(null, $url, $input);
    }

    protected function makeRequest(MerchantEntity $merchant = null,
                                   string $url = "",
                                   array $data = [],
                                   string $app = null,
                                   array $headers = [],
                                   string $method = 'POST',
                                   string $mode = null)
    {
        if ($merchant !== null) {
            $data = array_merge($data, ['merchant_id' => $merchant->getId()]);
        }

        if ($data !== null) {
            $data["app"] = $app;
        }

        if ($mode == null)
        {
            $headers[self::X_APP_MODE] = $this->app['rzp.mode'] ? $this->app['rzp.mode'] : Mode::LIVE;
        }
        else
        {
            $headers[self::X_APP_MODE] = $mode;
        }

        return $this->request($url, $data, $headers, $method);
    }

    protected function request(string $url = "",
                                   array $data = [],
                                   array $headers = [],
                                   string $method = 'POST')
    {

        $headers['Content-Type'] = 'application/json';

        $headers[self::X_RAZORPAY_TASKID_HEADER] = $this->app['request']->getTaskId();

        $headers[self::X_REQUEST_ID] = $this->app['request']->getId();

        $headers[self::X_MERCHANT_ID] = $this->app['basicauth']->getMerchantId();

        $headers[self::X_USER_ID] = optional($this->app['basicauth']->getUser())->getId() ?? '';

        $headers[self::X_ORG_ID] = $this->app['basicauth']->getOrgId();

        $options = [
            'auth' => ['api', $this->config['secret']],
            'timeout' => $this->config['timeout'],
        ];

        $dataLogged = $data;

        unset($dataLogged['file']);

        $this->trace->info(TraceCode::ACCOUNTING_PAYOUTS_REQUEST,
            [
                'url' => $url,
            ]);

        $response = Requests::request(
            $url,
            $headers,
            json_encode($data),
            $method,
            $options);

        $responseBody = json_decode($response->body, true);

        if ($response->status_code !== StatusCode::SUCCESS) {
            $description = array_pull($responseBody, 'msg', $responseBody);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_ACCOUNTING_PAYOUTS_SERVICE_FAILED,
                null,
                $description,
                $description);
        }

        return $responseBody;
    }
}

