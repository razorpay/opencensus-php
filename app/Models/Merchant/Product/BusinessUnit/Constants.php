<?php

namespace RZP\Models\Merchant\Product\BusinessUnit;

use RZP\Models\Merchant\Product\Name as ProductName;

class Constants
{
    const PAYMENTS = 'payments';

    //    const RAZORPAYX = 'razorpayx';

    const VALID_BUSINESS = [
        self::PAYMENTS
    ];

    const PRODUCT_BU_MAPPING = [
        ProductName::PAYMENT_GATEWAY => self::PAYMENTS,
        ProductName::PAYMENT_LINKS   => self::PAYMENTS,
        ProductName::ROUTE           => self::PAYMENTS,
    ];
}
