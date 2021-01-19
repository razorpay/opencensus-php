<?php

namespace RZP\Models\Survey;

use RZP\Base;

class Validator extends Base\Validator
{
    const BEFORE_CREATE = 'before_create';

    protected static $beforeCreateRules = [
        Entity::NAME                => 'required|string|max:255',
        Entity::DESCRIPTION         => 'required|string|max:255',
        Entity::SURVEY_TTL          => 'required|integer',
    ];

    protected static $createRules = [
        Entity::NAME                => 'required|string|max:255',
        Entity::DESCRIPTION         => 'required|string|max:255',
        Entity::SURVEY_TTL          => 'required|integer',
    ];
}
