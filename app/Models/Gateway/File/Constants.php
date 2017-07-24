<?php

namespace RZP\Models\Gateway\File;

use RZP\Models\Payment\Gateway;

class Constants
{
    const SUPPORTED_GATEWAYS = [
        Type::REFUND => [
            Gateway::NETBANKING_HDFC,
        ],
    ];
}
