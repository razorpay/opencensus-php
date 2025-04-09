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
    const MERCHANT_ID = "merchant_id";

    const RZP_ORG = '100000razorpay';

    const USER_NOTES_KEYS_COLUMNS = 'user_notes_key_columns';
    const PAYMENT_OPTIONAL_KEYS_COLUMNS = 'payment_optional_keys_columns';
    const PAYMENT_NOTES_KEY_COLUMNS = 'payment_notes_keys_columns';
    const DCS_CONFIG_SERVICE = 'dcs_config_service';

    // types
    const BOOLEAN = "bool";
    const INTEGER = "int";
    const STRING = "string";

    // default values for types
    const DEFAULT_VALUES = [
        self::BOOLEAN => false,
        self::INTEGER => 0,
        self::STRING => "",
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
        ],
        "rzp/pg/org/admindashboard/banking_program/InstrumentRequest" => [
            "default_iir_config" => [
                "type" => self::STRING,
                "short_key" => DcsConstants::OrgDefaultIIR,
                "description" => "Each org will be having some default instrument requests set which would be triggered
                post a merchant gets activated for that org. These requests will be triggered only when the
                `default_instruments_enablement` flag is enabled on the org and merchant level flag for disabling the
                same is disabled"
            ],

        ],
        "rzp/pg/org/cards/banking_program/CardsConfig" => [
            "pass_custom_udf_fss" => [
                "type" => self::BOOLEAN,
                "short_key" => DcsConstants::CustomUDFFlagConfig,
                "description" => "enabling this feature on an org the merchants of the org will
                allow passing the information (merchant related to identify merchant) into UDF(1-5) and restrict
                the notes values and default value from being captured in the UDF"
            ],
        ],
        "rzp/pg/merchant/onboarding/banking_program/MerchantConfigDetails" => [
            "rectangular_logo_url" => [
                "type" => self::STRING,
                "short_key" => DcsConstants::RectangularLogoUrl,
                "description" => "Rectangular logo URLs will be stored here when the custom_merchant_upi_qr feature flag is enabled for the merchant."
            ],
        ],
        "rzp/pg/org/dashboard/banking_program/DormancyPeriodConfig" => [
            DcsConstants::DormancyPeriod => [
                "type" => self::INTEGER,
                "short_key" => DcsConstants::DormancyPeriod,
                "description" => "Dormancy period will be stored (in days) to determine user inactivity and auto-disable inactive admin users."
            ],
        ]
    ];
}
