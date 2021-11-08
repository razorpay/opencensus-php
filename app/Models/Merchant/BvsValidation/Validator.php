<?php

namespace RZP\Models\Merchant\BvsValidation;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::VALIDATION_ID     => 'required|string|max:14',
        Entity::ARTEFACT_TYPE     => 'required|string|max:255',//todo validate Artefact type -> add custom validator
        Entity::OWNER_ID          => 'required|string|max:14',
        Entity::OWNER_TYPE        => 'required|string|in:merchant,banking_account,bas_document',
        Entity::PLATFORM          => 'required|string|in:pg,capital,rx',
        Entity::VALIDATION_STATUS => 'required|string|in:success,failed,captured',
        Entity::VALIDATION_UNIT   => 'required|string|in:identifier,proof',
        Entity::ERROR_DESCRIPTION => 'sometimes|string|max:255',
        Entity::ERROR_CODE        => 'sometimes|string|max:255',
    ];

    protected static $editRules = [
        Entity::VALIDATION_ID           => 'required|string|max:14',
        Entity::ARTEFACT_TYPE           => 'sometimes|string|max:255',//todo validate Artefact type
        Entity::OWNER_ID                => 'sometimes|string|max:14',
        Entity::OWNER_TYPE              => 'sometimes|string|in:merchant,banking_account,bas_document',
        Entity::PLATFORM                => 'sometimes|string|in:pg,capital,rx',
        Entity::VALIDATION_STATUS       => 'required|string|in:success,failed,captured',
        Entity::ERROR_DESCRIPTION       => 'sometimes|string|max:255',
        Entity::ERROR_CODE              => 'sometimes|string|max:255',
        Entity::RULE_EXECUTION_LIST     => 'sometimes|array',
    ];

    protected static $processKafkaMessageRules = [
        Constants::VALIDATION_ID            => 'required|string|max:14',
        Constants::STATUS                   => 'required|string|max:255',
        Constants::ERROR_CODE               => 'sometimes|string|max:255',
        Constants::ERROR_DESCRIPTION        => 'sometimes|string|max:255',
        Constants::RULE_EXECUTION_LIST      => 'sometimes|array',
    ];
}
