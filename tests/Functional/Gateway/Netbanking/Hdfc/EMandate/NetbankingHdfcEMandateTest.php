<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Hdfc\EMandate;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Customer\Token;
use RZP\Models\Payment;

class NetbankingHdfcEMandateTest extends TestCase
{
    use PaymentTrait;

    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingHdfcEMandateTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_netbanking_hdfc_recurring_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures(['charge_at_will', 'e_mandate']);

        $this->payment = $this->getNetbankingHdfcEmandateArray();

        $this->mockTokenex();
    }

     /**
      * The following is a test case for the E Mandate Registration payment for HDFC.
      * The HDFC E Mandate Registration payment is just a normal authorization payment.
      */
    public function testEMandateInitialPayment()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($data, $payment);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals($payment[Payment\Entity::TOKEN_ID], $token[Token\Entity::ID]);

        $this->assertEquals($this->payment['account_number'], $token[Token\Entity::ACCOUNT_NUMBER]);

        $this->assertTestResponse($token, 'matchInitiatedToken');
    }

    protected function getNetbankingHdfcEmandateArray(): array
    {
        $payment = $this->getNetbankingRecurringPaymentArray('HDFC');

        $payment['account_number'] = '0123456789';

        return $payment;
    }
}