<?php


namespace RZP\Notifications\Dashboard;

use RZP\Constants\MailTags;
use RZP\Notifications\Channel;
use RZP\Models\User\Role as UserRole;

class Events
{
    const EVENT = 'event';

    const MERCHANT_BUSINESS_WEBSITE_ADD                             = 'MERCHANT_BUSINESS_WEBSITE_ADD';

    const MERCHANT_BUSINESS_WEBSITE_UPDATE                          = 'MERCHANT_BUSINESS_WEBSITE_UPDATE';

    const BANK_ACCOUNT_CHANGE_SUCCESSFUL                            = 'BANK_ACCOUNT_CHANGE_SUCCESSFUL';

    const BANK_ACCOUNT_CHANGE_REQUEST                               = 'BANK_ACCOUNT_CHANGE_REQUEST';

    const BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                 = 'BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE';

    const INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE                = 'INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE';

    const BUSINESS_WEBSITE_ADD_REJECTION_REASON                     = 'BUSINESS_WEBSITE_ADD_REJECTION_REASON';

    const BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                  = 'BUSINESS_WEBSITE_UPDATE_REJECTION_REASON';

    const INCREASE_TRANSACTION_LIMIT_REJECTION_REASON               = 'INCREASE_TRANSACTION_LIMIT_REJECTION_REASON';

    const NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW            = 'NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW';

    const NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW               = 'NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW';

    const NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW       = 'NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW';

    const NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW  = 'NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW';

    const GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS                   = 'GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS';

    const GSTIN_UPDATED_ON_WORKFLOW_APPROVE                         = 'GSTIN_UPDATED_ON_WORKFLOW_APPROVE';

    const GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS                     = 'GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS';

    const GSTIN_ADDED_ON_WORKFLOW_APPROVE                           = 'GSTIN_ADDED_ON_WORKFLOW_APPROVE';

    const GSTIN_UPDATE_REJECTION_REASON                             = 'GSTIN_UPDATE_REJECTION_REASON';

    const GSTIN_ADD_REJECTION_REASON                                = 'GSTIN_ADD_REJECTION_REASON';

    const NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW                 = 'NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW';

    const NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW              = 'NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW';

    // Event vs sms templates mapping
    const SMS_TEMPLATES = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                            => 'sms.dashboard.merchant_business_website_add',
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                         => 'sms.dashboard.merchant_business_website_update',
        self::BANK_ACCOUNT_CHANGE_REQUEST                              => 'sms.dashboard.bank_account_change_request',
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                => 'sms.dashboard.bank_account_change_penny_testing_failure',
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                           => 'sms.dashboard.bank_account_change_successful',
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE               => 'sms.dashboard.increase_transaction_limit_request_approve',
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                    => 'sms.dashboard.merchant_business_website_add_rejection',
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                 => 'sms.dashboard.merchant_business_website_update_rejection',
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON              => 'sms.dashboard.increase_transaction_limit_rejection',
        self::GSTIN_UPDATE_REJECTION_REASON                            => 'sms.dashboard.merchant_gstin_rejection',
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW => 'sms.dashboard.increase_transaction_limit_needs_clarification',
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW      => 'sms.dashboard.merchant_bank_account_needs_clarification',
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW             => 'sms.dashboard.merchant_gstin_needs_clarification',
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW           => 'sms.dashboard.merchant_website_update_needs_clarification',
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW              => 'sms.dashboard.merchant_website_add_needs_clarification',
    ];

    /**
     * Event vs sms template keys mapping : only whitelisted keys will be sent to raven for template rendering
     * this will prevent to send extra key-value raven in payload
     */
    const SMS_TEMPLATE_KEYS = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                            => [Constants::UPDATED_BUSINESS_WEBSITE],
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                         => [Constants::PREVIOUS_BUSINESS_WEBSITE, Constants::UPDATED_BUSINESS_WEBSITE],
        self::BANK_ACCOUNT_CHANGE_REQUEST                              => [Constants::NAME, Constants::BENEFICIARY_NAME, Constants::ACCOUNT_NUMBER, Constants::IFSC_CODE],
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                => [],
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                           => [Constants::BENEFICIARY_NAME, Constants::ACCOUNT_NUMBER, Constants::IFSC_CODE],
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE               => [Constants::UPDATED_TRANSACTION_LIMIT],
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                    => [Constants::MERCHANT_NAME],
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                 => [Constants::MERCHANT_NAME],
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON              => [Constants::MERCHANT_NAME],
        self::GSTIN_UPDATE_REJECTION_REASON                            => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW => [Constants::MERCHANT_NAME, Constants::MAX_PAYMENT_AMOUNT],
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW      => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW           => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW              => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW             => [Constants::MERCHANT_NAME],
    ];

    // Event vs whatsapp templates mapping
    const WHATSAPP_TEMPLATES = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                            => 'whatsapp.merchant.dashboard.merchant_business_website_add',
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                         => 'whatsapp.merchant.dashboard.merchant_business_website_update',
        self::BANK_ACCOUNT_CHANGE_REQUEST                              => 'whatsapp.merchant.dashboard.bank_account_change_request',
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                => 'whatsapp.merchant.dashboard.bank_account_change_penny_testing_failure',
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                           => 'whatsapp.merchant.dashboard.bank_account_change_successful',
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE               => 'whatsapp.merchant.dashboard.increase_transaction_limit_request_approve',
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                    => 'whatsapp.merchant.dashboard.merchant_business_website_add_rejection',
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                 => 'whatsapp.merchant.dashboard.merchant_business_website_update_rejection',
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON              => 'whatsapp.merchant.dashboard.increase_transaction_limit_rejection',
        self::GSTIN_UPDATE_REJECTION_REASON                            => 'whatsapp.merchant.dashboard.merchant_gstin_rejection',
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW => 'whatsapp.merchant.dashboard.increase_transaction_limit_needs_clarification',
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW      => 'whatsapp.merchant.dashboard.merchant_bank_account_needs_clarification',
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW           => 'whatsapp.merchant.dashboard.merchant_business_website_update_needs_clarification',
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW              => 'whatsapp.merchant.dashboard.merchant_business_website_add_needs_clarification',
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW             => 'whatsapp.merchant.dashboard.merchant_gstin_needs_clarification',
    ];

    /**
     * Event vs whatsapp template keys mapping : only whitelisted keys will be sent to stork as parameters for template rendering
     */
    const WHATSAPP_TEMPLATE_KEYS = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                            => [Constants::UPDATED_BUSINESS_WEBSITE],
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                         => [Constants::PREVIOUS_BUSINESS_WEBSITE, Constants::UPDATED_BUSINESS_WEBSITE],
        self::BANK_ACCOUNT_CHANGE_REQUEST                              => [Constants::NAME, Constants::BENEFICIARY_NAME, Constants::ACCOUNT_NUMBER, Constants::IFSC_CODE],
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                => [],
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                           => [Constants::BENEFICIARY_NAME, Constants::ACCOUNT_NUMBER, Constants::IFSC_CODE],
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE               => [Constants::UPDATED_TRANSACTION_LIMIT],
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                    => [Constants::MERCHANT_NAME],
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                 => [Constants::MERCHANT_NAME],
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON              => [Constants::MERCHANT_NAME],
        self::GSTIN_UPDATE_REJECTION_REASON                            => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW => [Constants::MERCHANT_NAME, Constants::MAX_PAYMENT_AMOUNT],
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW      => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW           => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW              => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW             => [Constants::MERCHANT_NAME],
    ];

    // Event vs email templates mapping
    const EMAIL_TEMPLATES = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                            => 'emails.merchant.merchant_business_website_add',
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                         => 'emails.merchant.merchant_business_website_update',
        self::BANK_ACCOUNT_CHANGE_REQUEST                              => 'emails.merchant.bankaccount_change_request',
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                => 'emails.merchant.bankaccount_change_penny_testing_failure',
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                           => 'emails.merchant.bankaccount_change',
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE               => 'emails.merchant.increase_transaction_limit_request_approve',
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS                  => 'emails.merchant.gstin_updated_self_serve',
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE                        => 'emails.merchant.gstin_updated_on_workflow_approve',
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                    => 'emails.merchant.rejection_reason_notification',
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                 => 'emails.merchant.rejection_reason_notification',
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON              => 'emails.merchant.rejection_reason_notification',
        self::GSTIN_UPDATE_REJECTION_REASON                            => 'emails.merchant.rejection_reason_notification',
        self::GSTIN_ADD_REJECTION_REASON                               => 'emails.merchant.rejection_reason_notification',
        self::GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS                    => 'emails.merchant.gstin_updated_self_serve',
        self::GSTIN_ADDED_ON_WORKFLOW_APPROVE                          => 'emails.merchant.gstin_updated_on_workflow_approve',
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW      => 'emails.merchant.needs_clarification_on_workflow',
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW => 'emails.merchant.needs_clarification_on_workflow',
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW           => 'emails.merchant.needs_clarification_on_workflow',
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW              => 'emails.merchant.needs_clarification_on_workflow',
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW             => 'emails.merchant.needs_clarification_on_workflow',
        self::NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW                => 'emails.merchant.needs_clarification_on_workflow',
    ];

    // Event vs email Tags mapping
    const EMAIL_TAGS = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                            => MailTags::MERCHANT_BUSINESS_WEBSITE_ADD,
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                         => MailTags::MERCHANT_BUSINESS_WEBSITE_UPDATE,
        self::BANK_ACCOUNT_CHANGE_REQUEST                              => MailTags::ACCOUNT_CHANGE_REQUEST,
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                => MailTags::ACCOUNT_CHANGED,
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                           => MailTags::ACCOUNT_CHANGED,
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE               => MailTags::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE,
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS                  => MailTags::GSTIN_UPDATED_VALIDATION_SUCCESS,
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE                        => MailTags::GSTIN_UPDATED_WORKFLOW_APPROVE,
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                    => MailTags::MERCHANT_BUSINESS_WEBSITE_ADD_REJECTION_REASON,
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                 => MailTags::MERCHANT_BUSINESS_WEBSITE_UPDATE_REJECTION_REASON,
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON              => MailTags::MERCHANT_INCREASE_TRANSACTION_LIMIT_REJECTION_REASON,
        self::GSTIN_UPDATE_REJECTION_REASON                            => MailTags::MERCHANT_GSTIN_UPDATE_REJECTION_REASON,
        self::GSTIN_ADD_REJECTION_REASON                               => MailTags::MERCHANT_GSTIN_ADD_REJECTION_REASON,
        self::GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS                    => MailTags::GSTIN_ADDED_VALIDATION_SUCCESS,
        self::GSTIN_ADDED_ON_WORKFLOW_APPROVE                          => MailTags::GSTIN_ADDED_WORKFLOW_APPROVE,
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW      => MailTags::MERCHANT_CLARIFICATION_ON_BANK_ACCOUNT_WORKFLOW,
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW => MailTags::MERCHANT_CLARIFICATION_ON_TRANSACTION_LIMIT_WORKFLOW,
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW           => MailTags::MERCHANT_CLARIFICATION_ON_WEBSITE_UPDATE_WORKFLOW,
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW              => MailTags::MERCHANT_CLARIFICATION_ON_WEBSITE_ADD_WORKFLOW,
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW             => MailTags::MERCHANT_CLARIFICATION_ON_GSTIN_UPDATE_WORKFLOW,
        self::NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW                => MailTags::MERCHANT_CLARIFICATION_ON_GSTIN_ADD_WORKFLOW
    ];

    // Event vs email subject mapping
    const EMAIL_SUBJECTS = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                            => 'Razorpay | Update on API key access for %s(MID: %s)',
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                         => 'Razorpay | Website updated successfully for %s(MID: %s)',
        self::BANK_ACCOUNT_CHANGE_REQUEST                              => 'Razorpay | Bank account change request for %s(MID: %s)',
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                => 'Razorpay | Update on bank account change request for %s(MID: %s)',
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                           => 'Razorpay | Bank account change successful for %s(MID: %s)',
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE               => 'Razorpay: Transaction Limit updated successfully for %s(MID: %s)',
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS                  => 'Razorpay | Gstin updated for %s(MID: %s)',
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE                        => 'Razorpay | Gstin updated for %s(MID: %s)',
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                    => '%s',
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                 => '%s',
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON              => '%s',
        self::GSTIN_UPDATE_REJECTION_REASON                            => '%s',
        self::GSTIN_ADD_REJECTION_REASON                               => '%s',
        self::GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS                    => 'Razorpay | Gstin added for %s(MID: %s)',
        self::GSTIN_ADDED_ON_WORKFLOW_APPROVE                          => 'Razorpay | Gstin added for %s(MID: %s)',
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW      => '%s',
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW => '%s',
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW           => '%s',
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW              => '%s',
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW             => '%s',
        self::NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW                => '%s',
    ];

    // Event vs recipients role mapping
    const RECIPIENT_ROLES = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                            => [UserRole::OWNER],
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                         => [UserRole::OWNER],
        self::BANK_ACCOUNT_CHANGE_REQUEST                              => [UserRole::OWNER, UserRole::ADMIN],
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                => [UserRole::OWNER, UserRole::ADMIN],
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                           => [UserRole::OWNER, UserRole::ADMIN],
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE               => [UserRole::OWNER],
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS                  => [UserRole::OWNER],
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE                        => [UserRole::OWNER],
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                    => [UserRole::OWNER],
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                 => [UserRole::OWNER],
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON              => [UserRole::OWNER],
        self::GSTIN_UPDATE_REJECTION_REASON                            => [UserRole::OWNER],
        self::GSTIN_ADD_REJECTION_REASON                               => [UserRole::OWNER],
        self::GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS                    => [UserRole::OWNER],
        self::GSTIN_ADDED_ON_WORKFLOW_APPROVE                          => [UserRole::OWNER],
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW      => [UserRole::OWNER],
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW => [UserRole::OWNER],
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW           => [UserRole::OWNER],
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW              => [UserRole::OWNER],
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW             => [UserRole::OWNER],
        self::NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW                => [UserRole::OWNER],
    ];

    // Event vs supported channel mapping
    const SUPPORTED_CHANNELS_FOR_EVENTS = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                            => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                         => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BANK_ACCOUNT_CHANGE_REQUEST                              => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                           => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE               => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS                  => [Channel::EMAIL],
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE                        => [Channel::EMAIL],
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                    => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                 => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON              => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::GSTIN_UPDATE_REJECTION_REASON                            => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::GSTIN_ADD_REJECTION_REASON                               => [Channel::EMAIL],
        self::GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS                    => [Channel::EMAIL],
        self::GSTIN_ADDED_ON_WORKFLOW_APPROVE                          => [Channel::EMAIL],
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW      => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW           => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW              => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW             => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW                => [Channel::EMAIL],
    ];
}
