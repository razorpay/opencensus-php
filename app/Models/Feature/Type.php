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

    // Application ids
    const TEST_APP_ID   = '10000TestAppId';
    const JUSPAY_APP_ID = '100RandomAppId';

    const S2S_APPLICATION_IDS = [
        self::TEST_APP_ID,
        self::JUSPAY_APP_ID,
    ];

    /**
     * Maps the route endpoints to the corresponding Entity type
     *
     * @var array
     */
    protected static $routeToEntityTypeMap = [
        self::ACCOUNTS     => Constants::MERCHANT,
        self::APPLICATIONS => Constants::APPLICATION,
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