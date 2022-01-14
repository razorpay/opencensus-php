<?php

namespace RZP\Models\Workflow\Observer;

use RZP\Models\Merchant\RazorxTreatment;

class Constants
{
    const SCHEDULED_SETTLEMENT                  =   'schedule_assign';
    const MERCHANT_ACTION                       =   'merchant_actions';
    const EDIT_PAYMENT_METHOD                   =   'merchant_put_payment_methods';
    const EMAIL_CHANGE                          =   'merchant_edit_email';
    const MERCHANT_ACTIVATION_STATUS            =   'merchant_activation_status';

    const MERCHANT_SAVE_BUSINESS_WEBSITE        =   'merchant_save_business_website';

    const MERCHANT_ACTIVATION_UPDATE_WEBSITE    =  'merchant_activation_update_website';

    const INCREASE_TRANSACTION_LIMIT_SELF_SERVE =   'increase_transaction_limit_self_serve';

    const MERCHANT_GSTIN_SELF_SERVE_UPDATE      =   'merchant_gstin_self_serve_update';

    const MERCHANT_BANK_ACCOUNT_UPDATE          = 'merchant_bank_account_update';

    const APPROVED_TRANSACTION_LIMIT            =   'approved_transaction_limit';

    const APPROVE                               =   'approve';

    const WORKFLOW_VS_OBSERVER= [

        self::SCHEDULED_SETTLEMENT                  => ScheduleSettlementObserver::class,

        self::MERCHANT_ACTION                       => MerchantActionObserver::class,

        self::EDIT_PAYMENT_METHOD                   => PaymentMethodChangeObserver::class,

        self::EMAIL_CHANGE                          => EmailChangeObserver::class,

        self::MERCHANT_ACTIVATION_STATUS            => MerchantActivationStatusObserver::class,

        self::MERCHANT_SAVE_BUSINESS_WEBSITE        => MerchantSelfServeObserver::class,

        self::INCREASE_TRANSACTION_LIMIT_SELF_SERVE => MerchantSelfServeObserver::class,

        self::MERCHANT_ACTIVATION_UPDATE_WEBSITE    => MerchantSelfServeObserver::class,

        self::MERCHANT_GSTIN_SELF_SERVE_UPDATE      => MerchantSelfServeObserver::class,

        self::MERCHANT_BANK_ACCOUNT_UPDATE          => MerchantSelfServeObserver::class,
    ];

    const ROUTE_VS_RAZORX_EXPERIMENT = [

        self::SCHEDULED_SETTLEMENT           => RazorxTreatment::PERFORM_ACTION_ON_WORKFLOW_OBSERVER_DATA,

        self::MERCHANT_ACTION                => RazorxTreatment::PERFORM_ACTION_ON_WORKFLOW_OBSERVER_DATA,

        self::EDIT_PAYMENT_METHOD            => RazorxTreatment::PERFORM_ACTION_ON_WORKFLOW_OBSERVER_DATA,

        self::EMAIL_CHANGE                   => RazorxTreatment::PERFORM_ACTION_ON_WORKFLOW_OBSERVER_DATA,

        self::MERCHANT_ACTIVATION_STATUS     => RazorxTreatment::PERFORM_ACTION_ON_WORKFLOW_OBSERVER_DATA,
    ];

    const REJECTION_REASON                   = 'rejection_reason';

    const MESSAGE_SUBJECT                    = 'subject';

    const MESSAGE_BODY                       = 'body';

    const WORKFLOW_EXISTS                    = 'workflow_exists';

    const WORKFLOW_STATUS                    = 'workflow_status';

    const REJECTION_REASON_MESSAGE           = 'rejection_reason_message';

    const SHOW_REJECTION_REASON_ON_DASHBOARD = 'show_on_dashboard';

}
