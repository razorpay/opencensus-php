<?php

namespace RZP\Tests\Functional\PaymentsUpi;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\PaymentsUpiTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiSharpGatewayTest extends TestCase
{
    use PaymentTrait;
    use PaymentsUpiTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_sharp_terminal');
    }

    public function testValidateVpaNoName()
    {
        $this->validateVpa('test@razorpay');

        $vpa = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertSame(null, $vpa->getName());
        $this->assertSame('valid', $vpa->getStatus());
        $this->assertGreaterThanOrEqual(1600000000, $vpa->getReceivedAt());
    }

    public function testValidateVpaWithName()
    {
        $this->validateVpa('withname@razorpay');

        $vpa = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertSame('Razorpay Customer', $vpa->getName());
        $this->assertSame('valid', $vpa->getStatus());
        $this->assertGreaterThanOrEqual(1600000000, $vpa->getReceivedAt());
    }

    public function testValidateInvalidVpa()
    {
        $this->validateVpa('invalidvpa@razorpay', false);

        $vpa = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertSame(null, $vpa);
    }

    public function testValidateVpaExisting()
    {
        $this->createUpiPaymentsLocalCustomerVpa([
            'username'  => 'withname',
            'handle'    => 'razorpay',
            'name'      => 'tobeupdated',
        ]);

        $vpa = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertSame('withname', $vpa->getUsername());
        $this->assertSame('razorpay', $vpa->getHandle());
        $this->assertSame('tobeupdated', $vpa->getName());
        $this->assertSame(null, $vpa->getStatus());
        $this->assertGreaterThanOrEqual(null, $vpa->getReceivedAt());

        $this->validateVpa('withname@razorpay');

        $vpa2 = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertSame($vpa->getId(), $vpa2->getId());

        $this->assertSame('Razorpay Customer', $vpa2->getName());
        $this->assertSame('valid', $vpa2->getStatus());
        $this->assertGreaterThanOrEqual(1600000000, $vpa2->getReceivedAt());
    }
}
