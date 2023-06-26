<?php

namespace RZP\Services;

use Cache;
use ApiResponse;
use RZP\Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;

use RZP\Http\RequestHeader;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Environment;
use RZP\Http\Request\Requests;
use RZP\Http\BasicAuth\BasicAuth;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\BankingAccountService\Core;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\BankingAccount\Gateway\Icici;
use RZP\Models\BankingAccountService\Channel;
use RZP\Models\BankingAccountService\Constants;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;
use RZP\Models\BankingAccountService\Constants as Fields;
use RZP\Models\Merchant\Balance\Repository as BalanceRepo;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;

class BankingAccountService
{
    const CONTENT_TYPE_JSON = 'application/json';
    const DATA              = 'data';

    const GET   = 'GET';
    const POST  = 'POST';
    const PATCH = 'PATCH';

    const GET_GENERATED_CREDENTIALS_PATH        = 'internal/rbl/banking_account/%s/credentials';
    const GENERATE_CREDENTIALS_PATH             = 'internal/rbl/credentials';
    const DOWNLOAD_DOCKET_PDF_PATH              = 'internal/rbl/banking_account/%s/credentials/download?business_category=%s&merchant_name=%s';
    const PARTNER_LMS_RBL_APPLICATIONS          = 'partner_lms/rbl/applications';
    const PARTNER_LMS_RBL_ASSIGN_BANK_POC       = 'partner_lms/rbl/business/%s/application/%s/assign_poc';
    const PARTNER_LMS_RBL_ACTIVITY              = 'partner_lms/rbl/business/%s/application/%s/activity';
    const PARTNER_LMS_RBL_GET_COMMENTS          = 'partner_lms/rbl/business/%s/application/%s/comments';
    const PARTNER_LMS_RBL_ADD_COMMENT           = 'partner_lms/rbl/business/%s/application/%s/comment';
    const PARTNER_LMS_RBL_COMPOSITE_APPLICATION = 'partner_lms/rbl/business/%s/composite-applications/%s';
    const CREATE_BUSINESS                       = 'business';
    const CREATE_RBL_ONBOARDING_APPLICATION     = 'business/%s/apply';
    const COMPOSITE_APPLICATION                 = 'business/%s/composite-applications/%s';
    const SEARCH_LEADS_PATH                     = 'admin/leads/search';
    const GET_APPLICATION_STATUS_LOGS           = 'admin/business/%s/application/%s/application_status_logs?sort_order=desc';
    const GET_APPLICATION_COMMENTS              = 'admin/business/%s/application/%s/comments';
    const CREATE_APPLICATION_COMMENT            = 'admin/business/%s/application/%s/comment';
    const UPDATE_APPLICATION_COMMENT            = 'admin/business/%s/application/%s/comments/%s';
    const BULK_ASSIGN_ACCOUNT_MANAGER           = 'admin/banking_accounts/bulk_assign_account_manager';
    const ACTIVATE_RBL_ACCOUNT                  = 'admin/business/%s/applications/%s/activate_account';
    const RBL_ACCOUNT_OPENING_WEBHOOK           = 'webhooks/rbl/account_opening';

    protected $baseUrl;

    protected $key;

    protected $secret;

    /* @var $ba BasicAuth */
    protected $ba;

    protected $timeOut;

    protected $trace;

    protected $app;

    public function __construct($app)
    {
        $this->app     = $app;
        $this->ba      = $app['basicauth'];
        $basConfig     = $app['config']->get('applications.banking_account_service');
        $this->secret  = $basConfig['secret'];
        $this->baseUrl = $basConfig['url'];
        $this->timeOut = $basConfig['timeout'];
        $this->trace   = $app['trace'];
    }

    /**
     * Fetches icici ca
     *
     * @param string $merchantId
     *
     * @return array
     */
    public function fetchAccountDetails(string $merchantId): array
    {
        $repo = new BalanceRepo();

        $balances = $repo->getBalancesByMerchantIdChannelsAndAccountType($merchantId, Channel::getDirectTypeChannels(), AccountType::DIRECT);

        if (count($balances) === 0)
        {
            return [];
        }

        $this->isBusinessExists($merchantId);
        $account = [];
        foreach ($balances as $balance)
        {
            $account = $this->fetchBankingAccountByAccountNumberAndChannel($merchantId, $balance->getAccountNumber(), $balance->getChannel());
            if ($account[Constants::STATUS] === "ACTIVE")
            {
                return $account;
            }
        }

        return $account;
    }

    /**
     * Returns banking sensitive credentials like key, secret required to communicate to mozart
     * for fetching latest balances, payouts.
     *
     * @param string $merchantId
     * @param string $channel
     * @param string $accountNumber
     *
     * @return array
     */
    public function fetchBankingCredentials(string $merchantId, string $channel, string $accountNumber)
    {
        $businessId = $this->getBusinessId($merchantId);

        $path = 'business/' . $businessId . '/banking_account_by_account_number/' . $accountNumber . '/credentials';

        $headers = [
            Fields::CHANNEL => $channel,
        ];

        $response = $this->sendRequestAndProcessResponse($path, 'GET', [], $headers);

        if (isset($response['data']) === true)
        {
            if ($channel === Channel::ICICI)
            {
                $response = [
                    Icici\Fields::CORP_ID     => $response['data']['corp_id'] ?? null,
                    Icici\Fields::CORP_USER   => $response['data']['user_id'] ?? null,
                    Icici\Fields::URN         => $response['data']['urn'] ?? null,
                    Icici\Fields::CREDENTIALS => $response['data']['credentials'] ?? null,
                ];
            }
            else
            {
                $response = $response['data'];
            }

        }
        else
        {
            $this->trace->error(TraceCode::BANKING_ACCOUNT_SERVICE_ERROR_FETCH_CREDENTIALS,
                                [
                                    'error' => $response['error'],
                                ]
            );

            $response = $response['error'];
        }

        return $response;
    }

    /**
     * Fetches icici ca source fund_account_id that's registered at FTS
     *
     * @param string $merchantId
     * @param string $channel
     * @param string $accountNumber
     *
     * @return array
     */
    public function fetchFtsFundAccountIdFromBas(string $merchantId, string $channel, string $accountNumber)
    {
        $this->isBusinessExists($merchantId);

        $key = 'bas_fts_fund_account_id_' . $accountNumber . '_' . $channel;

        $ftsFundAccId = Cache::get($key);

        if (empty($ftsFundAccId) === true)
        {
            $bankingAccount = $this->fetchBankingAccountByAccountNumberAndChannel($merchantId, $accountNumber, $channel);

            $ftsFundAccId = $bankingAccount['fts_fund_account_id'];

            $expiresAt = Carbon::now()->addMinutes(30);

            Cache::put($key, $ftsFundAccId, $expiresAt);
        }

        return $ftsFundAccId;
    }

    public function fetchBankingAccountByAccountNumberAndChannel($merchantId, $accountNumber, $channel)
    {
        $businessId = $this->getBusinessId($merchantId);

        $path = 'business/' . $businessId . '/banking_account_by_account_number/' . $accountNumber;

        $headers = [
            Fields::CHANNEL => $channel,
        ];

        $response = $this->sendRequestAndProcessResponse($path, 'GET', [], $headers);

        return $response['data'];
    }

    /**
     * @throws \Exception
     */
    public function updateBVSValidationStatus(array $payload)
    {
        $path = 'document/bvs_validation_status';

        try
        {
            $this->sendRequestAndProcessResponse($path, 'POST', $payload);
        }
        catch (\Exception $ex)
        {
            $this->trace->error(TraceCode::REQUEST_TO_BAS_DOCUMENT_STATUS_FAIL, ['ERROR' => $ex]);

            try
            {
                $this->trace->info(TraceCode::RETRYING_REQUEST_TO_BAS_DOCUMENT_STATUS);

                $this->sendRequestAndProcessResponse($path, 'POST', $payload);
            }
            catch (\Exception $ex)
            {
                $this->trace->error(TraceCode::REQUEST_TO_BAS_DOCUMENT_STATUS_FAIL, ['ERROR' => $ex]);

                throw $ex;
            }
        }
    }

    /**
     *
     * @param string $balanceId
     *
     * @return mixed
     */
    public function fetchBankingAccountId(string $balanceId)
    {
        /* @var BalanceEntity $balance */
        $balance = $this->app['repo']->balance->findOrFailById($balanceId);

        $this->isBusinessExists($balance->getMerchantId());

        $key = 'bas_banking_account_id_' . $balance->getAccountNumber() . '_' . $balance->getChannel();

        $bankingAccountId = Cache::get($key);

        if (empty($bankingAccountId) === true)
        {
            $bankingAccount = $this->fetchBankingAccountByAccountNumberAndChannel($balance->getMerchantId(), $balance->getAccountNumber(), $balance->getChannel());

            $bankingAccountId = $bankingAccount['id'];

            $expiresAt = Carbon::now()->addMinutes(30);

            Cache::put($key, $bankingAccountId, $expiresAt);
        }

        //FE searches the id with this prefix
        $bcc = BankingAccountEntity::getIdPrefix();

        return $bcc . $bankingAccountId;
    }

    public function sendRequestAndProcessResponse($path, $method, $content, $headers = [], $queryParams = [], $preProcess = true)
    {
        if ($preProcess === true)
        {
            //Dashboard backend passes the get params in request body
            $this->preprocessForDashboardGetRequest($path, $content);
        }

        if (!empty($queryParams))
        {
            $path = $this->addQueryParamsToUrl($path, $queryParams);
        }

        $response = $this->sendRequest($path, $method, $content, $headers);

        $this->app['trace']->info(TraceCode::BANKING_ACCOUNT_SERVICE_RESPONSE, [
            'status_code' => $response->status_code,
        ]);

        return $this->processResponse($response);
    }

    public function sendRequest($path, $method, $content, $headers = [], $options = [])
    {
        $url = $this->baseUrl . $path;

        $headers = array_merge($headers, $this->getHeaders());

        $options = array_merge($options, $this->getOptions());

        $content = (empty($content) === false) ? json_encode($content) : '';

        $requestHeaders = $headers;

        //unsetting api-token value
        unset($requestHeaders['Api-Token']);

        $this->app['trace']->info(TraceCode::BANKING_ACCOUNT_SERVICE_REQUEST, [
            'url'     => $url,
            'path'    => $path,
            'method'  => $method,
            'content' => $content,
            'headers' => $requestHeaders,
        ]);

        //add retries here
        return Requests::request($url, $headers, $content, $method, $options);
    }

    protected function processResponse($response): array
    {
        $parsedResponse = $this->parseResponse($response);

        if ($response->status_code >= 500)
        {
            $this->trace->error(
                TraceCode::BANKING_ACCOUNT_SERVICE_ERROR,
                [
                    'error' => $parsedResponse['error'] ?? json_encode($response->body, true),
                ]);

            throw new Exception\ServerErrorException(
                'Internal Server Error occurred',
                ErrorCode::SERVER_ERROR);
        }
        else
        {
            if ($response->status_code >= 400)
            {
                if (empty($parsedResponse['error']) === false)
                {
                    $error = $parsedResponse['error'];
                }
                else
                {
                    $error = json_encode($response->body, true);
                }
                $this->trace->error(
                    TraceCode::BANKING_ACCOUNT_SERVICE_BAD_REQUEST,
                    [
                        'error' => $error,
                    ]);

                if ($response->status_code == 400)
                {
                    if (isset($error['description']))
                    {
                        $description = $error['description'];

                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_SERVICE_ERROR, null,
                            [
                                'errorDetail' => $response->body
                            ], $description);
                    }
                }
                else
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_SERVICE_ERROR, null,
                        [
                            'errorDetail' => $response->body
                        ], $error);
                }

            }
        }

        return $parsedResponse;
    }

    protected function parseResponse($response)
    {
        $responseArray = json_decode($response->body, true);

        if ($responseArray === null)
        {
            return [];
        }

        return $responseArray;
    }

    protected function getOptions()
    {
        return [
            'timeout' => $this->timeOut,
            'auth'    => [$this->key, $this->secret],
        ];
    }

    protected function getHeaders(): array
    {
        $headers = [
            'Accept'            => self::CONTENT_TYPE_JSON,
            'Content-Type'      => self::CONTENT_TYPE_JSON,
            'X-Razorpay-TaskId' => $this->app['request']->getTaskId(),
            'Api-Token'         => $this->secret,
        ];

        if ($this->ba->getMerchantId() !== null)
        {
            $headers['X-Razorpay-MerchantId'] = $this->ba->getMerchantId();
        }

        if ($this->ba->isAdminAuth() === true)
        {
            $headers['X-Admin-Id']    = $this->ba->getAdmin()->getId() ?? '';
            $headers['X-Admin-Email'] = $this->ba->getAdmin()->getEmail() ?? '';
            $headers['X-Admin-Name']  = $this->ba->getAdmin()->getName() ?? '';
        }

        $devstackLabel = $this->app['request']->header(RequestHeader::DEV_SERVE_USER);

        if ($this->app['env'] !== Environment::PRODUCTION && empty($devstackLabel) === false)
        {
            $headers[RequestHeader::DEV_SERVE_USER] = $devstackLabel;
        }

        {
            $adminIdHeader = $this->app['request']->header('X-Admin-Id');
        }

        if (empty($adminIdHeader) === false)
        {
            $internalAppName = app('request.ctx')->getInternalAppName();

            if ($internalAppName === 'master_onboarding')
            {
                $headers['X-Admin-Id'] = $this->app['request']->header('X-Admin-Id') ?? '';

                $headers['X-Admin-Email'] = $this->app['request']->header('X-Admin-Email') ?? '';

                $headers['X-Admin-Name'] = $this->app['request']->header('X-Admin-Name') ?? '';
            }
        }

        $user = $this->ba->getUser();

        if ($user !== null)
        {
            $headers['X-Razorpay-User-Id'] = $user->getId();

            $headers['X-Razorpay-User-Email'] = $user->getEmail();

            $headers['X-Razorpay-User-Name'] = $user->getName();

            $headers['X-Razorpay-User-Role'] = $this->ba->getUserRole();
        }

        $headers['X-Razorpay-Mode'] = $this->ba->getMode();

        $headers['X-Razorpay-Auth'] = $this->ba->getAuthType();

        return $headers;
    }

    //Dashboard backend passes the get params in request body
    public function preprocessForDashboardGetRequest(&$url, &$content)
    {
        /* @var Request $request */
        $request = $this->app['request'];

        $body = $request->post();

        $method = $request->getMethod();

        $queryString = $request->getQueryString();

        $urlAppend = '?';

        if (empty($queryString) === true and
            empty($body) === false and
            $method === Request::METHOD_GET)
        {
            $extraParams = http_build_query($body);

            $url .= $urlAppend . $extraParams;

            $content = [];
        }
    }

    /**
     * These are the fields that are stored in the cache.
     *
     * @param $result
     *
     * @return array
     */
    public function bankingAccountCacheFields($result)
    {
        return
            [
                'id'                  => $result['id'],
                'ifsc'                => $result['ifsc'],
                'status'              => $result['status'],
                'account_number'      => $result['account_number'],
                'fts_fund_account_id' => $result['fts_fund_account_id'],
            ];
    }

    public function getBusinessId($merchantId)
    {
        /** @var Entity $merchantDetail */
        $merchantDetail = $this->app['repo']->merchant_detail->findOrFail($merchantId);

        $businessId = $merchantDetail->getBasBusinessId();

        if (empty($businessId) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_BAS_BUSINESS_ID_NOT_CREATED);
        }

        return $businessId;
    }

    public function isBusinessExists(string $merchantId)
    {
        return $this->getBusinessId($merchantId);
    }

    public function getBusinessDetails(string $merchantId)
    {
        $businessId = $this->getBusinessId($merchantId);

        $path = 'business/' . $businessId;

        $response = $this->sendRequestAndProcessResponse($path, 'GET', [], []);

        return $response['data'];
    }

    public function getGeneratedRblCredentials(string $bankingAccountId)
    {
        $path = sprintf(self::GET_GENERATED_CREDENTIALS_PATH, $bankingAccountId);

        try
        {

            $response = $this->sendRequestAndProcessResponse($path, self::GET, [], []);

            return $response[self::DATA];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_GET_CREDENTIALS_ERROR,
                                         [
                                             'bankingAccountId' => $bankingAccountId
                                         ]);

            throw $e;

        }
    }

    public function generatedRblCredentials(string $bankingAccountId, $content)
    {
        $path = self::GENERATE_CREDENTIALS_PATH;

        try
        {

            $response = $this->sendRequestAndProcessResponse($path, Requests::POST, $content, []);

            return $response[self::DATA];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_GET_CREDENTIALS_ERROR,
                                         [
                                             'bankingAccountId' => $bankingAccountId
                                         ]);

            throw $e;

        }
    }

    public function getDocketPdfUrl(string $bankingAccountId, $businessCategory, $merchantName)
    {
        /**
         * Example: 'rbl/banking_account/LJrZXYtN4REuNo/credentials/download?business_category=private_public_limited_company&merchant_name=Testing%20user';
         */
        $path = sprintf(self::DOWNLOAD_DOCKET_PDF_PATH, $bankingAccountId, $businessCategory, $merchantName);

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, self::GET, [], []);

            $this->trace->info(TraceCode::BANKING_ACCOUNT_DOCKET_INITIATION_INFO, [
                'stage'    => 'service >> get pdf url',
                'response' => $response,
            ]);

            $url = '';
            if (array_key_exists('data', $response))
            {
                $data = $response['data'];

                if (array_key_exists('url', $data))
                {
                    $url = $data['url'];
                }
            }

            return $url;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_GET_DOCKET_URL_ERROR,
                                         [
                                             'bankingAccountId' => $bankingAccountId
                                         ]);

            throw $ex;
        }
    }

    public function fetchActivatedDirectAccountsFromBas(MerchantEntity $merchant)
    {
        $merchantId = $merchant->getMerchantId();

        $bankingAccount = $this->fetchAccountDetails($merchantId);

        if (empty($bankingAccount) === false)
        {
            $bankingAccount = (new Core())->generateInMemoryBankingAccount($merchantId, $bankingAccount);
        }

        return $bankingAccount;
    }

    public function fetchRblApplicationsForPartnerLms(array $input)
    {
        $path = self::PARTNER_LMS_RBL_APPLICATIONS;

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, self::GET, [], [], $input, false);

            return $response[self::DATA] ?? [];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_REQUEST_ERROR,
                                         [
                                             'path'  => $path,
                                             'input' => $input
                                         ]);

            throw $e;
        }
    }

    public function fetchRblApplications($input)
    {

        $path = self::SEARCH_LEADS_PATH;

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, self::GET, [], [], $input, false);

            return $response[self::DATA] ?? [];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_REQUEST_ERROR,
                                         [
                                             'path'         => $path,
                                             'query_params' => $input,
                                         ]);

            throw $e;
        }
    }

    public function getRblCompositeApplication(string $businessId, string $applicationId)
    {
        $path = sprintf(self::COMPOSITE_APPLICATION, $businessId, $applicationId);

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, self::GET, [], [], [], false);

            return $response[self::DATA];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_FETCH_RBL_APPLICATION_FROM_BAS_ERROR,
                                         [
                                             'id' => $applicationId
                                         ]);

            throw $e;
        }
    }

    /**
     * Call BAS endpoint for composite update
     *
     * @param string $bankingAccountId Banking Account Id
     *
     * @param array  $input            Input
     *
     * @throws \Throwable
     */
    public function patchRBLApplicationComposite(string $applicationIdOrReferenceNumber, array $input, string $merchantId = null)
    {
        $businessId = '_';

        if (empty($merchantId) == false)
        {
            // Business ID is guaranteed to exist,
            // this will throw error if business ID does not exist in merchant details
            $businessId = $this->getBusinessId($merchantId);
        }

        $path = sprintf(self::COMPOSITE_APPLICATION, $businessId, $applicationIdOrReferenceNumber);

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, Request::METHOD_PATCH, $input, [], [], false);

            return $response[self::DATA];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_PATCH_APPLICATION_COMPOSITE_ERROR, // TODO
                                         [
                                             'input' => $input
                                         ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    public function createBusinessOnBas(array $input): array
    {
        try
        {
            $response = $this->sendRequestAndProcessResponse(self::CREATE_BUSINESS, Request::METHOD_POST, $input, [], [], false);

            return $response[self::DATA];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_CREATE_BUSINESS_ERROR,
                                         [
                                             'input' => $input,
                                         ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    public function createRblOnboardingApplicationOnBas(string $businessId, array $input): array
    {
        $path = sprintf(self::CREATE_RBL_ONBOARDING_APPLICATION, $businessId);

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, Request::METHOD_POST, $input, [], [], false);

            return $response[self::DATA];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_CREATE_RBL_APPLICATION_ERROR,
                                         [
                                             'input' => $input,
                                         ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    public function getApplicationStatusLogs(string $businessId, string $applicationId, array $queryParams = [])
    {
        $path = sprintf(self::GET_APPLICATION_STATUS_LOGS, $businessId, $applicationId);

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, self::GET, [], [], $queryParams, false);

            return $response[self::DATA] ?? [];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_REQUEST_ERROR,
                                         [
                                             'path' => $path,
                                         ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    public function getApplicationComments(string $businessId, string $applicationId)
    {
        $path = sprintf(self::GET_APPLICATION_COMMENTS, $businessId, $applicationId);

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, self::GET, [], [], [], false);

            return $response[self::DATA] ?? [];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_REQUEST_ERROR,
                                         [
                                             'path' => $path,
                                         ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    public function addApplicationComment(string $businessId, string $applicationId, array $input)
    {
        $path = sprintf(self::CREATE_APPLICATION_COMMENT, $businessId, $applicationId);

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, self::POST, $input, [], [], false);

            return $response[self::DATA];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_REQUEST_ERROR,
                                         [
                                             'path'  => $path,
                                             'input' => $input,
                                         ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    public function updateApplicationComment(string $businessId, string $applicationId, string $commentId, array $input)
    {
        $path = sprintf(self::UPDATE_APPLICATION_COMMENT, $businessId, $applicationId, $commentId);

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, self::PATCH, $input, [], [], false);

            return $response[self::DATA];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_REQUEST_ERROR,
                                         [
                                             'path'  => $path,
                                             'input' => $input,
                                         ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    public function bulkAssignAccountManagerForRbl(array $input)
    {
        $path = self::BULK_ASSIGN_ACCOUNT_MANAGER;

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, self::POST, $input, [], [], false);

            return $response[self::DATA];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_REQUEST_ERROR,
                                         [
                                             'path'  => $path,
                                             'input' => $input,
                                         ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    public function processRblAccountOpeningWebhook(array $input)
    {
        $path = self::RBL_ACCOUNT_OPENING_WEBHOOK;

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, self::POST, $input, [], [], false);

            return $response[self::DATA];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_REQUEST_ERROR,
                                         [
                                             'path'  => $path,
                                             'input' => $input,
                                         ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    public function activateRblAccount(string $businessId, string $applicationId)
    {
        $path = sprintf(self::ACTIVATE_RBL_ACCOUNT, $businessId, $applicationId);

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, self::POST, [], [], [], false);

            return $response[self::DATA];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_REQUEST_ERROR,
                                         [
                                             'path' => $path,
                                         ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    public function getApplicationForRblPartnerLms(string $businessId, string $applicationId)
    {
        $path = sprintf(self::PARTNER_LMS_RBL_COMPOSITE_APPLICATION, $businessId, $applicationId);

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, self::GET, [], [], [], false);

            return $response[self::DATA];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_REQUEST_ERROR,
                                         [
                                             'path' => $path,
                                         ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    public function assignBankPocForRblPartnerLms(string $businessId, string $applicationId, array $input)
    {
        $path = sprintf(self::PARTNER_LMS_RBL_ASSIGN_BANK_POC, $businessId, $applicationId);

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, self::POST, $input, [], [], false);

            return $response[self::DATA];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_REQUEST_ERROR,
                                         [
                                             'path'  => $path,
                                             'input' => $input,
                                         ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    public function getActivityForRblPartnerLms(string $businessId, string $applicationId)
    {
        $path = sprintf(self::PARTNER_LMS_RBL_ACTIVITY, $businessId, $applicationId);

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, self::GET, [], [], [], false);

            return $response[self::DATA];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_REQUEST_ERROR,
                                         [
                                             'path' => $path,
                                         ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    public function getCommentsForRblPartnerLms(string $businessId, string $applicationId)
    {
        $path = sprintf(self::PARTNER_LMS_RBL_GET_COMMENTS, $businessId, $applicationId);

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, self::GET, [], [], [], false);

            return $response[self::DATA];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_REQUEST_ERROR,
                                         [
                                             'path' => $path,
                                         ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    public function addCommentForRblPartnerLms(string $businessId, string $applicationId, array $input)
    {
        $path = sprintf(self::PARTNER_LMS_RBL_ADD_COMMENT, $businessId, $applicationId);

        try
        {
            $response = $this->sendRequestAndProcessResponse($path, self::POST, $input, [], [], false);

            return $response[self::DATA];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_SERVICE_REQUEST_ERROR,
                                         [
                                             'path'  => $path,
                                             'input' => $input,
                                         ]);

            throw $e;
        }
    }

    public function addQueryParamsToUrl($url, $newParams): string
    {
        $urlParts       = parse_url($url);
        $existingParams = array();

        if (isset($urlParts['query']))
        {
            parse_str($urlParts['query'], $existingParams);
        }

        $mergedParams   = array_merge($existingParams, $newParams);
        $queryParamsStr = http_build_query($mergedParams);

        if (!empty($queryParamsStr))
        {
            $newUrl = $urlParts['path'] . '?' . $queryParamsStr;
        }
        else
        {
            $newUrl = $urlParts['path'];
        }

        return $newUrl;
    }
}
