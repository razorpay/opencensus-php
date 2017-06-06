<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class FraudDetectionTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/FraudDetectionTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');
    }

    public function testBlockedBin()
    {
        $this->ba->appAuth();

        $addIin = [
            'request' => [
                'url' => '/iins',
                'method' => 'post',
                'content' => [
                    'iin' => 521729,
                    'network' => 'MasterCard',
                    'type' => 'debit',
                    'country' => null,
                ],
            ],
            'response' => [
                'content' => [],
                'status_code' => 200
            ]
        ];

        $this->runRequestResponseFlow($addIin);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5217294025032720';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testFraudDetected()
    {
        $this->mockMaxmind();

        $this->fixtures->merchant->enableInternational();

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4012010000000007';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testFraudNotDetected()
    {
        $this->mockMaxmind();

        $this->fixtures->merchant->enableInternational();

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5105105105105100';

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
    }

}
