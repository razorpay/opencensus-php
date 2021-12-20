<?php

namespace RZP\Services;

use Request;
use RZP\Http\Request\Requests;
use RZP\Exception;
use Requests_Exception;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

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

    const AccountBaseURL = '/twirp/rzp.ledger.account.v1.AccountAPI';

    const AccountDetailBaseURL = '/twirp/rzp.ledger.account_detail.v1.AccountDetailAPI';

    const JournalBaseURL = '/twirp/rzp.ledger.journal.v1.JournalAPI';

    const LedgerConfigBaseURL = '/twirp/rzp.ledger.ledger_config.v1.LedgerConfigAPI';

    const GovernorURL = '/twirp/rzp.ledger.governor.v1.GovernorAPI';

    const CommonDashboardURL = '/twirp/rzp.common.dashboard.v1.Dashboard';

    const DashboardURL = '/twirp/rzp.ledger.dashboard.v1.DashboardAPI';

    const URLS = [
        'create'                            => 'Create',
        'createOnEvent'                     => 'CreateOnEvent',
        'createInBulk'                      => 'CreateInBulk',
        'activate'                          => 'Activate',
        'deactivate'                        => 'Deactivate',
        'archive'                           => 'Archive',
        'update'                            => 'Update',
        'delete'                            => 'Delete',
        'request'                           => 'Request',
        'fetch'                             => 'Fetch',
        'fetchMultiple'                     => 'FetchMultiple',
        'fetchFilter'                       => 'FetchFilter',
        'fetchAccountFormFieldOptions'      => 'FetchAccountFormFieldOptions',
        'fetchJournalFormFieldOptions'      => 'FetchJournalFormFieldOptions',
        'fetchLedgerConfigFormFieldOptions' => 'FetchLedgerConfigFormFieldOptions',
        'fetchAccountTypes'                 => 'FetchAccountTypes',
        'fetchFundAccountTypes'             => 'FetchFundAccountTypes',
        'fetchMerchantLedgerEntryByID'      => 'FetchMerchantLedgerEntryByID',
        'deleteMerchants'                   => 'DeleteMerchants',
    ];

    // Headers
    const ACCEPT               = 'Accept';
    const X_MODE               = 'X-Mode';
    const ADMIN_EMAIL          = 'X-Dashboard-Admin-Email';
    const CONTENT_TYPE         = 'Content-Type';
    const X_REQUEST_ID         = 'X-Request-ID';
    const LEDGER_TENANT_HEADER = 'Ledger-Tenant';

    const REQUEST_TIMEOUT = 60; // In seconds

    const RESPONSE_CODE          = 'code';
    const RESPONSE_BODY          = 'body';

    const MODE = 'mode';

    /**
     * Ledger constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->trace = $app['trace'];

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
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function createAccount($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountBaseURL . '/' . self::URLS['create'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function createAccountsOnEvent($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountBaseURL . '/' . self::URLS['createOnEvent'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function createAccountsInBulk($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountBaseURL . '/' . self::URLS['createInBulk'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function activateAccount($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountBaseURL . '/' . self::URLS['activate'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function deactivateAccount($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountBaseURL . '/' . self::URLS['deactivate'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function archiveAccount($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountBaseURL . '/' . self::URLS['archive'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function updateAccount($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountBaseURL . '/' . self::URLS['update'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function updateAccountDetail($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::AccountDetailBaseURL . '/' . self::URLS['update'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function createJournal($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::JournalBaseURL . '/' . self::URLS['create'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function createLedgerConfig($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::LedgerConfigBaseURL . '/' . self::URLS['create'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function updateLedgerConfig($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::LedgerConfigBaseURL . '/' . self::URLS['update'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function deleteLedgerConfig($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::LedgerConfigBaseURL . '/' . self::URLS['delete'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function requestGovernor($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::GovernorURL . '/' . self::URLS['request'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param $input
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetch($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::CommonDashboardURL . '/' . self::URLS['fetch'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param $input
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchMultiple($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::CommonDashboardURL . '/' . self::URLS['fetchMultiple'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param $input
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchFilter($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['fetchFilter'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param $input
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchAccountFormFieldOptions($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['fetchAccountFormFieldOptions'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param $input
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchJournalFormFieldOptions($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['fetchJournalFormFieldOptions'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param $input
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchLedgerConfigFormFieldOptions($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['fetchLedgerConfigFormFieldOptions'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param $input
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchAccountTypes($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['fetchAccountTypes'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param $input
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function deleteMerchants($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['deleteMerchants'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param $input
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchFundAccountTypes($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['fetchFundAccountTypes'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param $input
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchMerchantLedgerEntryByID($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::DashboardURL . '/' . self::URLS['fetchMerchantLedgerEntryByID'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    protected function sendRequest(
        string $endpoint,
        string $method,
        array $data = [],
        bool $throwExceptionOnFailure = false): array
    {
        $request = $this->generateRequest($endpoint, $method, $data);

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
     * @param array $request
     *
     * @return \Requests_Response
     * @throws \Throwable
     */
    protected function sendLedgerRequest(array $request): \Requests_Response
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
     * @param \Requests_Response $response
     * @param bool               $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     */
    protected function parseResponse(\Requests_Response $response, bool $throwExceptionOnFailure = false): array
    {
        $code = $response->status_code;

        if (($throwExceptionOnFailure === true) and
            (in_array($code, [200, 201, 204, 302], true) === false))
        {

            throw new Exception\RuntimeException(
                'Unexpected response code received from Ledger service.',
                [
                    'status_code'   => $code,
                    'response_body' => json_decode($response->body),
                ]);
        }

        return [
            'body' => json_decode($response->body, true),
            'code' => $code,
        ];
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     *
     * @return array
     */
    protected function generateRequest(string $endpoint, string $method, array $data): array
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

        $this->headers[self::LEDGER_TENANT_HEADER] = $this->getTenant($data);

        // json encode if data is must, else ignore.
        if (in_array($method, [Requests::POST, Requests::PATCH, Requests::PUT], true) === true)
        {
            $data = (empty($data) === false) ? json_encode($data) : null;
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
            'content'   => $data
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
     * @param array  $data
     *
     * @return string
     */
    protected function getTenant(array $data): string
    {
        $tenant = Request::header(self::LEDGER_TENANT_HEADER);

        // TODO: remove conditional X after everything is in place
        // reading tenant from header for rest call
        if ($tenant !== NULL)
        {
            return $tenant;
        }
        // reading from request if call is internal.
        // Eg if payout want to trigger some endpoint the header will not contain the tenant
        elseif (isset($data['tenant']) === true and $data['tenant'] !== NULL)
        {
            return $data['tenant'];
        }
        // for backward compatibility adding X as default value
        // As we have only onboarded X use cases till now
        else
        {
            return 'X';
        }
    }
}
