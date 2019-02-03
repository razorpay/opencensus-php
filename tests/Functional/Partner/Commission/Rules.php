<?php

namespace Functional\Partner\Commission;

use RZP\Tests\Functional\TestCase;

class Rules extends TestCase
{
    public function BptVjGnFv6ITBm(array $data)
    {
        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $commissionFee = $calculator->getCommissionFee();
        $commissionTax = $calculator->getCommissionTax();
        $paymentFee    = $calculator->getPaymentFee();
        $paymentTax    = $calculator->getPaymentTax();
        $this->assertTrue($commissionFee < $paymentFee);
        $this->assertTrue($commissionTax < $paymentTax);
    }
}
