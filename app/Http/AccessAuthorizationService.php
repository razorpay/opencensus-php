<?php

namespace RZP\Http;

use Monolog\Logger;
use RZP\Trace\TraceCode;
use RZP\Models\AuthzEnforcer;

class AccessAuthorizationService
{
    private static $routeNameResourceAndActionMap;

    const RAZORPAYX_SYSTEM_SUBJECT = 'razorpayx';
    const RAZORPAYX_ORG_NAME = 'razorpayx';

    const AUTHZ_ACTION = 'post';

    private static function init()
    {
        self::$routeNameResourceAndActionMap = [
            'payout_create'                         => ['/v1/payouts', 'post'],
            'payout_create_with_otp'                => ['/v1/payouts_with_otp', 'post'],
            'payouts_batch_create'                  => ['/v1/payouts_batch', 'post'],
            'payout_validate'                       => ['/v1/validate_payouts', 'post'],
            'payout_approve'                        => ['/v1/payouts/{id}/approve', 'post'],
            'payout_2fa_approve'                    => ['/v1/payouts/approve/2fa', 'post'],
            'payout_cancel'                         => ['/v1/payouts/{id}/cancel', 'post'],
            'payout_reject'                         => ['/v1/payouts/{id}/reject', 'post'],
            'payout_bulk_create'                    => ['/v1/payouts/bulk', 'post'],
            'payout_bulk_approve'                   => ['/v1/payouts/bulk_approve', 'post'],
            'payout_fetch_by_id'                    => ['/v1/payouts_internal/{id}', 'get'],
            'payout_fetch_multiple'                 => ['/v1/payouts', 'get'],
            'transaction_statement_fetch'           => ['/v1/transactions/{id}', 'get'],
            'transaction_statement_fetch_multiple'  => ['/v1/transactions', 'get'],
            'fund_account_create'                   => ['/v1/fund_accounts', 'post'],
            'fund_account_get'                      => ['/v1/fund_accounts/{id}', 'get'],
            'fund_account_list'                     => ['/v1/fund_accounts', 'get'],
            'contact_create'                        => ['/v1/contacts', 'post'],
            'contact_update'                        => ['/v1/contacts/{id}', 'patch'],
            'contact_get'                           => ['/v1/contacts/{id}', 'get'],
            'contact_list'                          => ['/v1/contacts', 'get']
        ];
    }

    private static function getRouteNameResourceAndActionMap()
    {
        if (empty(self::$routeNameResourceAndActionMap) === true)
        {
            self::init();
        }

        return self::$routeNameResourceAndActionMap;
    }

    public static function hasAccessAllowedV2(string $permission,
                                            array $role,
                                            string $subject = self:: RAZORPAYX_SYSTEM_SUBJECT,
                                            string $org = self::RAZORPAYX_ORG_NAME) : bool
    {
        try
        {
            $resourceAndAction = [$permission, self::AUTHZ_ACTION];

            $response = (new AuthzEnforcer\Service())->enforcerAPIEnforce($resourceAndAction, $role, $subject, $org);

            return $response->getIsAllowed();
        }
        catch (\Exception $exception)
        {
            app('trace')->traceException($exception, Logger::ERROR, TraceCode::AUTHZ_ENFORCER_API_FAILED,
                [
                    'permission' => $permission,
                    'role' => $role,
                    'subject' => $subject
                ]);
        }
    }

    public static function hasAccessAllowed(string $routeName,
                                                    array $role,
                                                    string $subject = self:: RAZORPAYX_SYSTEM_SUBJECT,
                                                    string $org = self::RAZORPAYX_ORG_NAME) : bool
    {
        try
        {
            $resourceAndAction = self::getResourceAndActionForRouteName($routeName);

            $response = (new AuthzEnforcer\Service())->enforcerAPIEnforce($resourceAndAction, $role, $subject, $org);

            return $response->getIsAllowed();
        }
        catch (\Exception $exception)
        {
            app('trace')->traceException($exception, Logger::ERROR, TraceCode::AUTHZ_ENFORCER_API_FAILED,
                [
                    'route_name' => $routeName,
                    'role' => $role,
                    'subject' => $subject
                ]);
        }
    }

    public static function isAuthorizationEnabled($route): bool
    {
        $resourceAction = self::getResourceAndActionForRouteName($route);

        if (empty($resourceAction) === true)
        {
            return false;
        }

        return true;
    }

    public static function getResourceAndActionForRouteName(string $routeName)
    {
        return self::getRouteNameResourceAndActionMap()[$routeName] ?? [];
    }
}
