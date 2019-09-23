<?php

namespace RZP\Models\Merchant\Document;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::DOCUMENT_TYPE => 'required|string|max:255',
        Entity::FILE          => 'required|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::FILE_STORE_ID => 'sometimes|string|max:14',
    ];

    protected static $editRules = [
        Entity::DOCUMENT_TYPE => 'required|string|max:255',
        Entity::FILE          => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::FILE_STORE_ID => 'sometimes|string|max:14',
    ];
}
