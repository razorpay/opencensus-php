<?php

namespace RZP\Models\LineItem;

use RZP\Base;
use RZP\Models\Item;
use RZP\Models\Invoice;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::QUANTITY            => 'sometimes|integer|min:1',
        Entity::ITEM_ID             => 'sometimes|string|max:19',
        Entity::REF                 => 'sometimes',
        Entity::NAME                => 'required_without:item_id|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|nullable|string|max:2048',
        Entity::AMOUNT              => 'required_without:item_id|integer|min:100',
        Entity::UNIT_AMOUNT         => 'required_without_all:amount,item_id|integer|min:100',
        Entity::CURRENCY            => 'required_without:item_id|size:3|in:INR|custom',
        Entity::UNIT                => 'sometimes|nullable|string|max:512',
        Entity::TYPE                => 'filled|string|max:16|custom',
        Entity::TAX_INCLUSIVE       => 'sometimes|boolean',
        Entity::TAX_ID              => 'sometimes|nullable|public_id|size:18',
        Entity::TAX_GROUP_ID        => 'sometimes|nullable|public_id|size:19',
    ];

    protected static $createManyRules = [
        Entity::LINE_ITEMS          => 'required|array|min:1|max:10',
        Entity::LINE_ITEMS . '.*'   => 'required|array',
    ];

    protected static $editRules = [
        Entity::QUANTITY            => 'sometimes|nullable|integer|min:1',
        Entity::ITEM_ID             => 'sometimes|nullable|string|max:19',
        Entity::NAME                => 'sometimes|nullable|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|nullable|string|max:2048',
        Entity::AMOUNT              => 'sometimes|nullable|integer|min:100',
        Entity::UNIT_AMOUNT         => 'sometimes|nullable|integer|min:100',
        Entity::CURRENCY            => 'sometimes|nullable|size:3|in:INR|custom',
        Entity::TYPE                => 'filled|string|max:16|custom',
        Entity::UNIT                => 'sometimes|nullable|string|max:512',
        Entity::TAX_INCLUSIVE       => 'sometimes|nullable|boolean',
        Entity::TAX_ID              => 'sometimes|nullable|public_id|size:18',
        Entity::TAX_GROUP_ID        => 'sometimes|nullable|public_id|size:19',
    ];

    protected static $removeManyRules = [
        Entity::IDS                 => 'required|array|min:1|max:10',
    ];

    public function validateType($attribute, $value)
    {
        Item\Type::checkType($value);

        $lineItem    = $this->entity;
        $morphEntity = $lineItem->entity;

        if ($morphEntity instanceof Invoice\Entity === false)
        {
            return;
        }

        $allowed = $morphEntity->getAllowedLineItemTypes();

        if (in_array($value, $allowed, true) === false)
        {
            $traceData = [
                Entity::ENTITY    => $lineItem->getEntity(),
                Entity::ID        => $lineItem->getId(),
                Entity::TYPE      => $value,
                Entity::ENTITY_ID => $morphEntity->getId(),
            ];

            throw new BadRequestException(ErrorCode::BAD_REQUEST_INCOMPATIBLE_ITEM_TYPE, null, $traceData);
        }
    }

    public function validateCurrency($attribute, $value)
    {
        $lineItem    = $this->entity;
        $morphEntity = $lineItem->entity;

        if ($morphEntity instanceof Invoice\Entity === false)
        {
            return;
        }

        $morphEntityCurrency = $morphEntity->getCurrency();

        if ($value !== $morphEntityCurrency)
        {
            $entity = $morphEntity->getEntity();

            $traceData = [
                Entity::ENTITY    => $entity,
                Entity::ID        => $lineItem->getId(),
                Entity::TYPE      => $value,
                Entity::ENTITY_ID => $morphEntity->getId(),
            ];

            throw new BadRequestValidationFailureException(
                "Currency of all items should be the same as of the $entity.",
                Entity::CURRENCY,
                $traceData);
        }
    }
}
