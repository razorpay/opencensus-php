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
        Entity::IINS                      => 'sometimes_if:payment_method,card|array',
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
        Entity::ADDITIONAL_DETAILS        => 'sometimes|string|max:40',
        Entity::CUSTOM_LONG_DISPLAY_TEXT  => 'sometimes|string|max:200',
        Entity::CUSTOM_SHORT_DISPLAY_TEXT => 'sometimes|string|max:50'
    ];

    protected static $createValidators = [
        self::CASHBACK_CRITERIA,
        self::OFFER_PERIOD,
        Entity::FLAT_CASHBACK,
        Entity::PAYMENT_NETWORK,
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

        $paymentMethod =  $input[Entity::PAYMENT_METHOD];

        switch ($paymentMethod)
        {
            case Payment\Method::CARD:
                $this->validateCardNetwork($input);
                break;
            case Payment\Method::WALLET:
                Wallet::validateExists($input[Entity::PAYMENT_NETWORK]);
                break;
            case Payment\Method::NETBANKING:
                $this->validateNetbanking($input);
                break;
            case Payment\Method::EMI:
                $this->validateCardNetwork($input);
                break;
            default:
                throw new Exception\BadRequestException("Invalid payment method used");
                break;
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
        $now = Carbon::now('Asia/Kolkata')->timestamp;

        $startsAt = $input[Entity::STARTS_AT];

        $endsAt = $input[Entity::ENDS_AT];

        if (($startsAt <= $now) or ($endsAt <= $now) or ($startsAt >= $endsAt))
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

    public function validateIins(array $iins)
    {
        if ($this->entity->getPaymentMethod() !== Payment\Method::CARD)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_IINS_EDITABLE_FOR_CARD_OFFER);
        }

        if ($this->isAssociativeArray($iins) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_FORMAT_FOR_IINS);
        }
    }

    protected function isAssociativeArray(array $input)
    {
        return array_keys($input) !== range(0, count($input) - 1);
    }
}
