<?php


namespace RZP\Notifications\Onboarding;


use RZP\Mail\Base\Constants;
use RZP\Models\Merchant\RazorxTreatment;

class Events
{
    const NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE     = 'NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE';
    const NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE = 'NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE';
    const NC_COUNT_1_PAYMENTS_NOT_LIVE                  = 'NC_COUNT_1_PAYMENTS_NOT_LIVE';
    const NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE     = 'NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE';
    const NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE = 'NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE';
    const NC_COUNT_2_PAYMENTS_NOT_LIVE                  = 'NC_COUNT_2_PAYMENTS_NOT_LIVE';
    const NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER     = 'NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER';
    const NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER = 'NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER';
    const NC_COUNT_1_PAYMENTS_NOT_LIVE_REMINDER                  = 'NC_COUNT_1_PAYMENTS_NOT_LIVE_REMINDER';
    const NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER     = 'NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER';
    const NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER = 'NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER';
    const NC_COUNT_2_PAYMENTS_NOT_LIVE_REMINDER                  = 'NC_COUNT_2_PAYMENTS_NOT_LIVE_REMINDER';
    const NC_COUNT_1_ONBOARDING_PAUSE          = 'NC_COUNT_1_ONBOARDING_PAUSE';
    const NC_COUNT_2_ONBOARDING_PAUSE          = 'NC_COUNT_2_ONBOARDING_PAUSE';
    const NC_COUNT_1_ONBOARDING_PAUSE_REMINDER = 'NC_COUNT_1_ONBOARDING_PAUSE_REMINDER';
    const NC_COUNT_2_ONBOARDING_PAUSE_REMINDER = 'NC_COUNT_2_ONBOARDING_PAUSE_REMINDER';


    const PARTNER_EVENTS_PREFIX             = 'PARTNER_';
    const PARTNER_SUBMERCHANT_EVENTS_PREFIX = 'PARTNER_SUBMERCHANT_';

    const PAYMENTS_ENABLED                            = 'PAYMENTS_ENABLED';
    const UNREGISTERED_PAYMENTS_ENABLED               = 'UNREGISTERED_PAYMENTS_ENABLED';
    const UNREGISTERED_SETTLEMENTS_ENABLED            = 'UNREGISTERED_SETTLEMENTS_ENABLED';
    const REGISTERED_PAYMENTS_ENABLED                 = 'REGISTERED_PAYMENTS_ENABLED';
    const REGISTERED_SETTLEMENTS_ENABLED              = 'REGISTERED_SETTLEMENTS_ENABLED';
    const PENNY_TESTING_FAILURE                       = 'PENNY_TESTING_FAILURE';
    const NEEDS_CLARIFICATION                         = 'NEEDS_CLARIFICATION';
    const PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION   = 'PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION';
    const PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED = 'PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED';
    const ACTIVATED_MCC_PENDING                       = "ACTIVATED_MCC_PENDING";
    const FUNDS_ON_HOLD                               = 'FUNDS_ON_HOLD';
    const FUNDS_ON_HOLD_REMINDER                      = 'FUNDS_ON_HOLD_REMINDER';
    const WEBSITE_ADHERENCE_SOFT_NUDGE                = "WEBSITE_ADHERENCE_SOFT_NUDGE";
    const WEBSITE_ADHERENCE_GRACE_PERIOD_REMINDER     = "WEBSITE_ADHERENCE_GRACE_PERIOD_REMINDER";
    const WEBSITE_ADHERENCE_HARD_NUDGE                = "WEBSITE_ADHERENCE_HARD_NUDGE";
    const DOWNLOAD_MERCHANT_WEBSITE_SECTION           = "WEBSITE_SECTION_DOWNLOADED_3";
    const WEBSITE_SECTION_PUBLISHED                   = "WEBSITE_SECTION_PUBLISHED_3";
    const ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH     = 'ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH';
    const ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH     = 'ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH';
    const ACTIVATED_MCC_PENDING_SUCCESS               = 'ACTIVATED_MCC_PENDING_SUCCESS';
    const ACTIVATED_MCC_PENDING_ACTION_REQUIRED       = 'ACTIVATED_MCC_PENDING_ACTION_REQUIRED';
    const ONBOARDING_VERIFY_EMAIL                     = 'ONBOARDING_VERIFY_EMAIL';
    const FIRST_PAYMENT_OFFER                         = 'MTU_OFFER_NEW_TEXT';
    const L1_NOT_SUBMITTED_IN_1_DAY                   = 'L1_NOT_SUBMITTED_IN_1_DAY';
    const L2_BANK_DETAILS_NOT_SUBMITTED_IN_1_HOUR     = 'L2_BANK_DETAILS_NOT_SUBMITTED_IN_1_HOUR';
    const L2_AADHAR_DETAILS_NOT_SUBMITTED_IN_1_HOUR   = 'L2_AADHAR_DETAILS_NOT_SUBMITTED_IN_1_HOUR';
    const INSTANTLY_ACTIVATED_BUT_NOT_TRANSACTED      = 'INSTANTLY_ACTIVATED_BUT_NOT_TRANSACTED';
    const L1_NOT_SUBMITTED_IN_1_HOUR                  = 'L1_NOT_SUBMITTED_IN_1_HOUR';
    const SIGNUP_STARTED_NOTIFY                       = 'SIGNUP_STARTED_NOTIFY';

    // partner submerchant events
    const PARTNER_ADDED_SUBMERCHANT                            = "PARTNER_ADDED_SUBMERCHANT";
    const PARTNER_ADDED_SUBMERCHANT_FOR_X                      = "PARTNER_ADDED_SUBMERCHANT_FOR_X";
    const PARTNER_SUBMERCHANT_ACTIVATED_MCC_PENDING_SUCCESS    = 'PARTNER_SUBMERCHANT_ACTIVATED_MCC_PENDING_SUCCESS';
    const PARTNER_SUBMERCHANT_KYC_ACCESS_APPROVED              = "PARTNER_SUBMERCHANT_KYC_ACCESS_APPROVED";
    const PARTNER_SUBMERCHANT_KYC_ACCESS_REJECTED              = "PARTNER_SUBMERCHANT_KYC_ACCESS_REJECTED";
    const PARTNER_SUBMERCHANT_NEEDS_CLARIFICATION              = 'PARTNER_SUBMERCHANT_NEEDS_CLARIFICATION';
    const PARTNER_SUBMERCHANT_PAYMENTS_ENABLED                 = 'PARTNER_SUBMERCHANT_PAYMENTS_ENABLED';
    const PARTNER_SUBMERCHANT_REGISTERED_SETTLEMENTS_ENABLED   = 'PARTNER_SUBMERCHANT_REGISTERED_SETTLEMENTS_ENABLED';
    const PARTNER_SUBMERCHANT_UNREGISTERED_SETTLEMENTS_ENABLED = 'PARTNER_SUBMERCHANT_UNREGISTERED_SETTLEMENTS_ENABLED';

    const SMS_TEMPLATES = [
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE         => 'sms.onboarding.nc_revamp',
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE     => 'sms.onboarding.nc_revamp',
        self::NC_COUNT_1_PAYMENTS_NOT_LIVE                      => 'sms.onboarding.nc_revamp',
        self::NC_COUNT_1_ONBOARDING_PAUSE                       => 'sms.onboarding.nc_revamp',

        self::PAYMENTS_ENABLED                            => 'sms.onboarding.payments_enabled',
        self::NEEDS_CLARIFICATION                         => 'sms.onboarding.needs_clarification_v2',
        self::UNREGISTERED_PAYMENTS_ENABLED               => 'sms.onboarding.unregistered.payments_enabled_v2',
        self::UNREGISTERED_SETTLEMENTS_ENABLED            => 'sms.onboarding.unregistered.settlements_enabled',
        self::REGISTERED_PAYMENTS_ENABLED                 => 'sms.onboarding.registered.payments_enabled',
        self::REGISTERED_SETTLEMENTS_ENABLED              => 'sms.onboarding.registered.settlements_enabled',
        self::PENNY_TESTING_FAILURE                       => 'sms.onboarding.penny_test_failure',
        self::ACTIVATED_MCC_PENDING                       => 'sms.onboarding.activated_mcc_pending',
        self::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION   => 'sms.onboarding.escalation.payments_limit_breach_v2',
        self::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED => 'sms.onboarding.escalation.payments_breach_blocked',
        self::ACTIVATED_MCC_PENDING_SUCCESS               => 'sms.onboarding.activated_mcc_pending_success',
        self::ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH     => 'sms.onboarding.activated_mcc_pending_soft_limit_breach',
        self::ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH     => 'sms.onboarding.activated_mcc_pending_hard_limit_breach',
        self::FUNDS_ON_HOLD                               => 'sms.onboarding.funds_on_hold',
        self::FUNDS_ON_HOLD_REMINDER                      => 'sms.onboarding.funds_on_hold_reminder',
        self::ONBOARDING_VERIFY_EMAIL                     => 'sms.onboarding.onboarding_verify_email',
        self::L1_NOT_SUBMITTED_IN_1_DAY                   => 'sms.onboarding.l1_activation_not_started_in_1_day',
        self::L2_BANK_DETAILS_NOT_SUBMITTED_IN_1_HOUR     => 'sms.onboarding.Onboarding_L2_not_submit_bank_details_SMS1A',
        self::INSTANTLY_ACTIVATED_BUT_NOT_TRANSACTED      => 'sms.onboarding.Onboarding_IA_SMS2',
        self::L2_AADHAR_DETAILS_NOT_SUBMITTED_IN_1_HOUR   => 'sms.onboarding.Onboarding_L2_not_submit_Aadhaar_SMS3',
        self::L1_NOT_SUBMITTED_IN_1_HOUR                  => 'sms.onboarding.Onboarding_L1_not_submit_SMS2',
        self::SIGNUP_STARTED_NOTIFY                       => 'sms.onboarding.welcome_sms_v1',
        self::WEBSITE_ADHERENCE_HARD_NUDGE                => 'sms.onboarding.website_adherence_hard_nudge_1',

        // Note: the template name for PARTNER_ADDED_SUBMERCHANT_FOR_X was incorrectly registered and now we have to use the same.
        self::PARTNER_ADDED_SUBMERCHANT_FOR_X                      => 'sms.onboarding.partner_submerchant_unregistered_settlements',
        self::PARTNER_ADDED_SUBMERCHANT                            => 'Sms.Partnerships.Add_sub_merchant_partner',

        // Note: PARTNER_SUBMERCHANT_ prefix is checked for notifying all affiliated partners.
        self::PARTNER_SUBMERCHANT_KYC_ACCESS_APPROVED              => 'sms.onboarding.partner_submerchant_kyc_access_approved',
        self::PARTNER_SUBMERCHANT_KYC_ACCESS_REJECTED              => 'sms.onboarding.partner_submerchant_kyc_access_rejected',
        self::PARTNER_SUBMERCHANT_NEEDS_CLARIFICATION              => 'sms.onboarding.partner_submerchant_needs_clarification',
        self::PARTNER_SUBMERCHANT_PAYMENTS_ENABLED                 => 'sms.onboarding.partner_submerchant_payments_enabled',
        // Note: currently we have same content for below 3 events, thus using same template name.
        self::PARTNER_SUBMERCHANT_REGISTERED_SETTLEMENTS_ENABLED   => 'sms.onboarding.partner_submerchant_registered_settlements',
        self::PARTNER_SUBMERCHANT_UNREGISTERED_SETTLEMENTS_ENABLED => 'sms.onboarding.partner_submerchant_registered_settlements',
        self::PARTNER_SUBMERCHANT_ACTIVATED_MCC_PENDING_SUCCESS    => 'sms.onboarding.partner_submerchant_registered_settlements',
    ];

    const SMS_TEMPLATES_CUSTOM_NAMESPACES = [
        self::PARTNER_ADDED_SUBMERCHANT => 'partnerships',
    ];

    const SMS_TEMPLATES_SPLITZ_EXPERIMENTS = [
        self::PARTNER_ADDED_SUBMERCHANT                            => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
        self::PARTNER_ADDED_SUBMERCHANT_FOR_X                      => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
        self::PARTNER_SUBMERCHANT_ACTIVATED_MCC_PENDING_SUCCESS    => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
        self::PARTNER_SUBMERCHANT_KYC_ACCESS_APPROVED              => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
        self::PARTNER_SUBMERCHANT_KYC_ACCESS_REJECTED              => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
        self::PARTNER_SUBMERCHANT_NEEDS_CLARIFICATION              => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
        self::PARTNER_SUBMERCHANT_PAYMENTS_ENABLED                 => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
        self::PARTNER_SUBMERCHANT_REGISTERED_SETTLEMENTS_ENABLED   => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
        self::PARTNER_SUBMERCHANT_UNREGISTERED_SETTLEMENTS_ENABLED => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
    ];

    const SMS_TEMPLATES_RAZORX_EXPERIMENTS = [
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE         => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE         => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE     => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE     => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_1_PAYMENTS_NOT_LIVE                      => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_2_PAYMENTS_NOT_LIVE                      => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_1_ONBOARDING_PAUSE                       => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_2_ONBOARDING_PAUSE                       => RazorxTreatment::NC_REVAMP,
    ];

    const WHATSAPP_TEMPLATES = [
        self::NEEDS_CLARIFICATION              => 'Hi {merchantName}, we need more clarifications on your KYC, please visit your dashboard and make the necessary changes at {dashboardUrl}',
        self::UNREGISTERED_SETTLEMENTS_ENABLED => 'Congratulations {merchantName}, your KYC is approved and settlements have been enabled for your Razorpay account. Visit your dashboard to accept payments {dashboardUrl}',
        self::REGISTERED_SETTLEMENTS_ENABLED   => 'Congratulations {merchantName}, your account is activated, you can now accept payments and get funds settled to your bank account. Visit your dashboard to accept payments {dashboardUrl}',
        self::PENNY_TESTING_FAILURE            => "Hi {merchantName}, we couldn't verify your Bank Account, kindly visit your Dashboard and upload scanned copy of cheque/bank statement at {dashboardUrl}",
        self::ACTIVATED_MCC_PENDING            => "Dear Customer, Congratulations! You can now start accepting payments and the payments will be settled in your bank account as per your settlement schedule. Please note that as part of the routine compliance checks mandated by our banking partners, we will review your business model, website details and reach out for further clarifications. You can now visit your dashboard to accept payments at {dashboardUrl}."
    ];

    // Add template name here if the registered template name defers from standard pattern of 'onboarding.*'
    const WHATSAPP_TEMPLATE_NAMES = [

        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE     => 'nc_count_payments_live_settlements_live',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE     => 'nc_count_2_payments_live_settlements_live',
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE => 'nc_count_payments_live_settlements_not_live',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE => 'nc_count_2_payments_live_settlements_not_live',
        self::NC_COUNT_1_PAYMENTS_NOT_LIVE                  => 'nc_count_payments_not_live',
        self::NC_COUNT_2_PAYMENTS_NOT_LIVE                  => 'nc_count_2_payments_not_live',
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER     => 'nc_revamp_payments_live_settlements_live_reminder',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER     => 'nc_revamp_payments_live_settlements_live_reminder',
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER => 'nc_revamp_payments_live_settlements_not_live_reminder',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER => 'nc_revamp_payments_live_settlements_not_live_reminder',
        self::NC_COUNT_1_PAYMENTS_NOT_LIVE_REMINDER                  => 'nc_revamp_payments_not_live_reminder',
        self::NC_COUNT_2_PAYMENTS_NOT_LIVE_REMINDER                  => 'nc_revamp_payments_not_live_reminder',
        self::NC_COUNT_1_ONBOARDING_PAUSE          => 'nc_revamp_onboarding_pause',
        self::NC_COUNT_2_ONBOARDING_PAUSE          => 'nc_revamp_2_onboarding_pause',
        self::NC_COUNT_1_ONBOARDING_PAUSE_REMINDER => 'nc_revamp_onboarding_pause_reminder',
        self::NC_COUNT_2_ONBOARDING_PAUSE_REMINDER => 'nc_revamp_onboarding_pause_reminder',

        self::PARTNER_ADDED_SUBMERCHANT                           => 'whatsapp_partnerships_add_sub_merchant_partner',
        self::PARTNER_ADDED_SUBMERCHANT_FOR_X                      => 'whatsapp_partnerships_add_sub_merchant_partner_for_x',
        self::PARTNER_SUBMERCHANT_ACTIVATED_MCC_PENDING_SUCCESS    => 'whatsapp_partnerships_partner_submerchant_activated_mcc_pending_success',
        self::PARTNER_SUBMERCHANT_KYC_ACCESS_APPROVED              => 'whatsapp_partnerships_partner_submerchant_kyc_access_approved',
        self::PARTNER_SUBMERCHANT_KYC_ACCESS_REJECTED              => 'whatsapp_partnerships_partner_submerchant_kyc_access_rejected',
        self::PARTNER_SUBMERCHANT_NEEDS_CLARIFICATION              => 'whatsapp_partnerships_partner_submerchant_needs_clarification',
        self::PARTNER_SUBMERCHANT_PAYMENTS_ENABLED                 => 'whatsapp_partnerships_partner_submerchant_payments_enable',
        self::PARTNER_SUBMERCHANT_REGISTERED_SETTLEMENTS_ENABLED   => 'whatsapp_partnerships_partner_submerchant_registered_settlements_enable',
        self::PARTNER_SUBMERCHANT_UNREGISTERED_SETTLEMENTS_ENABLED => 'whatsapp_partnerships_partner_submerchant_unregistered_settlements_payments_enable',
    ];

    const WHATSAPP_TEMPLATES_SPLITZ_EXPERIMENTS = [
        self::PARTNER_ADDED_SUBMERCHANT                            => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
        self::PARTNER_ADDED_SUBMERCHANT_FOR_X                      => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
        self::PARTNER_SUBMERCHANT_ACTIVATED_MCC_PENDING_SUCCESS    => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
        self::PARTNER_SUBMERCHANT_KYC_ACCESS_APPROVED              => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
        self::PARTNER_SUBMERCHANT_KYC_ACCESS_REJECTED              => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
        self::PARTNER_SUBMERCHANT_NEEDS_CLARIFICATION              => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
        self::PARTNER_SUBMERCHANT_PAYMENTS_ENABLED                 => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
        self::PARTNER_SUBMERCHANT_REGISTERED_SETTLEMENTS_ENABLED   => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
        self::PARTNER_SUBMERCHANT_UNREGISTERED_SETTLEMENTS_ENABLED => 'send_sms_whatsapp_partner_submerchant_onboarding_events',
    ];

    const WHATSAPP_TEMPLATES_NEW_EXPERIMENTS = [
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE         => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE         => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE     => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE     => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_1_PAYMENTS_NOT_LIVE                      => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_2_PAYMENTS_NOT_LIVE                      => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER     => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER     => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_1_PAYMENTS_NOT_LIVE_REMINDER                  => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_2_PAYMENTS_NOT_LIVE_REMINDER                  => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_1_ONBOARDING_PAUSE                            => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_2_ONBOARDING_PAUSE                            => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_1_ONBOARDING_PAUSE_REMINDER                   => RazorxTreatment::NC_REVAMP,
        self::NC_COUNT_2_ONBOARDING_PAUSE_REMINDER                   => RazorxTreatment::NC_REVAMP,

        self::DOWNLOAD_MERCHANT_WEBSITE_SECTION       => RazorxTreatment::WEBSITE_ADHERENCE_WHATSAPP_COMMUNICATION,
        self::WEBSITE_SECTION_PUBLISHED               => RazorxTreatment::WEBSITE_ADHERENCE_WHATSAPP_COMMUNICATION,
        self::WEBSITE_ADHERENCE_HARD_NUDGE            => RazorxTreatment::WEBSITE_ADHERENCE_WHATSAPP_COMMUNICATION,
        self::WEBSITE_ADHERENCE_SOFT_NUDGE            => RazorxTreatment::WEBSITE_ADHERENCE_WHATSAPP_COMMUNICATION,
        self::WEBSITE_ADHERENCE_GRACE_PERIOD_REMINDER => RazorxTreatment::WEBSITE_ADHERENCE_WHATSAPP_COMMUNICATION,
    ];

    const WHATSAPP_TEMPLATES_CTA_TEMPLATE = [
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE         => 'onboarding/needs-clarification',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE         => 'onboarding/needs-clarification',
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE     => 'onboarding/needs-clarification',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE     => 'onboarding/needs-clarification',
        self::NC_COUNT_1_PAYMENTS_NOT_LIVE                      => 'onboarding/needs-clarification',
        self::NC_COUNT_2_PAYMENTS_NOT_LIVE                      => 'onboarding/needs-clarification',
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER     => 'onboarding/needs-clarification',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER     => 'onboarding/needs-clarification',
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER => 'onboarding/needs-clarification',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER => 'onboarding/needs-clarification',
        self::NC_COUNT_1_PAYMENTS_NOT_LIVE_REMINDER                  => 'onboarding/needs-clarification',
        self::NC_COUNT_2_PAYMENTS_NOT_LIVE_REMINDER                  => 'onboarding/needs-clarification',
        self::NC_COUNT_1_ONBOARDING_PAUSE                            => 'onboarding/needs-clarification',
        self::NC_COUNT_2_ONBOARDING_PAUSE                            => 'onboarding/needs-clarification',
        self::NC_COUNT_1_ONBOARDING_PAUSE_REMINDER                   => 'onboarding/needs-clarification',
        self::NC_COUNT_2_ONBOARDING_PAUSE_REMINDER                   => 'onboarding/needs-clarification',

        self::WEBSITE_ADHERENCE_HARD_NUDGE            => 'app/website-app-detail',
        self::WEBSITE_ADHERENCE_SOFT_NUDGE            => 'app/website-app-detail',
        self::FIRST_PAYMENT_OFFER                     => 'signin?utm_source=Reactivation&utm_medium=whatsapp&utm_campaign=10k_referral_content',
        self::PARTNER_ADDED_SUBMERCHANT               => 'app/partners/submerchants',
        self::PARTNER_SUBMERCHANT_KYC_ACCESS_APPROVED => 'app/partners',
        self::PARTNER_SUBMERCHANT_KYC_ACCESS_REJECTED => 'app/partners',
        self::PARTNER_SUBMERCHANT_PAYMENTS_ENABLED    => 'app/partners',
    ];

    // blade templates
    const WHATSAPP_TEMPLATES_NEW = [
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE     => 'whatsapp.merchant.onboarding.nc_count_payments_live_settlements_live',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE     => 'whatsapp.merchant.onboarding.nc_count_2_payments_live_settlements_live',
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE => 'whatsapp.merchant.onboarding.nc_count_payments_live_settlements_not_live',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE => 'whatsapp.merchant.onboarding.nc_count_2_payments_live_settlements_not_live',
        self::NC_COUNT_1_PAYMENTS_NOT_LIVE                  => 'whatsapp.merchant.onboarding.nc_count_payments_not_live',
        self::NC_COUNT_2_PAYMENTS_NOT_LIVE                  => 'whatsapp.merchant.onboarding.nc_count_2_payments_not_live',
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER     => 'whatsapp.merchant.onboarding.nc_revamp_payments_live_settlements_live_reminder',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER     => 'whatsapp.merchant.onboarding.nc_revamp_payments_live_settlements_live_reminder',
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER => 'whatsapp.merchant.onboarding.nc_revamp_payments_live_settlements_not_live_reminder',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER => 'whatsapp.merchant.onboarding.nc_revamp_payments_live_settlements_not_live_reminder',
        self::NC_COUNT_1_PAYMENTS_NOT_LIVE_REMINDER                  => 'whatsapp.merchant.onboarding.nc_revamp_payments_not_live_reminder',
        self::NC_COUNT_2_PAYMENTS_NOT_LIVE_REMINDER                  => 'whatsapp.merchant.onboarding.nc_revamp_payments_not_live_reminder',
        self::NC_COUNT_1_ONBOARDING_PAUSE                            => 'whatsapp.merchant.onboarding.nc_revamp_onboarding_pause',
        self::NC_COUNT_2_ONBOARDING_PAUSE                            => 'whatsapp.merchant.onboarding.nc_revamp_2_onboarding_pause',
        self::NC_COUNT_1_ONBOARDING_PAUSE_REMINDER                   => 'whatsapp.merchant.onboarding.nc_revamp_onboarding_pause_reminder',
        self::NC_COUNT_2_ONBOARDING_PAUSE_REMINDER                   => 'whatsapp.merchant.onboarding.nc_revamp_onboarding_pause_reminder',

        self::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION   => 'whatsapp.merchant.onboarding.payments_limit_breach',
        self::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED => 'whatsapp.merchant.onboarding.payments_breach_blocked',
        self::UNREGISTERED_PAYMENTS_ENABLED               => 'whatsapp.merchant.onboarding.payments_enabled',
        self::REGISTERED_PAYMENTS_ENABLED                 => 'whatsapp.merchant.onboarding.payments_enabled',
        self::ACTIVATED_MCC_PENDING_SUCCESS               => 'whatsapp.merchant.onboarding.activated_mcc_pending_success',
        self::ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH     => 'whatsapp.merchant.onboarding.activated_mcc_pending_soft_limit_breach',
        self::ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH     => 'whatsapp.merchant.onboarding.activated_mcc_pending_hard_limit_breach',
        self::FUNDS_ON_HOLD                               => 'whatsapp.merchant.onboarding.funds_on_hold',
        self::FUNDS_ON_HOLD_REMINDER                      => 'whatsapp.merchant.onboarding.funds_on_hold_reminder',
        self::L1_NOT_SUBMITTED_IN_1_DAY                   => 'whatsapp.merchant.onboarding.onboarding_activation_l1_pending',
        self::L2_BANK_DETAILS_NOT_SUBMITTED_IN_1_HOUR     => 'whatsapp.merchant.onboarding.Onboarding_L2_not_submit_bank_details_WA1',
        self::L2_AADHAR_DETAILS_NOT_SUBMITTED_IN_1_HOUR   => 'whatsapp.merchant.onboarding.Onboarding_L2_not_submit_Aadhaar_WA3',
        self::PAYMENTS_ENABLED                            => 'whatsapp.merchant.onboarding.payments_enabled',
        self::ONBOARDING_VERIFY_EMAIL                     => 'whatsapp.merchant.onboarding.onboarding_verify_email',
        self::INSTANTLY_ACTIVATED_BUT_NOT_TRANSACTED      => 'whatsapp.merchant.onboarding.Onboarding_IA_WA2',
        self::L1_NOT_SUBMITTED_IN_1_HOUR                  => 'whatsapp.merchant.onboarding.Onboarding_L1_not_submit_WA2_A',
        self::SIGNUP_STARTED_NOTIFY                       => 'whatsapp.merchant.onboarding.welcome_wa_noemoji',
        self::FIRST_PAYMENT_OFFER                         => 'whatsapp.merchant.onboarding.mtu_offer_new_text',

        self::PARTNER_ADDED_SUBMERCHANT                            => 'whatsapp.merchant.onboarding.partner_added_submerchant',
        self::PARTNER_ADDED_SUBMERCHANT_FOR_X                      => 'whatsapp.merchant.onboarding.partner_added_submerchant_for_x',
        self::PARTNER_SUBMERCHANT_NEEDS_CLARIFICATION              => 'whatsapp.merchant.onboarding.partner_submerchant_needs_clarification',
        self::PARTNER_SUBMERCHANT_KYC_ACCESS_APPROVED              => 'whatsapp.merchant.onboarding.partner_submerchant_kyc_access_approved',
        self::PARTNER_SUBMERCHANT_KYC_ACCESS_REJECTED              => 'whatsapp.merchant.onboarding.partner_submerchant_kyc_access_rejected',
        self::PARTNER_SUBMERCHANT_ACTIVATED_MCC_PENDING_SUCCESS    => 'whatsapp.merchant.onboarding.partner_submerchant_activated_mcc_pending_success',
        self::PARTNER_SUBMERCHANT_PAYMENTS_ENABLED                 => 'whatsapp.merchant.onboarding.partner_submerchant_payments_enabled',
        self::PARTNER_SUBMERCHANT_REGISTERED_SETTLEMENTS_ENABLED   => 'whatsapp.merchant.onboarding.partner_submerchant_registered_settlements_enabled',
        self::PARTNER_SUBMERCHANT_UNREGISTERED_SETTLEMENTS_ENABLED => 'whatsapp.merchant.onboarding.partner_submerchant_registered_settlements_enabled',

        self::DOWNLOAD_MERCHANT_WEBSITE_SECTION => 'whatsapp.merchant.onboarding.website_section_downloaded',
        self::WEBSITE_SECTION_PUBLISHED         => 'whatsapp.merchant.onboarding.website_section_published',
        self::WEBSITE_ADHERENCE_HARD_NUDGE      => 'whatsapp.merchant.onboarding.website_adherence_hard_nudge',
        self::WEBSITE_ADHERENCE_SOFT_NUDGE      => 'whatsapp.merchant.onboarding.website_adherence_soft_nudge',

    ];

    const EMAIL_TEMPLATES = [
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE     => 'emails.merchant.onboarding.nc_count_1_payments_live_settlements_live',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE     => 'emails.merchant.onboarding.nc_count_2_payments_live_settlements_live',
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE => 'emails.merchant.onboarding.nc_count_1_payments_live_settlements_not_live',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE => 'emails.merchant.onboarding.nc_count_2_payments_live_settlements_not_live',
        self::NC_COUNT_1_PAYMENTS_NOT_LIVE                  => 'emails.merchant.onboarding.nc_count_1_payments_not_live',
        self::NC_COUNT_2_PAYMENTS_NOT_LIVE                  => 'emails.merchant.onboarding.nc_count_2_payments_not_live',
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER     => 'emails.merchant.onboarding.nc_count_1_payments_live_settlements_live_reminder',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER     => 'emails.merchant.onboarding.nc_count_2_payments_live_settlements_live_reminder',
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER => 'emails.merchant.onboarding.nc_count_1_payments_live_settlements_not_live_reminder',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER => 'emails.merchant.onboarding.nc_count_2_payments_live_settlements_not_live_reminder',
        self::NC_COUNT_1_PAYMENTS_NOT_LIVE_REMINDER                  => 'emails.merchant.onboarding.nc_count_1_payments_not_live_reminder',
        self::NC_COUNT_2_PAYMENTS_NOT_LIVE_REMINDER                  => 'emails.merchant.onboarding.nc_count_2_payments_not_live_reminder',
        self::NC_COUNT_1_ONBOARDING_PAUSE                            => 'emails.merchant.onboarding.nc_count_1_onboarding_pause',
        self::NC_COUNT_2_ONBOARDING_PAUSE                            => 'emails.merchant.onboarding.nc_count_2_onboarding_pause',
        self::NC_COUNT_1_ONBOARDING_PAUSE_REMINDER                   => 'emails.merchant.onboarding.nc_count_1_onboarding_pause_reminder',
        self::NC_COUNT_2_ONBOARDING_PAUSE_REMINDER                   => 'emails.merchant.onboarding.nc_count_2_onboarding_pause_reminder',

        self::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION   => 'emails.merchant.onboarding.payments_limit_breach',
        self::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED => 'emails.merchant.onboarding.payments_breach_blocked',
        self::ACTIVATED_MCC_PENDING_SUCCESS               => 'emails.merchant.onboarding.activated_mcc_pending_success',
        self::ACTIVATED_MCC_PENDING_ACTION_REQUIRED       => 'emails.merchant.onboarding.activated_mcc_pending_action_required',
        self::ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH     => 'emails.merchant.onboarding.activated_mcc_pending_soft_limit_breach',
        self::ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH     => 'emails.merchant.onboarding.activated_mcc_pending_hard_limit_breach',
        self::FUNDS_ON_HOLD                               => 'emails.merchant.onboarding.funds_on_hold',
        self::FUNDS_ON_HOLD_REMINDER                      => 'emails.merchant.onboarding.funds_on_hold_reminder',

        self::DOWNLOAD_MERCHANT_WEBSITE_SECTION       => 'emails.merchant.onboarding.website_section_downloaded',
        self::WEBSITE_SECTION_PUBLISHED               => 'emails.merchant.onboarding.website_section_published',
        self::WEBSITE_ADHERENCE_HARD_NUDGE            => 'emails.merchant.onboarding.website_adherence_hard_nudge',
        self::WEBSITE_ADHERENCE_SOFT_NUDGE            => 'emails.merchant.onboarding.website_adherence_soft_nudge',
        self::WEBSITE_ADHERENCE_GRACE_PERIOD_REMINDER => 'emails.merchant.onboarding.website_adherence_grace_period_reminder',

        self::PARTNER_SUBMERCHANT_ACTIVATED_MCC_PENDING_SUCCESS    => 'partner.submerchant.onboarding.activated_mcc_pending_success',
        self::PARTNER_SUBMERCHANT_NEEDS_CLARIFICATION              => 'partner.submerchant.onboarding.needs_clarification',
        self::PARTNER_SUBMERCHANT_UNREGISTERED_SETTLEMENTS_ENABLED => 'partner.submerchant.onboarding.unregistered_settlements_enabled',
        self::PARTNER_SUBMERCHANT_REGISTERED_SETTLEMENTS_ENABLED   => 'partner.submerchant.onboarding.registered_settlements_enabled',
        self::PARTNER_SUBMERCHANT_PAYMENTS_ENABLED                 => 'partner.submerchant.onboarding.payments_enabled',
    ];

    const EMAIL_SUBJECTS = [
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE     => '[Action required] Few more details required to complete KYC verification',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE     => '[Action required] Few more details required to complete KYC verification',
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE => '[Action required] Few more details required to complete KYC verification',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE => '[Action required] Few more details required to complete KYC verification',
        self::NC_COUNT_1_PAYMENTS_NOT_LIVE                  => '[Action required] Few more details required to complete KYC verification',
        self::NC_COUNT_2_PAYMENTS_NOT_LIVE                  => '[Action required] Few more details required to complete KYC verification',
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER     => '[Action required] Reminder to update your details for KYC verification',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER     => '[Action required] Reminder to update your details for KYC verification',
        self::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER => '[Action required] Reminder to update your details for KYC verification',
        self::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER => '[Action required] Reminder to update your details for KYC verification',
        self::NC_COUNT_1_PAYMENTS_NOT_LIVE_REMINDER                  => '[Action required] Reminder to update your details for KYC verification',
        self::NC_COUNT_2_PAYMENTS_NOT_LIVE_REMINDER                  => '[Action required] Reminder to update your details for KYC verification',
        self::NC_COUNT_1_ONBOARDING_PAUSE                            => '[Action required] Few more details required to complete KYC verification',
        self::NC_COUNT_2_ONBOARDING_PAUSE                            => '[Action required] Few more details required to complete KYC verification',
        self::NC_COUNT_1_ONBOARDING_PAUSE_REMINDER                   => '[Action required] Reminder to update your details for KYC verification',
        self::NC_COUNT_2_ONBOARDING_PAUSE_REMINDER                   => '[Action required] Reminder to update your details for KYC verification',

        self::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION   => 'Razorpay Reminder: Update your KYC details to continue accepting payments',
        self::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED => 'Razorpay Alert: Your payments are paused, submit KYC details to resume payments',
        self::ACTIVATED_MCC_PENDING_SUCCESS               => 'Congratulations! You can now receive payments in your bank account with Razorpay',
        self::ACTIVATED_MCC_PENDING_ACTION_REQUIRED       => '[Important] Action needed for continuity of your Razorpay account',
        self::ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH     => '[Important] Action needed for continuity of your Razorpay account',
        self::ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH     => '[Urgent] Clarifications needed for continuity of your Razorpay account',
        self::FUNDS_ON_HOLD                               => '[Urgent] Settlements have been paused for your Razorpay account',
        self::FUNDS_ON_HOLD_REMINDER                      => '[Urgent] Settlements have been paused for your Razorpay account',

        self::DOWNLOAD_MERCHANT_WEBSITE_SECTION       => 'Content for your website/app pages',
        self::WEBSITE_SECTION_PUBLISHED               => 'Published links for your website/app pages',
        self::WEBSITE_ADHERENCE_SOFT_NUDGE            => 'Update your website/app details',
        self::WEBSITE_ADHERENCE_GRACE_PERIOD_REMINDER => 'Update content on your website/app to avoid payment disruption',
        self::WEBSITE_ADHERENCE_HARD_NUDGE            => 'Reminder: Update your website/app details',

        self::PARTNER_SUBMERCHANT_ACTIVATED_MCC_PENDING_SUCCESS    => 'Your affiliate {merchantName} can now accept payments via Razorpay',
        self::PARTNER_SUBMERCHANT_NEEDS_CLARIFICATION              => 'Your affiliate {merchantName}\'s KYC needs action',
        self::PARTNER_SUBMERCHANT_UNREGISTERED_SETTLEMENTS_ENABLED => 'Your affiliate {merchantName}\'s KYC is approved',
        self::PARTNER_SUBMERCHANT_REGISTERED_SETTLEMENTS_ENABLED   => 'Your affiliate {merchantName} can now accept payments via Razorpay',
        self::PARTNER_SUBMERCHANT_PAYMENTS_ENABLED                 => 'Your affiliate {merchantName} can now accept payments via Razorpay',
    ];


    const EMAIL_CC = [
        self::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION            => Constants::MAIL_ADDRESSES[Constants::RAZORPAY_HELP_DESK],
        self::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED          => Constants::MAIL_ADDRESSES[Constants::RAZORPAY_HELP_DESK],
        self::ACTIVATED_MCC_PENDING_SUCCESS                        => Constants::MAIL_ADDRESSES[Constants::RAZORPAY_HELP_DESK],
        self::ACTIVATED_MCC_PENDING_ACTION_REQUIRED                => Constants::MAIL_ADDRESSES[Constants::RAZORPAY_HELP_DESK],
        self::ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH              => Constants::MAIL_ADDRESSES[Constants::RAZORPAY_HELP_DESK],
        self::ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH              => Constants::MAIL_ADDRESSES[Constants::RAZORPAY_HELP_DESK],
        self::FUNDS_ON_HOLD                                        => Constants::MAIL_ADDRESSES[Constants::RAZORPAY_HELP_DESK],
        self::FUNDS_ON_HOLD_REMINDER                               => Constants::MAIL_ADDRESSES[Constants::RAZORPAY_HELP_DESK],
        self::PARTNER_SUBMERCHANT_ACTIVATED_MCC_PENDING_SUCCESS    => Constants::MAIL_ADDRESSES[Constants::RAZORPAY_HELP_DESK],
        self::PARTNER_SUBMERCHANT_NEEDS_CLARIFICATION              => Constants::MAIL_ADDRESSES[Constants::RAZORPAY_HELP_DESK],
        self::PARTNER_SUBMERCHANT_UNREGISTERED_SETTLEMENTS_ENABLED => Constants::MAIL_ADDRESSES[Constants::RAZORPAY_HELP_DESK],
        self::PARTNER_SUBMERCHANT_REGISTERED_SETTLEMENTS_ENABLED   => Constants::MAIL_ADDRESSES[Constants::RAZORPAY_HELP_DESK],
        self::PARTNER_SUBMERCHANT_PAYMENTS_ENABLED
    ];
}
