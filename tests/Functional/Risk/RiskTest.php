<?php

namespace RZP\Tests\Functional\Risk;


use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class RiskTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/RiskTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');
    }

    private function makeFraudalentPayment()
    {
        //
        // Running testFraudDetected test again till we can do
        // runDependencyTest across test files
        //
        $this->mockMaxmind();

        $this->fixtures->merchant->enableInternational();

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4012010000000007';

        $data = $this->testData['testFraudDetected'];

        $result = $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        return $payment;
    }

    public function testFetchMultiple()
    {
        $authPayment = $this->makeFraudalentPayment();

        $content = $this->testData[__FUNCTION__]['request']['content'];

        $content['payment_id'] = $authPayment['id'];

        $this->testData[__FUNCTION__]['request']['content'] = $content;

        $this->startTest();
    }
}
