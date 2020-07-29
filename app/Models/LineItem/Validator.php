<?php

namespace RZP\Models\LineItem;

use RZP\Base;
use RZP\Models\Item;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Validator
 *
 * @package RZP\Models\LineItem
 *
 * @property Entity     $entity
 */
class Validator extends Base\Validator
{
    const TAX_CODES  = 'tax_codes';
    const TAX_INPUTS = 'tax_inputs';

    const TAX_ATTRIBUTES = [
        Entity::HSN_CODE,
        Entity::SAC_CODE,
        Entity::TAX_RATE,
        Entity::TAX_ID,
        Entity::TAX_IDS,
        Entity::TAX_GROUP_ID
    ];

    protected static $createRules = [
        Entity::QUANTITY            => 'filled|integer|min:1',
        Entity::ITEM_ID             => 'sometimes|nullable|string|max:19',
        Entity::REF                 => 'sometimes',
        Entity::NAME                => 'required_without:item_id|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|nullable|string|max:2048',
        Entity::AMOUNT              => 'required_without:item_id|integer|min_amount',
        Entity::UNIT_AMOUNT         => 'required_without_all:amount,item_id|integer|min_amount',
        Entity::CURRENCY            => 'required_without:item_id|currency|custom',
        Entity::UNIT                => 'sometimes|nullable|string|max:512',
        Entity::TYPE                => 'filled|string|max:16|custom',
        Entity::TAX_INCLUSIVE       => 'filled|boolean',
        Entity::HSN_CODE            => 'sometimes|nullable|string|max:8',
        Entity::SAC_CODE            => 'sometimes|nullable|string|max:8',
        Entity::TAX_RATE            => 'sometimes|nullable|int_percentage',
        Entity::TAX_ID              => 'sometimes|nullable|public_id|size:18',
        Entity::TAX_IDS             => 'sometimes|nullable|array|max:10',
        Entity::TAX_IDS . '.*'      => 'filled|public_id|size:18',
        Entity::TAX_GROUP_ID        => 'sometimes|nullable|public_id|size:19',
    ];

    protected static $createManyRules = [
        Entity::LINE_ITEMS          => 'required|array|min:1|max:50',
        Entity::LINE_ITEMS . '.*'   => 'required|array',
    ];

    protected static $editRules = [
        Entity::QUANTITY            => 'sometimes|nullable|integer|min:1',
        Entity::ITEM_ID             => 'sometimes|nullable|string|max:19',
        Entity::NAME                => 'filled|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|nullable|string|max:2048',
        Entity::AMOUNT              => 'filled|integer',
        Entity::UNIT_AMOUNT         => 'filled|integer',
        Entity::CURRENCY            => 'sometimes|nullable|currency|custom',
        Entity::TYPE                => 'filled|string|max:16|custom',
        Entity::UNIT                => 'sometimes|nullable|string|max:512',
        Entity::TAX_INCLUSIVE       => 'sometimes|nullable|boolean',
        Entity::HSN_CODE            => 'sometimes|nullable|string|max:8',
        Entity::SAC_CODE            => 'sometimes|nullable|string|max:8',
        Entity::TAX_RATE            => 'sometimes|nullable|int_percentage',
        Entity::TAX_ID              => 'sometimes|nullable|public_id|size:18',
        Entity::TAX_IDS             => 'sometimes|nullable|array|max:10',
        Entity::TAX_IDS . '.*'      => 'filled|public_id|size:18',
        Entity::TAX_GROUP_ID        => 'sometimes|nullable|public_id|size:19',
    ];

    protected static $removeManyRules = [
        Entity::IDS                 => 'required|array|min:1|max:10',
    ];

    protected static $minAmountCheckRules = [
        Entity::AMOUNT => 'required|integer|min_amount'
    ];

    protected static $createValidators = [
        self::TAX_CODES,
        self::TAX_INPUTS,
    ];

    protected static $editValidators = [
        self::TAX_CODES,
        self::TAX_INPUTS,
        'min_amount',
    ];

    public function validateType($attribute, $type)
    {
        Item\Type::checkType($type);

        $lineItem        = $this->entity;
        $morphEntity     = $lineItem->entity;
        $morphEntityId   = $morphEntity->getId();
        $morphEntityType = $morphEntity->getEntity();

        $traceData = [
            Entity::ID          => $lineItem->getId(),
            Entity::TYPE        => $type,
            Entity::ENTITY_ID   => $morphEntityId,
            Entity::ENTITY_TYPE => $morphEntityType,
        ];

        if (method_exists($morphEntity, 'getAllowedLineItemTypes') === false)
        {
            throw new LogicException('Not implemented: getAllowedLineItemTypes', null, $traceData);
        }

        $allowed = $morphEntity->getAllowedLineItemTypes();

        if (in_array($type, $allowed, true) === false)
        {
            throw new BadRequestValidationFailureException(
                "{$morphEntityType} can only use item of one of following types: " . implode(', ', $allowed),
                Entity::TYPE,
                $traceData);
        }
    }

    public function validateMinAmount(array $input)
    {

        if ((empty($input[Entity::AMOUNT]) === false) or (empty($input[Entity::UNIT_AMOUNT]) === false))
        {
            $input[Entity::AMOUNT] = $input[Entity::AMOUNT] ?? $input[Entity::UNIT_AMOUNT];

            $currency = $this->entity->getCurrency();

            $inputAmount = [
                Entity::AMOUNT   => $input[Entity::AMOUNT],
                Entity::CURRENCY => $currency,
            ];

            $this->validateInputValues('min_amount_check', $inputAmount);
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

    /**
     * Validates that only one of tax_id, tax_ids or tax_group_id is sent.
     *
     * @param array $input
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateTaxInputs(array $input)
    {
        $taxId      = $input[Entity::TAX_ID] ?? null;
        $taxIds     = $input[Entity::TAX_IDS] ?? null;
        $taxGroupId = $input[Entity::TAX_GROUP_ID] ?? null;

        if (count(array_filter([$taxId, $taxIds, $taxGroupId])) > 1)
        {
            throw new BadRequestValidationFailureException(
                'Only one among tax_id, tax_ids or tax_group_id can be present');
        }
    }
}
