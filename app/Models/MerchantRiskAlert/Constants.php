<?php

namespace RZP\Models\MerchantRiskAlert;

class Constants
{
    const MERCHANT_FOH_KEY          = 'merchant_foh';
    const MERCHANT_FOH_WORKFLOW_KEY = 'merchant_foh_workflow_open';
    const MERCHANT_CREATED_AT       = 'merchant_created_at';
    const MERCHANT_MIN_AOV          = 'merchant_min_aov';
    const MERCHANT_MAX_AOV          = 'merchant_max_aov';
    const MERCHANT_HAS_AOV          = 'merchant_has_aov';

    const ACTION_MANUAL_FOH      = 'manual';
    const ACTION_AUTO_FOH        = 'auto';
    const ACTION_AUTO_REVIEW_FOH = 'auto_review';

    const MANUAL_FOH_TAG = 'manual_foh';
    const AUTO_FOH_TAG   = 'auto_foh';

    const MERCHANT_DETAIL_KEY = 'merchant_detail';

    const MUTEX_PREFIX = 'merchant_risk_alert_foh:';

    const FOH_WORKFLOW_EXECUTE_CONTROLLER = 'RZP\Http\Controllers\MerchantRiskAlertController@executeFOHWorkflow';

    const FOH_SMS_TEMPLATE = 'sms.merchant_risk.alert.funds_on_hold';

    const FOH_WHATSAPP_TEMPLATE = 'We have put your settlement on hold as we observed suspicious account activity on your account  MID - {merchantId} in the name of M/s. {merchantName} held with Razorpay. Please check your registered email for more details';
}
