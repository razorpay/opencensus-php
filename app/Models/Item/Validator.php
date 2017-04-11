<?php

namespace RZP\Models\Item;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME                => 'required|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'required|integer|min:100|max:50000000',
        Entity::CURRENCY            => 'required|size:3|in:INR',
        Entity::TYPE                => 'required|string|max:16|custom',
    ];

    protected static $editRules  = [
        Entity::ACTIVE              => 'sometimes|boolean',
        Entity::NAME                => 'sometimes|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'sometimes|integer|min:100|max:50000000',
        Entity::CURRENCY            => 'sometimes|size:3|in:INR',
    ];

    public function validateType($attribute, $value)
    {
        Type::checkType($value);
    }

    public function validateDeleteOperation(Entity $item)
    {
        if ($item->lineItems()->count() > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ITEM_OPERATION_NOT_ALLOWED,
                null,
                [
                    'item_id' => $item->getId(),
                ]);
        }
    }
}
