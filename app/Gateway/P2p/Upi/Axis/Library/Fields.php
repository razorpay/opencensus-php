<?php

namespace RZP\Gateway\P2p\Upi\Axis\Library;

use RZP\Gateway\P2p\Base;

class Fields extends Base\Fields
{
    // ----------------- COMMON FIELDS ---------------- //

    const SIM_ID                    = 'simId';
    const UDF_PARAMETERS            = 'udfParameters';
    const MERCHANT_CUSTOMER_ID      = 'merchantCustomerId';
    const CUSTOMER_MOBILE_NUMBER    = 'customerMobileNumber';
    const MERCHANT_SIGNATURE        = 'merchantSignature';
    const TIMESTAMP                 = 'timestamp';
    const SHOULD_ACTIVATE           = 'shouldActivate';
    const ID                        = 'id';
    const PAYLOAD                   = 'payload';
    const ACTION                    = 'action';
}
