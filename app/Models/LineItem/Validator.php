<?php

namespace RZP\Models\LineItem;

use RZP\Base;
use RZP\Models\Item;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const TAX_CODES  = 'tax_codes';

    protected static $createRules = [
        Entity::QUANTITY            => 'filled|integer|min:1',
        Entity::ITEM_ID             => 'sometimes|nullable|string|max:19',
        Entity::REF                 => 'sometimes',
        Entity::NAME                => 'required_without:item_id|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|nullable|string|max:2048',
        Entity::AMOUNT              => 'required_without:item_id|mysql_unsigned_int|min:100',
        Entity::UNIT_AMOUNT         => 'required_without_all:amount,item_id|mysql_unsigned_int|min:100',
        Entity::CURRENCY            => 'required_without:item_id|size:3|in:INR|custom',
        Entity::UNIT                => 'sometimes|nullable|string|max:512',
        Entity::TYPE                => 'filled|string|max:16|custom',
        Entity::TAX_INCLUSIVE       => 'filled|boolean',
        Entity::HSN_CODE            => 'sometimes|nullable|string|max:8',
        Entity::SAC_CODE            => 'sometimes|nullable|string|max:8',
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
        Entity::NAME                => 'filled|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|nullable|string|max:2048',
        Entity::AMOUNT              => 'filled|mysql_unsigned_int|min:100',
        Entity::UNIT_AMOUNT         => 'filled|mysql_unsigned_int|min:100',
        Entity::CURRENCY            => 'sometimes|nullable|size:3|in:INR|custom',
        Entity::TYPE                => 'filled|string|max:16|custom',
        Entity::UNIT                => 'sometimes|nullable|string|max:512',
        Entity::TAX_INCLUSIVE       => 'sometimes|nullable|boolean',
        Entity::HSN_CODE            => 'sometimes|nullable|string|max:8',
        Entity::SAC_CODE            => 'sometimes|nullable|string|max:8',
        Entity::TAX_ID              => 'sometimes|nullable|public_id|size:18',
        Entity::TAX_GROUP_ID        => 'sometimes|nullable|public_id|size:19',
    ];

    protected static $removeManyRules = [
        Entity::IDS                 => 'required|array|min:1|max:10',
    ];

    protected static $createValidators = [
        self::TAX_CODES,
    ];

    protected static $editValidators = [
        self::TAX_CODES,
    ];

    public function validateType($attribute, $value)
    {
        Item\Type::checkType($value);

        $lineItem    = $this->entity;
        $morphEntity = $lineItem->entity;

        $traceData = [
            Entity::ID          => $lineItem->getId(),
            Entity::TYPE        => $value,
            Entity::ENTITY_ID   => $morphEntity->getId(),
            Entity::ENTITY_TYPE => $morphEntity->getEntity(),
        ];

        if (method_exists($morphEntity, 'getAllowedLineItemTypes') === false)
        {
            throw new LogicException('Not implemented: getAllowedLineItemTypes', null, $traceData);
        }

        $allowed = $morphEntity->getAllowedLineItemTypes();

        if (in_array($value, $allowed, true) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INCOMPATIBLE_ITEM_TYPE, Entity::TYPE, $traceData);
        }
    }

    public function validateCurrency($attribute, $value)
    {
        $lineItem        = $this->entity;
        $morphEntity     = $lineItem->entity;
        $morphEntityName = $morphEntity->getEntity();


        $traceData = [
            Entity::ID          => $lineItem->getId(),
            Entity::CURRENCY    => $value,
            Entity::ENTITY_ID   => $morphEntity->getId(),
            Entity::ENTITY_TYPE => $morphEntityName,
        ];

        if (method_exists($morphEntity, 'getCurrency') === false)
        {
            throw new LogicException('Not implemented: getCurrency', null, $traceData);
        }

        $morphEntityCurrency = $morphEntity->getCurrency();

        if ($value !== $morphEntityCurrency)
        {
            throw new BadRequestValidationFailureException(
                "Currency of all items should be the same as of the $morphEntityName.",
                Entity::CURRENCY,
                $traceData);
        }
    }

    /**
     * TODO: This functions exists in both Item and LineItem Validator. Make Common.
     *
     * @param array $input
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateTaxCodes(array $input)
    {
        $hsnCode = array_key_exists(Entity::HSN_CODE, $input) ?
                    $input[Entity::HSN_CODE] : $this->entity->getHsnCode();
        $sacCode = array_key_exists(Entity::SAC_CODE, $input) ?
                    $input[Entity::SAC_CODE] : $this->entity->getSacCode();

        if ((empty($hsnCode) === false) and (empty($sacCode) === false))
        {
            throw new BadRequestValidationFailureException('Both hsn_code and sac_code cannot be present');
        }
    }
}
