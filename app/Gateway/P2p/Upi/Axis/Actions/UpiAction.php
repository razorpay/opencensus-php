<?php

namespace RZP\Gateway\P2p\Upi\Axis\Actions;

use RZP\Gateway\P2p\Upi\Axis\Fields;

/**
 * Class UpiAction
 * @package RZP\Gateway\P2p\Upi\Axis\Actions
 *
 * Constants of this class are used in RZP\Http\Controllers\GatewayController for redirecting p2p callbacks to correct
 * controller based on callback types
 */
class UpiAction extends Action
{
    const COLLECT_REQUEST_RECEIVED                  = 'COLLECT_REQUEST_RECEIVED';

    const CUSTOMER_CREDITED_VIA_PAY                 = 'CUSTOMER_CREDITED_VIA_PAY';

    const CUSTOMER_DEBITED_VIA_PAY                  = 'CUSTOMER_DEBITED_VIA_PAY';

    const CUSTOMER_CREDITED_VIA_COLLECT             = 'CUSTOMER_CREDITED_VIA_COLLECT';

    const CUSTOMER_DEBITED_VIA_COLLECT              = 'CUSTOMER_DEBITED_VIA_COLLECT';

    const CUSTOMER_DEBITED_FOR_MERCHANT_VIA_PAY     = 'CUSTOMER_DEBITED_FOR_MERCHANT_VIA_PAY';

    const CUSTOMER_DEBITED_FOR_MERCHANT_VIA_COLLECT = 'CUSTOMER_DEBITED_FOR_MERCHANT_VIA_COLLECT';

    //mandate callbacks
    const CUSTOMER_INCOMING_MANDATE_CREATE_REQUEST_RECEIVED             = 'CUSTOMER_INCOMING_MANDATE_CREATE_REQUEST_RECEIVED';

    const CUSTOMER_INCOMING_MANDATE_CREATED                             = 'CUSTOMER_INCOMING_MANDATE_CREATED';

    const CUSTOMER_INCOMING_MANDATE_UPDATE_REQUEST_RECEIVED             = 'CUSTOMER_INCOMING_MANDATE_UPDATE_REQUEST_RECEIVED';

    const CUSTOMER_INCOMING_MANDATE_PAUSE_REQUEST_RECEIVED              = 'CUSTOMER_INCOMING_MANDATE_PAUSE_REQUEST_RECEIVED';

    const CUSTOMER_INCOMING_MANDATE_UPDATED                             = 'CUSTOMER_INCOMING_MANDATE_UPDATED';

    const AUTHORIZE_MANDATE                                             = 'AUTHORIZE_MANDATE';

    const REJECT_MANDATE                                                = 'REJECT_MANDATE';

    const CUSTOMER_INCOMING_PRE_PAYMENT_NOTIFICATION_MANDATE_RECEIVED   = 'CUSTOMER_INCOMING_PRE_PAYMENT_NOTIFICATION_MANDATE_RECEIVED';

    const CUSTOMER_OUTGOING_MANDATE_PAUSED                              = 'CUSTOMER_OUTGOING_MANDATE_PAUSED';

    const MANDATE_STATUS_UPDATE                                         = 'MANDATE_STATUS_UPDATE';

    const INCOMING_MANDATE_UPDATE                                       = 'incoming_mandate_update';

    const INCOMING_MANDATE_PAUSE                                        = 'incoming_mandate_pause';
}
