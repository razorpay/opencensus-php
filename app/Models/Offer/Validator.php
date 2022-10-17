<?php

namespace RZP\Models\Offer;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Emi;
use RZP\Models\Feature\Constants;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Payment\Processor\CardlessEmi;

class Validator extends Base\Validator
{
    const CASHBACK_CRITERIA = 'cashback_criteria';
    const OFFER_PERIOD      = 'offer_period';
    const EMI_ISSUER        = 'emi_issuer';
    const MERCHANT_CATEGORY = 'merchant_category';
    const OFFER_FEATURE_BLOCK = 'offer_feature_block';
    const MIN_AMOUNT_CARDLESS_EMI = 'min_amount_cardless_emi';

    const CASHBACK_CRITERIA_PARAMS = [
        Entity::PERCENT_RATE,
        Entity::MAX_CASHBACK,
        Entity::FLAT_CASHBACK,
    ];

    protected static $createRules = [
        Entity::NAME                => 'sometimes|filled|string|max:50',
        Entity::PAYMENT_METHOD      => 'filled|string|custom',
        Entity::PAYMENT_METHOD_TYPE => 'sometimes_if:payment_method,card,emi|in:debit,credit',
        Entity::PAYMENT_NETWORK     => 'filled|alpha',
        Entity::ISSUER              => 'filled|string',
        Entity::INTERNATIONAL       => 'sometimes_if:payment_method,card,emi|boolean',
        Entity::IINS                => 'filled|array',
        Entity::PERCENT_RATE        => 'filled|integer|min:0|max:10000',
        Entity::MAX_CASHBACK        => 'filled|integer|min:0',
        Entity::FLAT_CASHBACK       => 'filled|integer|min:0',
        Entity::MIN_AMOUNT          => 'filled|integer|min:0',
        Entity::MAX_PAYMENT_COUNT   => 'filled|integer|min:1',
        Entity::LINKED_OFFER_IDS    => 'filled|array',
        Entity::PROCESSING_TIME     => 'filled|integer',
        Entity::TYPE                => 'required|filled|in:instant,deferred,already_discounted',
        Entity::CHECKOUT_DISPLAY    => 'filled|boolean',
        Entity::STARTS_AT           => 'filled|epoch',
        Entity::ENDS_AT             => 'required|epoch',
        Entity::DISPLAY_TEXT        => 'filled|string|max:255',
        Entity::ERROR_MESSAGE       => 'filled|string|max:255',
        Entity::TERMS               => 'required|string',
        Entity::MAX_OFFER_USAGE     => 'sometimes|filled|integer|min:1',
        Entity::BLOCK               => 'required|boolean',
        Entity::ACTIVE              => 'filled|boolean',
        Entity::DEFAULT_OFFER       => 'filled|boolean',
        Entity::MAX_ORDER_AMOUNT    => 'filled|integer|min:0',
        Entity::PRODUCT_TYPE        => 'sometimes|filled|string|in:subscription',
    ];

    protected static $createBulkRules = [
        'offer'          => 'associative_array',
        'merchant_ids'   => 'array',
        'merchant_ids.*' => 'filled|string|unsigned_id',
    ];

    protected static $emiSubventionRules = [
        Entity::NAME                => 'sometimes|filled|string|max:50',
        Entity::PAYMENT_METHOD      => 'required|in:emi',
        Entity::PAYMENT_METHOD_TYPE => 'sometimes|in:debit,credit',
        Entity::ISSUER              => 'required_without:payment_network|filled',
        Entity::PAYMENT_NETWORK     => 'required_without:issuer|in:AMEX,BAJAJ|filled',
        Entity::EMI_SUBVENTION      => 'required|boolean|in:1',
        Entity::EMI_DURATIONS       => 'sometimes|array',
        Entity::MIN_AMOUNT          => 'filled|integer|min:0',
        Entity::MAX_PAYMENT_COUNT   => 'filled|integer|min:1',
        Entity::PROCESSING_TIME     => 'filled|integer',
        Entity::STARTS_AT           => 'filled|epoch',
        Entity::ENDS_AT             => 'required|epoch',
        Entity::DISPLAY_TEXT        => 'filled|string|max:255',
        Entity::ERROR_MESSAGE       => 'filled|string|max:255',
        Entity::TERMS               => 'required|string',
        Entity::BLOCK               => 'required|boolean',
        Entity::MAX_OFFER_USAGE     => 'sometimes|filled|integer',
        Entity::DEFAULT_OFFER       => 'filled|boolean',
        Entity::MAX_ORDER_AMOUNT    => 'filled|integer|min:0',
        Entity::TYPE                => 'required|in:instant,deferred,already_discounted',
    ];

    protected static $editRules = [
        Entity::NAME               => 'filled|string|max:50',
        Entity::IINS               => 'filled|array',
        Entity::MAX_PAYMENT_COUNT  => 'filled|integer|min:1',
        Entity::LINKED_OFFER_IDS   => 'filled|array',
        Entity::ACTIVE             => 'filled',
        Entity::ENDS_AT            => 'filled|epoch',
        Entity::DISPLAY_TEXT       => 'filled|string|max:255',
        Entity::ERROR_MESSAGE      => 'filled|string|max:255',
        Entity::TERMS              => 'filled|string'
    ];

    protected static $createValidators = [
        self::MERCHANT_CATEGORY,
        self::OFFER_FEATURE_BLOCK,
        self::CASHBACK_CRITERIA,
        self::OFFER_PERIOD,
        Entity::PAYMENT_NETWORK,
        Entity::IINS,
        Entity::FLAT_CASHBACK,
        Entity::MAX_PAYMENT_COUNT,
        Entity::LINKED_OFFER_IDS,
        Entity::MAX_CASHBACK,
        Entity::ISSUER,
    ];

    protected static $emiSubventionValidators = [
        Entity::MIN_AMOUNT,
        self::OFFER_PERIOD,
        self::EMI_ISSUER,
        Entity::EMI_DURATIONS,
    ];

    protected static $editValidators = [
        Entity::IINS,
        Entity::MAX_PAYMENT_COUNT,
        Entity::LINKED_OFFER_IDS,
    ];

    protected static $validateCheckoutOffersRules = [
        'amount'                        => 'required|integer',
        'method'                        => 'required|string|in:card',
        'card'                          => 'required_if:method,card|array',
        'card.number'                   => 'sometimes|min:6',
        'card.token'                    => 'sometimes',
        'offers'                        => 'required|array',
        'order_id'                      => 'required|string',
    ];

    protected function validatePaymentNetwork(array $input)
    {
        $networkCode = $input[Entity::PAYMENT_NETWORK] ?? null;

        $method = $input[Entity::PAYMENT_METHOD] ?? null;

        if (empty($networkCode) === true)
        {
            return;
        }

        if ((empty($method) === false) and
            (in_array($method, [Payment\Method::CARD, Payment\Method::EMI], true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                "Payment network should be sent only for card offers");
        }

        if (Network::isValidNetworkCode($networkCode) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payment network for card should be a valid card network code');
        }

        if (Network::isUnsupportedNetwork($networkCode) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'This card payment network is not supported');
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
        if (isset($input[Entity::FLAT_CASHBACK]) === false)
        {
            return;
        }

        if ((isset($input[Entity::PERCENT_RATE]) === true) or
             (isset($input[Entity::MAX_CASHBACK]) === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FLAT_CASHBACK_WITH_PERCENT_RATE_OR_MAX_CASHBACK);
        }

        if ((isset($input[Entity::MIN_AMOUNT]) === true) and
            ($input[Entity::FLAT_CASHBACK] > $input[Entity::MIN_AMOUNT]))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Flat cashback cannot be greater than minimum amount', null, [
                    Entity::FLAT_CASHBACK => $input[Entity::FLAT_CASHBACK],
                    Entity::MIN_AMOUNT    => $input[Entity::MIN_AMOUNT],
                ]);
        }
    }

    protected function validateMaxCashback(array $input)
    {
        if ((isset($input[Entity::MAX_CASHBACK]) === true) and
            (isset($input[Entity::PERCENT_RATE]) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MAX_CASHBACK_WITHOUT_PERCENT_RATE, null, [
                    'attributes' => Entity::PERCENT_RATE,
                ]);
        }
    }

    protected function validateIssuer(array $input)
    {

        if (empty($input[Entity::ISSUER]))
        {
            return;
        }

        if ((empty($input[Entity::PAYMENT_METHOD]) === false) and
            ($input[Entity::PAYMENT_METHOD] === Payment\Method::CARDLESS_EMI))
        {
            // If issuer is set, it should be a valid cardless emi provider
            if (CardlessEmi::exists(($input[Entity::ISSUER])) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid issuer name : '. $input[Entity::ISSUER]);
            }
            // Validate minimum amount required at provider level
            return $this->validateMinAmountCardlessEmi($input);
        }

        if ((IFSC::exists($input[Entity::ISSUER]) === false) and
            (Wallet::exists($input[Entity::ISSUER]) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid issuer name : '. $input[Entity::ISSUER]);
        }
    }

    protected function validateMinAmountCardlessEmi($input)
    {
        // If minimum amount field is set, each cardless_emi providers requires minimum order amount
        if ((empty($input[Entity::MIN_AMOUNT]) === false) and (
                $input[Entity::MIN_AMOUNT] < CardlessEmi::MIN_AMOUNTS[$input[Entity::ISSUER]]))
        {
            throw new Exception\BadRequestValidationFailureException(
                "Minimum amount for cardless emi provider " . $input[Entity::ISSUER] ." should be greater than Rs. " . CardlessEmi::MIN_AMOUNTS[$input[Entity::ISSUER]]/100);
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
                'IINs should be a valid array');
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
                'IINs can be only edited for card / emi offer');
        }

        $invalidIin = array_first($iins, function ($iin)
        {
            return strlen($iin) != 6;
        });

        if (empty($invalidIin) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid IIN : All IINs should have exactly 6 digits');
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

    protected function validateMinAmount(array $input)
    {
        if (isset($input[Entity::MIN_AMOUNT]) === false)
        {
            return;
        }

        $minAmount = $input[Entity::MIN_AMOUNT] ?? null;

        $bank = $input[Entity::ISSUER] ?? null;

        $network = $input[Entity::PAYMENT_NETWORK] ?? null;

        if ($network === Network::BAJAJ) {
            // Skip Min Amount Validation for BFL Network
            return;
        }

        $type = $input[Entity::PAYMENT_METHOD_TYPE] ?? null;

        //in case of emi PAYMENT_METHOD_TYPE comes as null
        //as of now only credit is supported so added type credit for emi

        if (($type === null) and
             ($input[Entity::PAYMENT_METHOD] === 'emi'))
        {
            $type = 'credit';
        }

        $emiDurations = $input[Entity::EMI_DURATIONS] ?? [];

        $requiredMinAmount = (new Emi\Core)->calculateMinAmountForPlans($emiDurations, $bank, $network, $type);

        if ($minAmount < $requiredMinAmount)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Min amount for this offer should be greater than " .$requiredMinAmount/100);
        }
    }

    protected function validateEmiDurations(array $input)
    {
        if (isset($input[Entity::EMI_DURATIONS]) === false)
        {
            return;
        }

        $emiRepo = new Emi\Repository();

        $validDurations = [];

        if (isset($input[Entity::ISSUER]) === true)
        {
            $validDurations = $emiRepo->fetchDurationsByMerchantAndIssuer($this->entity->merchant->getId(),
                $input[Entity::ISSUER]);
        }

        if (isset($input[Entity::PAYMENT_NETWORK]) === true)
        {
            $validDurations = $emiRepo->fetchDurationsByMerchantAndNetwork($this->entity->merchant->getId(),
                $input[Entity::PAYMENT_NETWORK]);
        }

        $diff = array_diff($input[Entity::EMI_DURATIONS], $validDurations);

        if (empty($diff) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid emi durations given " . implode(", ", $diff));
        }
    }

    protected function validateEmiIssuer(array $input)
    {
        if (isset($input[Entity::ISSUER]) === false)
        {
            return;
        }

        if (isset($input[Entity::PAYMENT_NETWORK]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Either issuer or payment network should be sent');
        }

        if (in_array($input[Entity::ISSUER], Gateway::$emiBanks, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid issuer name: '. $input[Entity::ISSUER]);
        }
    }

    protected function validateMerchantCategory(array $input)
    {
        $isInsuranceCategory = $this->entity->merchant->isInsuranceCategory($this->entity->merchant->getCategory());

        if($isInsuranceCategory === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Offer creation is not allowed for this Merchant category');
        }
    }

    protected function validateOfferFeatureBlock(array $input)
    {
        $hasBlockingFeature = $this->entity->merchant->isFeatureEnabled(Constants::BLOCK_OFFER_CREATION);

        if($hasBlockingFeature === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Offer creation is not allowed for Merchant');
        }
    }
}
