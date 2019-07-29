<?php

namespace RZP\Gateway\P2p\Upi\Axis\Actions;

use RZP\Gateway\P2p\Upi\Axis\Fields;

class UpiAction extends Action
{
    const COLLECT_REQUEST_RECEIVED                  = 'COLLECT_REQUEST_RECEIVED';

    const CUSTOMER_CREDITED_VIA_PAY                 = 'CUSTOMER_CREDITED_VIA_PAY';

    const CUSTOMER_DEBITED_VIA_PAY                  = 'CUSTOMER_DEBITED_VIA_PAY';

    const CUSTOMER_CREDITED_VIA_COLLECT             = 'CUSTOMER_CREDITED_VIA_COLLECT';

    const CUSTOMER_DEBITED_VIA_COLLECT              = 'CUSTOMER_DEBITED_VIA_COLLECT';
}
