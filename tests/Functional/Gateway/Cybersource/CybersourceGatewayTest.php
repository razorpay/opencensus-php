<?php

namespace RZP\Tests\Functional\Gateway\Cybersource;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\Cybersource;
use RZP\Error\PublicErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CybersourceGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CybersourceGatewayTestData.php';

        parent::setUp();

        $this->sharedHdfcTerminal = $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'cybersource';

        $this->mockTokenex();
    }

    public function testPayment()
    {
        $payment = $this->defaultAuthPayment();

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['transaction_id'], null);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('cybersource', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testCybersourceCaptureEntity'], $payment);
    }

    public function testGatewayTimeoutError()
    {
        $payment = $this->getDefaultPaymentArray();

        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function(&$content)
        {
            throw new \SoapFault('HTTP', 'Error Fetching http headers');
        });

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }
}
