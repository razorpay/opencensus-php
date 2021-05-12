<?php

namespace RZP\Services;

use Cache;
use ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\BankingAccountService\Channel;
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
                Fields::CORP_ID   => $response['data']['corp_id'],
                Fields::CORP_USER => $response['data']['user_id'],
                Fields::URN       => $response['data']['urn'],
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
        $bankingAccount = $this->fetchBankingAccountByAccountNumberAndChannel($merchantId, $accountNumber, $channel);

        return $bankingAccount['fts_fund_account_id'];
    }

    public function fetchBankingAccountByAccountNumberAndChannel($merchantId, $accountNumber, $channel)
    {
        /*$key = 'bas_banking_account_' . $accountNumber . '_' . $channel;

        $bankingAccount = json_decode(Cache::get($key), true);

        if (empty($bankingAccount) === false and
            $bankingAccount['status'] === 'ACTIVE')
        {
            return $bankingAccount;
        }*/

        $businessId = $this->getBusinessId($merchantId);

        $path = 'business/'. $businessId .'/banking_account_by_account_number/' . $accountNumber;

        $headers = [
            Fields::CHANNEL => $channel,
        ];

        $response = $this->sendRequestAndProcessResponse($path, 'GET', [], $headers);

        /*$expiresAt = Carbon::now()->addMinutes(30);

        $key = 'bas_banking_account_' . $accountNumber . '_' . $channel;

        $value = $this->bankingAccountCacheFields($response['data']);

        Cache::put($key, json_encode($value), $expiresAt);*/

        return $response['data'];
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

        $bankingAccount = $this->fetchBankingAccountByAccountNumberAndChannel($balance->getMerchantId(), $balance->getAccountNumber(), $balance->getChannel());

        //FE searches the id with this prefix
        $bcc = BankingAccountEntity::getIdPrefix();

        return $bcc . $bankingAccount['id'];
    }

    public function sendRequestAndProcessResponse($path, $method, $content, $headers = [])
    {
        //Dashboard backend passes the get params in request body
        $this->preprocessForDashboardGetRequest($path, $content);

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

        $this->app['trace']->info(TraceCode::BANKING_ACCOUNT_SERVICE_REQUEST, [
            'path'       => $path,
            'method'     => $method,
            'content'    => $content,
            'headers'    => $headers,
        ]);

        //add retries here
        return Requests::request($url, $headers, $content, $method, $options);
    }

    protected function processResponse(\Requests_Response $response): array
    {
        $parsedResponse = $this->parseResponse($response);

        if ($response->status_code >= 400)
        {
            $this->trace->error(
                TraceCode::BANKING_ACCOUNT_SERVICE_ERROR,
                [
                    'error' => $parsedResponse['error'] ?? json_encode($response->body, true),
                ]);

            throw new IntegrationException('banking account service exception',
                                           ErrorCode::SERVER_ERROR);
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
            'Accept'            => self::CONTENT_TYPE_JSON,
            'Content-Type'      => self::CONTENT_TYPE_JSON,
            'X-Razorpay-TaskId' => $this->app['request']->getTaskId(),
            'Api-Token'         => $this->secret,
        ];

        if ($this->ba->getMerchantId() !== null)
        {
            $headers['X-Razorpay-MerchantId'] = $this->ba->getMerchantId();
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

        return $merchantDetail->getBasBusinessId();
    }
}
