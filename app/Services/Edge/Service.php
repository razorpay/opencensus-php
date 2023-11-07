<?php

namespace RZP\Services\Edge;

Use ApiResponse;
use RZP\Constants\Product;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\BasicAuth\Type;
use RZP\Http\Middleware\AdminAccess;
use RZP\Http\Middleware\MerchantIpFilter;
use RZP\Http\Middleware\ProductIdentifier;
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
    private array $userRoles;
    private array $userEnforcementRoles;
    private mixed $repo;
    private mixed $trace;
    private array $inputHeaders;

    public function __construct($inputHeaders)
    {
        $this->app = App::getFacadeRoot();
        $this->repo = $this->app['repo'];
        $this->ba = $this->app['basicauth'];
        $this->trace = $this->app['trace'];
        $this->ba->init();
        $this->inputHeaders = array_change_key_case($inputHeaders, CASE_LOWER);
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
            $res = $this->setAdminAuthIfApplicable();

            if ($res !== null) {
                return $this->failedSetAdminAuth($res);
            }

            $this->ba->setDashboardHeaders($this->inputHeaders);

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
            $res = $this->setAdminAuthIfApplicable();
            if ($res !== null) {
                return $this->failedSetAdminAuth($res);
            }

            $this->ba->setDashboardHeaders($this->inputHeaders);
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

        if ($this->ba->isProxyAuth()) {
            return $this->authorizeUserAccessExceptRBAC();
        }

        return $this->failedUnreachable();
    }

    /**
     * authorizeAdminAccessExceptRBAC performs all operations done by AdminAccess middleware apart from RBAC
     *
     * @throws BadRequestException|BadRequestValidationFailureException
     */
    private function authorizeAdminAccessExceptRBAC(array $input, $adminAccess = null, $adminGroupCore = null)
    {
        $this->adminAccess = $adminAccess ?? new AdminAccess($this->app);
        $adminGroupCore = $adminGroupCore ?? new Core();
        $orgId = $this->getOrgId($input);
        $this->ba->setOrgId($orgId);
        $this->adminAccess->setOrgType($orgId);
        $admin = $this->ba->getAdmin();
        $merchant = $this->adminAccess->getMerchant(isset($input['dashboard']['route_params']) ? $input['dashboard']['route_params']['merchant_id']: null);

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

        if ($orgId !== $admin->getOrgId())
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_INVALID_ORG_ID);
        }

        if ($merchant and !$adminGroupCore->groupCheck($admin, $merchant))
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        return null;
    }

    /**
     * Performs all authorization related operations performed on a proxy auth request coming from dashboard
     * Middlewares involved: ProductIdentifier -> UserAccess -> MerchantIpFilter
     * @param null $merchantIpFilter
     * @param null $roleAccessPolicyMapService
     * @return null
     */
    private function authorizeUserAccessExceptRBAC($merchantIpFilter = null, $roleAccessPolicyMapService = null)
    {
         $this->setRequestOriginProduct();
         $res = $this->verifyAndSetUser();
         if ($res !== null)
             return $res;

        // To maintain parity with the passport generated by API, passport generated by Edge will need to have both the user role and authZ roles
        $roleAccessPolicyMapService = $roleAccessPolicyMapService ?? new \RZP\Models\RoleAccessPolicyMap\Service();
        $authzRoles =  $roleAccessPolicyMapService->getAuthzRolesForRoleId($this->ba->getUserRole());
        $this->userRoles = array_merge([$this->ba->getUserRole()], $authzRoles);

        // Note: LMS also comes under Banking Product
        // LMS requests are those that have partner-lms.razorpay.com as host
        // Non LMS and Product=Banking requests have x.razorpay.com as host
        // For product=banking and non LMS, if CAC is enabled on the merchant, enforcement is done using the authZ roles mapped to
        // the user role in role_access_policy_map table. Otherwise, it is done using the user role.
        $isCACEnabled = $this->ba->getMerchant()->isCACEnabled();
        if (!$this->ba->isBankLms() and $this->ba->getRequestOriginProduct() === Product::BANKING and $isCACEnabled)
        {
            // CAC is enabled, peform authorization based on authz roles mapped to the user role
            $this->userEnforcementRoles = $authzRoles;
        }
        else
        {
            $this->userEnforcementRoles = [$this->ba->getUserRole()];
        }

        $clientRequestIp = $this->getHeader(RequestHeader::X_DASHBOARD_IP);
        $this->trace->info(TraceCode::EDGE_THIRD_PARTY_USER_AUTHORIZE,
            [   'product' => $this->ba->getRequestOriginProduct(),
                'is_lms' => $this->ba->isBankLms(),
                'user_id' => $this->ba->getUser()->getId(),
                'merchant_id' => $this->ba->getMerchantId(),
                'is_cac_enabled' => $isCACEnabled,
                'client_ip' => $clientRequestIp
            ]);

        // If Dashboard Ip was provided in headers, check if it is whitelisted for merchant
        // This can be done at Edge through ip-restriction-x plugin in the future
        $merchantIpFilter = $merchantIpFilter ?? new MerchantIpFilter($this->app);
        $res = $merchantIpFilter->authenticateIpForProxyAuth($clientRequestIp);
        if ($res !== null)
            return $res;

        return null;
    }

    /**
     * @param array $input
     * @return mixed
     */
    private function setCredentials(array $input): mixed
    {
        $dashboardRequestInfo = $input['dashboard'];
        $args = [$dashboardRequestInfo['key'], $dashboardRequestInfo['secret']];
        if (isset($dashboardRequestInfo['account_id']) === true)
            $args[] = $dashboardRequestInfo['account_id'];
        return $this->ba->setCredentials(...$args);
    }

    /**
     * @return mixed
     */
    private function setAdminAuthIfApplicable(): mixed
    {
        $adminToken = $this->getHeader(RequestHeader::X_ADMIN_TOKEN);
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
     * @throws BadRequestValidationFailureException
     */
    private function getOrgId($input): mixed
    {
        $dashboardRequestInfo = $input['dashboard'];
        $orgId = null;
        if(empty($dashboardRequestInfo['org_id']) === false)
        {
            $orgId = $dashboardRequestInfo['org_id'];
            // org ID is expected to be already stripped
            Entity::verifyUniqueId($orgId);
            $this->repo->org->isValidOrg($orgId);
        }
        else if (empty($this->getHeader(AdminAccess::ORG_HOSTNAME_HEADER_KEY)) === false)
        {
            // Resolving OrgId from hostname.
            $orgHostname = $this->getHeader(AdminAccess::ORG_HOSTNAME_HEADER_KEY);

            $orgId = $this->adminAccess->resolveOrgIdFromHostname($orgHostname);
        }

        return $orgId;
    }

    private function setRequestOriginProduct(): void
    {
        $productIdentifier = new ProductIdentifier($this->app);
        $originDomain = $this->getHeader(RequestHeader::X_REQUEST_ORIGIN);

        $product = $productIdentifier->getRequestOriginProductFromOrigin($originDomain);
        $this->ba->setRequestOriginProduct($product);
        $productIdentifier->setIfBankLmsRequestFromOrigin($originDomain);
    }

    private function verifyAndSetUser()
    {
        $dashboardHeaders = $this->ba->getDashboardHeaders();
        $userId = $dashboardHeaders['user_id'] ?? null;

        if ($userId === null)
        {
            $userId = $this->getHeader(RequestHeader::X_Creator_Id);

            $userType = $this->getHeader(RequestHeader::X_Creator_Type);

            if ((empty($userType) === false) and
                ($userType === 'admin'))
            {
                return ApiResponse::unauthorized(
                    ErrorCode::BAD_REQUEST_USER_NOT_FOUND);
            }
        }

        if (empty($userId) === false)
        {
            $this->ba->setUserAndRoles($userId);
        }

        return null;
    }

    public function getUserRoles(): array
    {
        return $this->userRoles;
    }

    public function getUserEnforcementRoles(): array
    {
        return $this->userEnforcementRoles;
    }

    private function getHeader(string $key)
    {
        return $this->inputHeaders[strtolower($key)] ?? null;
    }
}
