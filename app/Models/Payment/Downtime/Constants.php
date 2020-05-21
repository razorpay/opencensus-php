<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class Constants
{
    /**
     * Map of method vs field which is being used as downtime instrument
     */
    const METHOD_QUERY_MAP = [
        Method::CARD       => Entity::NETWORK,
        Method::NETBANKING => Entity::ISSUER,
        Method::WALLET     => Entity::ISSUER,
        Method::UPI        => Entity::VPA_HANDLE,
    ];

    // List of active UPI gateways that are being used
    const UPI_GATEWAYS = [
        Gateway::UPI_AXIS,
        Gateway::UPI_ICICI,
        Gateway::UPI_MINDGATE,
    ];

    // List of active card gateways that are being used
    const CARD_GATEWAYS = [
        Gateway::AMEX,
        Gateway::AXIS_MIGS,
        Gateway::CYBERSOURCE,
        Gateway::FIRST_DATA,
        Gateway::HDFC,
        Gateway::HITACHI,
    ];
}
