<?php

namespace RZP\Tests\Functional\Gateway\Hdfc;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class HdfcGatewayCaptureTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/HdfcGatewayTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->gateway = 'hdfc';

        $this->fixtures->merchant->enableInternational();
    }

    public function testCaptureDeniedByRisk()
    {
        $payment = $this->doAuthPayment();

        $this->hdfcPaymentFailedDueToDeniedByRisk();

        $this->makeRequestAndCatchException(
            function () use ($payment)
            {
                $this->capturePayment($payment['razorpay_payment_id'], '50000');
            });

        $hdfc = $this->getLastEntity('hdfc', true);
        $this->assertTestResponse($hdfc);

        $payment = $this->getLastPayment(true);
        $this->assertEquals($payment['status'], 'failed');
        $this->assertEquals($payment['internal_error_code'], ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK);
    }

    /**
     * Tests that a capture succeeds on gateway but fails on our end.
     * Then on next verify, it succeeds.
     * Finally, when refunding, it should succeed.
     * @return [type] [description]
     */
    public function testForcedCapture()
    {
        $payment = $this->doAuthPayment();
        $payment = $this->getLastEntity('payment', true);

        $payment = $this->captureErrorReturnGW00176();
        $payment = $this->getLastEntity('payment', true);
        $payment = $this->capturePayment($payment['id'], $payment['amount']);
        $this->assertEquals($payment['status'], 'captured');

        $hdfcPayment = $this->getLastEntity('hdfc', true);
        $this->assertEquals($hdfcPayment['error_code'], 'GW00176');

        $this->resetGatewayDriver();
        $this->resetMockServer();

        $refund = $this->refundPayment($payment['id'], $payment['amount']);
        $this->assertEquals($refund['entity'], 'refund');
    }

    public function testCaptureTimeout()
    {
        $this->defaultAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $this->captureErrorReturnGatewayTimeout();

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $this->assertEquals($payment['status'], 'captured');

        $hdfc = $this->getEntities('hdfc', [], true);

        $this->assertEquals($hdfc['items'][0]['status'], 'captured');

        $this->assertEquals($hdfc['items'][1]['status'], 'capture_failed');
    }

    public function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->replaceDefualtValues($testData['request']['content']);

        return $this->runRequestResponseFlow($testData);
    }
}
