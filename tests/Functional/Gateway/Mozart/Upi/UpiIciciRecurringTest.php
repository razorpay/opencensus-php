<?php

namespace RZP\Tests\Functional\Gateway\Mozart\Upi;

use RZP\Exception\BadRequestException;

class UpiIciciRecurringTest extends UpiInitialRecurringTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->terminal = $this->fixtures->create('terminal:shared_icici_recurring_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->payment = $this->getDefaultUpiRecurringPaymentArray();

        $this->setMockGatewayTrue();
    }

    public function testIciciRecurringMandateInvalidPsp(){

        $this->payment['vpa'] = 'razoypay@okicici'; // override the vpa to test this scenario in TEST env

        $this->makeRequestAndCatchException(function ()
        {
            $this->testRecurringMandateCreate();
        },
            BadRequestException::class,
            "App not Supported for Upi AutoPay");
    }

    public function testEncryptedRecurringCallback(){

        // test when callback is encrypted.
        $this->testRecurringMandateCreate(true);
    }
}
