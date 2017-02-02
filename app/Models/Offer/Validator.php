<?php

namespace RZP\Models\Offer;

use Carbon\Carbon;
use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card;
use RZP\Models\Card\Network;
use RZP\Models\Offer;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Payment\Processor\Wallet;

class Validator extends Base\Validator
{
    const CASHBACK_CRITERIA = 'cashback_criteria';
    const OFFER_PERIOD      = 'offer_period';

    protected $payment;

    protected static $createRules = [
        Entity::NAME                      => 'sometimes|alpha_space_num|max:25',
        Entity::PAYMENT_METHOD            => 'required|alpha|custom',
        Entity::PAYMENT_METHOD_TYPE       => 'sometimes|in:debit,credit',
        ENTITY::PAYMENT_NETWORK           => 'sometimes|alpha',
        Entity::ISSUER                    => 'sometimes_if:payment_method,card|alpha',
        Entity::IINS                      => 'required_without_all:payment_network,issuer|array',
        Entity::PERCENT_RATE              => 'sometimes|integer|min:0|max:10000',
        Entity::MAX_CASHBACK              => 'sometimes|integer|min:0',
        Entity::FLAT_CASHBACK             => 'sometimes|integer|min:0',
        Entity::MIN_AMOUNT                => 'sometimes|integer|min:0',
        Entity::PAYMENT_COUNT             => 'sometimes|integer|min:1',
        Entity::PROCESSING_TIME           => 'sometimes|integer',
        Entity::STARTS_AT                 => 'required|integer',
        Entity::ENDS_AT                   => 'required|integer',
        Entity::ADDITIONAL_DETAILS        => 'sometimes|string|max:40',
        Entity::CUSTOM_LONG_DISPLAY_TEXT  => 'sometimes|string|max:200',
        Entity::CUSTOM_SHORT_DISPLAY_TEXT => 'sometimes|string|max:50'
    ];

    protected static $editRules = [
        Entity::NAME                      => 'sometimes|alpha_space_num|max:25',
        Entity::PAYMENT_METHOD            => 'sometimes|alpha|custom',
        Entity::PAYMENT_METHOD_TYPE       => 'sometimes_if:payment_method,card|in:debit,credit',
        ENTITY::PAYMENT_NETWORK           => 'sometimes|alpha',
        Entity::ISSUER                    => 'sometimes_if:payment_method,card|alpha',
        Entity::IINS                      => 'sometimes_without_all:payment_network,issuer|array',
        Entity::PERCENT_RATE              => 'sometimes|integer|min:0|max:10000',
        Entity::MAX_CASHBACK              => 'sometimes|integer|min:0',
        Entity::FLAT_CASHBACK             => 'sometimes|integer|min:0',
        Entity::MIN_AMOUNT                => 'sometimes|integer|min:0',
        Entity::PAYMENT_COUNT             => 'sometimes|integer|min:1',
        Entity::PROCESSING_TIME           => 'sometimes|integer',
        Entity::STARTS_AT                 => 'sometimes|integer',
        Entity::ENDS_AT                   => 'sometimes|integer',
        Entity::ACTIVE                    => 'sometimes|boolean',
        Entity::ADDITIONAL_DETAILS        => 'sometimes|string|max:40',
        Entity::CUSTOM_LONG_DISPLAY_TEXT  => 'sometimes|string|max:200',
        Entity::CUSTOM_SHORT_DISPLAY_TEXT => 'sometimes|string|max:50'
    ];

    protected static $merchantRules = [
        'merchant_ids'      => 'required|array',
        'action'            => 'required|string|in:add,remove'
    ];

    protected static $createValidators = [
        self::CASHBACK_CRITERIA,
        self::OFFER_PERIOD,
        Entity::FLAT_CASHBACK,
        Entity::PAYMENT_NETWORK,
    ];

    protected static $editValidators = [
        self::OFFER_PERIOD,
        Entity::FLAT_CASHBACK,
        Entity::PAYMENT_NETWORK
    ];

    protected $cashbackCriteriaParams = [
        Entity::PERCENT_RATE,
        Entity::MAX_CASHBACK,
        Entity::FLAT_CASHBACK,
    ];

    protected function validatePaymentNetwork(array $input)
    {
        if (isset($input[Entity::PAYMENT_NETWORK]) === false)
        {
            return;
        }

        $paymentMethod =  $input[Entity::PAYMENT_METHOD] ?? $this->entity->getPaymentMethod();

        if ($paymentMethod === Payment\Method::WALLET)
        {
            Wallet::validateExists($input[Entity::PAYMENT_NETWORK]);
        }
        elseif ($paymentMethod === Payment\Method::CARD)
        {
            $this->validateCardNetwork($input);
        }
        elseif ($paymentMethod === Payment\Method::NETBANKING)
        {
            $this->validateNetbanking($input);
        }
    }

    protected function validateCashbackCriteria(array $input)
    {
        if ($this->cashbackCriteriaPresent($input) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CASHBACK_CRITERIA_MISSING);
        }
    }

    protected function cashbackCriteriaPresent(array $input)
    {
        foreach ($this->cashbackCriteriaParams as $param)
        {
            if (isset($input[$param]) === true)
            {
                return true;
            }
        }

        return false;
    }

    protected function validateOfferPeriod(array $input)
    {
        $startsAt = $input[Entity::STARTS_AT] ?? $this->entity->getAttribute(Entity::STARTS_AT);

        $endsAt = $input[Entity::ENDS_AT] ?? $this->entity->getAttribute(Entity::ENDS_AT);

        if ($startsAt >= $endsAt)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_OFFER_DURATION);
        }
    }

    protected function validatePaymentMethod(string $attribute, string $value)
    {
        if (in_array($value, Payment\Method::getAllPaymentMethods()) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid payment method: $value", $attribute);
        }
    }

    protected function validateFlatCashback(array $input)
    {
        if (empty($input[Entity::FLAT_CASHBACK]) === false)
        {
            if ((empty($input[Entity::PERCENT_RATE]) === false) or
                    (empty($input[Entity::MAX_CASHBACK]) === false))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_FLAT_CASHBACK_WITH_PERCENT_RATE_OR_MAX_CASHBACK);
            }
        }
    }

    protected function validateCardNetwork(array $input)
    {
        if (Network::isValidNetwork($input[Entity::PAYMENT_NETWORK]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payment network for card should be a valid card network');
        }
        else if (Network::isUnsupportedNetwork($input[Entity::PAYMENT_NETWORK]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'This card payment network is not supported');
        }
    }

    protected function validateNetbanking(array $input)
    {
        if (IFSC::exists($input[Entity::PAYMENT_NETWORK]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payment network for bank should be a valid bank name');
        }
    }
}
