<?php

namespace RZP\Models\Merchant\Product;

class Name
{
    const ALL = 'all';

    const PAYMENT_GATEWAY = 'payment_gateway';

    const PAYMENT_LINKS = 'payment_links';

    const ROUTE         = 'route';

    const ADMIN_ENABLED = [
        self::ALL
    ];

    const ENABLED = [
        self::PAYMENT_GATEWAY,
        self::PAYMENT_LINKS,
        self::ROUTE,
    ];

}
