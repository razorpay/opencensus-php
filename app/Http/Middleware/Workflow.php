<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use RZP\Exception;
use RZP\Http\Route;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use Illuminate\Foundation\Application;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Workflow\Service as WorkflowService;
use RZP\Models\Workflow\Action\Differ\EntityValidator;

class Workflow
{
    const WILDCARD_PERMISSION = '*';

    const WORKFLOW_CONTROLLER = 'RZP\Http\Controllers\WorkflowController';

    // Mostly because workflow will be trigger
    // inside the code since the generic handler
    // is too generic to handle the diffing.
    // Workflows for EXCLUDED_PERMISSIONS will be triggered from inside
    // the code.
    const EXCLUDED_PERMISSIONS = [
        Permission::EDIT_THROTTLE_SETTINGS,
        Permission::EDIT_MERCHANT_METHODS,
        Permission::ASSIGN_MERCHANT_BANKS,
        Permission::ADD_MERCHANT_CREDITS,
        Permission::EDIT_MERCHANT_PRICING,
        Permission::EDIT_ACTIVATE_MERCHANT,
        Permission::EDIT_ACTIVATE_PARTNER,
        Permission::UPDATE_MOBILE_NUMBER,
        Permission::EDIT_MERCHANT_KEY_ACCESS,
        Permission::ADD_MERCHANT_ADJUSTMENT,
        Permission::SCHEDULE_ASSIGN,
        Permission::SCHEDULE_ASSIGN_BULK,
        Permission::PRICING_ASSIGN_BULK,
        Permission::METHODS_ASSIGN_BULK,
        Permission::EDIT_MERCHANT_ENABLE_LIVE,
        Permission::EDIT_MERCHANT_DISABLE_LIVE,
        Permission::DELETE_MERCHANT_FEATURES,
        Permission::CREATE_PRICING_PLAN,
        Permission::PAYMENTS_CREATE_BUY_PRICING_PLAN,
        Permission::CREATE_DISPUTE,
        Permission::EDIT_MERCHANT_BANK_DETAIL,
        Permission::MERCHANT_INVOICE_EDIT,
        Permission::CREATE_ADMIN,
        Permission::DELETE_ADMIN,
        Permission::EDIT_MERCHANT_REQUESTS,
        Permission::UPDATE_PRICING_PLAN,
        Permission::PAYMENTS_UPDATE_BUY_PRICING_PLAN,
        Permission::MANAGE_RAZORX_OPERATIONS,
        Permission::CREATE_PAYOUT,
        Permission::EDIT_MERCHANT_GSTIN_DETAIL,
        Permission::EDIT_MERCHANT_WEBSITE_DETAIL,
        Permission::UPDATE_MERCHANT_WEBSITE,
        Permission::DELETE_TERMINAL,
        Permission::TOGGLE_TERMINAL,
        Permission::EDIT_MERCHANT_INTERNATIONAL,
        Permission::EDIT_MERCHANT_PG_INTERNATIONAL,
        Permission::EDIT_MERCHANT_PROD_V2_INTERNATIONAL,
        Permission::TOGGLE_INTERNATIONAL_REVAMPED,
        Permission::COMMISSION_PAYOUT,
        Permission::CREATE_GOVERNOR_RULE,
        Permission::EDIT_GOVERNOR_RULE,
        Permission::EDIT_SCORECARD_GOVERNOR_CONF,
        Permission::DELETE_GOVERNOR_RULE,
        Permission::CREATE_GATEWAY_RULE,
        Permission::EDIT_GATEWAY_RULE,
        Permission::DELETE_GATEWAY_RULE,
        Permission::EDIT_MERCHANT_INTERNATIONAL_NEW,
        Permission::CREATE_SHIELD_RULE,
        Permission::CREATE_MERCHANT_RISK_THRESHOLD,
        Permission::UPDATE_MERCHANT_RISK_THRESHOLD,
        Permission::DELETE_MERCHANT_RISK_THRESHOLD,
        Permission::BULK_UPDATE_MERCHANT_RISK_THRESHOLD,
        Permission::CREATE_RISK_THRESHOLD_CONFIG,
        Permission::UPDATE_RISK_THRESHOLD_CONFIG,
        Permission::DELETE_RISK_THRESHOLD_CONFIG,
        Permission::EDIT_SHIELD_RULE,
        Permission::DELETE_SHIELD_RULE,
        Permission::CREATE_SHIELD_LIST,
        Permission::DELETE_SHIELD_LIST,
        Permission::ADD_SHIELD_LIST_ITEMS,
        Permission::PURGE_SHIELD_LIST_ITEMS,
        Permission::DELETE_SHIELD_LIST_ITEM,
        Permission::MERCHANT_RISK_ALERT_FOH,
        Permission::MERCHANT_RISK_ALERT_UPSERT_RULE,
        Permission::MERCHANT_RISK_ALERT_DELETE_RULE,
        Permission::EDIT_MERCHANT_RISK_ATTRIBUTES,
        Permission::ADD_ADDITIONAL_WEBSITE,
        Permission::INCREASE_TRANSACTION_LIMIT,
        Permission::INCREASE_INTERNATIONAL_TRANSACTION_LIMIT,
        Permission::SYNC_ENTITY_BY_ID,
        Permission::PAYMENTS_BUY_PRICING_PLAN_RULE_ADD,
        Permission::PAYMENTS_BUY_PRICING_PLAN_RULE_FORCE_DELETE,
        Permission::ENABLE_NON_3DS_PROCESSING,
        Permission::CREATE_CYBER_HELPDESK_WORKFLOW,
        Permission::EXECUTE_MERCHANT_MAX_PAYMENT_LIMIT_WORKFLOW,
        Permission::EDIT_TERMINAL,
        Permission::EDIT_TERMINAL_GOD_MODE,
        Permission::ASSIGN_MERCHANT_TERMINAL,
        Permission::TERMINAL_MANAGE_MERCHANT
    ];

    protected $app;

    protected $config;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->config = $this->app['config'];

        $this->router = $app['router'];

        $this->ba = $this->app['basicauth'];
    }

    public function handle($request, Closure $next)
    {
        $routeName = $this->router->currentRouteName();

        $maker = $this->app['workflow']->getWorkflowMaker();

        // Disable workflows if:
        // - It is mocked
        // - The maker isn't an Admin or Merchant
        // - Auth is not apt for workflows
        // - No org ID found in the incoming request
        if (($this->config->get('heimdall.workflows.mock') === true) or
            (empty($maker) === true) or
            ($this->isAptAuthForWorkflows() === false) or
            (empty($this->ba->getOrgId()) === true))
        {
            return $next($request);
        }

        try
        {
            $permission = $this->getRoutePermission($routeName);

            // Workflows for EXCLUDED_PERMISSIONS will be triggered from inside the code
            if (in_array($permission, self::EXCLUDED_PERMISSIONS, true) === true)
            {
                // Set the default permission in workflow service
                $this->app['workflow']
                     ->setPermission($permission);

                return $next($request);
            }

            // ba->getOrgId() returns route's org ID, not maker's org ID
            // This will also work when RZP admin tries to hit route for HDFC
            // but yes the admin can spoof the call by passing random org_id in $input
            // note: this will only happen for Route::$crossOrgRoutes since for all
            // other routes we have a strict check of admin->org === route->org
            //
            // So only RZP admins can exploit this, can figure out a solution later
            // when this is a "real" issue.
            $orgId = $this->ba->getOrgId();

            $merchantId = null;

            //
            // For merchant app permissions, maker=merchant, we send the merchant ID for fetching
            // only workflows defined for the merchant
            //
            if (Permission::isMerchantPermission($permission) === true)
            {
                $merchantId = $maker->getId();
            }

            $permissionHasWorkflow = (new WorkflowService)->permissionHasWorkflow(
                $permission, Org\Entity::verifyIdAndSilentlyStripSign($orgId), $merchantId);

            // rzp admin -> hdfc bank_account_update
            // rzp P1 no workflow

            // If the permissions has no workflow assigned to it
            // then let's not apply any maker-checker process
            if ($permissionHasWorkflow === false)
            {
                return $next($request);
            }
        }
        catch(Exception\BadRequestException $ex)
        {
            // This middleware will only run for routes whose
            // permission have a workflow defined for them.
            if ($ex->getCode() === ErrorCode::BAD_REQUEST_PERMISSION_ERROR)
            {
                return $next($request);
            }
            else
            {
                throw $ex;
            }
        }

        $entity = EntityValidator::getEntityName($routeName);

        $this->app['workflow']
             ->setEntity($entity)
             ->setPermission($permission);

        // Workflow service returns array value
        $response = $this->app['workflow']->trigger();

        // Middleware must return instance of Response class
        return ApiResponse::json($response);
    }

    private function getRoutePermission($routeName)
    {
        $routePermissionList = Route::$routePermission;

        if (empty($routePermissionList[$routeName]) === false)
        {
            $permission = $routePermissionList[$routeName];

            // Required permission cannot be wildcard
            // since we won't apply workflows on wildcards
            if ($permission !== self::WILDCARD_PERMISSION)
            {
                return $permission;
            }
        }

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_PERMISSION_ERROR);
    }

    /**
     * Workflows will work for admin auth (admins) + proxy auth (merchants)
     * (without internal auth)
     */
    private function isAptAuthForWorkflows()
    {
        $adminAuth = $this->ba->isAdminAuth();

        $proxyAuth = $this->ba->isProxyAuth();

        $strictPrivateAuth = $this->ba->isStrictPrivateAuth();

        if (($adminAuth === true) or
            ($proxyAuth === true and $strictPrivateAuth === false))
        {
            return true;
        }

        return false;
    }

}
