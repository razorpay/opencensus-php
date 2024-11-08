<?php

namespace RZP\Models\P2p\Session;

use RZP\Models\P2p\Base;

class Entity extends Base\Entity
{

    /************* Constants *******************/
    // Request constants
    const CUSTOMER_REFERENCE      = 'customer_reference';
    const MERCHANT_ID             = 'merchant_id';

    // Response constants
    const TOKEN                   = "token";
    const EXPIRE_AT               = "expire_at";
}
