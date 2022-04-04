<?php

namespace RZP\Services;

use Cache;
use ApiResponse;
use RZP\Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Exception\IntegrationException;
use RZP\Models\BankingAccountService\Core;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\BankingAccount\Gateway\Icici;
use RZP\Models\BankingAccountService\Channel;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;
use RZP\Models\BankingAccountService\Constants as Fields;
use RZP\Models\Merchant\Balance\Repository as BalanceRepo;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;

class BankingAccountService
{
    const CONTENT_TYPE_JSON = 'application/json';

    protected $baseUrl;

    protected $key;

    protected $secret;

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
    public function fetchAccountDetails(string $merchantId)
    {
        $repo = new BalanceRepo();

        $balance = $repo->getBalanceByMerchantIdChannelAndAccountType($merchantId, Channel::ICICI, AccountType::DIRECT);

        if(empty($balance) === true)
        {
            return [];
        }

        $this->isBusinessExists($merchantId);

        return $this->fetchBankingAccountByAccountNumberAndChannel($merchantId, $balance->getAccountNumber(), $balance->getChannel());
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

        $path = 'business/'. $businessId . '/banking_account_by_account_number/'. $accountNumber . '/credentials';

        $headers = [
            Fields::CHANNEL        => $channel,
        ];

        $response = $this->sendRequestAndProcessResponse($path, 'GET', [], $headers);

        if (isset($response['data']) === true)
        {
            $response = [
                Icici\Fields::CORP_ID   => $response['data']['corp_id'],
                Icici\Fields::CORP_USER => $response['data']['user_id'],
                Icici\Fields::URN       => $response['data']['urn'],
            ];
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

        $path = 'business/'. $businessId .'/banking_account_by_account_number/' . $accountNumber;

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

    public function sendRequestAndProcessResponse($path, $method, $content, $headers = [], $preProcess = true)
    {
        if ($preProcess === true)
        {
            //Dashboard backend passes the get params in request body
            $this->preprocessForDashboardGetRequest($path, $content);
        }

        $response = $this->sendRequest($path, $method, $content, $headers);

        $this->app['trace']->info(TraceCode::BANKING_ACCOUNT_SERVICE_RESPONSE, [
            'status_code'     => $response->status_code,
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

    protected function processResponse(\Requests_Response $response): array
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
        else if($response->status_code >= 400)
        {
            if(empty($parsedResponse['error']) === false)
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

        return $parsedResponse;
    }

    protected function parseResponse(\Requests_Response $response)
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
            'Accept' => self::CONTENT_TYPE_JSON,
            'Content-Type' => self::CONTENT_TYPE_JSON,
            'X-Razorpay-TaskId' => $this->app['request']->getTaskId(),
            'Api-Token' => $this->secret,
        ];

        if ($this->ba->getMerchantId() !== null) {
            $headers['X-Razorpay-MerchantId'] = $this->ba->getMerchantId();
        }

        if ($this->ba->isAdminAuth() === true)
        {
            $headers['X-Admin-Id'] = $this->ba->getAdmin()->getId() ?? '';
            $headers['X-Admin-Email'] = $this->ba->getAdmin()->getEmail() ?? '';
            $headers['X-Admin-Name'] = $this->ba->getAdmin()->getName() ?? '';
        }

        $user = $this->ba->getUser();

        if ($user !== null)
        {
            $headers['X-Razorpay-UserId']   = $user->getId();

            $headers['X-Razorpay-UserRole'] = $this->ba->getUserRole();
        }

        $headers['X-Razorpay-Mode']          = $this->ba->getMode();

        $headers['X-Razorpay-Auth']          = $this->ba->getAuthType();

        return $headers;
    }

    //Dashboard backend passes the get params in request body
    public function preprocessForDashboardGetRequest(& $url, & $content)
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

            $url .= $urlAppend .$extraParams;

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
        /* @var Entity $merchantDetail */
        $merchantDetail = $this->app['repo']->merchant_detail->findOrFail($merchantId);

        $businessId = $merchantDetail->getBasBusinessId();

        if(empty($businessId) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_BAS_BUSINESS_ID_NOT_CREATED);
        }

        return $businessId;
    }

    public function isBusinessExists(string $merchantId)
    {
        $this->getBusinessId($merchantId);
    }

    public function getBusinessDetails(string $merchantId)
    {
        $businessId = $this->getBusinessId($merchantId);

        $path = 'business/'. $businessId;

        $response = $this->sendRequestAndProcessResponse($path, 'GET', [], []);

        return $response['data'];
    }

    public function fetchIciciActivatedAccountFromBas(MerchantEntity $merchant)
    {
        $merchantId = $merchant->getMerchantId();

        $bankingAccount = $this->fetchAccountDetails($merchantId);

        if(empty($bankingAccount) === false)
        {
            $bankingAccount = (new Core())->generateInMemoryBankingAccount($merchantId, $bankingAccount);
        }

        return $bankingAccount;
    }
}
