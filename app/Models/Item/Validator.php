<?php

namespace RZP\Models\Item;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ACTIVE              => 'sometimes|boolean',
        Entity::NAME                => 'required|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'required|integer|min:100|max:50000000',
        Entity::CURRENCY            => 'required|size:3|in:INR',
    ];

    protected static $editRules  = [
        Entity::ACTIVE              => 'sometimes|boolean',
        Entity::NAME                => 'sometimes|string',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'sometimes|integer',
        Entity::CURRENCY            => 'sometimes|size:3|in:INR',
    ];

    /**
     * Allows edits if:
     * - Only attempting to change ACTIVE attribute
     * - Editing fields when there is no invoice already generated using this item
     */
    public function validateEditAllowed(Entity $item, array $input = array())
    {
        if (isset($input[Entity::ACTIVE]) and count($input) === 1)
        {
            return;
        }

        if ($item->lineItems()->count() > 0)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ITEM_EDIT_NOT_ALLOWED);
        }
    }
}
