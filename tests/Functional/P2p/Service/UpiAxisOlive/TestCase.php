<?php

namespace RZP\Tests\P2p\Service\UpiAxisOlive;

use RZP\Tests\P2p\Service;
use RZP\Gateway\P2p\Upi\AxisOlive\Mock\Callback;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class TestCase extends Service\TestCase
{
    use Service\Base\Traits\NpciClTrait;

    protected $gateway = 'p2m_upi_axis_olive';

    protected $deviceSetMap = [
        Fixtures::DEVICE_1 => [
            'merchant'      => Fixtures::TEST_MERCHANT,
            'customer'      => Fixtures::RZP_LOCAL_CUSTOMER_1,
            'device'        => Fixtures::CUSTOMER_1_DEVICE_1,
            'handle'        => Fixtures::RAZOR_AXIS_OLIVE,
            'bank_account'  => Fixtures::CUSTOMER_1_BANK_ACCOUNT_1_SHARP,
            'vpa'           => Fixtures::CUSTOMER_1_VPA_1_SHARP,
        ],
        Fixtures::DEVICE_2 => [
            'merchant'      => Fixtures::TEST_MERCHANT,
            'customer'      => Fixtures::RZP_LOCAL_CUSTOMER_2,
            'device'        => Fixtures::CUSTOMER_2_DEVICE_1,
            'handle'        => Fixtures::RAZOR_AXIS_OLIVE,
            'bank_account'  => Fixtures::CUSTOMER_2_BANK_ACCOUNT_1_SHARP,
            'vpa'           => Fixtures::CUSTOMER_2_VPA_1_SHARP,
        ],
    ];

    protected function mockCallback($gateway = null): Callback
    {
        return parent::mockCallback($gateway);
    }
}
