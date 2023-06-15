<?php

namespace RZP\Models\BankingConfig;


use RZP\Models\NetbankingConfig;
use RZP\Services\Dcs\Configurations\Constants as DcsConstants;


class Constants {

    // field values
    const KEY = "key";
    const SHORT_KEY = "short_key";
    const FIELD_NAME = "field_name";
    const FIELD_VALUE = "field_value";
    const FIELDS = "fields";
    const ENTITY_ID = "entity_id";

    const RZP_ORG = '100000razorpay';

    // types
    const BOOLEAN = "bool";
    const INTEGER = "int";

    // default values for types
    const DEFAULT_VALUES = [
        self::BOOLEAN => false,
        self::INTEGER => 0,
    ];


    // all configs
    const BANKING_CONFIGS = [
        NetbankingConfig\Constants::KEY => [
            NetbankingConfig\Constants::AUTO_REFUND_OFFSET => [
                "type" => self::INTEGER,
                "short_key" => DcsConstants::NetbankingConfigurations,
                "description" => "auto_refund_offset is used to store the delay after auto-refund should happen for a merchant. The existing feature flag 'nb_corporate_refund_delay is being enhanced to make the auto refund limit for CIB txns configurable"
            ]
        ],
        "rzp/pg/org/onboarding/banking_program/Config" => [
            "assign_custom_hardlimit" => [
                "type" => self::BOOLEAN,
                "short_key" => DcsConstants::CustomHardLimitConfigurations,
                "description" => "enabling this flag on org, allow them to change change the hard transaction limits for its merchants"
            ],
            "custom_transaction_limit_for_kyc_pending" => [
                "type" => self::INTEGER,
                "short_key" => DcsConstants::CustomHardLimitConfigurations,
                "description" => "This limit is the total amount for which its merchant can do collections and gets settled for"
            ]
        ]
    ];


}
