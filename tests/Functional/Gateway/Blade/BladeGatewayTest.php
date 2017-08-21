<?php

namespace RZP\Tests\Functional\Gateway\Blade;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Fixtures\Entity\TransactionTrait;

class BladeGatewayTest extends TestCase
{
    use PaymentTrait;
    use TransactionTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/BladeGatewayTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_blade_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'blade';
    }

    public function testSuccessful13DigitPan()
    {
        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => '4532249047240',
                'expiry_month' => '02',
                'expiry_year'  => '21',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        sd($payment);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);
        $this->assertEquals('1000CybrsTrmnl', $payment['terminal_id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($payment['approval_code']);
        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('cybersource', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testCybersourceCaptureEntity'], $payment);
    }
}
