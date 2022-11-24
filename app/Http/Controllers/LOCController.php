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

    const MAIL_ERROR_REGEX = '/View \[emails.loc.(?:\w+)?\] not found./';

    protected function handleProxyRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();
        $this->trace->info(TraceCode::LINE_OF_CREDIT_PROXY_REQUEST, [
            'request' => $url,
        ]);

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
        catch (\WpOrg\Requests\Exception $e)
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

    protected function hasRequestTimedOut(\WpOrg\Requests\Exception $e): bool
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
