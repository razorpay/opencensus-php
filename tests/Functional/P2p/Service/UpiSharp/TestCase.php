<?php

namespace RZP\Tests\P2p\Service\UpiSharp;

use RZP\Tests\P2p\Service;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class TestCase extends Service\TestCase
{
    use Service\Base\Traits\NpciClTrait;

    protected $gateway = 'p2p_upi_sharp';

    protected $deviceSetMap = [
        Fixtures::DEVICE_1 => [
            'merchant'      => Fixtures::TEST_MERCHANT,
            'customer'      => Fixtures::RZP_LOCAL_CUSTOMER_1,
            'device'        => Fixtures::CUSTOMER_1_DEVICE_1,
            'handle'        => Fixtures::RAZOR_SHARP,
            'bank_account'  => Fixtures::CUSTOMER_1_BANK_ACCOUNT_1_SHARP,
            'vpa'           => Fixtures::CUSTOMER_1_VPA_1_SHARP,
        ],
        Fixtures::DEVICE_2 => [
            'merchant'      => Fixtures::TEST_MERCHANT,
            'customer'      => Fixtures::RZP_LOCAL_CUSTOMER_2,
            'device'        => Fixtures::CUSTOMER_2_DEVICE_1,
            'handle'        => Fixtures::RAZOR_SHARP,
            'bank_account'  => Fixtures::CUSTOMER_2_BANK_ACCOUNT_1_SHARP,
            'vpa'           => Fixtures::CUSTOMER_2_VPA_1_SHARP,
        ],
    ];
}
