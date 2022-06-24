<?php


namespace RZP\Models\AuthzAdmin;

use App;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{


    /**
     * @var \Illuminate\Contracts\Foundation\Application|mixed
     */
    private $authzXPlatformAdminClient;

    const ORG_ID = "razorpayx";
    private $serviceId = "";
    const PAGE_SIZE = 100;

    /**
     * Service constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->authzXPlatformAdminClient  = app('authzXPlatformAdmin');

        $this->config      = app('config')->get('applications.authzXPlatformAdmin');

        $this->serviceId = $this->config['service_id'];
    }

    public function adminAPIListPolicy(array $roles)
    {
        $this->trace->info(TraceCode::AUTHZ_POLICY_LIST_REQUEST, [
            'roles'      => $roles
        ]);

        $response = [];

        $paginationToken = "*";

        $startTimeMs = round(microtime(true) * 1000);

        $fetchListPolicy = true;

        while ($fetchListPolicy) {

            $policyItemsAndCount = $this->authzXPlatformAdminClient->adminAPIListPolicy(
                $paginationToken,
                $resourceGroupIdList = null, $resourceIdList = null,
                $roleId = null, $serviceIdList = $this->serviceId,
                $permissionIdList = null, $roleNames = $roles,
                $orgId = self::ORG_ID
            );

            $paginationToken = $policyItemsAndCount->getPaginationToken();

            $policyList = array_map(function ($item) {
                return $item->getName();
            },$policyItemsAndCount->getItems());

            if ( empty($paginationToken) === true || ( $policyItemsAndCount->getCount() < self::PAGE_SIZE) ) {
                $fetchListPolicy = false;
            }

            $response = array_merge($response, array_unique($policyList));
        }

        $endTimeMs = round(microtime(true) * 1000);

        $totalFetchTime = $endTimeMs - $startTimeMs;

        $this->trace->info(TraceCode::AUTHZ_POLICY_LIST_RESPONSE, [
            'authz_list_policy_res'                    => $response,
            'authz_request_time_elapsed_ms' => $totalFetchTime
        ]);

        return array_values(array_unique($response));
    }

}
