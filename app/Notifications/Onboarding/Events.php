<?php


namespace RZP\Notifications\Onboarding;


class Events
{
    const UNREGISTERED_PAYMENTS_ENABLED     = 'UNREGISTERED_PAYMENTS_ENABLED';

    const UNREGISTERED_SETTLEMENTS_ENABLED  = 'UNREGISTERED_SETTLEMENTS_ENABLED';

    const REGISTERED_PAYMENTS_ENABLED       = 'REGISTERED_PAYMENTS_ENABLED';

    const REGISTERED_SETTLEMENTS_ENABLED    = 'REGISTERED_SETTLEMENTS_ENABLED';

    const PENNY_TESTING_FAILURE             = 'PENNY_TESTING_FAILURE';

    const NEEDS_CLARIFICATION               = 'NEEDS_CLARIFICATION';

    const ACTIVATED_MCC_PENDING             = 'ACTIVATED_MCC_PENDING';

    const PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION = 'PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION';
    const PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED   = 'PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED';

    const SMS_TEMPLATES = [
        self::NEEDS_CLARIFICATION              => 'sms.onboarding.needs_clarification',
        self::UNREGISTERED_PAYMENTS_ENABLED    => 'sms.onboarding.unregistered.payments_enabled',
        self::UNREGISTERED_SETTLEMENTS_ENABLED => 'sms.onboarding.unregistered.settlements_enabled',
        self::REGISTERED_PAYMENTS_ENABLED      => 'sms.onboarding.registered.payments_enabled',
        self::REGISTERED_SETTLEMENTS_ENABLED   => 'sms.onboarding.registered.settlements_enabled',
        self::PENNY_TESTING_FAILURE            => 'sms.onboarding.penny_test_failure',
        self::ACTIVATED_MCC_PENDING            => 'sms.onboarding.activated_mcc_pending',

        self::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION => 'sms.onboarding.escalation.payments_limit_breach',
        self::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED   => 'sms.onboarding.escalation.payments_breach_blocked',
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
        self::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION     => 'whatsapp.merchant.onboarding.payments_limit_breach',
        self::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED   => 'whatsapp.merchant.onboarding.payments_breach_blocked',
        self::UNREGISTERED_PAYMENTS_ENABLED                 => 'whatsapp.merchant.onboarding.payments_enabled',
        self::REGISTERED_PAYMENTS_ENABLED                   => 'whatsapp.merchant.onboarding.payments_enabled',
    ];

    const EMAIL_TEMPLATES = [
        self::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION => 'emails.merchant.onboarding.payments_limit_breach',
        self::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED   => 'emails.merchant.onboarding.payments_breach_blocked',
    ];

    const EMAIL_SUBJECTS = [
        self::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION => 'Razorpay Reminder: Update your KYC details to continue accepting payments',
        self::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED   => 'Razorpay Alert: Your payments are paused, submit KYC details to resume payments',
    ];
}
