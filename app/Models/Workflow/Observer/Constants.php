<?php

namespace RZP\Models\Workflow\Observer;

class Constants
{
    const SCHEDULED_SETTLEMENT                  =   'schedule_assign';
    const MERCHANT_ACTION                       =   'merchant_actions';
    const EDIT_PAYMENT_METHOD                   =   'merchant_put_payment_methods';
    const EMAIL_CHANGE                          =   'merchant_edit_email';

    const APPROVE                               =   'approve';

    const WORKFLOW_VS_OBSERVER= [

        self::SCHEDULED_SETTLEMENT          => ScheduleSettlementObserver::class,

        self::MERCHANT_ACTION               => MerchantActionObserver::class,

        self::EDIT_PAYMENT_METHOD           => PaymentMethodChangeObserver::class,

        self::EMAIL_CHANGE                  => EmailChangeObserver::class,

    ];
}
