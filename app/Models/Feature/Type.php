<?php

namespace RZP\Models\Feature;

use RZP\Exception;

/**
 * Class Type
 *
 * This class defines the constants and functions required to map the features with other entity types.
 *
 * @package RZP\Models\Feature
 */
class Type
{
    // Entity routes
    const ACCOUNTS      = 'accounts';
    const MERCHANTS     = 'merchants';
    const APPLICATIONS  = 'applications';
    const ORG            = 'org';
    const PARTNER_APPLICATION  = 'partner_application';

    // Application ids
    const TEST_APP_ID    = '10000TestAppId';
    const JUSPAY_APP_ID  = 'A0m8HLZLyVIDQ9';
    const JUSPAY_APP_ID1 = 'D0HP2c6t4I1bLX';

    const S2S_APPLICATION_IDS = [
        self::TEST_APP_ID,
        self::JUSPAY_APP_ID,
        self::JUSPAY_APP_ID1,
    ];

    /**
     * Maps the route endpoints to the corresponding Entity type
     *
     * @var array
     */
    protected static $routeToEntityTypeMap = [
        self::ACCOUNTS     => Constants::MERCHANT,
        self::APPLICATIONS => Constants::APPLICATION,
        self::ORG          => Constants::ORG,
        self::PARTNER_APPLICATION => Constants::PARTNER_APPLICATION
    ];

    /**
     * Extracts entity type from route endpoint.
     * @param string $routeEndpoint
     *
     * @return string
     * @throws Exception\BadRequestException
     */
    public static function getEntityTypeFromRoute(string $routeEndpoint): string
    {
        if (array_key_exists($routeEndpoint, self::$routeToEntityTypeMap) === false)
        {
            throw new Exception\BadRequestException(
                'Entity type is invalid',
                ['route_endpoint' => $routeEndpoint]);
        }

        return self::$routeToEntityTypeMap[$routeEndpoint];
    }
}
