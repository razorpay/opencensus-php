<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Exception;
use RZP\Mail\Loc\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Permission\Entity;
use RZP\Models\Admin\Permission\Name;
use RZP\Tests\Functional\Fixtures\Entity\Permission;
use RZP\Trace\TraceCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use RZP\Http\Request\Requests as RzpRequest;

class LOCController extends Controller
{
    const GET      = 'GET';
    const POST     = 'POST';
    const PUT      = 'PUT';
    const PATCH    = 'PATCH';
    const DELETE   = 'DELETE';
    const MERCHANT = 'MERCHANT';
    const OPS      = 'ops';

    const SEED_DATA_REGEX                        = 'SEED_DATA_REGEX';
    const CREATE_WITHDRAWAL_REGEX                = 'CREATE_WITHDRAWAL_REGEX';
    const GET_WITHDRAWAL_REGEX                   = 'GET_WITHDRAWAL_REGEX';
    const LIST_OR_SEARCH_WITHDRAWAL_REGEX        = 'LIST_OR_SEARCH_WITHDRAWAL_REGEX';
    const UPDATE_WITHDRAWAL_REGEX                = 'UPDATE_WITHDRAWAL_REGEX';
    const ADD_REPAYMENT_REGEX                    = 'ADD_REPAYMENT_REGEX';
    const CREATE_WITHDRAWAL_CONFIG_REGEX         = 'CREATE_WITHDRAWAL_CONFIG_REGEX';
    const GET_WITHDRAWAL_CONFIG_REGEX            = 'GET_WITHDRAWAL_CONFIG_REGEX';
    const UPDATE_WITHDRAWAL_CONFIG_REGEX         = 'UPDATE_WITHDRAWAL_CONFIG_REGEX';
    const LIST_OR_SEARCH_WITHDRAWAL_CONFIG_REGEX = 'LIST_OR_SEARCH_WITHDRAWAL_CONFIG_REGEX';
    const CREATE_SOURCE_ACCOUNT_REGEX            = 'CREATE_SOURCE_ACCOUNT_REGEX';
    const GET_SOURCE_ACCOUNT_REGEX               = 'GET_SOURCE_ACCOUNT_REGEX';
    const UPDATE_SOURCE_ACCOUNT_REGEX            = 'UPDATE_SOURCE_ACCOUNT_REGEX';
    const CREATE_DESTINATION_ACCOUNT_REGEX       = 'CREATE_DESTINATION_ACCOUNT_REGEX';
    const GET_DESTINATION_ACCOUNT_REGEX          = 'GET_DESTINATION_ACCOUNT_REGEX';
    const UPDATE_DESTINATION_ACCOUNT_REGEX       = 'UPDATE_DESTINATION_ACCOUNT_REGEX';
    const POSIDEX_ACCESS_TOKEN                   = 'POSIDEX_ACCESS_TOKEN';
    const POSIDEX_CRN                            = 'POSIDEX_CRN';

    const ROUTES_URL_MAP = [
        self::SEED_DATA_REGEX                        => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalAPI/SeedData',
        self::CREATE_WITHDRAWAL_REGEX                => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalAPI/CreateWithdrawal',
        self::GET_WITHDRAWAL_REGEX                   => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalAPI/GetWithdrawalByReference',
        self::LIST_OR_SEARCH_WITHDRAWAL_REGEX        => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalAPI/ListOrSearchWithdrawal',
        self::UPDATE_WITHDRAWAL_REGEX                => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalAPI/UpdateWithdrawal',
        self::ADD_REPAYMENT_REGEX                    => 'twirp/rzp.capital.loc.withdrawal.v1.RepaymentAPI/AddRepayment',
        self::CREATE_WITHDRAWAL_CONFIG_REGEX         => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalConfigAPI/CreateWithdrawalConfig',
        self::GET_WITHDRAWAL_CONFIG_REGEX            => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalConfigAPI/GetWithdrawalConfig',
        self::UPDATE_WITHDRAWAL_CONFIG_REGEX         => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalConfigAPI/UpdateWithdrawalConfig',
        self::LIST_OR_SEARCH_WITHDRAWAL_CONFIG_REGEX => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalConfigAPI/ListOrSearchWithdrawalConfig',
        self::CREATE_SOURCE_ACCOUNT_REGEX            => 'twirp/rzp.capital.loc.defrayment.v1.SourceAccountsAPI/CreateSourceAccount',
        self::GET_SOURCE_ACCOUNT_REGEX               => 'twirp/rzp.capital.loc.defrayment.v1.SourceAccountsAPI/GetSourceAccount',
        self::UPDATE_SOURCE_ACCOUNT_REGEX            => 'twirp/rzp.capital.loc.defrayment.v1.SourceAccountsAPI/UpdateSourceAccount',
        self::CREATE_DESTINATION_ACCOUNT_REGEX       => 'twirp/rzp.capital.loc.defrayment.v1.DestinationAccountsAPI/CreateDestinationAccount',
        self::GET_DESTINATION_ACCOUNT_REGEX          => 'twirp/rzp.capital.loc.defrayment.v1.DestinationAccountsAPI/GetDestinationAccount',
        self::UPDATE_DESTINATION_ACCOUNT_REGEX       => 'twirp/rzp.capital.loc.defrayment.v1.DestinationAccountsAPI/UpdateDestinationAccount',
        self::POSIDEX_ACCESS_TOKEN                   => 'twirp/rzp.capital.loc.onboarding.v1.OnboardingAPI/GenerateIDFCAccessToken',
        self::POSIDEX_CRN                            => 'twirp/rzp.capital.loc.onboarding.v1.OnboardingAPI/CreateIDFCCRN',
    ];

    const MERCHANT_ROUTES = [
        self::SEED_DATA_REGEX,
        self::CREATE_WITHDRAWAL_REGEX,
        self::GET_WITHDRAWAL_REGEX,
        self::LIST_OR_SEARCH_WITHDRAWAL_REGEX,
        self::GET_WITHDRAWAL_CONFIG_REGEX,
        self::LIST_OR_SEARCH_WITHDRAWAL_CONFIG_REGEX,
        self::GET_DESTINATION_ACCOUNT_REGEX,
    ];

    const ROUTE_PERMISSION_MAP = [
        self::SEED_DATA_REGEX                        => Name::LOC,
        self::CREATE_WITHDRAWAL_CONFIG_REGEX         => Name::LOC_CONFIG_EDIT,
        self::UPDATE_WITHDRAWAL_CONFIG_REGEX         => Name::LOC_CONFIG_EDIT,
        self::CREATE_SOURCE_ACCOUNT_REGEX            => Name::LOC_CONFIG_EDIT,
        self::UPDATE_SOURCE_ACCOUNT_REGEX            => Name::LOC_CONFIG_EDIT,
        self::CREATE_DESTINATION_ACCOUNT_REGEX       => Name::LOC_CONFIG_EDIT,
        self::UPDATE_DESTINATION_ACCOUNT_REGEX       => Name::LOC_CONFIG_EDIT,
        self::POSIDEX_ACCESS_TOKEN                   => Name::LOC_CONFIG_EDIT,
        self::POSIDEX_CRN                            => Name::LOC_CONFIG_EDIT,
        self::GET_WITHDRAWAL_CONFIG_REGEX            => Name::LOC_CONFIG_VIEW,
        self::LIST_OR_SEARCH_WITHDRAWAL_CONFIG_REGEX => Name::LOC_CONFIG_VIEW,
        self::GET_DESTINATION_ACCOUNT_REGEX          => Name::LOC_CONFIG_VIEW,
        self::GET_SOURCE_ACCOUNT_REGEX               => Name::LOC_CONFIG_VIEW,
        self::CREATE_WITHDRAWAL_REGEX                => Name::LOC_WITHDRAWAL_EDIT,
        self::UPDATE_WITHDRAWAL_REGEX                => Name::LOC_WITHDRAWAL_EDIT,
        self::ADD_REPAYMENT_REGEX                    => Name::LOC_WITHDRAWAL_EDIT,
        self::GET_WITHDRAWAL_REGEX                   => Name::LOC_WITHDRAWAL_VIEW,
        self::LIST_OR_SEARCH_WITHDRAWAL_REGEX        => Name::LOC_WITHDRAWAL_VIEW,
    ];

    const MAIL_ERROR_REGEX = '/View \[emails.loc.(?:\w+)?\] not found./';

    protected function handleProxyRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();
        $this->trace->info(TraceCode::LINE_OF_CREDIT_PROXY_REQUEST, [
            'request' => $url,
        ]);

        $isMerchantAccessible = false;
        foreach (self::MERCHANT_ROUTES as $route)
        {
            if (self::ROUTES_URL_MAP[$route] === $path)
            {
                $isMerchantAccessible = true;
            }
        }

        if ($isMerchantAccessible === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $headers = [
            'X-Merchant-Id'    => $this->ba->getMerchant()->getId() ?? '',
            'X-Merchant-Email' => $this->ba->getMerchant()->getEmail() ?? '',
            'X-User-Id'        => $this->ba->getUser()->getId() ?? '',
            'X-User-Role'      => $this->ba->getUserRole() ?? '',
            'X-Auth-Type'      => 'proxy',
        ];

        $response = $this->sendRequestAndParseResponse($url, $body, $headers);

        return $response;
    }

    protected function handleAdminRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->info(TraceCode::LINE_OF_CREDIT_PROXY_REQUEST, [
            'request' => $url,
        ]);

        $isLocRoute = false;

        foreach (self::ROUTES_URL_MAP as $route => $url)
        {
            if ($url === $path)
            {
                $isLocRoute = true;
                break;
            }
        }

        if ($isLocRoute === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
        else if ((isset(self::ROUTE_PERMISSION_MAP[$route]) === false) or
                 ($this->ba->getAdmin()->hasPermission(self::ROUTE_PERMISSION_MAP[$route]) === false))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        $headers = [
            'X-Admin-Id'    => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email' => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'   => 'admin'
        ];

        $response = $this->sendRequestAndParseResponse($url, $body, $headers);

        return $response;
    }

    protected function sendRequestAndParseResponse(
        string $url,
        array $body = [],
        array $headers = [],
        array $options = [])
    {
        $config                  = config('applications.line_of_credit');
        $baseUrl                 = $config['url'];
        $username                = $config['username'];
        $password                = $config['secret'];
        $timeout                 = $config['timeout'];
        $headers['Accept']       = 'application/json';
        $headers['Content-Type'] = 'application/json';
        $headers['X-Task-Id']    = $this->app['request']->getTaskId();

        $auth           = [$username, $password];
        $defaultOptions = [
            'timeout' => $timeout,
            'auth'    => $auth,
        ];
        $method         = self::POST;

        try
        {
            $response = RzpRequest::request(
                $baseUrl . $url,
                $headers,
                empty($body) ? "{}" : json_encode($body),
                $method,
                $defaultOptions
            );
        }
        catch (\Requests_Exception $e)
        {
            $errorCode = ($this->hasRequestTimedOut($e) === true) ?
                ErrorCode::GATEWAY_ERROR_LINE_OF_CREDIT_TIMEOUT :
                ErrorCode::GATEWAY_ERROR_LINE_OF_CREDIT_FAILURE;
            throw new Exception\IntegrationException(
                $e->getMessage(),
                $errorCode,
                null,
                $e
            );
        }

        return $this->parseResponse($response);
    }

    protected function hasRequestTimedOut(\Requests_Exception $e): bool
    {
        $message = $e->getMessage();

        return Str::contains($message, [
            'operation timed out',
            'network is unreachable',
            'name or service not known',
            'failed to connect',
            'could not resolve host',
            'resolving timed out',
            'name lookup timed out',
            'connection timed out',
            'aborted due to timeout',
        ]);
    }

    protected function parseResponse($response)
    {
        $code = $response->status_code;
        $body = json_decode($response->body, true);

        $this->trace->info(TraceCode::LINE_OF_CREDIT_PROXY_RESPONSE, [
            'status_code' => $code,
        ]);

        if (isset($body['code']) === true)
        {
            throw new Exception\TwirpException($body);
        }
        elseif ($response->status_code === 404)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        return ApiResponse::json($body, $code);
    }

    protected function sendMail()
    {
        $request = Request::instance();
        $data    = $request->all();
        if ((isset($data['to']) === false) or
            ($data['to'] === "merchant" and (isset($data['merchant_id']) === false)) or
            (isset($data['template']) === false) or
            (isset($data['subject']) === false))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        }

        if ($data['to'] === 'merchant')
        {
            try
            {
                $merchant = $this->repo->merchant->findOrFail($data['merchant_id']);
            }
            catch (\Exception $ex)
            {
                if ($ex->getCode() === ErrorCode::SERVER_ERROR_DB_QUERY_FAILED)
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
                }
                else
                {
                    throw $ex;
                }
            }

            $data['merchant_email'] = $merchant->getEmail();
            $data['merchant_id']    = $merchant->getId();
            $data['merchant_name']  = $merchant->getName();
        }

        try
        {
            $mail = new Base($data);
            Mail::queue($mail);
        }
        catch (\Throwable $e)
        {
            if (preg_match(self::MAIL_ERROR_REGEX, $e->getMessage(), $matches) === 1)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, $e->getMessage());
            }
            else
            {
                throw $e;
            }
        }

        return ApiResponse::json(['success' => true]);
    }
}
