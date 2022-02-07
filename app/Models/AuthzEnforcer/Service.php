<?php

namespace RZP\Models\AuthzEnforcer;

use App;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Swagger\Client\Model\V1Claims;
use Swagger\Client\Model\V1EnforceRequest;
use Swagger\Client\Model\V1EnforceResponse;

class Service extends Base\Service
{
    /**
     * @var \Illuminate\Contracts\Foundation\Application|mixed
     */
    private $authzXPlatformEnforcerClient;
    /**
     * @var mixed
     */
    private $config;

    /**
     * Service constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->authzXPlatformEnforcerClient  = app('authzXPlatformEnforcer');
        $this->config      = app('config')->get('applications.authzXPlatformEnforcer');
    }

    public function enforcerAPIEnforce($resourceAndAction, $role, $subject, $org): V1EnforceResponse
    {
        $claims = $this->getCalimsObject($org, $role, $subject);

        $payload = $this->getEnforceRequestObject($claims, $resourceAndAction);

        $this->trace->info(TraceCode::AUTHZ_ENFORCEMENT_REQUEST, [
            'role'      => $role,
            'resource_action'   => $resourceAndAction
        ]);

        $startTimeMs = round(microtime(true) * 1000);

        $res = $this->authzXPlatformEnforcerClient->enforcerAPIEnforce($payload);

        $endTimeMs = round(microtime(true) * 1000);

        $totalFetchTime = $endTimeMs - $startTimeMs;

        $this->trace->info(TraceCode::AUTHZ_ENFORCEMENT_RESPONSE, [
            'is_allowed'                    => $res->getIsAllowed(),
            'authz_request_time_elapsed_ms' => $totalFetchTime
        ]);

        return $res;
    }

    public function getCalimsObject(string $org, array $role, $subject)
    {
        $claims = new V1Claims;
        $claims->setOrg($org);
        $claims->setRoles($role);
        $claims->setSubject($subject);

        return $claims;
    }

    public function getEnforceRequestObject(V1Claims $claims, array $resourceAndAction)
    {
        [$resource, $action] = $resourceAndAction;

        $payload =  new V1EnforceRequest;
        $payload->setClaims($claims);
        $payload->setAction($action);
        $payload->setResource($resource);

        return $payload;
    }
}
