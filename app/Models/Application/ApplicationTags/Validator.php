<?php

namespace RZP\Models\Application\ApplicationTags;

use RZP\Base;

class Validator extends Base\Validator
{
    const BEFORE_CREATE_MAPPING         = 'before_create_mapping';

    const BEFORE_DELETE_TAG             = 'before_delete_tag';

    const BEFORE_DELETE_APP_MAPPING     = 'before_delete_app_mapping';

    const CREATE                = 'create';

    protected static $beforeCreateMappingRules = [
        Entity::TAG                  => 'required|string|max:255',
        Entity::LIST                 => 'required|array',
    ];

    protected static $beforeDeleteTagRules = [
        Entity::TAG                  => 'required|string|max:255',
    ];

    protected static $beforeDeleteAppMappingRules = [
        Entity::TAG                  => 'required|string|max:255',
        Entity::LIST                 => 'required|array',
    ];

    protected static $createRules = [
        Entity::TAG                  => 'required|string|max:255',
        Entity::APP_ID               => 'required|string',
    ];
}
