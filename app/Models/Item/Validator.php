<?php

namespace RZP\Models\Item;

use RZP\Base;
use RZP\Models\Invoice;
use RZP\Error\ErrorCode;
use RZP\Models\Base as BaseModel;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const TAX_INPUTS = 'tax_inputs';

    protected static $createRules = [
        Entity::NAME                => 'required|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|nullable|string|max:2048',
        Entity::AMOUNT              => 'required_without:unit_amount|mysql_unsigned_int|min:100',
        Entity::UNIT_AMOUNT         => 'required_without:amount|mysql_unsigned_int|min:100',
        Entity::CURRENCY            => 'filled|size:3|in:INR',
        Entity::TYPE                => 'filled|string|max:16|custom',
        Entity::UNIT                => 'filled|string|max:512',
        Entity::TAX_INCLUSIVE       => 'filled|boolean',
        Entity::TAX_ID              => 'sometimes|nullable|public_id|size:18',
        Entity::TAX_GROUP_ID        => 'sometimes|nullable|public_id|size:19',
    ];

    protected static $editRules  = [
        Entity::ACTIVE              => 'filled|boolean',
        Entity::NAME                => 'filled|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|nullable|string|max:2048',
        Entity::AMOUNT              => 'filled|mysql_unsigned_int|min:100',
        Entity::UNIT_AMOUNT         => 'filled|mysql_unsigned_int|min:100',
        Entity::CURRENCY            => 'filled|size:3|in:INR',
        Entity::UNIT                => 'sometimes|nullable|string|max:512',
        Entity::TAX_INCLUSIVE       => 'filled|boolean',
        Entity::TAX_ID              => 'sometimes|nullable|public_id|size:18',
        Entity::TAX_GROUP_ID        => 'sometimes|nullable|public_id|size:19',
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

    /**
     * Validates inputs when either(or both) of tax_id, tax_group_id is sent.
     * It ensures that an item is only getting associated either a tax_id or a
     * tax_group_id.
     *
     * @param array $input
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateTaxInputs(array $input)
    {
        $taxId = array_key_exists(Entity::TAX_ID, $input) ?
                    $input[Entity::TAX_ID] : $this->entity->getTaxId();

        $taxGroupId = array_key_exists(Entity::TAX_GROUP_ID, $input) ?
                        $input[Entity::TAX_GROUP_ID] : $this->entity->getTaxGroupId();

        if ((empty($taxId) === false) and (empty($taxGroupId) === false))
        {
            throw new BadRequestValidationFailureException(
                'Both tax_id and tax_group_id cannot be present');
        }
    }

    public function validateUpdateOperation(Entity $item)
    {
        if ($item->isNotOfType(Type::INVOICE) === true)
        {
            $type = $item->getType();

            throw new BadRequestValidationFailureException(
                "Update operation not allowed for item of type: $type",
                null,
                [
                    Entity::ID   => $item->getId(),
                    Entity::TYPE => $item->getType(),
                ]);
        }
    }

    public function validateDeleteOperation(Entity $item)
    {
        if ($item->isNotOfType(Type::INVOICE) === true)
        {
            $type = $item->getType();

            throw new BadRequestValidationFailureException(
                "Delete operation not allowed for item of type: $type",
                null,
                [
                    Entity::ID   => $item->getId(),
                    Entity::TYPE => $item->getType(),
                ]);
        }

        if ($item->lineItems()->count() > 0)
        {
            throw new BadRequestValidationFailureException(
                'Cannot edit/delete an item with which invoices have been created already',
                null,
                [
                    Entity::ID   => $item->getId(),
                    Entity::TYPE => $item->getType(),
                ]);
        }
    }

    public function validateItemIsActive()
    {
        $item = $this->entity;

        if ($item->isNotActive() === true)
        {
            $traceData = [
                Entity::ID     => $item->getId(),
                Entity::ACTIVE => $item->isActive(),
            ];

            throw new BadRequestException(ErrorCode::BAD_REQUEST_ITEM_INACTIVE, null, $traceData);
        }
    }

    public function validateItemIsOfType(string $type)
    {
        $item = $this->entity;

        if ($item->isNotOfType($type) === true)
        {
            $traceData = [
                Entity::ENTITY => $item->getEntity(),
                Entity::ID     => $item->getId(),
                Entity::TYPE   => $item->getType(),
            ];

            throw new BadRequestException(ErrorCode::BAD_REQUEST_INCOMPATIBLE_ITEM_TYPE, null, $traceData);
        }
    }
}
