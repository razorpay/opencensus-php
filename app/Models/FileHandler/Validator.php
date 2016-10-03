<?php

namespace RZP\Models\FileHandler;

use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::FORMAT                => 'required|string',
        Entity::SIZE                  => 'required|',
        Entity::ENCRYPTION_METHOD     => 'required|string',
        Entity::LOCATION              => 'required|url',
        Entity::SERVICE               => 'required|in:s3',
        Entity::BUCKET                => 'sometimes|',
        Entity::NAME                  => 'required|',
        Entity::PASSWORD              => 'sometimes|string',
        Entity::ENTITY_NAME           => 'sometimes|string',
        Entity::ENTITY_ID             => 'sometimes|string',
        Entity::MERCHANT_ID           => 'sometimes|string',
        Entity::PERMISSION            => 'sometimes',
        Entity::METADATA              => 'sometimes',
        Entity::COMMENTS              => 'sometimes',
        Entity::DOCUMENT_TYPE         => 'sometimes',
    ];
}
