<?php

namespace RZP\Models\Item;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Currency\Currency;
use RZP\Exception\BadRequestException;
use RZP\Exception\ExtraFieldsException;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Validator
 *
 * @package RZP\Models\Item
 *
 * @property Entity $entity
 */
class Validator extends Base\Validator
{
    const TAX_INPUTS = 'tax_inputs';
    const TAX_CODES  = 'tax_codes';

    const TAX_ATTRIBUTES = [
        Entity::TAX_INCLUSIVE,
        Entity::HSN_CODE,
        Entity::SAC_CODE,
        Entity::TAX_RATE,
        Entity::TAX_ID,
        Entity::TAX_GROUP_ID
    ];

    protected static $createRules = [
        Entity::NAME                => 'required|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|nullable|string|max:2048',
        Entity::AMOUNT              => 'sometimes|nullable|integer|min_amount',
        Entity::UNIT_AMOUNT         => 'sometimes|nullable|integer|min_amount',
        Entity::CURRENCY            => 'required|currency|custom',
        Entity::TYPE                => 'filled|string|max:16|custom',
        Entity::UNIT                => 'filled|string|max:512',
        Entity::TAX_INCLUSIVE       => 'filled|boolean',
        Entity::HSN_CODE            => 'filled|string|max:8',
        Entity::SAC_CODE            => 'filled|string|max:8',
        Entity::TAX_RATE            => 'sometimes|int_percentage',
        Entity::TAX_ID              => 'sometimes|nullable|public_id|size:18',
        Entity::TAX_GROUP_ID        => 'sometimes|nullable|public_id|size:19',
    ];

    protected static $editRules  = [
        Entity::ACTIVE              => 'filled|boolean',
        Entity::NAME                => 'filled|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|nullable|string|max:2048',
        Entity::AMOUNT              => 'sometimes|nullable|integer',
        Entity::UNIT_AMOUNT         => 'sometimes|nullable|integer',
        Entity::CURRENCY            => 'filled|currency|custom',
        Entity::UNIT                => 'sometimes|nullable|string|max:512',
        Entity::TAX_INCLUSIVE       => 'filled|boolean',
        Entity::HSN_CODE            => 'sometimes|nullable|string|max:8',
        Entity::SAC_CODE            => 'sometimes|nullable|string|max:8',
        Entity::TAX_RATE            => 'sometimes|nullable|int_percentage',
        Entity::TAX_ID              => 'sometimes|nullable|public_id|size:18',
        Entity::TAX_GROUP_ID        => 'sometimes|nullable|public_id|size:19',
    ];

    protected static $minAmountCheckRules = [
        Entity::AMOUNT => 'required|integer|min_amount'
    ];

    protected static $amountCreateRules = [
        Entity::AMOUNT      => 'required_without:unit_amount',
        Entity::UNIT_AMOUNT => 'required_without:amount',
    ];

    protected static $amountUpdateRules = [
        Entity::AMOUNT      => 'filled',
        Entity::UNIT_AMOUNT => 'filled',
    ];

    protected static $createValidators = [
        Entity::AMOUNT,
        'tax_attributes_international',
        self::TAX_INPUTS,
        self::TAX_CODES,
    ];

    protected static $editValidators = [
        'amount_payment_page',
        'currency_payment_page',
        'tax_attributes_international',
        self::TAX_INPUTS,
        self::TAX_CODES,
        'min_amount',
    ];

    public function validateType($attribute, $value)
    {
        Type::checkType($value);
    }

    public function validateAmount(array $input)
    {
        if ((isset($input[Entity::TYPE]) !== true) or
            ($input[Entity::TYPE] !== Type::PAYMENT_PAGE))
        {
            $this->validateInputValues('amount_create', $input);
        }
    }

    public function validateAmountPaymentPage(array $input)
    {
        if ($this->entity->getType() !== Type::PAYMENT_PAGE)
        {
            $this->validateInputValues('amount_update', $input);
        }
    }

    public function validateCurrencyPaymentPage(array $input)
    {
        if (($this->entity->getType() === Type::PAYMENT_PAGE) and
            (isset($input[Entity::CURRENCY]) === true))
        {
            throw new BadRequestValidationFailureException(
                'currency is not required'
            );
        }
    }

    /**
     * For international currency items we should not calculate or allow tax attributes.
     *
     * @param array $input
     *
     * @throws \RZP\Exception\ExtraFieldsException
     */
    public function validateTaxAttributesInternational(array $input)
    {
        $currency = $input[Entity::CURRENCY] ?? $this->entity->getCurrency();

        if ($currency !== Currency::INR)
        {
            $invalidKeys = array_intersect_key($input, array_flip(self::TAX_ATTRIBUTES));

            if (count($invalidKeys) > 0)
            {
                throw new ExtraFieldsException(array_keys($invalidKeys));
            }
        }
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
        $taxId      = array_key_exists(Entity::TAX_ID, $input) ?
                        $input[Entity::TAX_ID] : $this->entity->getTaxId();
        $taxGroupId = array_key_exists(Entity::TAX_GROUP_ID, $input) ?
                        $input[Entity::TAX_GROUP_ID] : $this->entity->getTaxGroupId();

        if ((empty($taxId) === false) and (empty($taxGroupId) === false))
        {
            throw new BadRequestValidationFailureException('Both tax_id and tax_group_id cannot be present');
        }
    }

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

    public function validateUpdateOperation(Entity $item)
    {
        if (($item->isNotOfType(Type::INVOICE) === true) and
            ($item->isNotOfType(Type::PAYMENT_PAGE) === true))
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
                'Cannot delete an item with which invoices have been created already',
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

    public function validateItemIsOfType(string $expectedType)
    {
        $item       = $this->entity;
        $id         = $item->getId();
        $actualType = $item->getType();

        if ($item->isNotOfType($expectedType) === true)
        {
            throw new BadRequestValidationFailureException(
                "item must be of type: {$actualType}",
                Entity::TYPE,
                compact('id', 'expectedType', 'actualType'));
        }
    }

    public function validateCurrency($attribute, $currency)
    {
        $merchant = app()->basicauth->getMerchant();

        $international = $merchant->isInternational();

        if ((($merchant->convertOnApi() === null) and
            ($currency !== Currency::INR)) or
            (in_array($currency, Currency::SUPPORTED_CURRENCIES, true) === false))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_INTERNATIONAL_NOT_ENABLED,
                null,
                [
                    'currency' => $currency
                ]);
        }
    }
}
