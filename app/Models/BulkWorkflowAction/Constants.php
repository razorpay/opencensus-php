<?php

namespace RZP\Models\BulkWorkflowAction;

use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Merchant\Action;

class Constants
{
    const RISK_ATTRIBUTES         = "risk_attributes";
    const RISK_REASONS            = "risk_reasons";
    const RISK_TAG                = "risk_tag";
    const RISK_SOURCES            = "risk_sources";
    const TRIGGER_COMMUNICATION   = "trigger_communication";
    const CLEAR_RISK_TAGS         = "clear_risk_tags";
    
    const RISK_SOURCE_PREFIX      = "risk_source_";
    
    //risk source allowed values
    const RISK_SOURCE_MYSTERY_SHOPPING      = "mystery_shopping";
    const RISK_SOURCE_RISK_ENGINE_DEDUPE    = "risk_engine_dedupe";
    const RISK_SOURCE_RISK_ENGINE_RAS       = "risk_engine_ras";
    const RISK_SOURCE_SMART_COLLECT_FLAG    = "smart_collect_flag";
    const RISK_SOURCE_TXN_DEDUPE            = "txn_dedupe";
    const RISK_SOURCE_HIGH_FTS              = "high_fts";
    const RISK_SOURCE_HIGH_CTS              = "high_cts";
    const RISK_SOURCE_CUSTOMER_REPORT       = "customer_report";
    const RISK_SOURCE_OTHERS                = "others";
    
    
    const RISK_REASON_PREFIX                = "risk_reason_";
    
    //risk reason allowed values
    const RISK_REASON_HIGH_CTS              = "high_cts";
    const RISK_REASON_HIGH_FTS              = "high_fts";
    const RISK_REASON_OTHERS                = "others";
    const RISK_REASON_COPYRIGHT_INFRINGEMENT_OTHERS                         = "copyright_infringement_others";
    const RISK_REASON_MULTIPLE_TRANSACTION_PERFORMED_USING_SAME_CREDENTIALS = "multiple_transaction_performed_using_same_credentials";
    const RISK_REASON_NEEDS_CLARIFICATION_MAIL_WASNT_REVERTED_FOR_5_DAYS    = "needs_clarification_mail_wasn’t_reverted_for_5_days";
    const RISK_REASON_MCC_VIOLATION_OTHERS                                  = "mcc_violation_others";
    const RISK_REASON_SPIKE_IN_COMPLAINTS_ACROSS_OTHERS                     = "spike_in_complaints_across_others";
    const RISK_REASON_COPYRIGHT_INFRINGEMENT_TRADEMARK_VIOLATION            = "copyright_infringement_trademark_violation";
    const RISK_REASON_COPYRIGHT_INFRINGEMENT_PRICE_TOO_GOOD_TO_BE_TRUE      = "copyright_infringement_price_too_good_to_be_true";
    const RISK_REASON_MCC_VIOLATION__BUSINESS_MODEL_DEVIATION               = "mcc_violation__business_model_deviation";
    const RISK_REASON_MCC_VIOLATION_MANY_BUSINESSES_RUNNING_UNDER_ONE_MID   = "mcc_violation_many_businesses_running_under_one_mid";
    const RISK_REASON_MCC_VIOLATION_RESTRICTED_BUSINESS_MODEL_              = "mcc_violation_restricted_business_model_";
    const RISK_REASON_SPIKE_IN_COMPLAINTS_ACROSS_END_USER                   = "spike_in_complaints_across_end_user";
    const RISK_REASON_SPIKE_IN_COMPLAINTS_ACROSS_CYBER_CRIME                = "spike_in_complaints_across_cyber_crime";
    const RISK_REASON_SPIKE_IN_COMPLAINTS_ACROSS_DISPUTES                   = "spike_in_complaints_across_disputes";
    const RISK_REASON_SPIKE_IN_COMPLAINTS_ACROSS_CBS                        = "spike_in_complaints_across_cb’s";
    const RISK_REASON_SPIKE_IN_COMPLAINTS_ACROSS_BANKS                      = "spike_in_complaints_across_banks";
    
    const RISK_TAG_PREFIX = "risk_tag_";
    
    //risk tag allowed values
    const RISK_TAG_RISK_REVIEW_SUSPEND      = "risk_review_suspend";
    const RISK_TAG_RISK_REVIEW_ONHOLD       = "risk_review_onhold";
    const RISK_TAG_RISK_REVIEW_DISABLE_LIVE = "risk_review_disable_live";
    const RISK_TAG_MS_RISK_REVIEW_SUSPEND   = "ms_risk_review_suspend";
    const RISK_TAG_MS_RISK_REVIEW_WATCHLIST = "ms_risk_review_watchlist";
    const RISK_TAG_SC_RISK_REVIEW_WATCHLIST = "sc_risk_review_watchlist";
    const RISK_TAG_RISK_REVIEW_WATCHLIST    = "risk_review_watchlist";
    const RISK_TAG_SC_RISK_REVIEW_ONHOLD    = "sc_risk_review_onhold";
    const RISK_TAG_SC_RISK_REVIEW_SUSPEND   = "sc_risk_review_suspend";
    const RISK_TAG_SC_FEATURE_BLOCKED       = "sc_feature_blocked";
    const RISK_TAG_NO_TAG                   = "";
    
    //trigger communication values
    const SMS       = "sms";
    const MAIL      = "email";
    const DASHBOARD = "dashboard";
    const WHATSAPP  = "whatsapp";
    
    const RISK_REASON_LIST = [
        self::RISK_REASON_HIGH_CTS,
        self::RISK_REASON_HIGH_FTS,
        self::RISK_REASON_OTHERS,
        self::RISK_REASON_COPYRIGHT_INFRINGEMENT_OTHERS,
        self::RISK_REASON_MULTIPLE_TRANSACTION_PERFORMED_USING_SAME_CREDENTIALS,
        self::RISK_REASON_NEEDS_CLARIFICATION_MAIL_WASNT_REVERTED_FOR_5_DAYS,
        self::RISK_REASON_MCC_VIOLATION_OTHERS,
        self::RISK_REASON_SPIKE_IN_COMPLAINTS_ACROSS_OTHERS,
        self::RISK_REASON_COPYRIGHT_INFRINGEMENT_TRADEMARK_VIOLATION,
        self::RISK_REASON_COPYRIGHT_INFRINGEMENT_PRICE_TOO_GOOD_TO_BE_TRUE,
        self::RISK_REASON_MCC_VIOLATION__BUSINESS_MODEL_DEVIATION,
        self::RISK_REASON_MCC_VIOLATION_MANY_BUSINESSES_RUNNING_UNDER_ONE_MID,
        self::RISK_REASON_MCC_VIOLATION_RESTRICTED_BUSINESS_MODEL_,
        self::RISK_REASON_SPIKE_IN_COMPLAINTS_ACROSS_END_USER,
        self::RISK_REASON_SPIKE_IN_COMPLAINTS_ACROSS_CYBER_CRIME,
        self::RISK_REASON_SPIKE_IN_COMPLAINTS_ACROSS_DISPUTES,
        self::RISK_REASON_SPIKE_IN_COMPLAINTS_ACROSS_CBS,
        self::RISK_REASON_SPIKE_IN_COMPLAINTS_ACROSS_BANKS,
    ];
    
    const RISK_TAG_LIST = [
        self::RISK_TAG_RISK_REVIEW_SUSPEND,
        self::RISK_TAG_RISK_REVIEW_ONHOLD,
        self::RISK_TAG_RISK_REVIEW_DISABLE_LIVE,
        self::RISK_TAG_MS_RISK_REVIEW_SUSPEND,
        self::RISK_TAG_MS_RISK_REVIEW_WATCHLIST,
        self::RISK_TAG_SC_RISK_REVIEW_WATCHLIST,
        self::RISK_TAG_RISK_REVIEW_WATCHLIST,
        self::RISK_TAG_SC_RISK_REVIEW_ONHOLD,
        self::RISK_TAG_SC_RISK_REVIEW_SUSPEND,
        self::RISK_TAG_SC_FEATURE_BLOCKED,
        self::RISK_TAG_NO_TAG
    ];
    
    const RISK_SOURCE_LIST = [
        self::RISK_SOURCE_MYSTERY_SHOPPING,
        self::RISK_SOURCE_RISK_ENGINE_DEDUPE,
        self::RISK_SOURCE_RISK_ENGINE_RAS,
        self::RISK_SOURCE_SMART_COLLECT_FLAG,
        self::RISK_SOURCE_TXN_DEDUPE,
        self::RISK_SOURCE_HIGH_FTS,
        self::RISK_SOURCE_HIGH_CTS,
        self::RISK_SOURCE_CUSTOMER_REPORT,
        self::RISK_SOURCE_OTHERS,
    ];
    
    const TRIGGER_COMMUNICATION_LIST = [
        self::SMS,
        self::MAIL,
        self::DASHBOARD,
        self::WHATSAPP,
    ];
    
    const BULK_WORKFLOW_ACTION_ROUTE_NAME = 'execute_bulk_action';
    const BULK_WORKFLOW_ACTION_ROUTE_CONTROLLER = 'RZP\Http\Controllers\BulkActionController@executeBulkAction';
    
    const BULK_WORKFLOW_ACTION_PERMISSION_NAME = [
        Action::LIVE_ENABLE     => Permission::EXECUTE_MERCHANT_TOGGLE_LIVE_BULK,
        Action::LIVE_DISABLE    => Permission::EXECUTE_MERCHANT_TOGGLE_LIVE_BULK,
        Action::HOLD_FUNDS      => Permission::EXECUTE_MERCHANT_HOLD_FUNDS_BULK,
        Action::RELEASE_FUNDS   => Permission::EXECUTE_MERCHANT_HOLD_FUNDS_BULK,
        Action::SUSPEND         => Permission::EXECUTE_MERCHANT_SUSPEND_BULK,
        Action::UNSUSPEND       => Permission::EXECUTE_MERCHANT_SUSPEND_BULK,
    ];
    
    const BULK_WORKFLOW_ACTION_PERMISSION = [
        Permission::EXECUTE_MERCHANT_TOGGLE_LIVE_BULK,
        Permission::EXECUTE_MERCHANT_HOLD_FUNDS_BULK,
        Permission::EXECUTE_MERCHANT_SUSPEND_BULK,
    ];
    
    const MERCHANT_ACTION_ROUTE_NAME = 'execute_risk_workflow_action';
    const MERCHANT_ACTION_ROUTE_CONTROLLER = 'RZP\Http\Controllers\MerchantController@executeRiskAction';
    
    const PARENT_PREFIX = "parent_";
    
    const BULK_MERCHANT_WORKFLOW_ACTION_NEW_FLOW_FEATURE = "BULK_MERCHANT_WORKFLOW_ACTION";
}