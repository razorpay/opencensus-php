<?php

namespace RZP\Models\P2p\Upi;

use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const INITIATE_GATEWAY_CALLBACK         = 'initiateGatewayCallback';
    const INITIATE_GATEWAY_CALLBACK_SUCCESS = 'initiateGatewayCallbackSuccess';

    const GATEWAY_CALLBACK                  = 'gatewayCallback';
    const GATEWAY_CALLBACK_SUCCESS          = 'gatewayCallbackSuccess';

    const INITIATE_REMINDER_CALLBACK         = 'initiateReminderCallback';
    const INITIATE_REMINDER_CALLBACK_SUCCESS = 'initiateReminderCallbackSuccess';

    const REMINDER_CALLBACK                  = 'reminderCallback';
}
