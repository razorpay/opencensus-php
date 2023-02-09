<?php

namespace RZP\Services;

use Config;
use Request;
use ApiResponse;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use Illuminate\Support\Str;
use RZP\Exception\TwirpException;
use RZP\Exception\IntegrationException;
use RZP\Http\Request\Requests as RzpRequest;
use WpOrg\Requests\Exception as WpOrgException;
use RZP\Models\Admin\Permission\Category as PermissionCategory;

class LOSService extends Base\Service
{

    const GET    = 'GET';
    const POST   = 'POST';
    const PUT    = 'PUT';
    const PATCH  = 'PATCH';
    const DELETE = 'DELETE';

    protected string $baseUrl;

    protected string $username;

    protected string $password;

    protected string $timeout;

    protected mixed  $ba;

    public function __construct()
    {
        parent::__construct();

        $losConfig      = $this->app['config']['applications.loan_origination_system'];
        $this->baseUrl  = $losConfig['url'];
        $this->username = $losConfig['username'];
        $this->password = $losConfig['secret'];
        $this->timeout  = $losConfig['timeout'];
        $this->ba       = $this->app['basicauth'];
    }

    public function getCapitalRolesAndPermissionsForAdmin(): array
    {
        $adminRolesPermissions = $this->ba->getAdmin()->getRolesAndPermissionsList();
        $adminRoles            = $adminRolesPermissions['roles'];
        $adminPermissions      = $adminRolesPermissions['permissions'];
        $permissionCategories  = Config::get('heimdall.permissions');
        $capitalPermissions    = $permissionCategories[PermissionCategory::RAZORPAY_CAPITAL];
        $permissionsString     = "";
        foreach ($adminPermissions as $adminPermission)
        {
            if (isset($capitalPermissions[$adminPermission]) || str_starts_with($adminPermission, "capital_los_"))
            {
                $permissionsString .= $adminPermission . ":";
            }
        }
        $rolesString = "";
        foreach ($adminRoles as $adminRole)
        {
            if (str_starts_with($adminRole, "Capital LOS"))
            {
                $rolesString .= $adminRole . ":";
            }
        }

        return array('permissions' => $permissionsString, 'roles' => $rolesString);
    }


    /**
     * @throws TwirpException
     */
    public function parseResponse($response)
    {
        $statusCode = $response->status_code;
        $body       = json_decode($response->body, true);

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_RESPONSE, [
            'status_code' => $statusCode,
        ]);

        if ($statusCode >= 400)
        {
            throw new Exception\TwirpException($body);
        }

        return ApiResponse::json($body, $statusCode);
    }

    /**
     * @param string $url
     * @param array  $body
     * @param array  $headers
     * @param array  $options
     *
     * @return mixed
     * @throws IntegrationException
     */
    public function sendRequest(
        string $url,
        array  $body = [],
        array  $headers = [],
        array  $options = []): mixed
    {
        $clientIpAddress         = $_SERVER['HTTP_X_IP_ADDRESS'] ?? $this->app['request']->ip();
        $headers['Accept']       = 'application/json';
        $headers['Content-Type'] = 'application/json';
        $headers['X-Task-Id']    = $this->app['request']->getTaskId();
        $headers['X-Client-IP']  = $clientIpAddress;

        if (empty(Request::header(RequestHeader::DEV_SERVE_USER)) === false)
        {
            $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
        }

        $auth           = [$this->username, $this->password];
        $defaultOptions = ['timeout' => $this->timeout, 'auth' => $auth,];
        $method         = self::POST;

        try
        {
            $response = RzpRequest::request(
                $this->baseUrl . $url,
                $headers,
                empty($body) ? "{}" : json_encode($body),
                $method,
                $defaultOptions
            );
        }
        catch (WpOrgException $e)
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

        return $response;
    }

    protected function hasRequestTimedOut(WpOrgException $e): bool
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
}
