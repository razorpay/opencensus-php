<?php

namespace RZP\Models\LineItem;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::QUANTITY            => 'sometimes|integer|min:1',
        Entity::ITEM_ID             => 'sometimes|string|max:19',
        Entity::REF                 => 'sometimes',
        Entity::NAME                => 'required_without:item_id|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'required_without:item_id|integer|min:100',
        Entity::UNIT_AMOUNT         => 'required_without_all:amount,item_id|integer|min:100',
        Entity::CURRENCY            => 'required_without:item_id|size:3|in:INR',
        Entity::UNIT                => 'sometimes|string|max:512',
        Entity::TAX_INCLUSIVE       => 'sometimes|boolean',
        Entity::TAX_ID              => 'sometimes|public_id|size:18',
        Entity::TAX_GROUP_ID        => 'sometimes|public_id|size:19',
    ];

    protected static $createManyRules = [
        Entity::LINE_ITEMS          => 'required|array|min:1|max:10',
        Entity::LINE_ITEMS . '.*'   => 'required|array',
    ];

    protected static $editRules = [
        Entity::QUANTITY            => 'sometimes|integer|min:1',
        Entity::ITEM_ID             => 'sometimes|string|max:19',
        Entity::NAME                => 'sometimes|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'sometimes|integer|min:100',
        Entity::UNIT_AMOUNT         => 'sometimes|integer|min:100',
        Entity::CURRENCY            => 'sometimes|size:3|in:INR',
        Entity::UNIT                => 'sometimes|string|max:512',
        Entity::TAX_INCLUSIVE       => 'sometimes|boolean',
        Entity::TAX_ID              => 'sometimes|public_id|size:18',
        Entity::TAX_GROUP_ID        => 'sometimes|public_id|size:19',
    ];

    protected static $removeManyRules = [
        Entity::IDS                 => 'required|array|min:1|max:10',
    ];

    public function validateCurrency(string $expectedCurrency)
    {
        $currency = $this->entity->getCurrency();

        if ($currency !== $expectedCurrency)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Currency of all items should be the same as of the invoice.');
        }
    }
}
