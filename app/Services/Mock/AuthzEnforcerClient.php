<?php


namespace RZP\Services\Mock;

use App;
use Swagger\Client\Model\V1EnforceRequest;
use Swagger\Client\Model\V1EnforceResponse;


class AuthzEnforcerClient
{
    private static $resourceRoleAccessMap;

    private static function init()
    {
        self::$resourceRoleAccessMap = [
                '/v1/payouts' => [
                    'post' => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3'],
                    'get'  => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3', 'operations', 'view_only'],
                ],

                '/v1/fund_accounts' => [
                    'post' => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3'],
                    'get'  => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3', 'operations', 'view_only'],
                ],

                '/v1/fund_accounts/{id}' => [
                    'get'  => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3', 'operations', 'view_only'],
                ],

                '/v1/payouts_with_otp' => [
                    'post' => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3'],
                ],

                '/v1/payouts_batch' => [
                    'post' => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3'],
                ],

                '/v1/validate_payouts' => [
                    'post' => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3'],
                ],

                '/v1/payouts/{id}/approve' => [
                    'post' => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3'],
                ],

                '/v1/payouts/{id}/cancel' => [
                    'post' => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3'],
                ],

                '/v1/payouts/{id}/reject' => [
                    'post' => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3'],
                ],

                '/v1/payouts/bulk' => [
                    'post' => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3'],
                ],

                '/v1/payouts/bulk_approve' => [
                    'post' => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3'],
                ],

                '/v1/transactions/{id}' => [
                    'get'  => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3', 'view_only'],
                ],

                '/v1/transactions' => [
                    'get'  => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3', 'view_only'],
                ],

                '/v1/contacts' => [
                    'post' => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3', 'operations'],
                    'get'  => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3', 'operations', 'view_only'],
                ],

                '/v1/contacts/{id}' => [
                    'patch' => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3', 'operations'],
                    'get'  => [ 'owner', 'admin', 'finance_l1', 'finance_l2', 'finance_l3', 'operations', 'view_only'],
                ],
        ];
    }


    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->config = $app['config']->get('applications.authzXPlatformEnforcer');

        $this->trace = $app['trace'];

        $this->baseUrl = $this->config['url'];
    }

    public function enforcerAPIEnforce (V1EnforceRequest $body)
    {
        $response = new V1EnforceResponse;

        $response->setIsAllowed(true);

        return $response;

       $resource = $body->getResource();
       $action   = $body->getAction();
       $userRole = $body->getClaims()->getRoles()[0];

       $response = new V1EnforceResponse;
       $response->setIsAllowed(false);

       $resourceMap = self::getResourceRoleAccessMap()[$resource] ?? [];

       if (empty($resourceMap) === true)
       {
           return $response;
       }

       $resourceActionMap = $resourceMap[$action] ?? [];

        if (empty($resourceActionMap) === true)
        {
            return $response;
        }

        if (array_search($userRole, $resourceActionMap) === false)
        {
            return $response;
        }

        $response->setIsAllowed(true);
        return $response;
    }

    private static function getResourceRoleAccessMap()
    {
        if (empty(self::$resourceRoleAccessMap) === true)
        {
            self::init();
        }

        return self::$resourceRoleAccessMap;
    }
}
