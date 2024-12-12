<?php


namespace RZP\Models\AuthzAdminOmni;

use App;
use RZP\Models\Base;
use RZP\Models\User\Constants;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    /**
     * @var \Illuminate\Contracts\Foundation\Application|mixed
     */
    private $authzOmniPlatformAdminClient;

    const ORG_ID = "100000razorpay";
    private $serviceId = "";
    private $resourceGroupId = "";
    const PAGE_SIZE = 100;

    /**
     * Service constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->authzOmniPlatformAdminClient = app('authzOmniPlatformAdmin');

        $this->config = app('config')->get('applications.authzOmniPlatformAdmin');

        $this->serviceId = $this->config['service_id'];
        $this->resourceGroupId = $this->config['resource_group_id'];
    }

    public function adminAPIListPermissionGroup($role, $organizationIds, $role_owner_id, $enrich_role)
    {
        $this->trace->info(TraceCode::AUTHZ_POLICY_LIST_REQUEST, [
            'role' => $role,
            'organizationIds' => $organizationIds
        ]);

        $response = [];
        $paginationToken = "*";

        foreach ($organizationIds as $organizationId) {
            $fetchListPolicy = true;
            while ($fetchListPolicy) {
                $policyItemsAndCount = $this->authzOmniPlatformAdminClient->adminAPIListPolicy(
                    $paginationToken,
                    [$this->resourceGroupId],
                    null,
                    null,
                    [$this->serviceId],
                    null,
                    null,
                    $organizationId,
                    $role_owner_id,
                    $enrich_role,
                    null,
                    $role
                );

                $paginationToken = $policyItemsAndCount->getPaginationToken();

                $permissionGrpList = array_map(function ($item) {
                    $permission = $item->getPermission();
                    return $permission[Constants::GROUP];
                }, $policyItemsAndCount->getItems());

                if (empty($paginationToken) || ($policyItemsAndCount->getCount() < self::PAGE_SIZE)) {
                    $fetchListPolicy = false;
                }

                $response = array_merge($response, array_unique($permissionGrpList));
            }
        }

        $response = array_values(array_unique($response));
        $this->trace->info(TraceCode::AUTHZ_POLICY_LIST_RESPONSE, [
            'authz_list_policy_res' => $response,
        ]);

        return $response;
    }
}
