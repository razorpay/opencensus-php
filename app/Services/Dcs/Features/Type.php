<?php

namespace RZP\Services\Dcs\Features;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Feature\Constants;

/**
 * Class Type
 *
 * This class defines the constants and functions required to map the features with other entity types.
 *
 * @package RZP\Services\DCS\Features
 */
class Type
{
    // Entity routes
    const ACCOUNT      = 'account';
    const MERCHANT     = 'merchant';
    const APPLICATION  = 'application';
    const ORG            = 'org';
    const PARTNER_APPLICATION  = 'partner_application';
    const PARTNER  = 'partner';

    /**
     * Maps the route endpoints to the corresponding Entity type
     *
     * @var array
     */
    protected static $dcsTypeToApiEntityTypeMap = [
        self::MERCHANT     => Constants::MERCHANT,
        self::APPLICATION => Constants::APPLICATION,
        self::ORG          => Constants::ORG,
        self::PARTNER_APPLICATION => Constants::PARTNER_APPLICATION,
        self::PARTNER => Constants::MERCHANT,
    ];

    /**
     * Extracts entity type from route endpoint.
     * @param string $dcsType
     * @return string
     * @throws Exception\BadRequestException
     */
    public static function getAPIEntityTypeFromDCSType(string $dcsType): string
    {
        if (array_key_exists($dcsType, self::$dcsTypeToApiEntityTypeMap) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::SERVER_ERROR_DCS_SERVICE_FAILURE,
                ['dcs_type' => $dcsType], null,
                'not a valid enityt type,please check entityType in $dcsTypeToApiEntityTypeMap');
        }

        return self::$dcsTypeToApiEntityTypeMap[$dcsType];
    }
}
