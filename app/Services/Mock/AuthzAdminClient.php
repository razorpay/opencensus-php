<?php


namespace RZP\Services\Mock;

use App;
use AuthzAdmin\Client\Model\V1ListPolicyResponse;
use AuthzAdmin\Client\Model\V1ExpandedPolicy;

class AuthzAdminClient
{

    private static $rolePolicyMap;

    private static function init()
    {
        $payout_create = new V1ExpandedPolicy;
        $payout_create->setName("payout_create");

        $view_create = new V1ExpandedPolicy;
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
        $response = new V1ListPolicyResponse;

        $rolePolicyList = self::$rolePolicyMap[$role_names[0]] ?? [];

        $response->setPaginationToken("");
        $response->setItems($rolePolicyList);

        return $response;
    }

}
