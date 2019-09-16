<?php

namespace RZP\Models\Plan\Subscription\SubscriptionTransaction;

use App;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const CYCLE_ID              = 'cycle_id';
    const TYPE                  = 'type';
    const ADDON_AMOUNT          = 'addon_amount';
    const PLAN_AMOUNT           = 'plan_amount';
    const UNUSED_AMOUNT         = 'unused_amount';
    const PAYMENT_AMOUNT        = 'payment_amount';
    const REFUND_AMOUNT         = 'refund_amount';
    const INVOICE_ID            = 'invoice_id';
    const PAYMENT_ID            = 'payment_id';
    const CREDIT_NOTE_ID        = 'credit_note_id';
    const STATUS                = 'status';

    protected $entity = 'subscription_transaction';
}
