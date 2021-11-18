<?php

namespace RZP\Models\RiskWorkflowAction;

use RZP\Models\Merchant\Action;

class Constants
{
    const RISK_ACTION_ROUTE_NAME        = 'merchant_actions';
    const RISK_ACTION_ROUTE_CONTROLLER  = 'RZP\Http\Controllers\MerchantController@putAction';

    const ACTION                 = 'action';
    const RISK_ATTRIBUTES        = 'risk_attributes';
    const RISK_REASON            = 'risk_reason';
    const RISK_TAG               = 'risk_tag';
    const RISK_SOURCE            = 'risk_source';
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
        self::RISK_SOURCE_OTHERS;

    // Risk reasons
    const RISK_REASON_COPYRIGHT_INFRINGEMENT = 'copyright_infringement_or_trademark_violation_or_price_too_good_to_be_true';
    const RISK_REASON_MULTIPLE_TRANSACTION   = 'multiple_transaction_performed_using_same_credentials';
    const RISK_REASON_NEEDS_CLARIFICATION    = 'needs_clarification_mail_wasnt_reverted_for_5_days';

    const RISK_REASON_COMPLIANCE_DOCUMENT_DISCREPANCY = 'compliance_document_discrepancy';

    const RISK_REASON_SPIKE_IN_COMPLAINTS  = 'spike_in_complaints_across_end_user_or_cyber_crime_or_chargebacks_or_banks';

    const RISK_REASON_MCC_VIOLATION = 'mcc_violation_business_model_deviation_or_restricted_business_model_or_many_businesses_running_under_one_mid';

    const RISK_REASON_HIGH_CTS = 'high_cts';
    const RISK_REASON_HIGH_FTS = 'high_fts';
    const RISK_REASON_OTHERS   = 'others';

    //risk reason allowed values
    const RISK_REASONS_CSV =
        self::RISK_REASON_COPYRIGHT_INFRINGEMENT . ',' .
        self::RISK_REASON_MULTIPLE_TRANSACTION . ',' .
        self::RISK_REASON_NEEDS_CLARIFICATION . ',' .
        self::RISK_REASON_COMPLIANCE_DOCUMENT_DISCREPANCY . ',' .
        self::RISK_REASON_SPIKE_IN_COMPLAINTS . ',' .
        self::RISK_REASON_MCC_VIOLATION . ',' .
        self::RISK_REASON_HIGH_CTS . ',' .
        self::RISK_REASON_HIGH_FTS . ',' .
        self::RISK_REASON_OTHERS;

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

    const RISK_ACTIONS_CSV =
        Action::SUSPEND . ',' .
        Action::HOLD_FUNDS . ',' .
        Action::LIVE_DISABLE . ',' .
        Action::UNSUSPEND . ',' .
        Action::RELEASE_FUNDS . ',' .
        Action::DISABLE_INTERNATIONAL . ',' .
        Action::ENABLE_INTERNATIONAL . ',' .
        Action::LIVE_ENABLE;

    const RISK_SOURCE_PREFIX      = 'risk_source_';
    const RISK_REASON_PREFIX      = 'risk_reason_';
    const RISK_TAG_PREFIX         = 'risk_tag_';

    const BULK_WORKFLOW_GROUP_TAG_PREFIX = 'bulk_workflow_group_';

    const MERCHANT_ID             = 'merchant_id';
    const BULK_WORKFLOW_ACTION_ID = 'bulk_workflow_action_id';

    const BULK_RISK_ACTION_INDIVIDUAL_WORKFLOW_MAKER_EMAIL = 'BULK_RISK_ACTION_INDIVIDUAL_WORKFLOW_MAKER_EMAIL';

    const BULK_WORKFLOW_DETAILS_TPL = 'BULK_WORKFLOW_DETAILS:: MAKER: %s CHECKER: %s LINK: %s';

    const CREATE_DESTRUCTIVE_RISK_ATTRIBUTES_VALIDATOR          = 'create_destructive_risk_attributes';
    const CREATE_CONSTRUCTIVE_RISK_ATTRIBUTES_VALIDATOR         = 'create_constructive_risk_attributes';
    const CREATE_ENABLE_INTERNATIONAL_RISK_ATTRIBUTES_VALIDATOR = 'create_enable_international_risk_attributes';
    const CREATE_DISABLE_INTERNATIONAL_RISK_ATTRIBUTES_VALIDATOR = 'create_disable_international_risk_attributes';

    // Risk workflow statuses
    const EXECUTED    = 'EXECUTED';
    const INVALIDATED = 'INVALIDATED';
    const FAILED      = 'FAILED';
}
