<?php

namespace RZP\Models\Offer;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Gateway\Upi\Yesbank\PayerAccountType;
use RZP\Models\Emi;
use RZP\Models\Feature\Constants;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Gateway\Upi\Base as upi;
use RZP\Models\PaymentsUpi;
use RZP\Models\Offer\Constants as OfferConstants;

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
        Entity::PERCENT_RATE        => 'filled|integer|min:1|max:10000',
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
        Entity::LOW_COST_EMI        => 'sometimes|array',
        Entity::UPI                 => 'sometimes|array',
        Entity::INSTRUMENTS         => 'sometimes|array',
    ];

    protected static $adminFetchMultipleRules = [
        Entity::MERCHANT_ID => 'sometimes|unsigned_id',
        'page'              => 'required|int|min:1|max:1000',
        'page_size'         => 'required|int|min:1|max:50',
        'from'              => 'sometimes|epoch',
        'to'                => 'sometimes|epoch'
    ];

    protected static $adminFetchRules = [
        'offer_id'          => 'required|alpha_num|size:14',
        Entity::MERCHANT_ID => 'sometimes|alpha_num|size:14',
        'page'              => 'required|int|in:1',
        'page_size'         => 'required|int|in:1'
    ];

    protected static $fetchMultipleRules = [
        Entity::MERCHANT_ID => 'sometimes|unsigned_id',
        'page'              => 'sometimes|int|min:1|max:1000',
        'page_size'         => 'sometimes|int|min:1|max:50'
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
        Entity::EMI_DURATIONS       => 'required|array',
        Entity::MIN_AMOUNT          => 'filled|integer|min:0',
        Entity::MAX_PAYMENT_COUNT   => 'filled|integer|min:1',
        Entity::PROCESSING_TIME     => 'filled|integer',
        Entity::STARTS_AT           => 'filled|epoch',
        Entity::ENDS_AT             => 'required|epoch',
        Entity::DISPLAY_TEXT        => 'filled|string|max:255',
        Entity::ERROR_MESSAGE       => 'filled|string|max:255',
        Entity::TERMS               => 'required|string',
        Entity::BLOCK               => 'required|boolean',
        Entity::MAX_OFFER_USAGE     => 'sometimes|filled|integer|min:1',
        Entity::DEFAULT_OFFER       => 'filled|boolean',
        Entity::MAX_ORDER_AMOUNT    => 'filled|integer|min:0',
        Entity::TYPE                => 'required|in:instant,deferred,already_discounted',
        Entity::PERCENT_RATE        => 'sometimes|filled|integer|min:1|max:10000',
        Entity::LOW_COST_EMI        => 'sometimes|array',
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
        Entity::UPI,
        Entity::INSTRUMENTS,
    ];

    protected static $emiSubventionValidators = [
        Entity::MIN_AMOUNT,
        self::OFFER_PERIOD,
        self::EMI_ISSUER,
        Entity::EMI_DURATIONS,
        Entity::PERCENT_RATE,
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

    protected static $fetchOfferCreateInfoRules = [
        'merchant_id'               => 'required|unsigned_id',
        'offer'                     => 'required|array',
        'offer.is_no_cost_emi'      => 'required|boolean',
        'offer.emi_durations'       => 'required_if:offer.is_no_cost_emi,true|array',
        'offer.emi_durations.*'     => 'sometimes|integer',
        'offer.issuer'              => 'sometimes|string',
        'offer.payment_network'     => 'sometimes|string',
        'offer.payment_method'      => 'sometimes|string',
        'offer.payment_method_type' => 'sometimes|string',
    ];

    protected static $allowedPspApps = [
        upi\ProviderPsp::GOOGLE_PAY,
        upi\ProviderPsp::PHONEPE,
        upi\ProviderPsp::PAYTM,
        upi\ProviderPsp::BHIM,
        upi\ProviderPsp::WHATSAPP,
        upi\ProviderPsp::AMAZON_PAY,
        upi\ProviderPsp::BHIM_BARODAPAY,
        upi\ProviderPsp::IMOBILE,
        upi\ProviderPsp::BHIM_BOI_UPI,
        upi\ProviderPsp::CANDI_CANARA_BANK,
        upi\ProviderPsp::NSDL_JIFFY,
        upi\ProviderPsp::BHIM_AXISPAY,
        upi\ProviderPsp::DAKPAY_UPI_IPBB,
        upi\ProviderPsp::MOBIKWIK,
        upi\ProviderPsp::DIGI_BANK,
        upi\ProviderPsp::BHIM_DLB_UPI,
        upi\ProviderPsp::PAYZAPP,
        upi\ProviderPsp::BHIM_INDUSPAY,
        upi\ProviderPsp::GROWW,
        upi\ProviderPsp::OK_CREDIT,
        upi\ProviderPsp::JIO,
        upi\ProviderPsp::BHIM_SBIPAY,
        upi\ProviderPsp::IDFC,
        upi\ProviderPsp::TATA_NEU,
        upi\ProviderPsp::JUPITER_MONEY,
        upi\ProviderPsp::BHIM_PNB,
        upi\ProviderPsp::FAM_PAY,
        upi\ProviderPsp::FAVE,
        upi\ProviderPsp::ZOMATO,
        upi\ProviderPsp::HDFC,
        upi\ProviderPsp::BAJAJ_FINSERVE,
        upi\ProviderPsp::GO_NIYO,
        upi\ProviderPsp::EQUITAS_SMALL_FINANCE_BANK_LTD,
        upi\ProviderPsp::NAVI,
        upi\ProviderPsp::SHRIRAMONE,
        upi\ProviderPsp::GOKIWI,
        upi\ProviderPsp::MAHAMOBILE_PLUS,
        upi\ProviderPsp::INDIAN_OVERSEAS_BANK,
        upi\ProviderPsp::BHIM_CENT_UPI_APP,
        upi\ProviderPsp::FINCARE_BANK,
        upi\ProviderPsp::INDUSIND_BANK_APP,
        upi\ProviderPsp::SAMSUNG_PAY,
        upi\ProviderPsp::YESPAY_NEXT,
        upi\ProviderPsp::KOTAK_BANK_APP,
        upi\ProviderPsp::AXIS_BANK,
        upi\ProviderPsp::CRED,
        upi\ProviderPsp::FREECHARGE,
        upi\ProviderPsp::YONO_SBI,
        upi\ProviderPsp::ADITYA_BIRLA_CAPITAL_DIGITAL,
        upi\ProviderPsp::FI,
        upi\ProviderPsp::CITRUS,
        upi\ProviderPsp::TIMEPAY,
        upi\ProviderPsp::BOB_WORLD_UPI,
        upi\ProviderPsp::SIB_MIRROR_PLUS,
        upi\ProviderPsp::FAMPAY,
        upi\ProviderPsp::BHIM_CRGB_PAY,
        upi\ProviderPsp::FAVE_MONEY,
        upi\ProviderPsp::FREO,
        upi\ProviderPsp::POP,
        upi\ProviderPsp::SUPER_MONEY,
        upi\ProviderPsp::BOI_MOBILE_OMNI_NEO_BANK,
        upi\ProviderPsp::FLIPKART,
        upi\ProviderPsp::ONECARD,
        upi\ProviderPsp::RAZORPAY,
        upi\ProviderPsp::ICICI_IMOBILE,
        upi\ProviderPsp::SAMSUNG_WALLET,
        upi\ProviderPsp::KOTAK_811,
        upi\ProviderPsp::SBI_BANK,
        upi\ProviderPsp::SBI_YONO,
        upi\ProviderPsp::HDFC_BANK,
        upi\ProviderPsp::AXIS_PAY,
        upi\ProviderPsp::AXIS_MOBILE,
        upi\ProviderPsp::SLICE,
        upi\ProviderPsp::BOB_UPI,
        upi\ProviderPsp::PNB_BANK,
        upi\ProviderPsp::FED_MOBILE,
        upi\ProviderPsp::INDIA_POST,
        upi\ProviderPsp::MY_JIO,
        upi\ProviderPsp::VYOM,
        upi\ProviderPsp::SIB_MIRROR,
        upi\ProviderPsp::OMNICARD,
        upi\ProviderPsp::DIGIBANK,
        upi\ProviderPsp::INDOASIS,
        upi\ProviderPsp::AU_0101,
        upi\ProviderPsp::SHRIRAM_ONE,
        upi\ProviderPsp::CENT_MOBILE,
        upi\ProviderPsp::RBL_MOBANK,
        upi\ProviderPsp::INDUS_MOBILE,
        upi\ProviderPsp::INDUS_INDIE,
        upi\ProviderPsp::DIGI_KHATA,
        upi\ProviderPsp::POP_CLUB,
        upi\ProviderPsp::BHIM_UCO,
        upi\ProviderPsp::YES_BANK_IRIS,
        upi\ProviderPsp::YES_BANK,
        upi\ProviderPsp::INTENT_SAMPLE,
        upi\ProviderPsp::WHATSAPP_BIZ,
        upi\ProviderPsp::ICICI_POCKET,
        upi\ProviderPsp::UNITED_UPI,
        upi\ProviderPsp::KVB,
        upi\ProviderPsp::VIJAYA,
        upi\ProviderPsp::DENA,
        upi\ProviderPsp::JK_UPI,
        upi\ProviderPsp::HIKE,
        upi\ProviderPsp::ABPB,
        upi\ProviderPsp::MICROSOFT_KAIZALA,
        upi\ProviderPsp::FINO,
        upi\ProviderPsp::ORIENTAL,
        upi\ProviderPsp::LOTZA,
        upi\ProviderPsp::INDUS_PAY,
        upi\ProviderPsp::WIZELY,
        upi\ProviderPsp::DCB_BANK,
        upi\ProviderPsp::YES_MERCHANT,
        upi\ProviderPsp::CHILLR,
        upi\ProviderPsp::BULLET,
        upi\ProviderPsp::MI_PAY,
        upi\ProviderPsp::MI_PAY_2,
        upi\ProviderPsp::ULTRACASH,
        upi\ProviderPsp::GOIBIBO,
        upi\ProviderPsp::DAKPAY,
        upi\ProviderPsp::BHIM_IOB,
        upi\ProviderPsp::BHIM_CSB,
        upi\ProviderPsp::TVAM,
        upi\ProviderPsp::PNB_ONE,
        upi\ProviderPsp::FREOPAY,
        \RZP\Models\Offer\Constants::ALL,
    ];

    protected static $allowedPayerAccountType = [
        PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_WALLET,
        PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT,
        PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_WALLET,
        PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_BANK_ACCOUNT,
        PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_PPIWALLET,
        \RZP\Models\Offer\Constants::ALL,
    ];

    // only to be used for LC EMI offer
    protected function validatePercentRate(array $input)
    {
        if (!(isset($input[Entity::EMI_SUBVENTION]) && isset($input[Entity::LOW_COST_EMI])))
        {
            return;
        }
        if (!isset($input[Entity::PERCENT_RATE]))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Percentage rate is required for Low cost EMI');
        }

        if ($input[Entity::PERCENT_RATE] > 0 && $input[Entity::PERCENT_RATE] < 10000)
        {
            return;
        }

        throw new Exception\BadRequestValidationFailureException(
            'Percentage rate Should be minimum .01%');

    }

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

        // adding 15 min buffer for starts at
        $nowWithBuffer = Carbon::now()->subMinutes(10)->getTimestamp();

        $endsAt = $input[Entity::ENDS_AT];

        $startsAt = $input[Entity::STARTS_AT] ?? $now;


        if (($startsAt < $nowWithBuffer) or
            ($endsAt <= $now) or
            ($startsAt >= $endsAt))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_OFFER_DURATION);
        }
    }

    protected function validatePaymentMethod(string $attribute, string $method)
    {
        if (empty($method) === true || $method == Entity::MULTIPLE)
        {
            return;
        }

        if ((in_array($method, Payment\Method::getAllPaymentMethods(), true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid payment method: $method", $attribute);
        }
    }

    protected function validateInstruments(array $input)
    {
        $method      = $input[Entity::PAYMENT_METHOD] ?? null;
        $instruments = $input[Entity::INSTRUMENTS] ?? [];

        if ((empty($method) === true) or
            ($method !== Entity::MULTIPLE))
        {
            return;
        }

        if (empty($instruments) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                "No payment instruments found for payment method multiple");
        }

        $methods = [];
        foreach ($input[Entity::INSTRUMENTS] as $instrument)
        {
            if (empty($instrument[OfferConstants::METHOD]) === false)
            {
                $methods[] = $instrument[OfferConstants::METHOD];
            }
        }

        if (empty($methods) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid Payment Instruments passed");
        }

        foreach ($methods as $method)
        {
            if ((in_array($method, Payment\Method::getAllPaymentMethods(), true) === false))
            {
                throw new Exception\BadRequestValidationFailureException(
                    "Invalid payment method: $method", $method);
            }
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

    //optimise code for this function
    protected function validateUpi(array $input)
    {
        $method = $input[Entity::PAYMENT_METHOD] ?? null;
        $upiInstrument = $input[Entity::UPI] ?? null;

        // If no UPI instrument details are provided, nothing to validate
        if ($upiInstrument === null)
        {
            return;
        }

        // Check if the payment method is not UPI but UPI instrument details are provided
        if ($method !== Payment\Method::UPI)
        {
            throw new Exception\BadRequestValidationFailureException(
                'UPI app details not allowed'
            );
        }

        // Validate UPI apps
        $this->validateArrayValues(
            $upiInstrument[\RZP\Models\Offer\Constants::APPS] ?? null,
            Validator::$allowedPspApps,
            'UPI apps'
        );

        // Validate UPI payer account types
        $this->validateArrayValues(
            $upiInstrument[\RZP\Models\Upi\Turbo\Constants::PAYER_ACCOUNT_TYPE] ?? null,
                Validator::$allowedPayerAccountType,
            'UPI payerAccountTypes'
        );

        if ($input[Entity::TYPE] !== \RZP\Models\Offer\Constants::CASHBACK_OFFER){
            throw new Exception\BadRequestValidationFailureException(
                'UPI offers are only allowed for cashback');
        }

    }

    private function validateArrayValues($values, array $allowedValues, string $type): void
    {
        if ($values !== null) {
            if (!is_array($values)) {
                throw new Exception\BadRequestValidationFailureException(
                    "$type should be an array"
                );
            }

            foreach ($values as $value) {
                if (!in_array($value, $allowedValues, true)) {
                    throw new Exception\BadRequestValidationFailureException(
                        "Invalid $type: $value"
                    );
                }
            }
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
        if ($this->entity->merchant === null or
            $this->entity->getOfferCreateExpValue() === true){
            return null;
        }

        $isInsuranceCategory = $this->entity->merchant->isInsuranceCategory($this->entity->merchant->getCategory());

        if($isInsuranceCategory === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Offer creation is not allowed for this Merchant category');
        }
    }

    protected function validateOfferFeatureBlock(array $input)
    {
        if (($this->entity->merchant === null) or
            ($this->entity->getOfferCreateExpValue() === true))
        {
            return null;
        }
        $hasBlockingFeature = $this->entity->merchant->isFeatureEnabled(Constants::BLOCK_OFFER_CREATION);

        if($hasBlockingFeature === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Offer creation is not allowed for Merchant');
        }
    }
}
