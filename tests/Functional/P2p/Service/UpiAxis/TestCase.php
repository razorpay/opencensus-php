<?php

namespace RZP\Tests\P2p\Service\UpiAxis;

use Carbon\Carbon;
use RZP\Tests\P2p\Service;
use RZP\Gateway\P2p\Upi\Axis\Mock\Sdk;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class TestCase extends Service\TestCase
{
    protected $gateway = 'p2p_upi_axis';

    protected $deviceSetMap = [
        Fixtures::DEVICE_1 => [
            'merchant'      => Fixtures::TEST_MERCHANT,
            'customer'      => Fixtures::RZP_LOCAL_CUSTOMER_1,
            'device'        => Fixtures::CUSTOMER_1_DEVICE_1,
            'handle'        => Fixtures::RAZOR_AXIS,
            'bank_account'  => Fixtures::CUSTOMER_1_BANK_ACCOUNT_1_AXIS,
            'vpa'           => Fixtures::CUSTOMER_1_VPA_1_AXIS,
        ],
        Fixtures::DEVICE_2 => [
            'merchant'      => Fixtures::TEST_MERCHANT,
            'customer'      => Fixtures::RZP_LOCAL_CUSTOMER_2,
            'device'        => Fixtures::CUSTOMER_2_DEVICE_1,
            'handle'        => Fixtures::RAZOR_AXIS,
            'bank_account'  => Fixtures::CUSTOMER_2_BANK_ACCOUNT_1_AXIS,
            'vpa'           => Fixtures::CUSTOMER_2_VPA_1_AXIS,
        ],
    ];

    protected function mockSdk($gateway = null): Sdk
    {
        return parent::mockSdk($gateway);
    }
}
