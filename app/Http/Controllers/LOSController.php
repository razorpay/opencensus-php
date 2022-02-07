<?php
namespace RZP\Http\Controllers;

use Config;
use Request;
use ApiResponse;
use RZP\Exception;
use RZP\Mail\Los\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use RZP\Http\Request\Requests as RzpRequest;
use RZP\Models\Admin\Permission\Category as PermissionCategory;

class LOSController extends Controller
{
    const GET    = 'GET';
    const POST   = 'POST';
    const PUT    = 'PUT';
    const PATCH  = 'PATCH';
    const DELETE = 'DELETE';

    const LEEGALITY_WEBHOOK_URL                       = 'twirp/rzp.capital.los.contracts.v1.DocSignAPI/LeegalityWebhook';

    protected function handleProxyRequests($path = null)
    {
        $request = Request::instance();
        $url = $path;
        $body   = $request->all();

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_REQUEST, [
            'request' => $url,
        ]);

        $headers = [
            'X-Merchant-Id'    => $this->ba->getMerchant()->getId() ?? '',
            'X-Merchant-Email' => $this->ba->getMerchant()->getEmail() ?? '',
            'X-User-Id'        => $this->ba->getUser()->getId() ?? '',
            'X-User-Role'      => $this->ba->getUserRole() ?? '',
            'X-Auth-Type'      => 'proxy',
        ];

        return $this->sendRequestAndParseResponse($url, $body, $headers);
    }

    protected function handleAdminRequests($path = null)
    {
        $request = Request::instance();
        $url = $path;
        $body   = $request->all();

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_REQUEST, [
            'request' => $url,
        ]);


        $headers = [
            'X-Admin-Id'    => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email' => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'   => 'admin',
            'X-Admin-Permissions' => $this->getCapitalPermissionsStringForAdmin(),
        ];

        return $this->sendRequestAndParseResponse($url, $body, $headers);
    }

    protected function handleDevAdminRequests($path = null)
    {
        $request = Request::instance();
        $url = $path;
        $body   = $request->all();

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_REQUEST, [
            'request' => $url,
        ]);

        $headers = [
            'X-Admin-Id'    => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email' => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'   => 'admin'
        ];


        return $this->sendRequestAndParseResponse($url, $body, $headers);
    }

    protected function handleCronRequests($path = null) {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_CRON_REQUEST, [
            'request' => $url,
        ]);

        $headers = [
            'X-Service-Name' => $this->ba->getInternalApp() ?? '',
            'X-Auth-Type'   => 'internal'
        ];

        $response = $this->sendRequestAndParseResponse($url, $body, $headers);

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_CRON_RESPONSE, [
            'request' => $url,
            'response' => $response,
        ]);

        return $response;
    }

    protected function handleLeegalityWebhook($path = null)
    {
        $request = Request::instance();
        $url = self::LEEGALITY_WEBHOOK_URL;
        $body   = $request->all();

        $headers = [
            'X-Service-Name'    => $this->ba->getInternalApp() ?? '',
            'X-Auth-Type'       => 'internal',
        ];

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_REQUEST, [
            'request' => $url,
        ]);

        $response = $this->sendRequestAndParseResponse($url, $body, $headers);
        return $response;
    }

    protected function sendRequestAndParseResponse(
        string $url,
        array $body = [],
        array $headers = [],
        array $options = [])
    {
        $config = config('applications.loan_origination_system');
        $baseUrl = $config['url'];
        $username = $config['username'];
        $password = $config['secret'];
        $timeout = $config['timeout'];
        $headers['Accept']       = 'application/json';
        $headers['Content-Type'] = 'application/json';
        $headers['X-Task-Id'] = $this->app['request']->getTaskId();

        $auth = [$username, $password];
        $defaultOptions = [
            'timeout' => $timeout,
            'auth'    => $auth,
        ];
        $method = self::POST;

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
                ErrorCode::GATEWAY_ERROR_LOAN_ORIGINATION_SYSTEM_TIMEOUT :
                ErrorCode::GATEWAY_ERROR_LOAN_ORIGINATION_SYSTEM_FAILURE;
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
        $statusCode = $response->status_code;
        $body = json_decode($response->body, true);

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_RESPONSE, [
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
        $data   = $request->all();
        if ((isset($data['name']) === false) or
            (isset($data['email']) === false) or
            (isset($data['template']) === false) or
            (isset($data['subject']) === false))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        }

        $mail = new Base($data);
        Mail::queue($mail);

        return ApiResponse::json(['success' => true]);
    }

    protected function getCapitalPermissionsStringForAdmin() {
        $permissions = $this->ba->getAdmin()->getPermissionsList();
        $permissionsString = "";
        $permissionCategories = Config::get('heimdall.permissions');
        $capitalPermissions = $permissionCategories[PermissionCategory::RAZORPAY_CAPITAL];
        foreach ($permissions as $permission) {
            if (isset($capitalPermissions[$permission])) {
                $permissionsString .= $permission.":";
            }
        }
        return substr($permissionsString, 0, -1);
    }

    protected function startWorkflow($body)
    {
        $this->app['workflow']
            ->setEntityAndId('loan_origination_system', $body['disbursal']['application_id'])
            ->handle([], ['status' => 'loan_origination_system_workflow_started']);
    }
}

