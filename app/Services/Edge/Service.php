<?php

namespace RZP\Services\Edge;

Use ApiResponse;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\BasicAuth\Type;
use RZP\Http\Middleware\AdminAccess;
use RZP\Http\RequestHeader;
use RZP\Models\Admin\Group\Core;
use RZP\Models\Admin\Org\Entity;
use RZP\Trace\TraceCode;
use App;

class Service
{
    use TraceError;
    private $app;
    private BasicAuth $ba;
    private AdminAccess $adminAccess;
    private mixed $repo;
    private mixed $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->repo = $this->app['repo'];
        $this->ba = $this->app['basicauth'];
        $this->trace = $this->app['trace'];
        $this->ba->init();
    }
    /**
     * appAuth() does all operations that BasicAuth::appAuth() would do for authenticating an internal auth request
     * The request needs to have:
     * 1. Key (format: rzp_{mode})
     * 2. Secret (app secret)
     * 3. X-Admin-Token header
     * Optionally, the request can have
     * 1. account ID
     */
    public function appAuth(array $input)
    {
        $this->ba->setType(Type::PRIVILEGE_AUTH);
        $this->ba->setAppAuth(true);
        $res = $this->setCredentials($input);
        if ($res !== null) {
            return $this->failedSetCredentials($res);
        }

        if (($this->ba->isKeyBlank()) and ($this->verifyInternalApp($input['apps']))) {
            $res = $this->setAdminAuthIfApplicable($input['headers']);
            if ($res !== null) {
                return $this->failedSetAdminAuth($res);
            }

            $this->ba->setDashboardHeaders($input['headers']);
            $res = $this->ba->checkAndSetAccountScope();
            if ($res !== null) {
                return $this->failedSetAccountScope($res);
            }

            return null;
        }
        else if (!$this->ba->isKeyBlank())
        {
            return $this->failedKeyNotBlank();
        }
        else
        {
            return $this->failedVerifyApp();
        }
    }

    /**
     * proxyAuth() does all operations that BasicAuth::proxyAuth() would do
     * The request needs to have a valid proxy auth credential which comprises:
     * 1. Key (format: rzp_{mode}_{mid})
     * 2. Secret (app secret)
     * Optional:
     * 1. X-Admin-Token header: In this case, if the admin token is found valid, admin auth is used for authentication
     * 2. Account ID
     */
    public function proxyAuth(array $input)
    {
        $this->ba->setType(Type::PRIVATE_AUTH);
        $this->ba->setProxyTrue();

        $res = $this->setCredentials($input);
        if ($res !== null) {
            return $this->failedSetCredentials($res);
        }

        if ($this->verifyInternalAppAsProxy($input['apps'])) {
            $res = $this->setAdminAuthIfApplicable($input['headers']);
            if ($res !== null) {
                return $this->failedSetAdminAuth($res);
            }

            $this->ba->setDashboardHeaders($input['headers']);
            $res = $this->ba->checkAndSetAccountScope();
            if ($res !== null) {
                return $this->failedSetAccountScope($res);
            }

            return null;
        }
        else
        {
            return $this->failedVerifyApp();
        }
    }

    /**
     * @throws BadRequestException
     */
    public function authorizeExceptRBAC(array $input)
    {
        if ($this->ba->isAdminAuth()) {
            return $this->authorizeAdminAccessExceptRBAC($input);
        }

        if (($this->ba->isProxyAuth())) {
            return $this->authorizeUserAccessExceptRBAC($input);
        }

        return $this->failedUnreachable();
    }

    /**
     * authorizeAdminAccessExceptRBAC performs all operations done by AdminAccess middleware apart from RBAC
     *
     * @throws BadRequestException
     */
    private function authorizeAdminAccessExceptRBAC(array $input, $adminAccess = null, $adminGroupCore = null)
    {
        $this->adminAccess = $adminAccess ?? new AdminAccess($this->app);
        $adminGroupCore = $adminGroupCore ?? new Core();
        $orgId = $this->getOrgId($input);
        $this->ba->setOrgId($orgId);
        $this->adminAccess->setOrgType($orgId);
        $admin = $this->ba->getAdmin();
        $merchant = $this->adminAccess->getMerchant(isset($input['route_params']) ? $input['route_params']['merchant_id']: null);

        $this->trace->info(TraceCode::EDGE_THIRD_PARTY_ADMIN_AUTHORIZE,
            [   'org_id' => $orgId,
                'admin_id' => $admin->getId(),
                'admin_public_org_id' => $admin->getPublicOrgId(),
                'merchant_id' => $merchant ? $merchant->getId() : null
            ]);

        if ($admin->isLocked() === true)
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_USER_ACCOUNT_LOCKED);
        }

        if ($admin->isDisabled() === true)
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_USER_ACCOUNT_DISABLED);
        }

        if ($orgId !== $admin->getPublicOrgId())
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_INVALID_ORG_ID);
        }

        if ($merchant and !$adminGroupCore->groupCheck($admin, $merchant))
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        return null;
    }

    protected function authorizeUserAccessExceptRBAC(array $input)
    {
        // WIP
        return null;
    }

    /**
     * @param array $input
     * @return mixed
     */
    private function setCredentials(array $input): mixed
    {
        $args = [$input['key'], $input['secret']];
        if (isset($input['account_id']) === true)
            $args[] = $input['account_id'];
        return $this->ba->setCredentials(...$args);
    }

    /**
     * @param array $inputHeaders
     * @return mixed
     */
    private function setAdminAuthIfApplicable(array $inputHeaders): mixed
    {
        $adminToken = $inputHeaders[RequestHeader::X_ADMIN_TOKEN] ?? null;
        if ($adminToken) {
            return $this->ba->fetchAndSetAdminUsingToken($adminToken);
        }

        return null;
    }

    /**
     * @param $appNames
     * @return bool
     */
    private function verifyInternalAppAsProxy($appNames): bool
    {
        if ($this->verifyInternalApp($appNames) === false) {
            return false;
        }

        $merchantId = $this->ba->authCreds->getKey();
        $merchant = null;
        if (empty($merchantId) === false) {
            $merchant = $this->repo->merchant->find($merchantId);
        }

        $this->ba->authCreds->setMerchant($merchant);
        return ($merchant !== null);
    }

    /**
     * @param $appNames
     * @return bool
     */
    private function verifyInternalApp($appNames): bool
    {
        if ($this->ba->verifyInternalAppSecret() === false)
        {
            return false;
        }

        // Secret specified should belong to one of the apps in the input
        return in_array($this->ba->getInternalApp(), $appNames, true);
    }

    /**
     * @return mixed|string|null
     */
    private function getOrgId($input): mixed
    {
        $orgId = null;
        if(empty($input['org_id']) === false)
        {
            $orgId = $input['org_id'];
            $validateOrgId = $orgId;
            $this->repo->org->isValidOrg(Entity::verifyIdAndStripSign($validateOrgId));
        }
        else if (empty($input['headers'][AdminAccess::ORG_HOSTNAME_HEADER_KEY]) === false)
        {
            // Resolving OrgId from hostname.
            $orgHostname = $input['headers'][AdminAccess::ORG_HOSTNAME_HEADER_KEY];
            $orgId = $this->adminAccess->resolveOrgIdFromHostname($orgHostname);
        }

        return $orgId;
    }
}
