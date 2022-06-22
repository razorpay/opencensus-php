<?php

namespace RZP\Http\Controllers;

use Config;
use Request;
use ApiResponse;
use RZP\Error\Error;
use RZP\Exception;
use RZP\Mail\Loc\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Permission\Name;
use RZP\Models\Base\PublicCollection;
use RZP\Trace\TraceCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use RZP\Http\Request\Requests as RzpRequest;
use RZP\Models\Admin\Permission\Category as PermissionCategory;

class LOCController extends Controller
{
    const GET      = 'GET';
    const POST     = 'POST';
    const PUT      = 'PUT';
    const PATCH    = 'PATCH';
    const DELETE   = 'DELETE';
    const MERCHANT = 'MERCHANT';
    const OPS      = 'ops';

    const SEED_DATA_REGEX                                = 'SEED_DATA_REGEX';
    const CREATE_WITHDRAWAL_REGEX                        = 'CREATE_WITHDRAWAL_REGEX';
    const GET_WITHDRAWAL_REGEX                           = 'GET_WITHDRAWAL_REGEX';
    const LIST_OR_SEARCH_WITHDRAWAL_REGEX                = 'LIST_OR_SEARCH_WITHDRAWAL_REGEX';
    const UPDATE_WITHDRAWAL_REGEX                        = 'UPDATE_WITHDRAWAL_REGEX';
    const ADD_REPAYMENT_REGEX                            = 'ADD_REPAYMENT_REGEX';
    const CREATE_WITHDRAWAL_CONFIG_REGEX                 = 'CREATE_WITHDRAWAL_CONFIG_REGEX';
    const GET_WITHDRAWAL_CONFIG_REGEX                    = 'GET_WITHDRAWAL_CONFIG_REGEX';
    const UPDATE_WITHDRAWAL_CONFIG_REGEX                 = 'UPDATE_WITHDRAWAL_CONFIG_REGEX';
    const GET_FUNCTIONAL_WITHDRAWAL_CONFIG_REGEX         = 'GET_FUNCTIONAL_WITHDRAWAL_CONFIG_REGEX';
    const LIST_OR_SEARCH_WITHDRAWAL_CONFIG_REGEX         = 'LIST_OR_SEARCH_WITHDRAWAL_CONFIG_REGEX';
    const CREATE_SOURCE_ACCOUNT_REGEX                    = 'CREATE_SOURCE_ACCOUNT_REGEX';
    const GET_SOURCE_ACCOUNT_REGEX                       = 'GET_SOURCE_ACCOUNT_REGEX';
    const UPDATE_SOURCE_ACCOUNT_REGEX                    = 'UPDATE_SOURCE_ACCOUNT_REGEX';
    const CREATE_DESTINATION_ACCOUNT_REGEX               = 'CREATE_DESTINATION_ACCOUNT_REGEX';
    const GET_DESTINATION_ACCOUNT_REGEX                  = 'GET_DESTINATION_ACCOUNT_REGEX';
    const UPDATE_DESTINATION_ACCOUNT_REGEX               = 'UPDATE_DESTINATION_ACCOUNT_REGEX';
    const POSIDEX_ACCESS_TOKEN                           = 'POSIDEX_ACCESS_TOKEN';
    const POSIDEX_CRN                                    = 'POSIDEX_CRN';
    const BULK_UPDATE_WITHDRAWAL                         = 'BULK_UPDATE_WITHDRAWAL';
    const REPAYMENTS_SCHEDULE                            = 'REPAYMENTS_SCHEDULE';
    const WITHDRAWAL_ENGAGEMENT_MAILER_CRON              = 'WITHDRAWAL_ENGAGEMENT_MAILER_CRON';
    const GET_AUTOMATED_LOC                              = 'GET_AUTOMATED_LOC';
    const SET_AUTOMATED_LOC                              = 'SET_AUTOMATED_LOC';
    const CREATE_MERCHANT_DETAILS                        = 'CREATE_MERCHANT_DETAILS';
    const GET_MERCHANT_DETAILS                           = 'GET_MERCHANT_DETAILS';
    const UPDATE_MERCHANT_DETAILS                        = 'UPDATE_MERCHANT_DETAILS';
    const GET_ONHOLD_STATUS_REASONS                      = 'GET_ONHOLD_STATUS_REASONS';
    const SCHEDULE_LATE_REPAYMENT_NOTIFICATION           = 'SCHEDULE_LATE_REPAYMENT_NOTIFICATION';
    const WITHDRAWAL_CONFIG_UPDATE_DELAYED_REPAYMENTS    = 'WITHDRAWAL_CONFIG_DELAYED_REPAYMENT';
    const RECON_REPAID_WITHDRAWALS_WITH_GROMOR           = 'RECON_REPAID_WITHDRAWALS_WITH_GROMOR';
    const APPLY_CREDIT_LIMIT_UPDATE                      = 'APPLY_CREDIT_LIMIT_UPDATE';
    const GET_CREDIT_LIMIT_UPDATE                        = 'GET_CREDIT_LIMIT_UPDATE';
    const GET_CREDIT_SUMMARY_REGEX                       = 'GET_CREDIT_SUMMARY_REGEX';
    const GET_ACCOUNT_PRODUCT_CONFIG                     = 'GET_ACCOUNT_PRODUCT_CONFIG';

    const ROUTES_URL_MAP = [
        self::SEED_DATA_REGEX                        => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalAPI/SeedData',
        self::CREATE_WITHDRAWAL_REGEX                => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalAPI/CreateWithdrawal',
        self::GET_WITHDRAWAL_REGEX                   => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalAPI/GetWithdrawalByReference',
        self::LIST_OR_SEARCH_WITHDRAWAL_REGEX        => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalAPI/ListOrSearchWithdrawal',
        self::UPDATE_WITHDRAWAL_REGEX                => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalAPI/UpdateWithdrawal',
        self::BULK_UPDATE_WITHDRAWAL                 => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalAPI/BulkUpdateWithdrawal',
        self::ADD_REPAYMENT_REGEX                    => 'twirp/rzp.capital.loc.withdrawal.v1.RepaymentAPI/AddRepayment',
        self::CREATE_WITHDRAWAL_CONFIG_REGEX         => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalConfigAPI/CreateWithdrawalConfig',
        self::GET_WITHDRAWAL_CONFIG_REGEX            => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalConfigAPI/GetWithdrawalConfig',
        self::GET_FUNCTIONAL_WITHDRAWAL_CONFIG_REGEX => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalConfigAPI/GetFunctionalWithdrawalConfig',
        self::UPDATE_WITHDRAWAL_CONFIG_REGEX         => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalConfigAPI/UpdateWithdrawalConfig',
        self::LIST_OR_SEARCH_WITHDRAWAL_CONFIG_REGEX => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalConfigAPI/ListOrSearchWithdrawalConfig',
        self::GET_AUTOMATED_LOC                      => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalConfigAPI/GetAutomatedLOC',
        self::SET_AUTOMATED_LOC                      => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalConfigAPI/SetAutomatedLOC',
        self::CREATE_SOURCE_ACCOUNT_REGEX            => 'twirp/rzp.capital.loc.defrayment.v1.SourceAccountsAPI/CreateSourceAccount',
        self::GET_SOURCE_ACCOUNT_REGEX               => 'twirp/rzp.capital.loc.defrayment.v1.SourceAccountsAPI/GetSourceAccount',
        self::UPDATE_SOURCE_ACCOUNT_REGEX            => 'twirp/rzp.capital.loc.defrayment.v1.SourceAccountsAPI/UpdateSourceAccount',
        self::CREATE_DESTINATION_ACCOUNT_REGEX       => 'twirp/rzp.capital.loc.defrayment.v1.DestinationAccountsAPI/CreateDestinationAccount',
        self::GET_DESTINATION_ACCOUNT_REGEX          => 'twirp/rzp.capital.loc.defrayment.v1.DestinationAccountsAPI/GetDestinationAccount',
        self::UPDATE_DESTINATION_ACCOUNT_REGEX       => 'twirp/rzp.capital.loc.defrayment.v1.DestinationAccountsAPI/UpdateDestinationAccount',
        self::POSIDEX_ACCESS_TOKEN                   => 'twirp/rzp.capital.loc.onboarding.v1.OnboardingAPI/GenerateIDFCAccessToken',
        self::POSIDEX_CRN                            => 'twirp/rzp.capital.loc.onboarding.v1.OnboardingAPI/CreateIDFCCRN',
        self::REPAYMENTS_SCHEDULE                    => 'twirp/rzp.capital.loc.withdrawal.v1.RepaymentAPI/GetRepaymentsSchedule',
        self::CREATE_MERCHANT_DETAILS                => 'twirp/rzp.capital.loc.migration.v1.MerchantDetailsAPI/CreateMerchantDetails',
        self::GET_MERCHANT_DETAILS                   => 'twirp/rzp.capital.loc.migration.v1.MerchantDetailsAPI/GetMerchantDetails',
        self::UPDATE_MERCHANT_DETAILS                => 'twirp/rzp.capital.loc.migration.v1.MerchantDetailsAPI/UpdateMerchantDetails',
        self::GET_ONHOLD_STATUS_REASONS              => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalConfigAPI/GetOnholdStatusReasons',
        self::APPLY_CREDIT_LIMIT_UPDATE              => 'twirp/rzp.capital.loc.withdrawal.v1.CreditLimitUpdateAPI/ApplyCreditLimitUpdate',
        self::GET_CREDIT_LIMIT_UPDATE                => 'twirp/rzp.capital.loc.withdrawal.v1.CreditLimitUpdateAPI/GetCreditLimitUpdate',
        self::GET_CREDIT_SUMMARY_REGEX               => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalConfigAPI/GetCreditSummary',
        self::GET_ACCOUNT_PRODUCT_CONFIG             => 'twirp/rzp.capital.loc.account.v1.AccountAPI/GetAccountProductConfig',
    ];

    const CRON_URL_MAP = [
        self::WITHDRAWAL_ENGAGEMENT_MAILER_CRON              => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalConfigAPI/WithdrawalEngagementMail',
        self::SCHEDULE_LATE_REPAYMENT_NOTIFICATION           => 'twirp/rzp.capital.loc.withdrawal.v1.RepaymentAPI/ScheduleLateRepaymentNotificationToPartnerCron',
        self::WITHDRAWAL_CONFIG_UPDATE_DELAYED_REPAYMENTS    => 'twirp/rzp.capital.loc.withdrawal.v1.RepaymentAPI/SetOnHoldStatusForDelayedWithdrawalRepaymentsCron',
        self::RECON_REPAID_WITHDRAWALS_WITH_GROMOR           => 'twirp/rzp.capital.loc.withdrawal.v1.WithdrawalAPI/ReconRepaidWithdrawalsWithGromor',
    ];

    const MERCHANT_ROUTES = [
        self::SEED_DATA_REGEX,
        self::CREATE_WITHDRAWAL_REGEX,
        self::GET_WITHDRAWAL_REGEX,
        self::LIST_OR_SEARCH_WITHDRAWAL_REGEX,
        self::GET_WITHDRAWAL_CONFIG_REGEX,
        self::GET_FUNCTIONAL_WITHDRAWAL_CONFIG_REGEX,
        self::LIST_OR_SEARCH_WITHDRAWAL_CONFIG_REGEX,
        self::GET_DESTINATION_ACCOUNT_REGEX,
        self::REPAYMENTS_SCHEDULE,
        self::GET_AUTOMATED_LOC,
        self::SET_AUTOMATED_LOC,
        self::GET_MERCHANT_DETAILS,
        self::GET_CREDIT_SUMMARY_REGEX,
        self::GET_ACCOUNT_PRODUCT_CONFIG,
    ];

    const ROUTE_PERMISSION_MAP = [
        self::SEED_DATA_REGEX                              => Name::LOC,
        self::GET_ONHOLD_STATUS_REASONS                    => Name::LOC_CONFIG_VIEW,
        self::CREATE_MERCHANT_DETAILS                      => Name::LOC_CONFIG_EDIT,
        self::GET_MERCHANT_DETAILS                         => Name::LOC_CONFIG_EDIT,
        self::UPDATE_MERCHANT_DETAILS                      => Name::LOC_CONFIG_EDIT,
        self::CREATE_WITHDRAWAL_CONFIG_REGEX               => Name::LOC_CONFIG_EDIT,
        self::UPDATE_WITHDRAWAL_CONFIG_REGEX               => Name::LOC_CONFIG_EDIT,
        self::CREATE_SOURCE_ACCOUNT_REGEX                  => Name::LOC_CONFIG_EDIT,
        self::UPDATE_SOURCE_ACCOUNT_REGEX                  => Name::LOC_CONFIG_EDIT,
        self::CREATE_DESTINATION_ACCOUNT_REGEX             => Name::LOC_CONFIG_EDIT,
        self::UPDATE_DESTINATION_ACCOUNT_REGEX             => Name::LOC_CONFIG_EDIT,
        self::POSIDEX_ACCESS_TOKEN                         => Name::LOC_CONFIG_EDIT,
        self::POSIDEX_CRN                                  => Name::LOC_CONFIG_EDIT,
        self::GET_AUTOMATED_LOC                            => Name::LOC_CONFIG_VIEW,
        self::SET_AUTOMATED_LOC                            => Name::LOC_CONFIG_EDIT,
        self::GET_WITHDRAWAL_CONFIG_REGEX                  => Name::LOC_CONFIG_VIEW,
        self::LIST_OR_SEARCH_WITHDRAWAL_CONFIG_REGEX       => Name::LOC_CONFIG_VIEW,
        self::GET_DESTINATION_ACCOUNT_REGEX                => Name::LOC_CONFIG_VIEW,
        self::GET_SOURCE_ACCOUNT_REGEX                     => Name::LOC_CONFIG_VIEW,
        self::CREATE_WITHDRAWAL_REGEX                      => Name::LOC_WITHDRAWAL_EDIT,
        self::UPDATE_WITHDRAWAL_REGEX                      => Name::LOC_WITHDRAWAL_EDIT,
        self::ADD_REPAYMENT_REGEX                          => Name::LOC_WITHDRAWAL_EDIT,
        self::GET_WITHDRAWAL_REGEX                         => Name::LOC_WITHDRAWAL_VIEW,
        self::GET_FUNCTIONAL_WITHDRAWAL_CONFIG_REGEX       => Name::LOC_CONFIG_VIEW,
        self::LIST_OR_SEARCH_WITHDRAWAL_REGEX              => Name::LOC_WITHDRAWAL_VIEW,
        self::REPAYMENTS_SCHEDULE                          => Name::LOC_WITHDRAWAL_VIEW,
        self::WITHDRAWAL_CONFIG_UPDATE_DELAYED_REPAYMENTS  => Name::LOC_CONFIG_EDIT,
        self::RECON_REPAID_WITHDRAWALS_WITH_GROMOR         => Name::LOC_CONFIG_VIEW,
        self::APPLY_CREDIT_LIMIT_UPDATE                    => Name::LOC_CONFIG_EDIT,
        self::GET_CREDIT_LIMIT_UPDATE                      => Name::LOC_CONFIG_VIEW,
    ];

    const MAIL_ERROR_REGEX = '/View \[emails.loc.(?:\w+)?\] not found./';

    public function postLocBulkWithdrawalUpdate()
    {
        $input = Request::all();
        $bulkCollection = new PublicCollection;
        try
        {
            if (isset($input['0']) == false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
            }

            $body = $input['0'];

            $idempotencyKey = $body['idempotency_key'];

            $url = self::ROUTES_URL_MAP[self::BULK_UPDATE_WITHDRAWAL];

            $this->trace->info(TraceCode::LINE_OF_CREDIT_PROXY_REQUEST, [
                'request' => $url,
            ]);

            $headers = [
                'X-Service-Name' => $this->ba->getInternalApp() ?? '',
                'X-Auth-Type'    => 'internal',
            ];

            $response = $this->sendRequest($url, $body, $headers);

            $responseArray = json_decode($response->body, true);

            if ($response->status_code !== 200)
            {
                throw new Exception\TwirpException($responseArray);
            }

            $responseArray['idempotency_key'] = $idempotencyKey;

            $bulkCollection->push($responseArray);
        }
        catch (\Throwable $e)
        {
            $bulkCollection->push([
                'idempotency_key'   => $idempotencyKey,
                'success'            => false,
                'error'             => [
                    Error::DESCRIPTION       => $e->getMessage(),
                    Error::PUBLIC_ERROR_CODE => $e->getCode(),
                ]
            ]);
        }

        return $bulkCollection->toArrayWithItems();
    }

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
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
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

    // Method to handle CRON jobs to LOC service
    protected function handleCron($path = null) {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->info(TraceCode::LINE_OF_CREDIT_CRON_REQUEST, [
            'request' => $url,
        ]);

        $isLocCronRoute = false;

        foreach (self::CRON_URL_MAP as $cron => $urlCron) {
            if ($urlCron === $path) {
                $isLocCronRoute = true;
                break;
            }
        }

        if ($isLocCronRoute === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        $headers = [
            'X-Service-Name' => $this->ba->getInternalApp() ?? '',
            'X-Auth-Type'   => 'internal'
        ];

        $response = $this->sendRequestAndParseResponse($url, $body, $headers);

        $this->trace->info(TraceCode::LINE_OF_CREDIT_CRON_RESPONSE, [
            'request' => $url,
            'response' => $response,
        ]);

        return $response;
    }

    // Method to handle Razorpay X webhooks
    public function razorpayXWebhook($path = null) {
        $request = Request::instance();
        $url     = 'xPayoutCallback';
        $body    = $request->all();

        $this->trace->info(TraceCode::LINE_OF_CREDIT_RAZORPAYX_WEBHOOK_REQUEST, [
            'request' => $url,
        ]);

        $headers = [
            'X-Service-Name' => 'RazorpayX',
            'X-Auth-Type'   => 'internal',
            'X-Razorpay-Signature' => $request->header('X-Razorpay-Signature'),
        ];

        $response = $this->sendRequestAndParseResponse($url, $body, $headers);

        $this->trace->info(TraceCode::LINE_OF_CREDIT_RAZORPAYX_WEBHOOK_RESPONSE, [
            'request' => $url,
            'response' => $response,
        ]);

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
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }
        else if ((isset(self::ROUTE_PERMISSION_MAP[$route]) === false) or
                 ($this->ba->getAdmin()->hasPermission(self::ROUTE_PERMISSION_MAP[$route]) === false))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        $headers = [
            'X-Admin-Id'          => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email'       => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Admin-Permissions' => $this->getCapitalPermissionsStringForAdmin(),
            'X-Auth-Type'         => 'admin'
        ];

        $response = $this->sendRequestAndParseResponse($url, $body, $headers);

        return $response;
    }

    protected function handleDevAdminRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->info(TraceCode::LINE_OF_CREDIT_PROXY_REQUEST, [
            'request' => $url,
        ]);

        $headers = [
            'X-Admin-Id'          => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email'       => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'         => 'admin',
            'X-Admin-Permissions' => $this->getCapitalPermissionsStringForAdmin(),
        ];

        return $this->sendRequestAndParseResponse($url, $body, $headers);
    }

    protected function sendRequestAndParseResponse(
        string $url,
        array $body = [],
        array $headers = [],
        array $options = [])
    {
        $response = $this->sendRequest($url, $body, $headers, $options);

        return $this->parseResponse($response);
    }

    protected function sendRequest(
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

        return $response;
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
        $statusCode = $response->status_code;
        $body = json_decode($response->body, true);

        $this->trace->info(TraceCode::LINE_OF_CREDIT_PROXY_RESPONSE, [
            'status_code' => $statusCode,
        ]);

        if ($statusCode >= 400)
        {
            throw new Exception\TwirpException($body);
        }

        return ApiResponse::json($body, $statusCode);
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
                /** @var \RZP\Models\Merchant\Entity $merchant */
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
            $data['merchant_id'] = $merchant->getId();
            $data['merchant_name'] = $merchant->getName();
            $data['brand_color'] = $merchant->getBrandColorElseDefault();

            $data['data']['merchant_name'] = $data['merchant_name'];
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

    protected function getCapitalPermissionsStringForAdmin()
    {
        $permissions = $this->ba->getAdmin()->getPermissionsList();
        $permissionsString = "";
        $permissionCategories = Config::get('heimdall.permissions');
        $capitalPermissions = $permissionCategories[PermissionCategory::RAZORPAY_CAPITAL];
        foreach ($permissions as $permission) {
            if (isset($capitalPermissions[$permission])) {
                $permissionsString .= $permission . ":";
            }
        }
        return substr($permissionsString, 0, -1);
    }
}
