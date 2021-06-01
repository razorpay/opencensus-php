<?php

namespace RZP\Tests\Functional\Gateway\Hdfc;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class HdfcGatewayCaptureTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    const ACTION_CAPTURE = '5';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/HdfcGatewayTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->gateway = 'hdfc';

        $this->fixtures->merchant->enableInternational();
    }

    public function testCaptureDeniedByRisk()
    {
        //All card payments are gateway captured for Razorpay Org ID, so using a different org
        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => Org::HDFC_ORG]);

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
        $this->assertEquals($payment['status'], 'authorized');
        $this->assertNull($payment['internal_error_code']);
    }

    /**
     * Tests that a capture succeeds on gateway but fails on our end.
     * Then on next verify, it succeeds.
     * Finally, when refunding, it should succeed.
     * @return [type] [description]
     */
    public function testForcedCapture()
    {
        //All card payments are gateway captured for Razorpay Org ID, so using a different org
        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => Org::HDFC_ORG]);

        $payment = $this->doAuthPayment();
        $payment = $this->getLastEntity('payment', true);

        $payment = $this->captureErrorReturnGW00176();
        $payment = $this->getLastEntity('payment', true);
        $payment = $this->capturePayment($payment['id'], $payment['amount']);
        $this->assertEquals($payment['status'], 'captured');

        $hdfcPayment = $this->getDbEntity('hdfc', ['action' => $this::ACTION_CAPTURE])->toArrayAdmin();

        $this->assertEquals($hdfcPayment['error_code2'], 'GW00176');

        $this->resetGatewayDriver();
        $this->resetMockServer();

        $refund = $this->refundPayment($payment['id'], $payment['amount']);
        $this->assertEquals($refund['entity'], 'refund');
    }

    // @codingStandardsIgnoreLine
    public function testCaptureCM90000()
    {
        $payment = $this->doAuthPayment();
        $payment = $this->getLastEntity('payment', true);

        // @codingStandardsIgnoreLine
        $payment = $this->captureErrorReturnCM90000();
        $payment = $this->getLastEntity('payment', true);
        $payment = $this->capturePayment($payment['id'], $payment['amount']);
        $this->assertEquals('captured', $payment['status']);
    }

    public function testCaptureTimeout()
    {
        //All card payments are gateway captured for Razorpay Org ID, so using a different org
        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => Org::HDFC_ORG]);

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
