<?php

namespace RZP\Models\Offer;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Emi;
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
        Entity::NAME                => 'sometimes|filled|string|max:50',
        Entity::PAYMENT_METHOD      => 'filled|alpha|custom',
        Entity::PAYMENT_METHOD_TYPE => 'filled|in:debit,credit',
        Entity::PAYMENT_NETWORK     => 'filled|alpha',
        Entity::ISSUER              => 'filled|alpha|custom',
        Entity::INTERNATIONAL       => 'sometimes_if:payment_method,card|boolean',
        Entity::IINS                => 'filled|array',
        Entity::PERCENT_RATE        => 'filled|integer|min:0|max:10000',
        Entity::MAX_CASHBACK        => 'filled|integer|min:0',
        Entity::FLAT_CASHBACK       => 'filled|integer|min:0',
        Entity::MIN_AMOUNT          => 'filled|integer|min:0',
        Entity::MAX_PAYMENT_COUNT   => 'filled|integer|min:1',
        Entity::LINKED_OFFER_IDS    => 'filled|array',
        Entity::PROCESSING_TIME     => 'filled|integer',
        Entity::TYPE                => 'filled|in:instant,deferred',
        Entity::CHECKOUT_DISPLAY    => 'filled|boolean',
        Entity::STARTS_AT           => 'filled|epoch',
        Entity::ENDS_AT             => 'required|epoch',
        Entity::DISPLAY_TEXT        => 'filled|string|max:255',
        Entity::ERROR_MESSAGE       => 'filled|string|max:255',
        Entity::TERMS               => 'required|string',
        Entity::EMI_SUBVENTION      => 'sometimes_if:payment_method,emi|boolean',
        Entity::EMI_DURATIONS       => 'sometimes_if:emi_subvention,1',

    ];

    protected static $emiSubventionRules = [
        Entity::NAME                => 'sometimes|filled|string|max:50',
        Entity::PAYMENT_METHOD      => 'required|in:emi',
        Entity::ISSUER              => 'required_without:payment_network',
        Entity::PAYMENT_NETWORK     => 'required_without:issuer|in:AMEX',
        Entity::EMI_SUBVENTION      => 'required|boolean|in:1',
        Entity::EMI_DURATIONS       => 'sometimes|array|custom',
        // Not validating these params as they have been validated.
        Entity::MIN_AMOUNT          => 'filled',
        Entity::MAX_PAYMENT_COUNT   => 'filled',
        Entity::PROCESSING_TIME     => 'filled',
        Entity::STARTS_AT           => 'filled',
        Entity::ENDS_AT             => 'filled',
        Entity::DISPLAY_TEXT        => 'filled',
        Entity::ERROR_MESSAGE       => 'filled',
        Entity::TERMS               => 'filled',
    ];

    protected static $editRules = [
        Entity::NAME               => 'filled|string|max:50',
        Entity::IINS               => 'filled|array',
        Entity::MAX_PAYMENT_COUNT  => 'filled|integer|min:1',
        Entity::LINKED_OFFER_IDS   => 'filled|array',
        Entity::ACTIVE             => 'filled|in:0',
        Entity::ENDS_AT            => 'filled|epoch',
        Entity::DISPLAY_TEXT       => 'filled|string|max:255',
        Entity::ERROR_MESSAGE      => 'filled|string|max:255',
        Entity::TERMS              => 'filled|string'
    ];

    protected static $createValidators = [
        self::CASHBACK_CRITERIA,
        self::OFFER_PERIOD,
        Entity::PAYMENT_NETWORK,
        Entity::IINS,
        Entity::FLAT_CASHBACK,
        Entity::MAX_PAYMENT_COUNT,
        Entity::LINKED_OFFER_IDS,
        Entity::EMI_SUBVENTION,
        Entity::MIN_AMOUNT,
    ];

    protected static $editValidators = [
        Entity::IINS,
        Entity::MAX_PAYMENT_COUNT,
        Entity::LINKED_OFFER_IDS,
    ];

    protected function validatePaymentNetwork(array $input)
    {
        $network = $input[Entity::PAYMENT_NETWORK] ?? null;

        $method = $input[Entity::PAYMENT_METHOD] ?? null;

        if (empty($network) === true)
        {
            return;
        }

        if ((empty($method) === false) and
            (in_array($method, [Payment\Method::CARD, Payment\Method::EMI], true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                "Payment network should be sent only for card offers");
        }

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

    protected function validateCashbackCriteria(array $input)
    {
        if (isset($input[Entity::EMI_SUBVENTION]) === true)
        {
            return;
        }

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
        $now = Carbon::now()->getTimestamp();

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

    protected function validatePaymentMethod(string $attribute, string $method)
    {
        if (empty($method) === true)
        {
            return;
        }

        if (in_array($method, Payment\Method::getAllPaymentMethods(), true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid payment method: $method", $attribute);
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

    protected function validateIssuer(string $attribute, string $issuer)
    {
        if ((IFSC::exists($issuer) === false) and (Wallet::exists($issuer) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid issuer name : $issuer", $attribute);
        }
    }

    protected function validateIins(array $input)
    {
        $iins = $input[Entity::IINS] ?? null;

        if (empty($iins) === true)
        {
            return;
        }

        if (is_associative_array($iins) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                        'Iins should be a valid array');
        }

        $paymentMethod = $input[Entity::PAYMENT_METHOD] ?? $this->entity->getPaymentMethod();

        if (empty($paymentMethod) === true)
        {
            return;
        }

        $allowedPaymentMethods = [Payment\Method::CARD, Payment\Method::EMI];

        if (in_array($paymentMethod, $allowedPaymentMethods, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Iins can be only edited for card / emi offer');
        }
    }

    protected function validateMaxPaymentCount(array $input)
    {
        if (empty($input[Entity::MAX_PAYMENT_COUNT]) === true)
        {
            return;
        }

        $paymentMethod = $input[Entity::PAYMENT_METHOD] ?? $this->entity->getPaymentMethod();

        if (empty($paymentMethod) === true)
        {
            return;
        }

        $allowedPaymentMethods = [Payment\Method::CARD, Payment\Method::EMI];

        if (in_array($paymentMethod, $allowedPaymentMethods, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'max_payment_count can only be set for card or emi offera');
        }
    }

    protected function validateLinkedOfferIds(array $input)
    {
        if (empty($input[Entity::LINKED_OFFER_IDS]) === true)
        {
            return;
        }

        $linkedOfferIds = $input[Entity::LINKED_OFFER_IDS];

        // Checks if it is a valid sequential array
        if (is_associative_array($linkedOfferIds) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                        'linked_offer_ids should be a valid array');
        }

        // Checks if the offer on which we are linking offer ids has the max_payment_count attribute
        $maxPaymentCount = $input[Entity::MAX_PAYMENT_COUNT] ?? $this->entity->getMaxPaymentCount();

        if (empty($maxPaymentCount) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                        'linked_offer_ids can only be set for offer with max_payment_count');

        }

        // Checks if all the linked offer ids belong to the merchant
        $merchantOfferIds = $this->entity->merchant->offers->pluck(Entity::ID)->toArray();

        $result = array_diff($linkedOfferIds, $merchantOfferIds);

        if (empty($result) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                        'Linked offer ids submitted are not valid');
        }
    }

    protected function validateEmiSubvention(array $input)
    {
        if (isset($input[Entity::EMI_SUBVENTION]) === false)
        {
            return;
        }

        $op =  'emi_subvention';

        $var = $this->getRulesVariableName($op);

        if (property_exists(__CLASS__, $var))
        {
            $this->validateInput($op, $input);
        }
    }

    protected function validateMinAmount(array $input)
    {
        $minAmount = $input[Entity::MIN_AMOUNT] ?? null;

        if ((isset($input[Entity::EMI_SUBVENTION]) === false) or
            (empty($minAmount) === true))
        {
            return;
        }

        $bank = $input[Entity::ISSUER] ?? null;

        $network = $input[Entity::PAYMENT_NETWORK] ?? null;

        $emiDurations = $input[Entity::EMI_DURATIONS] ?? null;

        $requiredMinAmount = (new Emi\Core)->calculateMinAmountForPlans($bank, $network, $emiDurations);

        if ($minAmount < $requiredMinAmount)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Min amount required for these emi subvention is $requiredMinAmount");
        }
    }

    protected function validateEmiDurations(string $attribute, array $emiDurations)
    {
        $validDurations = [3,6,9,12,18,24];

        foreach($emiDurations as $emiDuration)
        {
            if (in_array($emiDuration, $validDurations, true) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "Invalid emi duration given $emiDuration");
            }
        }
    }
}
