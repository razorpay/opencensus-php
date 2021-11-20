<?php


namespace RZP\Notifications\Dashboard;

use RZP\Constants\MailTags;
use RZP\Notifications\Channel;
use RZP\Models\User\Role as UserRole;

class Events
{
    const EVENT = 'event';

    const MERCHANT_BUSINESS_WEBSITE_ADD                     = 'MERCHANT_BUSINESS_WEBSITE_ADD';

    const MERCHANT_BUSINESS_WEBSITE_UPDATE                  = 'MERCHANT_BUSINESS_WEBSITE_UPDATE';

    const BANK_ACCOUNT_CHANGE_SUCCESSFUL                    = 'BANK_ACCOUNT_CHANGE_SUCCESSFUL';

    const BANK_ACCOUNT_CHANGE_REQUEST                       = 'BANK_ACCOUNT_CHANGE_REQUEST';

    const BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE         = 'BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE';

    const INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE        = 'INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE';

    const REJECTION_REASON_NOTIFICATION                     = 'REJECTION_REASON_NOTIFICATION';

    const GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS           = 'GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS';

    const GSTIN_UPDATED_ON_WORKFLOW_APPROVE                 = 'GSTIN_UPDATED_ON_WORKFLOW_APPROVE';

    // Event vs sms templates mapping
    const SMS_TEMPLATES = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                 => 'sms.dashboard.merchant_business_website_add',
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE              => 'sms.dashboard.merchant_business_website_update',
        self::BANK_ACCOUNT_CHANGE_REQUEST                   => 'sms.dashboard.bank_account_change_request',
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE     => 'sms.dashboard.bank_account_change_penny_testing_failure',
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                => 'sms.dashboard.bank_account_change_successful',
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE    => 'sms.dashboard.increase_transaction_limit_request_approve',
    ];

    /**
     * Event vs sms template keys mapping : only whitelisted keys will be sent to raven for template rendering
     * this will prevent to send extra key-value raven in payload
     */
    const SMS_TEMPLATE_KEYS = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                 => [Constants::UPDATED_BUSINESS_WEBSITE],
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE              => [Constants::PREVIOUS_BUSINESS_WEBSITE, Constants::UPDATED_BUSINESS_WEBSITE],
        self::BANK_ACCOUNT_CHANGE_REQUEST                   => [Constants::NAME, Constants::BENEFICIARY_NAME, Constants::ACCOUNT_NUMBER, Constants::IFSC_CODE],
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE     => [],
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                => [Constants::BENEFICIARY_NAME, Constants::ACCOUNT_NUMBER, Constants::IFSC_CODE],
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE    => [Constants::UPDATED_TRANSACTION_LIMIT],
    ];

    // Event vs whatsapp templates mapping
    const WHATSAPP_TEMPLATES = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                 => 'whatsapp.merchant.dashboard.merchant_business_website_add',
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE              => 'whatsapp.merchant.dashboard.merchant_business_website_update',
        self::BANK_ACCOUNT_CHANGE_REQUEST                   => 'whatsapp.merchant.dashboard.bank_account_change_request',
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE     => 'whatsapp.merchant.dashboard.bank_account_change_penny_testing_failure',
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                => 'whatsapp.merchant.dashboard.bank_account_change_successful',
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE    => 'whatsapp.merchant.dashboard.increase_transaction_limit_request_approve',
    ];

    /**
     * Event vs whatsapp template keys mapping : only whitelisted keys will be sent to stork as parameters for template rendering
     */
    const WHATSAPP_TEMPLATE_KEYS = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                 => [Constants::UPDATED_BUSINESS_WEBSITE],
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE              => [Constants::PREVIOUS_BUSINESS_WEBSITE, Constants::UPDATED_BUSINESS_WEBSITE],
        self::BANK_ACCOUNT_CHANGE_REQUEST                   => [Constants::NAME, Constants::BENEFICIARY_NAME, Constants::ACCOUNT_NUMBER, Constants::IFSC_CODE],
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE     => [],
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                => [Constants::BENEFICIARY_NAME, Constants::ACCOUNT_NUMBER, Constants::IFSC_CODE],
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE    => [Constants::UPDATED_TRANSACTION_LIMIT],
    ];

    // Event vs email templates mapping
    const EMAIL_TEMPLATES = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                 => 'emails.merchant.merchant_business_website_add',
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE              => 'emails.merchant.merchant_business_website_update',
        self::BANK_ACCOUNT_CHANGE_REQUEST                   => 'emails.merchant.bankaccount_change_request',
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE     => 'emails.merchant.bankaccount_change_penny_testing_failure',
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                => 'emails.merchant.bankaccount_change',
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE    => 'emails.merchant.increase_transaction_limit_request_approve',
        self::REJECTION_REASON_NOTIFICATION                 => 'emails.merchant.rejection_reason_notification',
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS       => 'emails.merchant.gstin_updated_self_serve',
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE             => 'emails.merchant.gstin_updated_on_workflow_approve',
    ];

    // Event vs email Tags mapping
    const EMAIL_TAGS = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                 => MailTags::MERCHANT_BUSINESS_WEBSITE_ADD,
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE              => MailTags::MERCHANT_BUSINESS_WEBSITE_UPDATE,
        self::BANK_ACCOUNT_CHANGE_REQUEST                   => MailTags::ACCOUNT_CHANGE_REQUEST,
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE     => MailTags::ACCOUNT_CHANGED,
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                => MailTags::ACCOUNT_CHANGED,
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE    => MailTags::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE,
        self::REJECTION_REASON_NOTIFICATION                 => MailTags::UPDATE_REJECTION_REASON,
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS       => MailTags::GSTIN_UPDATED_VALIDATION_SUCCESS,
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE             => MailTags::GSTIN_UPDATED_WORKFLOW_APPROVE,
    ];

    // Event vs email subject mapping
    const EMAIL_SUBJECTS = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                 => 'Razorpay | Update on API key access for %s(MID: %s)',
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE              => 'Razorpay | Website updated successfully for %s(MID: %s)',
        self::BANK_ACCOUNT_CHANGE_REQUEST                   => 'Razorpay | Bank account change request for %s(MID: %s)',
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE     => 'Razorpay | Update on bank account change request for %s(MID: %s)',
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                => 'Razorpay | Bank account change successful for %s(MID: %s)',
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE    => 'Razorpay: Transaction Limit updated successfully for %s(MID: %s)',
        self::REJECTION_REASON_NOTIFICATION                 => '%s',
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS       => 'Razorpay | Gstin updated for %s(MID: %s)',
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE             => 'Razorpay | Gstin updated for %s(MID: %s)',
    ];

    // Event vs recipients role mapping
    const RECIPIENT_ROLES = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                 => [UserRole::OWNER],
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE              => [UserRole::OWNER],
        self::BANK_ACCOUNT_CHANGE_REQUEST                   => [UserRole::OWNER, UserRole::ADMIN],
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE     => [UserRole::OWNER, UserRole::ADMIN],
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                => [UserRole::OWNER, UserRole::ADMIN],
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE    => [UserRole::OWNER],
        self::REJECTION_REASON_NOTIFICATION                 => [UserRole::OWNER],
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS       => [UserRole::OWNER],
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE             => [UserRole::OWNER],
    ];

    // Event vs supported channel mapping
    const SUPPORTED_CHANNELS_FOR_EVENTS = [
        Events::MERCHANT_BUSINESS_WEBSITE_ADD               => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        Events::MERCHANT_BUSINESS_WEBSITE_UPDATE            => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        Events::BANK_ACCOUNT_CHANGE_REQUEST                 => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        Events::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE   => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        Events::BANK_ACCOUNT_CHANGE_SUCCESSFUL              => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        Events::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE  => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        Events::REJECTION_REASON_NOTIFICATION               => [Channel::EMAIL],
        Events::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS     => [Channel::EMAIL],
        Events::GSTIN_UPDATED_ON_WORKFLOW_APPROVE           => [Channel::EMAIL],
    ];
}
