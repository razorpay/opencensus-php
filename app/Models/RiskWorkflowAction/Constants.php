<?php

namespace RZP\Models\RiskWorkflowAction;

use RZP\Models\Merchant\Action;
use RZP\Models\Feature\Constants as FeatureConstant;

class Constants
{
    const RISK_ACTION_ROUTE_NAME        = 'merchant_actions';
    const RISK_ACTION_ROUTE_CONTROLLER  = 'RZP\Http\Controllers\MerchantController@putAction';

    const ACTION                 = 'action';
    const RISK_ATTRIBUTES        = 'risk_attributes';
    const RISK_REASON            = 'risk_reason';
    const RISK_SUB_REASON        = 'risk_sub_reason';
    const RISK_TAG               = 'risk_tag';
    const RISK_SOURCE            = 'risk_source';
    const RISK_WORKFLOW_TAGS     = 'workflow_tags';
    const TRIGGER_COMMUNICATION  = 'trigger_communication';
    const CLEAR_RISK_TAGS        = 'clear_risk_tags';

    // Risk source
    const RISK_SOURCE_MYSTERY_SHOPPING      = 'mystery_shopping';
    const RISK_SOURCE_RISK_ENGINE_DEDUPE    = 'risk_engine_dedupe';
    const RISK_SOURCE_RISK_ENGINE_RAS       = 'risk_engine_ras';
    const RISK_SOURCE_SMART_COLLECT_FLAG    = 'smart_collect_flag';
    const RISK_SOURCE_TXN_DEDUPE            = 'txn_dedupe';
    const RISK_SOURCE_HIGH_FTS              = 'high_fts';
    const RISK_SOURCE_HIGH_CTS              = 'high_cts';
    const RISK_SOURCE_CUSTOMER_REPORT       = 'customer_report';
    const RISK_SOURCE_TXN_MONITORING        = 'transaction_monitoring';
    const RISK_SOURCE_BANK_NW_ALERTS        = 'bank_or_network_alerts';
    const RISK_SOURCE_OTHERS                = 'others';
    const RISK_SOURCE_MERCHANT_RISK_ALERTS  = 'merchant_risk_alerts';

    //risk source allowed values
    const RISK_SOURCES_CSV =
        self::RISK_SOURCE_MYSTERY_SHOPPING . ',' .
        self::RISK_SOURCE_RISK_ENGINE_DEDUPE . ',' .
        self::RISK_SOURCE_RISK_ENGINE_RAS . ',' .
        self::RISK_SOURCE_SMART_COLLECT_FLAG . ',' .
        self::RISK_SOURCE_TXN_DEDUPE . ',' .
        self::RISK_SOURCE_HIGH_FTS . ',' .
        self::RISK_SOURCE_HIGH_CTS . ',' .
        self::RISK_SOURCE_CUSTOMER_REPORT . ',' .
        self::RISK_SOURCE_TXN_MONITORING . ',' .
        self::RISK_SOURCE_BANK_NW_ALERTS . ',' .
        self::RISK_SOURCE_MERCHANT_RISK_ALERTS . ',' .
        self::RISK_SOURCE_OTHERS;

    // Risk Reasons
    const RISK_REASON_FRAUD_BEHAVIOUR       = 'suspicious_or_fraudulent_business_behaviour';
    const RISK_REASON_ANOMALY_TXN_PATTERN   = 'anomaly_in_transaction_pattern';
    const RISK_REASON_RAZORPAYX             = 'razorpay_x';
    const RISK_REASON_CHARGEBACK            = 'chargeback,disputes_and_fraud';
    const RISK_REASON_EXTERNAL_FACTORS      = 'external_factors';
    const RISK_REASON_CPV                   = 'CPV_checks';
    const RISK_REASON_KYC_LAPSES            = 'KYC_lapses';
    const RISK_REASON_INADEQUATE_CLARIFICATION = 'inadequate_response/risk_clarification';
    const RISK_REASON_MERCHANT_REQUEST      = 'merchant_request';

    // Risk Sub Reasons
    const RISK_SUB_REASON_BRAND             = 'brand_imposter';
    const RISK_SUB_REASON_INFRINGEMENT      = 'infringement';
    const RISK_SUB_REASON_BUSINESS_MODEL    = 'business_model_deviation';
    const RISK_SUB_REASON_PROHIBITED        = 'prohibited_and_restricted_business_category';
    const RISK_SUB_REASON_MERCHANT_POLICY   = 'standard_merchant_policy_not_updated';
    const RISK_SUB_REASON_DOCUMENTS         = 'illegitimate_documents';
    const RISK_SUB_REASON_API_KEY_MISUSE    = 'API_key_misuse_different_website_or_APP_used';
    const RISK_SUB_REASON_CHINESE_MERCHANT  = 'chinese_risky_merchant';
    const RISK_SUB_REASON_BLACKLIST_MATCH   = 'match_with_blacklist';
    const RISK_SUB_REASON_KYC_MISSING       = 'KYC_missing';
    const RISK_SUB_REASON_WRONG_MCC         = 'wrong_MCC_allotment';
    const RISK_SUB_REASON_BANK_MISMATCH     = 'bank_details_mismatch';
    const RISK_SUB_REASON_TXN_PATTERN       = 'abnormal_transaction_pattern';
    const RISK_SUB_REASON_TEST_TXN          = 'surge_in_low_value_or_test_transaction';
    const RISK_SUB_REASON_DORMANT           = 'dormant_more_than_three_months';
    const RISK_SUB_REASON_TXN_SIZE          = 'transaction_size_not_aligned_with_merchant_profile';
    const RISK_SUB_REASON_CARD_ENCASHMENT   = 'card/wallet_encashment';
    const RISK_SUB_REASON_MULTI_PAYOUTS     = 'multiple_payouts_with_same_beneficiary';
    const RISK_SUB_REASON_MULTI_VA          = 'multiple_VA_creations';
    const RISK_SUB_REASON_FUND_LOADING      = 'abnormal_fund_loading_pattern';
    const RISK_SUB_REASON_PAYOUT_PATTERN    = 'business_model_not_aligning_with_payout_pattern';
    const RISK_SUB_REASON_HIGH_CTS          = 'high_CTS';
    const RISK_SUB_REASON_HIGH_FTS          = 'high_FTS';
    const RISK_SUB_REASON_COMPLAINTS        = 'high_consumer_complaints';
    const RISK_SUB_REASON_REGULATORY        = 'regulatory';
    const RISK_SUB_REASON_LEA               = 'LEA';
    const RISK_SUB_REASON_BANK_ALERTS       = 'bank_or_payment_service_providers_alerts';
    const RISK_SUB_REASON_NETWORK_ALERTS    = 'network_alerts_VISA,MASTER,RUPAY,VAMP,GBPP,AML,Mastercard_match';
    const RISK_SUB_REASON_FREE_CREDITS      = 'abuse_of_free_credits';
    const RISK_SUB_REASON_NO_RESPONSE       = 'no_response_from_merchant_within_TAT';
    const RISK_SUB_REASON_UNSATISFIED_RESP  = 'merchant_response_not_satisfactory';
    const RISK_SUB_REASON_SUSPECTED_FINGERPRINT  = 'fingerprint_match_with_suspended_merchants';
    const RISK_SUB_REASON_SPIKE_IN_TRANSACTION  = 'sudden_spike_in_high_value_transactions';
    const RISK_SUB_REASON_NEGATIVE_CPV       = 'CPV_negative';
    const RISK_SUB_REASON_SUSPICIOUS_CPV     = 'CPV_referred_with_suspicious_reason';
    const RISK_SUB_REASON_APPROPRIATE_OR_NO_RESPONSE = 'appropriate_or_no_response_within_TAT';
    const RISK_SUB_REASON_NON_SERVICE        = 'non_service_delivery';
    const RISK_SUB_REASON_FAKE_WEBSITE       = 'suspected_fake_website';
    const RISK_SUB_REASON_MERCHANT_REQUEST      = 'merchant_request';

    const RISK_REASONS_MAP = [
        self::RISK_REASON_FRAUD_BEHAVIOUR   =>  [
            self::RISK_SUB_REASON_BRAND,
            self::RISK_SUB_REASON_INFRINGEMENT,
            self::RISK_SUB_REASON_BUSINESS_MODEL,
            self::RISK_SUB_REASON_PROHIBITED,
            self::RISK_SUB_REASON_MERCHANT_POLICY,
            self::RISK_SUB_REASON_DOCUMENTS,
            self::RISK_SUB_REASON_API_KEY_MISUSE,
            self::RISK_SUB_REASON_CHINESE_MERCHANT,
            self::RISK_SUB_REASON_BLACKLIST_MATCH,
            self::RISK_SUB_REASON_SUSPECTED_FINGERPRINT,
            self::RISK_SUB_REASON_FREE_CREDITS,
            self::RISK_SUB_REASON_NON_SERVICE,
            self::RISK_SUB_REASON_FAKE_WEBSITE,
        ],
        self::RISK_REASON_KYC_LAPSES  =>   [
            self::RISK_SUB_REASON_KYC_MISSING,
            self::RISK_SUB_REASON_WRONG_MCC,
            self::RISK_SUB_REASON_BANK_MISMATCH,
        ],
        self::RISK_REASON_ANOMALY_TXN_PATTERN   =>  [
            self::RISK_SUB_REASON_TXN_PATTERN,
            self::RISK_SUB_REASON_TEST_TXN,
            self::RISK_SUB_REASON_DORMANT,
            self::RISK_SUB_REASON_TXN_SIZE,
            self::RISK_SUB_REASON_CARD_ENCASHMENT,
            self::RISK_SUB_REASON_SPIKE_IN_TRANSACTION,
        ],
        self::RISK_REASON_RAZORPAYX   =>  [
            self::RISK_SUB_REASON_MULTI_PAYOUTS,
            self::RISK_SUB_REASON_MULTI_VA,
            self::RISK_SUB_REASON_FUND_LOADING,
            self::RISK_SUB_REASON_PAYOUT_PATTERN,
        ],
        self::RISK_REASON_CHARGEBACK    =>  [
            self::RISK_SUB_REASON_HIGH_FTS,
            self::RISK_SUB_REASON_HIGH_CTS,
            self::RISK_SUB_REASON_COMPLAINTS,
        ],
        self::RISK_REASON_EXTERNAL_FACTORS  =>  [
            self::RISK_SUB_REASON_REGULATORY,
            self::RISK_SUB_REASON_LEA,
            self::RISK_SUB_REASON_BANK_ALERTS,
            self::RISK_SUB_REASON_NETWORK_ALERTS,
        ],
        self::RISK_REASON_INADEQUATE_CLARIFICATION =>  [
            self::RISK_SUB_REASON_NO_RESPONSE,
            self::RISK_SUB_REASON_UNSATISFIED_RESP,
        ],
        self::RISK_REASON_CPV    =>  [
            self::RISK_SUB_REASON_NEGATIVE_CPV,
            self::RISK_SUB_REASON_SUSPICIOUS_CPV,
            self::RISK_SUB_REASON_APPROPRIATE_OR_NO_RESPONSE,
        ],
        self::RISK_REASON_MERCHANT_REQUEST => [
            self::RISK_SUB_REASON_MERCHANT_REQUEST,
        ]
    ];

    const ACTIONS_FEATURES_MAP = [
        Action::ENABLE_ACCEPT_ONLY_3DS_PAYMENTS    => FeatureConstant::ACCEPT_ONLY_3DS_PAYMENTS,
        Action::ENABLE_ES_ON_DEMAND                => FeatureConstant::ES_ON_DEMAND,
        Action::ENABLE_PAYOUT                      => FeatureConstant::PAYOUT,
        Action::ENABLE_MARKETPLACE                 => FeatureConstant::MARKETPLACE,
        Action::ENABLE_DIRECT_TRANSFER             => FeatureConstant::DIRECT_TRANSFER,
        Action::DISABLE_ACCEPT_ONLY_3DS_PAYMENTS   => FeatureConstant::ACCEPT_ONLY_3DS_PAYMENTS,
        Action::DISABLE_ES_ON_DEMAND               => FeatureConstant::ES_ON_DEMAND,
        Action::DISABLE_PAYOUT                     => FeatureConstant::PAYOUT,
        Action::DISABLE_MARKETPLACE                => FeatureConstant::MARKETPLACE,
        Action::DISABLE_DIRECT_TRANSFER            => FeatureConstant::DIRECT_TRANSFER,
    ];

    const CONSTRUCTIVE_FEATURES = [
        Action::ENABLE_ES_ON_DEMAND,
        Action::ENABLE_PAYOUT,
        Action::ENABLE_MARKETPLACE,
        Action::ENABLE_DIRECT_TRANSFER,
        Action::DISABLE_ACCEPT_ONLY_3DS_PAYMENTS
    ];

    const DESTRUCTIVE_FEATURES = [
        Action::DISABLE_ES_ON_DEMAND,
        Action::DISABLE_PAYOUT,
        Action::DISABLE_MARKETPLACE,
        Action::DISABLE_DIRECT_TRANSFER,
        Action::ENABLE_ACCEPT_ONLY_3DS_PAYMENTS,
    ];

    // Risk tags
    const RISK_TAG_RISK_REVIEW_SUSPEND          = 'risk_review_suspend';
    const RISK_TAG_RISK_REVIEW_ONHOLD           = 'risk_review_onhold';
    const RISK_TAG_RISK_REVIEW_DISABLE_LIVE     = 'risk_review_disable_live';
    const RISK_TAG_RISK_REVIEW_WATCHLIST        = 'risk_review_watchlist';
    const RISK_TAG_SC_RISK_REVIEW_SUSPEND       = 'sc_risk_review_suspend';
    const RISK_TAG_SC_RISK_REVIEW_ONHOLD        = 'sc_risk_review_onhold';
    const RISK_TAG_SC_RISK_REVIEW_WATCHLIST     = 'sc_risk_review_watchlist';
    const RISK_TAG_SC_FEATURE_BLOCKED           = 'sc_feature_blocked';
    const RISK_TAG_MS_RISK_REVIEW_SUSPEND       = 'ms_risk_review_suspend';
    const RISK_TAG_MS_RISK_REVIEW_WATCHLIST     = 'ms_risk_review_watchlist';
    const RISK_TAG_MS_RISK_REVIEW_ONHOLD        = 'ms_risk_review_onhold';
    const RISK_TAG_MS_RISK_REVIEW_DISABLE_LIVE  = 'ms_risk_review_disable_live';
    const RISK_TAG_INTERNATIONAL_DISABLEMENT    = 'risk_international_disablement';

    //Old Tags, No longer in use
    const RISK_REVIEW                           = 'risk_review';
    const RISK_SUSPECT                          = 'risk_suspect';

    const FRAUD_WHITELIST_TAGS = [
        self::RISK_TAG_RISK_REVIEW_WATCHLIST,
        self::RISK_TAG_SC_RISK_REVIEW_WATCHLIST,
        self::RISK_TAG_SC_FEATURE_BLOCKED,
        self::RISK_TAG_MS_RISK_REVIEW_WATCHLIST,
        self::RISK_REVIEW,
        self::RISK_SUSPECT
    ];

    //risk tag allowed values
    const RISK_TAGS_CSV =
        self::RISK_TAG_RISK_REVIEW_SUSPEND . ',' .
        self::RISK_TAG_RISK_REVIEW_ONHOLD . ',' .
        self::RISK_TAG_RISK_REVIEW_DISABLE_LIVE . ',' .
        self::RISK_TAG_RISK_REVIEW_WATCHLIST . ',' .
        self::RISK_TAG_SC_RISK_REVIEW_SUSPEND . ',' .
        self::RISK_TAG_SC_RISK_REVIEW_ONHOLD . ',' .
        self::RISK_TAG_SC_RISK_REVIEW_WATCHLIST . ',' .
        self::RISK_TAG_SC_FEATURE_BLOCKED . ',' .
        self::RISK_TAG_MS_RISK_REVIEW_SUSPEND . ',' .
        self::RISK_TAG_MS_RISK_REVIEW_ONHOLD . ',' .
        self::RISK_TAG_MS_RISK_REVIEW_DISABLE_LIVE . ',' .
        self::RISK_TAG_MS_RISK_REVIEW_WATCHLIST;

    const RISK_ACTIONS_CSV_WITH_RISK_ATTRIBUTES=
        Action::SUSPEND . ',' .
        Action::HOLD_FUNDS . ',' .
        Action::LIVE_DISABLE . ',' .
        Action::UNSUSPEND . ',' .
        Action::RELEASE_FUNDS . ',' .
        Action::LIVE_ENABLE;

    const RISK_ACTIONS_CSV =
        Action::SUSPEND . ',' .
        Action::HOLD_FUNDS . ',' .
        Action::LIVE_DISABLE . ',' .
        Action::UNSUSPEND . ',' .
        Action::RELEASE_FUNDS . ',' .
        Action::DISABLE_INTERNATIONAL . ',' .
        Action::ENABLE_INTERNATIONAL . ',' .
        Action::ENABLE_ACCEPT_ONLY_3DS_PAYMENTS . ',' .
        Action::ENABLE_ES_ON_DEMAND . ',' .
        Action::ENABLE_MARKETPLACE . ',' .
        Action::ENABLE_DIRECT_TRANSFER . ',' .
        Action::ENABLE_PAYOUT . ',' .
        Action::DISABLE_ACCEPT_ONLY_3DS_PAYMENTS . ',' .
        Action::DISABLE_ES_ON_DEMAND . ',' .
        Action::DISABLE_MARKETPLACE . ',' .
        Action::DISABLE_DIRECT_TRANSFER . ',' .
        Action::DISABLE_PAYOUT . ',' .
        Action::LIVE_ENABLE;

    const RISK_SOURCE_PREFIX      = 'risk_source_';
    const RISK_REASON_PREFIX      = 'risk_reason_';
    const RISK_SUB_REASON_PREFIX  = 'risk_sub_reason_';
    const RISK_TAG_PREFIX         = 'risk_tag_';

    const BULK_WORKFLOW_GROUP_TAG_PREFIX = 'bulk_workflow_group_';

    const MERCHANT_ID             = 'merchant_id';
    const BULK_WORKFLOW_ACTION_ID = 'bulk_workflow_action_id';

    const BULK_RISK_ACTION_INDIVIDUAL_WORKFLOW_MAKER_EMAIL = 'BULK_RISK_ACTION_INDIVIDUAL_WORKFLOW_MAKER_EMAIL';

    const BULK_WORKFLOW_DETAILS_TPL = 'BULK_WORKFLOW_DETAILS:: MAKER: %s CHECKER: %s LINK: %s';

    const CREATE_DESTRUCTIVE_RISK_ATTRIBUTES_VALIDATOR              = 'create_destructive_risk_attributes';
    const CREATE_CONSTRUCTIVE_RISK_ATTRIBUTES_VALIDATOR             = 'create_constructive_risk_attributes';
    const CREATE_ENABLE_INTERNATIONAL_RISK_ATTRIBUTES_VALIDATOR     = 'create_enable_international_risk_attributes';
    const CREATE_DISABLE_INTERNATIONAL_RISK_ATTRIBUTES_VALIDATOR    = 'create_disable_international_risk_attributes';
    const CREATE_DESTRUCTIVE_FEATURES_RISK_ATTRIBUTES               = 'create_destructive_features_risk_attributes';

    // Risk workflow statuses
    const EXECUTED    = 'EXECUTED';
    const INVALIDATED = 'INVALIDATED';
    const FAILED      = 'FAILED';

    const MAKER_ADMIN_ID = 'maker_admin_id';
}
