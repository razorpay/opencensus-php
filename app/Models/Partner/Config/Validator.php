<?php

namespace RZP\Models\Partner\Config;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Constants::PARTNER_ID          => 'required_without:'.Constants::APPLICATION_ID.'|alpha_num|size:14',
        Constants::APPLICATION_ID      => 'required_without:'.Constants::PARTNER_ID.'|alpha_num|size:14',
        Constants::SUBMERCHANT_ID      => 'filled|alpha_num|size:14',
        Entity::REVISIT_AT             => 'sometimes|integer',
        Entity::DEFAULT_PLAN_ID        => 'required|alpha_num|size:14',
        Entity::IMPLICIT_PLAN_ID       => 'filled|alpha_num|size:14',
        Entity::EXPLICIT_PLAN_ID       => 'filled|alpha_num|size:14',
        Entity::IMPLICIT_EXPIRY_AT     => 'sometimes|integer',
        Entity::COMMISSIONS_ENABLED    => 'required|boolean',
        Entity::EXPLICIT_REFUND_FEES   => 'required_with:'.Entity::EXPLICIT_PLAN_ID.'|boolean',
        Entity::EXPLICIT_SHOULD_CHARGE => 'required_with:'.Entity::EXPLICIT_PLAN_ID.'|boolean',
    ];

    protected static $editRules = [
        Entity::REVISIT_AT             => 'sometimes|integer',
        Entity::DEFAULT_PLAN_ID        => 'sometimes|alpha_num|size:14',
        Entity::IMPLICIT_PLAN_ID       => 'sometimes|alpha_num|size:14|nullable',
        Entity::EXPLICIT_PLAN_ID       => 'sometimes|alpha_num|size:14|nullable',
        Entity::IMPLICIT_EXPIRY_AT     => 'sometimes|integer|nullable',
        Entity::COMMISSIONS_ENABLED    => 'sometimes|boolean',
        Entity::EXPLICIT_REFUND_FEES   => 'sometimes|boolean',
        Entity::EXPLICIT_SHOULD_CHARGE => 'sometimes|boolean',
    ];

    public function validateEmptyConfig($config)
    {
        if (empty($config) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_APPLICATION_SUBMERCHANT_CONFIG_EXISTS,
                null, [
                    Entity::ENTITY_ID => $config->{Entity::ENTITY_ID},
                    Entity::ORIGIN_ID => $config->{Entity::ORIGIN_ID},
                    Entity::ID        => $config->{Entity::ID},
                ]);
        }
    }
}
