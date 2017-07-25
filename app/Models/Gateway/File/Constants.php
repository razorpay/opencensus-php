<?php

namespace RZP\Models\Gateway\File;

use RZP\Models\Payment\Gateway;

class Constants
{
    const SUPPORTED_GATEWAYS = [
        Type::REFUND => [
            Gateway::NETBANKING_ICICI,
            Gateway::NETBANKING_HDFC,
            Gateway::NETBANKING_KOTAK,
            Gateway::NETBANKING_AXIS,
            Gateway::NETBANKING_FEDERAL,
            Gateway::NETBANKING_RBL,
            Gateway::NETBANKING_INDUSIND
        ],
        Type::CLAIM => [
        ],
        Type::EMI => [
        ],
        Type::COMBINED => [
        ],
    ];
}
