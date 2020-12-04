<?php

namespace RZP\Models\Merchant\MerchantApplications;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        ENTITY::TYPE            => 'required|max:255',
        ENTITY::APPLICATION_ID  => 'required|size:14',
    ];

    /**
     * @param string $fromAppType
     * @param string $toAppType
     *
     * @return void
     * @throws BadRequestException
     */
    public function validateAppTypeChange(string $fromAppType, string $toAppType)
    {
        $allowedAppTypes = [Entity::REFERRED, Entity::MANAGED];

        if ((in_array($fromAppType, $allowedAppTypes) === false) or
            (in_array($toAppType, $allowedAppTypes) === false) or
            ($fromAppType === $toAppType))
        {
            throw new BadRequestException (
                ErrorCode::BAD_REQUEST_INVALID_APPLICATION_TYPE,
                [
                    'from_app_type' => $fromAppType,
                    'to_app_type'   => $toAppType,
                ]
            );
        }
    }
}
