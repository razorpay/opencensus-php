<?php

namespace RZP\Models\Offer;

/**
 * General constants for Merchant Model.
 */
final class Constants
{
    const INSTANT_OFFER = 'instant';
    const CASHBACK_OFFER = 'deferred';
    const ALREADY_DISCOUNTED = 'already_discounted';

    const SUBSCRIPTION_TYPE_VALUE_TO_ENUM_MAP = [
        'single' => 1,     // enum = 1
        'cycle' => 2,    // enum = 2
        'forever' => 3, // enum = 3
    ];

    const SUBSCRIPTION_TYPE_ENUM_TO_VALUE_MAP = [
        1 => 'single',     // enum = 1
        2 => 'cycle',    // enum = 2
        3 => 'forever', // enum = 3
    ];

    // Todo:: Add Benefit map for voucher benefit_type
    const API_OFFER_BENEFIT_MAP = [
        self::INSTANT_OFFER => self::BENEFIT_TYPE_DISCOUNT,
        self::CASHBACK_OFFER => self::BENEFIT_TYPE_CASHBACK,
        self::ALREADY_DISCOUNTED => self::BENEFIT_TYPE_ALREADY_DISCOUNTED,
    ];

    const BENEFIT_API_OFFER_MAP = [
        self::BENEFIT_TYPE_DISCOUNT           => Constants::INSTANT_OFFER,
        self::BENEFIT_TYPE_CASHBACK           => Constants::CASHBACK_OFFER,
        self::BENEFIT_TYPE_VOUCHER            => Constants::CASHBACK_OFFER,
        self::BENEFIT_TYPE_ALREADY_DISCOUNTED => Constants::ALREADY_DISCOUNTED,
    ];

    const BENEFIT_DISCOUNT_MAP = [
        self::BENEFIT_TYPE_DISCOUNT           => 'discount',
        self::BENEFIT_TYPE_CASHBACK           => 'cashback',
        self::BENEFIT_TYPE_ALREADY_DISCOUNTED => 'already_discounted',
        self::BENEFIT_TYPE_NO_COST_EMI        => 'no_cost_emi',
        self::BENEFIT_TYPE_LOW_COST_EMI       => 'low_cost_emi',
        self::BENEFIT_TYPE_VOUCHER            => 'vouchers',
    ];

    const METADATA = 'metadata';

    const NAME = 'name';

    const DISPLAY_NAME = 'display_name';

    const DESCRIPTION = 'description';

    const TERMS = 'terms';

    const TERMS_AND_CONDITIONS = 'tnc';

    const OFFER_ID = 'offer_id';

    const ADVERTISER_ID = 'advertiser_id';
    const CREATED_BY_ID = 'created_by_id';

    const STATE = 'state';

    const OFFER_ON = 'offer_on';

    const CURRENCY = 'currency';

    const SCHEDULES = 'schedules';

    const STARTS_AT = 'starts_at';

    const ENDS_AT = 'ends_at';
    const SPEC = 'spec';

    const TOTAL_USAGE = 'total_usage';

    const OFFER = 'offer';

    const OFFERS = 'offers';

    const OFFER_PUBLISHERS = 'offer_publishers';

    const CHANNEL_RZP_CHECKOUT = 'CHANNEL_RZP_CHECKOUT';
    const BENEFICIARY_TYPE_SELF = 'BENEFICIARY_TYPE_SELF';
    const PUBLISH = 'publish';
    const ALLOWED_CHANNELS = 'allowed_channels';
    const FUNDING = 'funding';
    const TYPE = 'type';
    const FUNDING_SPLIT = 'split';
    const FUNDING_BEARER = 'bearer';
    const VALUE_OPTION_PERCENTAGE = 'VALUE_OPTION_PERCENTAGE';
    const USER_TYPE_PUBLISHER = 'USER_TYPE_PUBLISHER';
    const VALUE = 'value';
    const BENEFITS_TYPES = 'benefits_types';
    const USAGE_LIMITS = 'usage_limits';

    const BENEFIT_TYPE_NO_COST_EMI = 'BENEFIT_TYPE_NO_COST_EMI';
    const BENEFIT_TYPE_LOW_COST_EMI = 'BENEFIT_TYPE_LOW_COST_EMI';
    const BENEFIT_TYPE_DISCOUNT = 'BENEFIT_TYPE_DISCOUNT';
    const BENEFIT_TYPE_CASHBACK = 'BENEFIT_TYPE_CASHBACK';
    const BENEFIT_TYPE_ALREADY_DISCOUNTED = 'BENEFIT_TYPE_ALREADY_DISCOUNTED';
    const BENEFIT_TYPE_VOUCHER = 'BENEFIT_TYPE_VOUCHER';

    const MAXIMUM_VALUE = 'maximum_value';
    const ON = 'on';
    const LIMIT_TYPE = 'limit_type';

    const LIMIT_ON_OFFER = 'LIMIT_ON_OFFER';
    const LIMIT_ON_CARD_NUMBER = 'LIMIT_ON_CARD_NUMBER';
    const LIMIT_TYPE_COUNT = 'LIMIT_TYPE_COUNT';

    const RULE_GROUPS = 'rule_groups';
    const RULES = 'rules';

    const STAGE_DISCOVER = 'STAGE_DISCOVER';
    const STAGE_AVAIL = 'STAGE_AVAIL';

    const STAGE_REDEEM = 'STAGE_REDEEM';
    const DISCOUNT = 'discount';
    const CASHBACK = 'cashback';
    const NO_COST_EMI = 'no_cost_emi';
    const LOW_COST_EMI = 'low_cost_emi';
    const PERCENTAGE_DISCOUNT = 'percent_discount';
    const FLAT_DISCOUNT = 'flat_discount';
    const MAX_DISCOUNT = 'max_discount';
    const APPLICABLE_ON = 'applicable_on';
    const WHEN = 'when_expression';
    const THEN = 'then';

    const TENURE = 'tenure';
    const BLOCKING = 'continue_txn_on_failure';
    const OFFER_TYPE = 'offer_type';
    const OFFER_TYPE_STAGE_REGULAR = 'OFFER_TYPE_STAGE_REGULAR';
    const OFFER_TYPE_STAGE_HIDDEN = 'OFFER_TYPE_STAGE_HIDDEN';

    const AUTO_APPLY = 'auto_apply';
    const CHANNEL_NAME = 'channel_name';

    const SUBSCRIPTION_TYPE_CYCLE = 'cycle';

    const SUBSCRIPTION_APPLICABLE_ON_BOTH = 'both';
    const TENURE_DISCOUNT_MAP = 'tenure_discount_map';

    const DISCOUNT_TO_AVAIL = 'discount_to_avail';

    const DISCOUNT_PERCENTAGE = 'discount_percentage';

    const SUBSCRIPTION_FIELDS = 'subscription_fields';

    const UPDATE_STATE_CREATED = 'STATE_PUBLISHED';

    const STATE_DISABLED = 'STATE_DEACTIVATED';

    const STATE_CREATED = 'STATE_CREATED';

    const CREATE_OFFER_DUAL_WRITE_EXP = 'app.offers_engine_dual_write_experiment_id';

    const OFFERS_ENGINE_FETCH_EXP              = 'app.offers_engine_fetch_offers_exp_id';
    const OFFERS_ENGINE_ADMIN_FETCH_OFFERS_EXP = 'app.offers_engine_admin_fetch_offers_exp_id';

    const OFFERS_ENGINE_VALIDATE_OFFER_EXP = 'app.offers_engine_validate_offer_exp_id';

    const OFFERS_ENGINE_REVERSE_SHADOW_EXP = 'app.offers_engine_reverse_shadow_exp_id';

    const OFFER_CREATE_READS_MIGRATION_EXP = 'app.offers_engine_create_read_migration_exp_id';

    const OFFER_MERCHANT_DASHBOARD_READS_MIGRATION_EXP = 'app.offers_engine_merchant_dashboard_read_migration_exp_id';

    const OFFERS_ENGINE_FIND_BY_PUBLIC_ID_MIGRATION_EXP = 'app.offers_engine_find_by_public_id_migration_exp_id';

    // FACT CONSTANTS

    // order fact
    const ORDER_FACT = 'order';
    const ORDER_TOTAL_AMOUNT = 'total_amount';
    const ORDER_CURRENCY = 'currency';

    const VALIDATE_CARD_NUMBER_REQUIRED_ERROR = 'MISSING_CUSTOMER_CARD_NUMBER_FACT';
    const VALIDATE_MISSING_FACT_ERROR = 'BAD_REQUEST_MISSING_FACT';

    // payment instrument
    const PAYMENT_INSTRUMENT_FACT = 'payment_instrument';
    const ISSUER = 'issuer';
    const METHOD = 'method';
    const CARD_TYPE = 'card_type';
    const CARD_NETWORK = 'card_network';
    const IS_CARD_INTERNATIONAL = 'is_card_international';
    const IIN = 'iin';
    const WALLET = 'wallet';
    const PROVIDER = 'provider';
    const CARD = 'card';

    const EMI_TENURE = 'emi_tenure';

    const CUSTOMER_FACT = 'customer';

    const CARD_NUMBER = 'card_number'; // has card PAR value

    const CARD_COBRANDING_PARTNER = 'card_cobranding_partner';

    const MOBILE_NUMBER = 'mobile_number';

    const EMAIL = 'email';

    const DUMMY_PAYMENT_CARD_NUMBER = '4111111111111111'; // for checkout validate call

    const SUBSCRIPTION_FACT = 'subscription';

    const VALIDATE_OFFER_CALLED = 'OE_VALIDATE_OFFER_CALLED';

    const VALIDATE_OFFER_RESPONSE = 'VALIDATE_OFFER_RESPONSE';

    const CALCULATED_DISCOUNT = 'calculated_discount';

    const STATUS = 'status';

    const STATUS_ACTIVE = 'STATUS_ACTIVE';

    const APPS = "apps";
    const ALL = "ALL";

    const UPI_APP = "UpiApp";

    const UPI_PAYER_ACCOUNT = "UpiPayerAccount";

    const PAYMENT_INSTRUMENT = "PaymentInstrument";

    const PLATFORM_AD_PUBLISHER = '100000razorpay';

    const IS_CARD_SAVED = 'is_saved';

}
