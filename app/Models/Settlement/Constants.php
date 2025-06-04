<?php

namespace RZP\Models\Settlement;

use RZP\Models\Currency\Currency;

class Constants {
    const LOGO_URL = 'logo_url';

    const SHOW_VIEW_SETTLEMENG_GUIDE_OPTION = 'settelement_guide';

    const SHOW_UTR = 'utr';

    const SHOW_ACC_NO = 'acc_no';

    const RAISE_REQUEST_ON_MAIL = 'raise_req_on_email';

    const RAISE_REQUEST_REDIRECT_LINK = 'raise_req_redirect';

    const CURRENCY_LOGO = 'currency_logo';

    const ORG_NAME = 'org_name';

    const ACTION = 'action';
    const EXECUTED = 'EXECUTED';
    const INVALIDATED = 'INVALIDATED';
    const FAILED = 'FAILED';
    const WORKFLOW_ACTION_ID = 'workflow_action_id';

    const ATTRIBUTES = 'attributes';

    const SETTLEMENT_WF_TAG = 'settlement_wf_tag';

    const CLEAR_RISK_TAGS = 'clear_risk_tags';

    const TRIGGER_COMMUNICATION = 'trigger_communication';

    const WF_ACTION_ROUTE_NAME        = 'merchant_actions';

    const WF_ACTION_ROUTE_CONTROLLER  = 'RZP\Http\Controllers\MerchantController@putAction';

    const ORG_DATA = [
        'MY' => [
            self::LOGO_URL => 'https://cdn.razorpay.com/static/assets/curlec/logo_invert.png',
            self::SHOW_VIEW_SETTLEMENG_GUIDE_OPTION => false,
            self::SHOW_ACC_NO => false,
            self::SHOW_UTR => false,
            self::RAISE_REQUEST_ON_MAIL => true,
            self::RAISE_REQUEST_REDIRECT_LINK => 'success@curlec.com',
            self::CURRENCY_LOGO => Currency::SYMBOL[Currency::MYR],
            self::ORG_NAME => 'Curlec',
        ],
        'IN' => [
            self::LOGO_URL => 'https://cdn.razorpay.com/logo_invert.png',
            self::SHOW_VIEW_SETTLEMENG_GUIDE_OPTION => true,
            self::SHOW_UTR => true,
            self::SHOW_ACC_NO => true,
            self::RAISE_REQUEST_ON_MAIL => false,
            self::RAISE_REQUEST_REDIRECT_LINK => 'https://dashboard.razorpay.com/#/app/dashboard#request',
            self::CURRENCY_LOGO => Currency::SYMBOL[Currency::INR],
            self::ORG_NAME => 'Razorpay'
        ]
    ];

    const RAZORX_SETL_FETCH_BY_ID_FROM_NSS_REVERSE_SHADOW = 'setl_fetch_by_id_from_nss_reverse_shadow';

    const RAZORX_SETL_FETCH_DETAILS_FROM_NSS_SHADOW = 'setl_fetch_details_from_nss_shadow';
    const RAZORX_SETL_FETCH_DETAILS_FROM_NSS_REVERSE_SHADOW = 'setl_fetch_details_from_nss_reverse_shadow';

    const RAZORX_SETL_FETCH_MULTIPLE_FROM_NSS_REVERSE_SHADOW = 'setl_fetch_multiple_from_nss_reverse_shadow';
    const RAZORX_SETL_FETCH_SOURCE_DETAILS_FROM_NSS_REVERSE_SHADOW = 'setl_fetch_source_details_from_nss_reverse_shadow';

    const RAZORX_SETL_GET_DETAILS_FROM_NSS_SHADOW = 'setl_get_details_from_nss_shadow';
    const RAZORX_SETL_GET_DETAILS_FROM_NSS_REVERSE_SHADOW = 'setl_get_details_from_nss_reverse_shadow';

    const RAZORX_SETL_AMOUNT_FROM_NSS_SHADOW = 'setl_amount_from_nss_shadow';
    const RAZORX_SETL_AMOUNT_FROM_NSS_REVERSE_SHADOW = 'setl_amount_from_nss_reverse_shadow';

    const RAZORX_VARIANT_ON = 'on';

    const GEFU_FILE_GENERATION_EXPERIMENT_ID = "gefu_file_generation_experiment_id";
}
