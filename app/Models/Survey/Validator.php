<?php

namespace RZP\Models\Survey;

use RZP\Base;

class Validator extends Base\Validator
{
    const BEFORE_CREATE = 'before_create';

    const BEFORE_EDIT   = 'before_edit';

    protected static $beforeCreateRules = [
        Entity::NAME                => 'required|string|max:255',
        Entity::DESCRIPTION         => 'required|string|max:255',
        Entity::SURVEY_TTL          => 'required|integer',
        Entity::SURVEY_URL          => 'required|url',
        Entity::TYPE                => 'required|string',
        Entity::CHANNEL             => 'required|integer',
    ];

    protected static $beforeEditRules = [
        Entity::NAME                => 'sometimes|string|max:255',
        Entity::DESCRIPTION         => 'sometimes|string|max:255',
        Entity::SURVEY_TTL          => 'sometimes|integer',
        Entity::SURVEY_URL          => 'sometimes|string',
        Entity::TYPE                => 'sometimes|string',
        Entity::CHANNEL             => 'sometimes|integer',
    ];

    protected static $createRules = [
        Entity::NAME                => 'required|string|max:255',
        Entity::DESCRIPTION         => 'required|string|max:255',
        Entity::SURVEY_TTL          => 'required|integer',
        Entity::SURVEY_URL          => 'required|string',
        Entity::TYPE                => 'required|string',
        Entity::CHANNEL             => 'required|integer',
    ];

    protected static $editRules = [
        Entity::NAME                => 'sometimes|string|max:255',
        Entity::DESCRIPTION         => 'sometimes|string|max:255',
        Entity::SURVEY_TTL          => 'sometimes|integer',
        Entity::SURVEY_URL          => 'sometimes|string',
        Entity::TYPE                => 'sometimes|string',
        Entity::CHANNEL             => 'sometimes|integer',
    ];
}
