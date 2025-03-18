<?php


namespace RZP\Services\Mock;

use App;
use AuthzAdmin\Client\ApiException;
use AuthzAdmin\Client\Model as AuthzAdminModel;

class AuthzAdminClient
{

    private static $rolePolicyMap;

    private static function init()
    {
        $payout_create = new AuthzAdminModel\V1ExpandedPolicy;
        $payout_create->setName("payout_create");

        $view_create = new AuthzAdminModel\V1ExpandedPolicy;
        $view_create->setName("view_payout");

        self::$rolePolicyMap = [
            'admin' => [
                $payout_create,
                $view_create
            ],
            "FwexM2MJ45NcOz" => [
                $payout_create,
                $view_create
            ]
        ];
    }
    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        self::init();
    }

    public function adminAPIListPolicy($pagination_token = null, $resource_group_id_list = null,
                                       $resource_id_list = null, $role_id = null,
                                       $service_id_list = null, $permission_id_list = null,
                                       $role_names=null, $org_id = null)
    {
        $response = new AuthzAdminModel\V1ListPolicyResponse;

        $rolePolicyList = self::$rolePolicyMap[$role_names[0]] ?? [];

        $response->setPaginationToken("");
        $response->setItems($rolePolicyList);

        return $response;
    }

    public function adminAPIMigrateRole($request = null)
    {
        return new AuthzAdminModel\V1MigrateRoleResponse([
            'success' => true,
        ]);
    }

    public function adminAPICreateRole($request = null, $x_passport_jwt_v1 = null)
    {
        return $request;
    }

    public function adminAPIUpdateRole($request = null, $x_passport_jwt_v1 = null)
    {
        return $request;
    }

    public function adminAPIGetRole($identifier, $org_id = null, $owner_id = null, $expand_children = null)
    {
        if ($identifier === 'owner')
        {
            $response = new AuthzAdminModel\V1Role([
                'id'            => 'AwexM2MJ45NcOz',
                'name'          => 'Owner',
                'org_id'        => 'razorpayx',
                'type'          => AuthzAdminModel\V1RolePolicyType::STANDARD,
                'owner_type'    => 'merchant',
                'owner_id'      => '10000000000000',
                'child_ids'     => [],
                'created_by'    => 'MerchantUser01',
                'children'      => null,
                'description'   => 'Owner role',
            ]);

            if ($expand_children === true)
            {
                $response->setChildren([
                    new AuthzAdminModel\V1ChildRoles(['name' => 'account_info_view_only']),
                    new AuthzAdminModel\V1ChildRoles(['name' => 'payout_admin']),
                    new AuthzAdminModel\V1ChildRoles(['name' => 'fundaccount_admin']),
                    new AuthzAdminModel\V1ChildRoles(['name' => 'contact_admin']),
                    new AuthzAdminModel\V1ChildRoles(['name' => 'workflow_view_only']),
                    new AuthzAdminModel\V1ChildRoles(['name' => 'payout_link_admin']),
                    new AuthzAdminModel\V1ChildRoles(['name' => 'payout_view_only']),
                    new AuthzAdminModel\V1ChildRoles(['name' => 'fundaccount_view_only']),
                    new AuthzAdminModel\V1ChildRoles(['name' => 'contact_view_only']),
                    new AuthzAdminModel\V1ChildRoles(['name' => 'workflow_view_only']),
                    new AuthzAdminModel\V1ChildRoles(['name' => 'payout_link_view_only']),
                    new AuthzAdminModel\V1ChildRoles(['name' => 'invoice_admin']),
                    new AuthzAdminModel\V1ChildRoles(['name' => 'invoice_view_only']),
                    new AuthzAdminModel\V1ChildRoles(['name' => 'tax_admin']),
                    new AuthzAdminModel\V1ChildRoles(['name' => 'tax_view_only']),
                    new AuthzAdminModel\V1ChildRoles(['name' => 'dev_controls_admin']),
                    new AuthzAdminModel\V1ChildRoles(['name' => 'dev_controls_view_only'])
                ]);
            }

            return $response;
        }

        throw new ApiException('sql: no rows in result set', 404);
    }

    public function adminAPIListRole($pagination_token = null, $role_name_prefix = null, $role_names = null, $role_ids = null, $org_id = null, $key_id = null, $key_owner_type = null, $key_owner_id = null, $owner_ids = null, $type = null, $types = null)
    {
        return (new AuthzAdminModel\V1ListRoleResponse());
    }

    public function adminAPIListPrivileges($org_id = null, $expand_actions = null, $expand_roles = null, $pagination_token = null, $visibility = 1)
    {
        return (new AuthzAdminModel\V1ListPrivilegesResponse());
    }

    public function adminAPICreatePrivilege($request = null)
    {
        return $request;
    }

    public function adminAPIUpdatePrivilege($request = null)
    {
        return $request;
    }

    public function adminAPICreatePrivilegeRoleMapping($request = null)
    {
        return $request;
    }

    public function adminAPIUpdatePrivilegeRoleMapping($request = null)
    {
        return $request;
    }
}
