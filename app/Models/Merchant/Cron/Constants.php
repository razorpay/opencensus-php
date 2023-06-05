<?php


namespace RZP\Models\Merchant\Cron;

class Constants
{
    const ONBOARDING_NAMESPACE = "onboarding";

    const CRON_NAME = "cron_name";

    const PENDING         = "pending";
    const SUCCESS         = "success";
    const FAIL            = "fail";
    const PARTIAL_SUCCESS = "partial_success";
    const SKIPPED         = "skipped";

    const MAX_RETRIES_ALLOWED = 5;

    # cron job names
    const ENABLE_M2M_REFERRAL_CRON_JOB_NAME                          = "enable_m2m_referral";
    const BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB                    = "bvs_partly_executed_validation_cron";
    const FIRST_PAYMENT_OFFER_DAILY_NOTIFICATION                     = 'first-payment-offer-daily-notification';
    const FRIEND_BUY_SEND_PURCHASE_EVENTS_CRON_JOB_NAME              = "friend-buy-send-purchase-events-cron";
    const MERCHANT_SEGMENT_TYPE_CRON_JOB_NAME                        = 'save-merchant-segment-type-cron';
    const MERCHANT_WEBSITE_INCOMPLETE_PAYMENTS_ENABLED_CRON_JOB_NAME = 'website_incomplete_payments_enabled_cron';
    const WEBSITE_COMPLIANCE_GRACE_PERIOD_REMINDER_JOB               = 'website_compliance_grace_period_reminder_job';
    const L1_FORM_EMAIL_TRIGGER_CRON_JOB_NAME                        = 'l1_form_email_trigger_cron_job';
    const MERCHANT_FIRST_TRANSACTION_POST_EVENT_CRON                 = 'merchant-first-transaction-post-event-cron';
    const MERCHANT_AUTO_KYC_FAILURE_CRON_JOB_NAME                    = 'merchant_auto_kyc_failure_cron';
    const SUBMERCHANT_FIRST_TRANSACTION                              = 'transacted-submerchants';
    const MERCHANT_AUTO_KYC_PASS_CRON_JOB_NAME                       = 'merchant_auto_kyc_pass_cron';
    const FOH_REMOVAL_CRON_JOB_NAME                                  = 'foh_removal_cron';
    const INTL_MERCHANTS_WA_NOTIFICATION_CRON_JOB                    = 'intl-merchants-wa-notification';
    const MTU_TRANSACTED_MERCHANTS_CRON_JON_NAME                     = 'mtu-transacted';
    const PRE_ACTIVATION_MERCHANT_RELEASE_FUNDS                      = 'pre-activation-merchant-release-funds';

    # map keys
    const AUTO_KYC_FAILURE_DATA = "auto_kyc_failure_data";
    const MERCHANT_IDS          = "merchant_ids";
    const CASE_TYPE             = "case_type";
    const LEVEL                 = 1;

    # cron cache keys
    const MERCHANT_AUTO_KYC_FAILURE_TIMESTAMP = "merchant_auto_kyc_failure_timestamp";

    const  AUTO_KYC_LAST_CRON_DEFAULT_VALUE = 1;

    const FOH_REMOVAL_LAST_CRON_DEFAULT_VLAUE = 24 ;

    const PRE_ACTIVATION_MERCHANT_RELEASE_FUNDS_LAST_CRON_DEFAULT_VALUE = 24;

    // Whatsapp event and templates
    const CB_SIGNUP_JOURNEY         = "CB_SIGNUP_JOURNEY";

    const WHATSAPP_TEMPLATE_NAME    = [
        self::CB_SIGNUP_JOURNEY     => "cb_signup_journey_qa3",
    ];

    const WHATSAPP_TEMPLATE_TEXT    = [
        self::CB_SIGNUP_JOURNEY     => "Interested in accepting international payments but don't know how?\n" .
                                      "\n" .
                                      "With a super easy activation process and maximum coverage across the global market, you can now accept payments internationally without any hassle with Razorpay!\n" .
                                      "- Accept payments from 100+currencies.\n" .
                                      "- Easy and timely settlements in INR.\n" .
                                      "- Super quick set up process.\n" .
                                      "- Industry best pricing.\n" .
                                      "\n" .
                                      "Here's a helpful video that will guide you through the activation process",
    ];

    const WHATSAPP_TEMPLATE_HEADER  = [
        self::CB_SIGNUP_JOURNEY     => "Want to grow your business internationally?",
    ];

    const WHATSAPP_MULTIMEDIA_LINK  = [
        self::CB_SIGNUP_JOURNEY     => "https://download.samplelib.com/mp4/sample-5s.mp4",
    ];
}
