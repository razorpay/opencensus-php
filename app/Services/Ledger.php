<?php

namespace RZP\Services;

use Request;
use RZP\Exception;
use RZP\Error\Error;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Base\RepositoryManager;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use Razorpay\Edge\Passport\Passport;
use RZP\Constants\Entity as EntityConstant;
use Symfony\Component\HttpFoundation\Response;
use RZP\Models\Payout\Service as PayoutService;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Reversal\Service as ReversalService;
use RZP\Models\Adjustment\Service as AdjustmentService;
use RZP\Models\BankTransfer\Service as BankTransferService;
use RZP\Models\FundAccount\Validation\Service as FAVService;

class Ledger
{
    protected $trace;

    protected $config;

    protected $baseLiveUrl;

    protected $baseTestUrl;

    protected $mode;

    protected $key;

    protected $secret;

    protected $proxy;

    protected $request;

    protected $headers;

    protected $auth;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    const AccountBaseURL = '/twirp/rzp.ledger.account.v1.AccountAPI';

    const AccountDetailBaseURL = '/twirp/rzp.ledger.account_detail.v1.AccountDetailAPI';

    const JournalBaseURL = '/twirp/rzp.ledger.journal.v1.JournalAPI';

    const LedgerConfigBaseURL = '/twirp/rzp.ledger.ledger_config.v1.LedgerConfigAPI';

    const GovernorURL = '/twirp/rzp.ledger.governor.v1.GovernorAPI';

    const CommonDashboardURL = '/twirp/rzp.common.dashboard.v1.Dashboard';

    const DashboardURL = '/twirp/rzp.ledger.dashboard.v1.DashboardAPI';


    const MERCHANT_IDS = 'merchant_ids';

    const ENTITIES = 'entities';

    const BANKING_ACCOUNT_ID = 'banking_account_id';

    const BANKING_ACCOUNT_STMT_DETAIL_ID = 'banking_account_stmt_detail_id';

    const URLS = [
        'create'                                => 'Create',
        'createOnEvent'                         => 'CreateOnEvent',
        'createInBulk'                          => 'CreateInBulk',
        'activate'                              => 'Activate',
        'deactivate'                            => 'Deactivate',
        'archive'                               => 'Archive',
        'update'                                => 'Update',
        'delete'                                => 'Delete',
        'request'                               => 'Request',
        'fetch'                                 => 'Fetch',
        'fetchMultiple'                         => 'FetchMultiple',
        'fetchFilter'                           => 'FetchFilter',
        'fetchAccountFormFieldOptions'          => 'FetchAccountFormFieldOptions',
        'fetchJournalFormFieldOptions'          => 'FetchJournalFormFieldOptions',
        'replayJournalRejectedEvents'           => 'ReplayJournalRejectedEvents',
        'fetchLedgerConfigFormFieldOptions'     => 'FetchLedgerConfigFormFieldOptions',
        'fetchAccountTypes'                     => 'FetchAccountTypes',
        'fetchMerchantLedgerEntryByID'          => 'FetchMerchantLedgerEntryByID',
        'deleteMerchants'                       => 'DeleteMerchants',
        'fetchMerchantAccounts'                 => 'FetchMerchantAccounts',
        'fetchByTransactor'                     => 'FetchByTransactor',
        'fetchById'                             => 'FetchById',
        'fetchByEntitiesAndMerchantID'          => 'FetchByEntitiesAndMerchantID',
        'updateAccountByEntitiesAndMerchantID'  => 'UpdateByEntitiesAndMerchantID'
    ];

    // Headers
    const ACCEPT                         = 'Accept';
    const X_MODE                         = 'X-Mode';
    const ADMIN_EMAIL                    = 'X-Dashboard-Admin-Email';
    const CONTENT_TYPE                   = 'Content-Type';
    const X_REQUEST_ID                   = 'X-Request-ID';
    const TENANT                         = 'tenant';
    const LEDGER_TENANT_HEADER           = 'ledger-tenant';
    const IDEMPOTENCY_KEY_HEADER         = 'idempotency-key';
    const LEDGER_INTEGRATION_MODE_HEADER = 'Ledger-Integration-Mode';

    const REQUEST_TIMEOUT = 60; // In seconds

    const RESPONSE_CODE          = 'code';
    const RESPONSE_BODY          = 'body';

    const MODE = 'mode';

    const RAZORPAY_X_TENANT = 'X';

    /**
     * Ledger constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->repo = $app['repo'];

        $this->config = $app['config']->get('applications.ledger');

        $this->baseLiveUrl = $this->config['url']['live'];
        $this->baseTestUrl = $this->config['url']['test'];

        // Refer: https://github.com/razorpay/api/issues/6385
        $this->mode = $app['rzp.mode'];

        $this->request = $app['request'];

        $this->key = $this->config['ledger_key'];

        $this->secret = $this->config['ledger_secret'];

        $this->auth = $app['basicauth'];

        $this->setHeaders();
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function createAccount($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountBaseURL . '/' . self::URLS['create'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function createAccountsOnEvent($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountBaseURL . '/' . self::URLS['createOnEvent'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function createAccountsInBulk($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountBaseURL . '/' . self::URLS['createInBulk'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function activateAccount($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountBaseURL . '/' . self::URLS['activate'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function deactivateAccount($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountBaseURL . '/' . self::URLS['deactivate'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function archiveAccount($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountBaseURL . '/' . self::URLS['archive'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function updateAccount($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountBaseURL . '/' . self::URLS['update'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function updateAccountByEntitiesAndMerchantID($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountBaseURL . '/' . self::URLS['updateAccountByEntitiesAndMerchantID'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function updateAccountDetail($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountDetailBaseURL . '/' . self::URLS['update'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function createJournal($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::JournalBaseURL . '/' . self::URLS['create'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function createBulkJournal($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        $request = $this->transformBulkJournalRequest($requestBody);

        return $this->sendRequest(self::JournalBaseURL . '/' . self::URLS['createInBulk'],
            Requests::POST, $request, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchById($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::JournalBaseURL . '/' . self::URLS['fetchById'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchByTransactor($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::JournalBaseURL . '/' . self::URLS['fetchByTransactor'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function createLedgerConfig($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::LedgerConfigBaseURL . '/' . self::URLS['create'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function updateLedgerConfig($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::LedgerConfigBaseURL . '/' . self::URLS['update'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function deleteLedgerConfig($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::LedgerConfigBaseURL . '/' . self::URLS['delete'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function requestGovernor($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::GovernorURL . '/' . self::URLS['request'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetch($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::CommonDashboardURL . '/' . self::URLS['fetch'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchMultiple($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::CommonDashboardURL . '/' . self::URLS['fetchMultiple'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchFilter($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['fetchFilter'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchAccountFormFieldOptions($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['fetchAccountFormFieldOptions'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchJournalFormFieldOptions($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['fetchJournalFormFieldOptions'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function replayJournalRejectedEvents($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['replayJournalRejectedEvents'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchLedgerConfigFormFieldOptions($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['fetchLedgerConfigFormFieldOptions'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchAccountTypes($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['fetchAccountTypes'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function deleteMerchants($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        $ledgerReverseShadowMerchantIds = [];
        if (empty(self::ENTITIES) === false)
        {
            if (empty($requestBody[self::ENTITIES][self::BANKING_ACCOUNT_ID]) === false)
            {
                // if RX VA case, prevent deletion of VA's reverse shadow merchants
                $ledgerReverseShadowMerchantIds = $this->repo->feature->getMerchantIdsHavingFeature(Feature\Constants::LEDGER_REVERSE_SHADOW, $requestBody[self::MERCHANT_IDS]);
            }

            $requestBody[self::MERCHANT_IDS] = empty($ledgerReverseShadowMerchantIds) ? $requestBody[self::MERCHANT_IDS] : array_diff($requestBody[self::MERCHANT_IDS], $ledgerReverseShadowMerchantIds);
        }

        $this->trace->info(TraceCode::LEDGER_DELETE_MERCHANTS_REQUEST, [
            'excluded_merchant_ids' => $ledgerReverseShadowMerchantIds,
            'merchant_ids_to_be_deleted' => $requestBody[self::MERCHANT_IDS],
        ]);

        // Call ledger only if there is something to delete
        if (empty($requestBody[self::MERCHANT_IDS]) === true)
        {
            throw new Exception\RuntimeException(
                'No merchants to delete after prerequisite checks',
                [
                    'status_code'   => 500,
                    'response_body' => null,
                ]);
        }

        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['deleteMerchants'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchMerchantAccounts($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['fetchMerchantAccounts'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchAccountsByEntitiesAndMerchantID($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountBaseURL . '/' . self::URLS['fetchByEntitiesAndMerchantID'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param      $requestBody
     * @param      $requestHeaders
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchMerchantLedgerEntryByID($requestBody, $requestHeaders = [], bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['fetchMerchantLedgerEntryByID'],
            Requests::POST, $requestBody, $requestHeaders, $throwExceptionOnFailure);
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array $body
     * @param array $headers
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    protected function sendRequest(
        string $endpoint,
        string $method,
        array $body = [],
        array $headers = [],
        bool $throwExceptionOnFailure = false): array
    {
        $request = $this->generateRequest($endpoint, $method, $body, $headers);

        $response = $this->sendLedgerRequest($request);

        $this->trace->info(TraceCode::LEDGER_RESPONSE, [
            'response' => $response->body
        ]);

        $decodedResponse = json_decode($response->body, true);

        $this->trace->info(TraceCode::LEDGER_RESPONSE, $decodedResponse ?? []);

        return $this->parseResponse($response, $throwExceptionOnFailure);
    }

    /**
     * Function used to set headers for the request
     */
    protected function setHeaders()
    {
        $headers = [];

        $headers[self::ACCEPT]               = 'application/json';
        $headers[self::CONTENT_TYPE]         = 'application/json';
        $headers[self::X_MODE]               = $this->mode;
        $headers[self::ADMIN_EMAIL]          = $this->getAdminEmail();
        $headers[self::X_REQUEST_ID]         = $this->request->getId();

        $this->headers = $headers;
    }

    /**
     * @param array $headers
     * Function used to add headers to the request
     */
    private function addHeaders(array $headers)
    {
        // Add ledger-tenant header
        if(isset($headers[self::LEDGER_TENANT_HEADER]) === true)
        {
            if(is_array($headers[self::LEDGER_TENANT_HEADER]) === true)
            {
                $this->headers[self::LEDGER_TENANT_HEADER] = $headers[self::LEDGER_TENANT_HEADER][0];
            }
            else
            {
                $this->headers[self::LEDGER_TENANT_HEADER] = $headers[self::LEDGER_TENANT_HEADER];
            }
        }
        // for backward compatibility adding X as default value
        // As we have only onboarded X use cases till now
        else
        {
            $this->trace->info(TraceCode::LEDGER_REQUEST_HEADER_MISSING, [
                'value' => self::LEDGER_TENANT_HEADER,
            ]);

            $this->headers[self::LEDGER_TENANT_HEADER] = self::RAZORPAY_X_TENANT;
        }

        // Add idempotency-key header
        if(isset($headers[self::IDEMPOTENCY_KEY_HEADER]) === true)
        {
            $this->headers[self::IDEMPOTENCY_KEY_HEADER] = $headers[self::IDEMPOTENCY_KEY_HEADER];
        }

        // Add Ledger-Integration-Mode header
        if(isset($headers[self::LEDGER_INTEGRATION_MODE_HEADER]) === true)
        {
            $this->headers[self::LEDGER_INTEGRATION_MODE_HEADER] = $headers[self::LEDGER_INTEGRATION_MODE_HEADER];
        }

        // Add passport header
        if(isset($headers[Passport::PASSPORT_JWT_V1]) === true)
        {
            $this->headers[Passport::PASSPORT_JWT_V1] = $headers[Passport::PASSPORT_JWT_V1];
        }
    }

    /**
     * @param array $request
     *
     * @return \WpOrg\Requests\Response
     * @throws \Throwable
     */
    protected function sendLedgerRequest(array $request): \WpOrg\Requests\Response
    {
        $this->traceRequest($request);

        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_FAILURE_EXCEPTION,
                [
                    'data' => $e->getMessage()
                ]);

            throw $e;
        }

        return $response;
    }

    /**
     * @param array $request
     */
    protected function traceRequest(array $request)
    {
        unset($request['options']['auth']);

        $this->trace->info(TraceCode::LEDGER_REQUEST, $request);
    }

    /**
     * @param \WpOrg\Requests\Response $response
     * @param bool               $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     */
    public function parseResponse($response, bool $throwExceptionOnFailure = false): array
    {
        $code = $response->status_code;

        $response = [
            'body' => json_decode($response->body, true),
            'code' => $code,
        ];

        if (($throwExceptionOnFailure === true) and
            (in_array($code, [200, 201, 204, 302], true) === false))
        {
            if (($code >= Response::HTTP_BAD_REQUEST) and ($code <Response::HTTP_INTERNAL_SERVER_ERROR))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    [
                        'status_code'   => $code,
                        'response_body' => $response['body'],
                    ],
                    $response['body']['msg']);
            }
            else
            {
                throw new Exception\RuntimeException(
                    'Unexpected response code received from Ledger service.',
                    [
                        'status_code'   => $code,
                        'response_body' => $response['body'],
                    ]);
            }
        }

        return $response;
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $body
     * @param array  $headers
     *
     * @return array
     */
    protected function generateRequest(string $endpoint, string $method, array $body, array $headers): array
    {
        $url = '';

        if ($this->mode === Mode::LIVE)
        {
            $url = $this->baseLiveUrl . $endpoint;
        }

        if ($this->mode === Mode::TEST)
        {
            $url = $this->baseTestUrl . $endpoint;
        }

        $this->addHeaders($headers);

        // json encode if data is must, else ignore.
        if (in_array($method, [Requests::POST, Requests::PATCH, Requests::PUT], true) === true)
        {
            $body = (empty($body) === false) ? json_encode($body) : null;
        }

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [
                $this->key,
                $this->secret
            ],
        ];

        return [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $this->headers,
            'options'   => $options,
            'content'   => $body
        ];
    }

    /**
     * This function transforms the request. This transformation is present in ledger-sdk,
     * but is absent in API, so when calling the endpoint directly, explicit transformation needs to be done.
     * @param $requestBody
     * @return array
     */
    protected function transformBulkJournalRequest($requestBody): array {
        $bulkRequest = [];
        foreach ($requestBody[LedgerConstants::JOURNALS] as $journals) {
            $request = [
                LedgerConstants::CURRENCY         => $requestBody[LedgerConstants::CURRENCY],
                LedgerConstants::TRANSACTOR_ID    => $requestBody[LedgerConstants::TRANSACTOR_ID],
                LedgerConstants::TRANSACTOR_EVENT => $requestBody[LedgerConstants::TRANSACTOR_EVENT],
                LedgerConstants::TRANSACTION_DATE => $requestBody[LedgerConstants::TRANSACTION_DATE]
            ];
            $request[LedgerConstants::MERCHANT_ID]        = $journals[LedgerConstants::MERCHANT_ID];
            $request[LedgerConstants::IDENTIFIERS]        = $journals[LedgerConstants::IDENTIFIERS];
            $request[LedgerConstants::ADDITIONAL_PARAMS]  = $journals[LedgerConstants::ADDITIONAL_PARAMS];
            $request[LedgerConstants::API_TRANSACTION_ID] = $journals[LedgerConstants::API_TRANSACTION_ID];
            $request[LedgerConstants::NOTES]              = $journals[LedgerConstants::NOTES];
            $request[LedgerConstants::MONEY_PARAMS]       = $journals[LedgerConstants::MONEY_PARAMS];

            array_push($bulkRequest, $request);
        }
        return [
            LedgerConstants::JOURNALS => $bulkRequest
        ];
    }

    /**
     * @return string
     */
    protected function getAdminEmail(): string
    {
        return $this->auth->getDashboardHeaders()['admin_email'] ?? '';
    }

    /**
     * @param $input
     * @return string[]
     * @throws LogicException
     */
    public function createJournalCron($input): array
    {
        $this->trace->info(TraceCode::LEDGER_JOURNAL_CRON_INIT, [
            'input' => $input,
        ]);

        $limit = 500;
        $blacklistIds = [];
        $whitelistIds = [];

        if(array_key_exists('limit', $input))
        {
            $limit = $input['limit'];
        }
        if(array_key_exists('blacklist_ids', $input))
        {
            $blacklistIds = $input['blacklist_ids'];
        }
        if(array_key_exists('whitelist_ids', $input))
        {
            $whitelistIds = $input['whitelist_ids'];
        }

        switch ($input['entity'])
        {
            case EntityConstant::PAYOUT:
                (new PayoutService())->createPayoutViaLedgerCronJob($blacklistIds, $whitelistIds, $limit);
                break;

            case EntityConstant::FUND_ACCOUNT_VALIDATION:
                (new FAVService())->createFundAccountValidationViaLedgerCronJob($blacklistIds, $whitelistIds, $limit);
                break;

            case EntityConstant::REVERSAL:
                (new ReversalService())->createReversalViaLedgerCronJob($blacklistIds, $whitelistIds, $limit);
                break;

            case EntityConstant::ADJUSTMENT:
                (new AdjustmentService())->createAdjustmentViaLedgerCronJob($blacklistIds, $whitelistIds, $limit);
                break;

            case EntityConstant::BANK_TRANSFER:
                (new BankTransferService())->createBankTransferViaLedgerCronJob($blacklistIds, $whitelistIds, $limit);
                break;

            default:
                throw new LogicException('entity mapping not implemented at ledger journal cron : ' . $input['entity']);
        }
        return [
            'code' => 200,
            'body' => [
                'success',
            ]
        ];
    }
}
