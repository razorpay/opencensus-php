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
        Entity::AMOUNT              => 'required_without:unit_amount|integer|min:100',
        Entity::UNIT_AMOUNT         => 'required_without:amount|integer|min:100',
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
        Entity::UNIT_AMOUNT         => 'sometimes|integer|min:100',
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
        if ($item->isNotOfType(Type::INVOICE))
        {
            $type = $item->getType();

            throw new BadRequestValidationFailureException(
                "Update operation not allowed for item of type: $type");
        }
    }

    public function validateDeleteOperation(Entity $item)
    {
        if ($item->isNotOfType(Type::INVOICE))
        {
            $type = $item->getType();

            throw new BadRequestValidationFailureException(
                "Delete operation not allowed for item of type: $type");
        }

        if ($item->lineItems()->count() > 0)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ITEM_OPERATION_NOT_ALLOWED,
                null,
                [
                    'item_id' => $item->getId(),
                ]);
        }
    }

    public function validateItemIsActive()
    {
        $item = $this->entity;

        if ($item->isNotActive())
        {
            $payload = [
                Entity::ID     => $item->getId(),
                Entity::ACTIVE => $item->isActive(),
            ];

            throw new BadRequestException(ErrorCode::BAD_REQUEST_ITEM_INACTIVE, null, $payload);
        }
    }

    public function validateItemTypeIsInAllowedList(array $allowedTypes)
    {
        $item = $this->entity;

        $isItemTypeInAllowed = in_array($item->getType(), $allowedTypes, true);

        if ($isItemTypeInAllowed === false)
        {
            $payload = [
                Entity::ID   => $item->getId(),
                Entity::TYPE => $item->getType(),
            ];

            throw new BadRequestException(ErrorCode::BAD_REQUEST_INCOMPATIBLE_ITEM_TYPE, null, $payload);
        }
    }


    public function validateItemTypeIsAllowedForEntity(Entity $item, BaseModel\PublicEntity $morphEntity)
    {
        if ($morphEntity instanceof Invoice\Entity === false)
        {
            $allowedTypes = [];
        }
        else if ($morphEntity->hasSubscription() === true)
        {
            $allowedTypes = [Type::PLAN, Type::ADDON];
        }
        else
        {
            $allowedTypes = [Type::INVOICE];
        }

        $this->validateItemTypeIsInAllowedList($allowedTypes);
    }
}
