<?php

namespace RZP\Models\Item;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    const TAX_INPUTS = 'tax_inputs';

    protected static $createRules = [
        Entity::NAME                => 'required|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'required|integer|min:100',
        Entity::CURRENCY            => 'required|size:3|in:INR',
        Entity::TYPE                => 'sometimes|string|max:16|custom',
        Entity::UNIT                => 'sometimes|string|max:512',
        Entity::TAX_INCLUSIVE       => 'sometimes|boolean',
        Entity::TAX_ID              => 'sometimes|public_id|size:18',
        Entity::TAX_GROUP_ID        => 'sometimes|public_id|size:19',
    ];

    protected static $editRules  = [
        Entity::ACTIVE              => 'sometimes|boolean',
        Entity::NAME                => 'sometimes|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'sometimes|integer|min:100',
        Entity::CURRENCY            => 'sometimes|size:3|in:INR',
        Entity::UNIT                => 'sometimes|string|max:512',
        Entity::TAX_INCLUSIVE       => 'sometimes|boolean',
        Entity::TAX_ID              => 'sometimes|public_id|size:18',
        Entity::TAX_GROUP_ID        => 'sometimes|public_id|size:19',
    ];

    protected static $createValidators = [
        self::TAX_INPUTS,
    ];

    protected static $editValidators = [
        self::TAX_INPUTS,
    ];

    public function validateType($attribute, $value)
    {
        Type::checkType($value);
    }

    public function validateTaxInputs(array $input)
    {
        $taxId = array_key_exists(Entity::TAX_ID, $input) ?
                    $input[Entity::TAX_ID] : $this->entity->getTaxId();

        $taxGroupId = array_key_exists(Entity::TAX_GROUP_ID, $input) ?
                        $input[Entity::TAX_GROUP_ID] : $this->entity->getTaxGroupId();

        if ((empty($taxId) === false) and (empty($taxGroupId) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Both tax_id and tax_group_id cannot be present');
        }
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
