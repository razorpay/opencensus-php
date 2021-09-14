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

    const APPROVE                               =   'approve';

    const WORKFLOW_VS_OBSERVER= [

        self::SCHEDULED_SETTLEMENT           => ScheduleSettlementObserver::class,

        self::MERCHANT_ACTION                => MerchantActionObserver::class,

        self::EDIT_PAYMENT_METHOD            => PaymentMethodChangeObserver::class,

        self::EMAIL_CHANGE                   => EmailChangeObserver::class,

        self::MERCHANT_ACTIVATION_STATUS     => MerchantActivationStatusObserver::class,

        self::MERCHANT_SAVE_BUSINESS_WEBSITE => BusinessWebsiteSelfServeObserver::class,

    ];

    const ROUTE_VS_RAZORX_EXPERIMENT = [

        self::SCHEDULED_SETTLEMENT           => RazorxTreatment::PERFORM_ACTION_ON_WORKFLOW_OBSERVER_DATA,

        self::MERCHANT_ACTION                => RazorxTreatment::PERFORM_ACTION_ON_WORKFLOW_OBSERVER_DATA,

        self::EDIT_PAYMENT_METHOD            => RazorxTreatment::PERFORM_ACTION_ON_WORKFLOW_OBSERVER_DATA,

        self::EMAIL_CHANGE                   => RazorxTreatment::PERFORM_ACTION_ON_WORKFLOW_OBSERVER_DATA,

        self::MERCHANT_ACTIVATION_STATUS     => RazorxTreatment::PERFORM_ACTION_ON_WORKFLOW_OBSERVER_DATA,
    ];

    const REJECTION_REASON         = 'rejection_reason';

    const MESSAGE_SUBJECT          = 'subject';

    const MESSAGE_BODY             = 'body';

    const WORKFLOW_EXISTS          = 'workflow_exists';

    const WORKFLOW_STATUS          = 'workflow_status';

    const REJECTION_REASON_MESSAGE = 'rejection_reason_message';

}
