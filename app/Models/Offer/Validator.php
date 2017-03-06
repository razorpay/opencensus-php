<?php

namespace RZP\Models\Offer;

use RZP\Base;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Processor\Wallet;

class Validator extends Base\Validator
{
    const CASHBACK_CRITERIA = 'cashback_criteria';
    const OFFER_PERIOD      = 'offer_period';

    const CASHBACK_CRITERIA_PARAMS = [
        Entity::PERCENT_RATE,
        Entity::MAX_CASHBACK,
        Entity::FLAT_CASHBACK,
    ];

    protected static $createRules = [
        Entity::NAME                      => 'sometimes|alpha_space_num|max:25',
        Entity::PAYMENT_METHOD            => 'required|alpha|custom',
        Entity::PAYMENT_METHOD_TYPE       => 'sometimes_if:payment_method,card|in:debit,credit',
        Entity::PAYMENT_NETWORK           => 'sometimes|alpha',
        Entity::ISSUER                    => 'sometimes_if:payment_method,card|alpha|custom',
        Entity::IINS                      => 'sometimes_if:payment_method,card|array',
        Entity::PERCENT_RATE              => 'sometimes|integer|min:0|max:10000',
        Entity::MAX_CASHBACK              => 'sometimes|integer|min:0',
        Entity::FLAT_CASHBACK             => 'sometimes|integer|min:0',
        Entity::MIN_AMOUNT                => 'sometimes|integer|min:0',
        Entity::PAYMENT_COUNT             => 'sometimes|integer|min:1',
        Entity::PROCESSING_TIME           => 'sometimes|integer',
        Entity::TYPE                      => 'sometimes|in:instant,deferred',
        Entity::STARTS_AT                 => 'sometimes|integer',
        Entity::ENDS_AT                   => 'required|integer',
        Entity::DISPLAY_TEXT              => 'sometimes|string|max:255',
        Entity::TERMS                     => 'required|string'
    ];

    protected static $editRules = [
        Entity::NAME                      => 'sometimes|alpha_space_num|max:25',
        Entity::IINS                      => 'sometimes|array',
        Entity::ACTIVE                    => 'sometimes|in:0',
        Entity::DISPLAY_TEXT              => 'sometimes|string|max:255',
        Entity::TERMS                     => 'sometimes|string'
    ];

    protected static $createValidators = [
        self::CASHBACK_CRITERIA,
        self::OFFER_PERIOD,
        Entity::FLAT_CASHBACK,
        Entity::PAYMENT_NETWORK,
        Entity::IINS,
    ];

    protected static $editValidators = [
        Entity::IINS,
    ];

    protected function validatePaymentNetwork(array $input)
    {
        if (isset($input[Entity::PAYMENT_NETWORK]) === false)
        {
            return;
        }

        $paymentMethod =  $input[Entity::PAYMENT_METHOD];
        $network = $input[Entity::PAYMENT_NETWORK];

        switch ($paymentMethod)
        {
            case Payment\Method::CARD:
            case Payment\Method::EMI:
                $this->validateCardNetwork($network);
                break;

            case Payment\Method::WALLET:
                Wallet::validateExists($network);
                break;

            case Payment\Method::NETBANKING:
                $this->validateNetbanking($network);
                break;

            default:
                throw new Exception\BadRequestException("Invalid payment method");
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
        foreach (self::CASHBACK_CRITERIA_PARAMS as $param)
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

        $endsAt = $input[Entity::ENDS_AT];

        $startsAt = $input[Entity::STARTS_AT] ?? $now;

        if (($startsAt < $now) or
            ($endsAt <= $now) or
            ($startsAt >= $endsAt))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_OFFER_DURATION);
        }
    }

    protected function validatePaymentMethod(string $attribute, string $value)
    {
        if (in_array($value, Payment\Method::getAllPaymentMethods(), true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid payment method: $value", $attribute);
        }
    }

    protected function validateFlatCashback(array $input)
    {
        if ((isset($input[Entity::FLAT_CASHBACK]) === true) and
            ((isset($input[Entity::PERCENT_RATE]) === true) or
             (isset($input[Entity::MAX_CASHBACK]) === true)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FLAT_CASHBACK_WITH_PERCENT_RATE_OR_MAX_CASHBACK);
        }
    }

    protected function validateCardNetwork($network)
    {
        if (Network::isValidNetwork($network) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payment network for card should be a valid card network');
        }

        if (Network::isUnsupportedNetwork($network) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'This card payment network is not supported');
        }
    }

    protected function validateNetbanking($network)
    {
        if (IFSC::exists($network) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payment network for bank should be a valid bank name');
        }
    }

    protected function validateIssuer(string $attribute, string $value)
    {
        if (IFSC::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Issuer name : '. $value);
        }
    }

    protected function validateIins(array $input)
    {
        if (isset($input[Entity::IINS]) === false)
        {
            return;
        }

        $iins = $input[Entity::IINS];

        $paymentMethod = $this->entity->getPaymentMethod() ?? $input[Entity::PAYMENT_METHOD];

        if ($paymentMethod !== Payment\Method::CARD)
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
