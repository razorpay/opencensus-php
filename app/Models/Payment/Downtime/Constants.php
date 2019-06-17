<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Models\Payment\Method;

/**
 * Map of method vs field which is being used as downtime instrument
 */
class Constants
{
    const METHOD_QUERY_MAP = [
        Method::CARD       => Entity::NETWORK,
        Method::NETBANKING => Entity::ISSUER,
        Method::WALLET     => Entity::ISSUER,
    ];
}
