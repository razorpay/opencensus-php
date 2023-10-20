<?php

namespace RZP\Http\Controllers;
Use ApiResponse;
use Request;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\RequestHeader;
use RZP\Services\Edge\Service;
use RZP\Services\Edge\TraceError;
use RZP\Trace\TraceCode;

class EdgeController extends Controller
{
    use TraceError;

    public function __construct()
    {
        parent::__construct();
        $this->service = new Service();
    }

    /**
     * To support dashboard proxy auth and admin auth request authentication at Edge until user auth is supported
     * Edge will call this endpoint to authenticate proxy auth and admin auth requests.
     * Passport generation and authorization (for most of the cases) will still happen at Edge.
     * Refer this doc: https://docs.google.com/document/d/18-Z9BvzlTAyYuoVqJdxRg60hO2qQpBi5DXkno_xhJH0/edit#heading=h.nmbffcswpsi5
     * @throws BadRequestException
     */
    public function authenticate()
    {
        // 'auth' indicates which auth scheme to use to authenticate the credentials
        // if 'internal', key would be in the format: rzp_{mode}
        // if 'proxy', key would be in the format: rzp_{mode}_{mid}
        if (!in_array($this->input['auth'], ['internal', 'proxy'], true)) {
            return $this->failedInvalidAuth();
        }

        // Internal auth without admin token is not supported
        // 'admin_token_required' field is expected to have a boolean value.
        // Laravel converts boolean true and false to "1" and "" respectively, hence the empty() check
        if ($this->input['auth'] === 'internal' and empty($this->input['admin_token_required']) === true)
            return $this->failedInternalAuthNotSupported();

        // Check if admin token is provided if it is set as required
        $hasAdminToken = isset($this->input['headers'][RequestHeader::X_ADMIN_TOKEN]);
        if (empty($this->input['admin_token_required']) === false and !$hasAdminToken)
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_TOKEN_NOT_FOUND);

        // Check if all apps specified are dashboard apps. If not, reject the request.
        // The intent of this interim state is to only authenticate requests coming from dashboard
        if (count(array_intersect($this->input['apps'], BasicAuth::DASHBOARD_APPS)) !== count($this->input['apps']))
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_INVALID_APPLICATION_TYPE);

        $this->trace->info(TraceCode::EDGE_THIRD_PARTY_AUTHENTICATE_REQUEST, [
            'auth' => $this->input['auth'],
            'apps' => $this->input['apps'],
            'key' => $this->input['key'],
            'account_id' => $this->input['account_id'] ?? null,
            'org_id' => $this->input['org_id'] ?? null,
            'has_admin_token' => $hasAdminToken
        ]);

        if ($this->input['auth'] === 'internal') {
            $res = $this->service->appAuth($this->input);
        }
        else {
            $res = $this->service->proxyAuth($this->input);
        }

        // Error occurred during authentication
        // Return the error
        if ($res !== null)
            return $res;

        $this->trace->info(TraceCode::EDGE_THIRD_PARTY_CUSTOM_AUTHORIZE);
        // This call will perform all custom authorization checks present in the Middlewares
        // and will return all details (roles, permissions, org) that are required to perform RBAC at Edge
        $res = $this->service->authorizeExceptRBAC($this->input);

        // Error occurred while doing custom authorization
        // Return error
        if ($res !== null)
            return $res;

        return $this->generateSuccessResponse();
    }

    private function generateSuccessResponse(): array
    {
        if ($this->ba->isAdminAuth())
        {
            $adminRolesPermissions = $this->ba->getAdmin()->getRolesAndPermissionsList();

            return [
                'admin_id' => $this->ba->getAdmin()->getId(),
                'org_id' => $this->ba->getAdmin()->getOrgId(),
                'roles' => array_values($adminRolesPermissions['roles']),
                'permissions' => array_values($adminRolesPermissions['permissions'])
                ];
        }
        else if ($this->ba->isProxyAuth())
        {
            return [];
        }

        // Should not reach here
        return $this->failedUnreachable();
    }
}
