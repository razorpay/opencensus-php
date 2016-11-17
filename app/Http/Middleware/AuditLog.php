<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use Illuminate\Foundation\Application;
use RZP\Http\Route;
use RZP\Models\Admin;
use RZP\Exception;
use RZP\Error\ErrorCode;

class AuditLog
{
    protected $app;

    const ROUTES_TO_LOG = [
        'group_create'               => ['entity' => 'group', 'action' => 'create', 'action_on' => ''],
        'admin_create'               => ['entity' => 'admin', 'action' => 'create', 'action_on' => ''],
        'group_get'                  => ['entity' => 'group', 'action' => 'view', 'action_on' => ''],
        'group_get_multiple'         => ['entity' => 'group', 'action' => 'view', 'action_on' => ''],
        'admin_get'                  => ['entity' => 'admin', 'action' => 'view', 'action_on' => ''],
        'org_create'                 => ['entity' => 'org', 'action' => 'create', 'action_on' => ''],
        'org_get_multiple'           => ['entity' => 'org', 'action' => 'view', 'action_on' => ''],
        'org_edit'                   => ['entity' => 'org', 'action' => 'edit', 'action_on' => ''],
        'org_delete'                 => ['entity' => 'org', 'action' => 'delete', 'action_on' => ''],
        'org_get'                    => ['entity' => 'org', 'action' => 'view', 'action_on' => ''],
        'role_create'                => ['entity' => 'role', 'action' => 'create', 'action_on' => ''],
        'role_get_multiple'          => ['entity' => 'role', 'action' => 'view', 'action_on' => ''],
        'role_get'                   => ['entity' => 'role', 'action' => 'view', 'action_on' => ''],
        'role_edit'                  => ['entity' => 'role', 'action' => 'edit', 'action_on' => ''],
        'role_delete'                => ['entity' => 'role', 'action' => 'delete', 'action_on' => ''],
        'admin_get_multiple'         => ['entity' => 'admin', 'action' => 'view', 'action_on' => ''],
        'admin_get_by_attr'          => ['entity' => 'admin', 'action' => 'view', 'action_on' => ''],
        'admin_get'                  => ['entity' => 'admin', 'action' => 'view', 'action_on' => ''],
        'admin_edit'                 => ['entity' => 'admin', 'action' => 'edit', 'action_on' => ''],
        'admin_delete'               => ['entity' => 'admin', 'action' => 'delete', 'action_on' => ''],
        'admin_roles_create'         => ['entity' => 'admin', 'action' => 'admin_roles_create', 'action_on' => ''],
        'admin_roles_revoke'         => ['entity' => 'admin', 'action' => 'admin_roles_revoke', 'action_on' => ''],
        'admin_merchants_create'     => ['entity' => 'admin', 'action' => 'admin_merchants_create', 'action_on' => ''],
        'admin_merchants_delete'     => ['entity' => 'admin', 'action' => 'admin_merchants_delete', 'action_on' => ''],
        'group_create'               => ['entity' => 'group', 'action' => 'create', 'action_on' => ''],
        'group_edit'                 => ['entity' => 'group', 'action' => 'edit', 'action_on' => ''],
        'group_delete'               => ['entity' => 'group', 'action' => 'delete', 'action_on' => ''],
        'group_merchants_create'     => ['entity' => 'group', 'action' => 'group_admins_create', 'action_on' => ''],
        'group_merchants_delete'     => ['entity' => 'group', 'action' => 'group_admins_create', 'action_on' => ''],
        'group_admins_get'           => ['entity' => 'group', 'action' => 'group_admins_create', 'action_on' => ''],
        'group_admins_create'        => ['entity' => 'group', 'action' => 'group_admins_create', 'action_on' => ''],
        'group_roles_create'         => ['entity' => 'group', 'action' => 'group_roles_create', 'action_on' => ''],
        'group_admins_delete'        => ['entity' => 'group', 'action' => 'group_admins_delete', 'action_on' => ''],
        'permission_create'          => ['entity' => 'permissions', 'action' => 'create', 'action_on' => ''],
        'permission_create_json'     => ['entity' => 'permissions', 'action' => 'create', 'action_on' => ''],
        'permission_get_multiple'    => ['entity' => 'permissions', 'action' => 'view', 'action_on' => ''],
        'permission_get'             => ['entity' => 'permissions', 'action' => 'view', 'action_on' => ''],
        'permission_edit'            => ['entity' => 'permissions', 'action' => 'edit', 'action_on' => ''],
        'permission_delete'          => ['entity' => 'permissions', 'action' => 'delete', 'action_on' => ''],
        'edit_activate_merchant'     => ['entity' => 'merchant', 'action' => 'merchant_activate', 'action_on' => ''],
        'edit_merchant_enable_live'  => ['entity' => 'merchant', 'action' => 'merchant_live_enable', 'action_on' => ''],
        'edit_merchant_disable_live' => ['entity' => 'merchant', 'action' => 'merchant_live_disable', 'action_on' => ''],
        'edit_merchant_methods'      => ['entity' => 'merchant', 'action' => 'edit_merchant_methods', 'action_on' => ''],
        'edit_merchant_pricing'      => ['entity' => 'merchant', 'action' => 'edit_merchant_methods', 'action_on' => ''],
        'add_merchant_adjustment'    => ['entity' => 'merchant', 'action' => 'add_merchant_adjustment', 'action_on' => ''],
        'edit_merchant_email'        => ['entity' => 'merchant', 'action' => 'edit_merchant_email', 'action_on' => ''],
    ];

    protected function __construct(Application $app)
    {
        $this->app = $app;

        $this->es = $app['es'];

        $this->repo = $app['repo'];

        $this->ba = $app['basicauth'];

        $this->router = $app['router'];
    }

    public function handle($request, Closure $next)
    {
        $routeName = $this->router->currentRouteName();

        if (($this->ba->isAdminAuth()) && isset(self::ROUTES_TO_LOG[$routeName]))
        {
            $admin = $this->ba->getAdmin();

            $routeInfo = self::ROUTES_TO_LOG[$routeName];

            $actionOn = $routeInfo['action_on'];

            $data = $this->prepareData($admin, $actionOn, $routeInfo, $request);

            $this->logToEs($data);
        }

        return $next($request);
    }

    protected function prepareData($admin, $actionOn, $routeInfo, $request)
    {
        return [
            'admin' => '',
            'collection' => $routeInfo['entity'],
            'action' => $routeInfo['action'],
            'comment' => 'comment',//$request[] If any,
            'entity_id' => '',
            'entity_type' => '',
            'entity_name' => '',
            'created_at' => time(),
            'user_agent' => '',
            'ip' => '',
        ];
    }

    protected function logToEs()
    {

    }

}
