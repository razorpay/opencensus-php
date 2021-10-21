<?php


namespace RZP\Notifications\Onboarding;


class Events
{
    const PAYMENTS_ENABLED     = 'PAYMENTS_ENABLED';
    const UNREGISTERED_PAYMENTS_ENABLED               = 'UNREGISTERED_PAYMENTS_ENABLED';
    const UNREGISTERED_SETTLEMENTS_ENABLED            = 'UNREGISTERED_SETTLEMENTS_ENABLED';
    const REGISTERED_PAYMENTS_ENABLED                 = 'REGISTERED_PAYMENTS_ENABLED';
    const REGISTERED_SETTLEMENTS_ENABLED              = 'REGISTERED_SETTLEMENTS_ENABLED';
    const PENNY_TESTING_FAILURE                       = 'PENNY_TESTING_FAILURE';
    const NEEDS_CLARIFICATION                         = 'NEEDS_CLARIFICATION';
    const PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION   = 'PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION';
    const PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED = 'PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED';
    const ONBOARDING_ACTIVATION_L1_PENDING            = 'ONBOARDING_ACTIVATION_L1_PENDING';
    const ACTIVATED_MCC_PENDING                       = "ACTIVATED_MCC_PENDING";
    const FUNDS_ON_HOLD                               = 'FUNDS_ON_HOLD';
    const FUNDS_ON_HOLD_REMINDER                      = 'FUNDS_ON_HOLD_REMINDER';
    const ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH     = 'ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH';
    const ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH     = 'ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH';
    const ACTIVATED_MCC_PENDING_SUCCESS               = 'ACTIVATED_MCC_PENDING_SUCCESS';
    const ACTIVATED_MCC_PENDING_ACTION_REQUIRED       = 'ACTIVATED_MCC_PENDING_ACTION_REQUIRED';
    const ONBOARDING_VERIFY_EMAIL                     = 'ONBOARDING_VERIFY_EMAIL';
    const COUPON_CODE_ELIGIBLE_MERCHANT_NOT_MTU       = 'COUPON_CODE_ELIGIBLE_MERCHANT_NOT_MTU';

    const SMS_TEMPLATES = [
        self::PAYMENTS_ENABLED                            => 'sms.onboarding.payments_enabled',
        self::NEEDS_CLARIFICATION                         => 'sms.onboarding.needs_clarification',
        self::UNREGISTERED_PAYMENTS_ENABLED               => 'sms.onboarding.unregistered.payments_enabled',
        self::UNREGISTERED_SETTLEMENTS_ENABLED            => 'sms.onboarding.unregistered.settlements_enabled',
        self::REGISTERED_PAYMENTS_ENABLED                 => 'sms.onboarding.registered.payments_enabled',
        self::REGISTERED_SETTLEMENTS_ENABLED              => 'sms.onboarding.registered.settlements_enabled',
        self::PENNY_TESTING_FAILURE                       => 'sms.onboarding.penny_test_failure',
        self::ACTIVATED_MCC_PENDING                       => 'sms.onboarding.activated_mcc_pending',
        self::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION   => 'sms.onboarding.escalation.payments_limit_breach',
        self::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED => 'sms.onboarding.escalation.payments_breach_blocked',
        self::ACTIVATED_MCC_PENDING_SUCCESS               => 'sms.onboarding.activated_mcc_pending_success',
        self::ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH     => 'sms.onboarding.activated_mcc_pending_soft_limit_breach',
        self::ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH     => 'sms.onboarding.activated_mcc_pending_hard_limit_breach',
        self::FUNDS_ON_HOLD                               => 'sms.onboarding.funds_on_hold',
        self::FUNDS_ON_HOLD_REMINDER                      => 'sms.onboarding.funds_on_hold_reminder',
        self::ONBOARDING_VERIFY_EMAIL                     => 'sms.onboarding.onboarding_verify_email',
        self::ONBOARDING_ACTIVATION_L1_PENDING            => 'sms.onboarding.l1_activation_not_started_in_1_day',
        self::COUPON_CODE_ELIGIBLE_MERCHANT_NOT_MTU       => 'sms.onboarding.coupon_code_eligible_merchant_not_mtu',
    ];


    const WHATSAPP_TEMPLATES = [
        self::NEEDS_CLARIFICATION              => 'Hi {merchantName}, we need more clarifications on your KYC, please visit your dashboard and make the necessary changes at {dashboardUrl}',
        self::UNREGISTERED_SETTLEMENTS_ENABLED => 'Congratulations {merchantName}, your KYC is approved and settlements have been enabled for your Razorpay account. Visit your dashboard to accept payments {dashboardUrl}',
        self::REGISTERED_SETTLEMENTS_ENABLED   => 'Congratulations {merchantName}, your account is activated, you can now accept payments and get funds settled to your bank account. Visit your dashboard to accept payments {dashboardUrl}',
        self::PENNY_TESTING_FAILURE            => "Hi {merchantName}, we couldn't verify your Bank Account, kindly visit your Dashboard and upload scanned copy of cheque/bank statement at {dashboardUrl}",
        self::ACTIVATED_MCC_PENDING            => "Dear Customer, Congratulations! You can now start accepting payments and the payments will be settled in your bank account as per your settlement schedule. Please note that as part of the routine compliance checks mandated by our banking partners, we will review your business model, website details and reach out for further clarifications. You can now visit your dashboard to accept payments at {dashboardUrl}."
    ];

    // blade templates
    const WHATSAPP_TEMPLATES_NEW = [
        self::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION   => 'whatsapp.merchant.onboarding.payments_limit_breach',
        self::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED => 'whatsapp.merchant.onboarding.payments_breach_blocked',
        self::UNREGISTERED_PAYMENTS_ENABLED               => 'whatsapp.merchant.onboarding.payments_enabled',
        self::REGISTERED_PAYMENTS_ENABLED                 => 'whatsapp.merchant.onboarding.payments_enabled',
        self::ACTIVATED_MCC_PENDING_SUCCESS               => 'whatsapp.merchant.onboarding.activated_mcc_pending_success',
        self::ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH     => 'whatsapp.merchant.onboarding.activated_mcc_pending_soft_limit_breach',
        self::ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH     => 'whatsapp.merchant.onboarding.activated_mcc_pending_hard_limit_breach',
        self::FUNDS_ON_HOLD                               => 'whatsapp.merchant.onboarding.funds_on_hold',
        self::FUNDS_ON_HOLD_REMINDER                      => 'whatsapp.merchant.onboarding.funds_on_hold_reminder',
        self::ONBOARDING_ACTIVATION_L1_PENDING            => 'whatsapp.merchant.onboarding.onboarding_activation_l1_pending',
        self::PAYMENTS_ENABLED                            => 'whatsapp.merchant.onboarding.payments_enabled',
        self::ONBOARDING_VERIFY_EMAIL                     => 'whatsapp.merchant.onboarding.onboarding_verify_email',
    ];

    const EMAIL_TEMPLATES = [
        self::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION   => 'emails.merchant.onboarding.payments_limit_breach',
        self::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED => 'emails.merchant.onboarding.payments_breach_blocked',
        self::ACTIVATED_MCC_PENDING_SUCCESS               => 'emails.merchant.onboarding.activated_mcc_pending_success',
        self::ACTIVATED_MCC_PENDING_ACTION_REQUIRED       => 'emails.merchant.onboarding.activated_mcc_pending_action_required',
        self::ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH     => 'emails.merchant.onboarding.activated_mcc_pending_soft_limit_breach',
        self::ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH     => 'emails.merchant.onboarding.activated_mcc_pending_hard_limit_breach',
        self::FUNDS_ON_HOLD                               => 'emails.merchant.onboarding.funds_on_hold',
        self::FUNDS_ON_HOLD_REMINDER                      => 'emails.merchant.onboarding.funds_on_hold_reminder',
    ];

    const EMAIL_SUBJECTS = [
        self::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION   => 'Razorpay Reminder: Update your KYC details to continue accepting payments',
        self::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED => 'Razorpay Alert: Your payments are paused, submit KYC details to resume payments',
        self::ACTIVATED_MCC_PENDING_SUCCESS               => 'Congratulations! You can now receive payments in your bank account with Razorpay',
        self::ACTIVATED_MCC_PENDING_ACTION_REQUIRED       => '[Important] Action needed for continuity of your Razorpay account',
        self::ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH     => '[Important] Action needed for continuity of your Razorpay account',
        self::ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH     => '[Urgent] Clarifications needed for continuity of your Razorpay account',
        self::FUNDS_ON_HOLD                               => '[Urgent] Settlements have been paused for your Razorpay account',
        self::FUNDS_ON_HOLD_REMINDER                      => '[Urgent] Settlements have been paused for your Razorpay account',
    ];
}
