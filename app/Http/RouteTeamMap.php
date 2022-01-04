<?php

namespace App\Http;

class RouteTeamMap
{

    /**
     * Follows the notation <BU>_<TeamName>
     */
    const TEAM_PAYMENTS_DASHBOARD = 'payments_dashboard';
    const TEAM_PAYMENTS_GROWTH    = 'payments_growth';
    const TEAM_UNKNOWN            = 'unknown_unknown';

    /**
     * @param $route
     *
     * @return string comma separated list of team names for the particular route
     * used primarily for identifying a the team/pod which owns a particular route.
     */
    public static function getTeamNamesForRoute($route)
    {
        if (array_key_exists($route, self::$routeTeamMap) === false)
        {
            return self::TEAM_UNKNOWN;
        }

        return implode(',', self::$routeTeamMap[$route]);
    }

    protected static $routeTeamMap = [
        'status'                => [self::TEAM_PAYMENTS_DASHBOARD],
        'user'                  => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant'              => [self::TEAM_PAYMENTS_DASHBOARD],
        'extension_user_logout' => [self::TEAM_PAYMENTS_DASHBOARD],
        'get_org'               => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_keep_alive'       => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_logout'           => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_details'          => [self::TEAM_PAYMENTS_DASHBOARD],
        'get_user_details'      => [self::TEAM_PAYMENTS_DASHBOARD],
        'remove_user'           => [self::TEAM_PAYMENTS_DASHBOARD],
        'tnc'                   => [self::TEAM_PAYMENTS_GROWTH],
        'user_pre_signup'       => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_details'      => [self::TEAM_PAYMENTS_GROWTH],
        'get_keys'              => [self::TEAM_PAYMENTS_GROWTH],
        'keys_setup'            => [self::TEAM_PAYMENTS_GROWTH],
        'post_activation'       => [self::TEAM_PAYMENTS_GROWTH],
    ];
}
