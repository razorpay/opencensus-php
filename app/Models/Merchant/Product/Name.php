<?php

namespace RZP\Models\Merchant\Product;

class Name
{
    const PAYMENT_GATEWAY = 'payment_gateway';

    const PAYMENT_LINKS = 'payment_links';

    const ENABLED = [
        self::PAYMENT_GATEWAY,
        self::PAYMENT_LINKS
    ];

}
