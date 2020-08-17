<?php

namespace RZP\Models\Merchant\BvsValidation;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::VALIDATION_ID,
        Entity::ARTEFACT_TYPE,
        Entity::OWNER_ID,
        Entity::OWNER_TYPE,
        Entity::ERROR_DESCRIPTION,
        Entity::ERROR_CODE,
        Entity::PLATFORM,
        Entity::STATUS,
    ];

    protected static $editRules = [
        Entity::VALIDATION_ID,
        Entity::ARTEFACT_TYPE,
        Entity::OWNER_ID,
        Entity::OWNER_TYPE,
        Entity::ERROR_DESCRIPTION,
        Entity::ERROR_CODE,
        Entity::PLATFORM,
        Entity::STATUS,
    ];
}
