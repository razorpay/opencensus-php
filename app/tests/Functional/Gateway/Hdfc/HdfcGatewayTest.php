<?php

namespace Tests\Functional\Gateway\Hdfc;

use EE\Exception;
use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class HdfcGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/HdfcGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'hdfc';

        $this->setMockGatewayTrue();
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

        $payment = $this->getLastEntity('hdfc', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHdfcPaymentEntity'], $payment);
    }

    public function testMaestroCard()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5081597022059105';

        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastPayment(true);
        $this->assertNotNull($payment['transaction_id']);
    }

    public function testHdfcEntityAfterPaymentRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $refund = $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('hdfc', true);//sd($refund);
        $this->assertTestResponse($refund);
    }

    public function testAuthorizedPaymentRefund()
    {
        $payment = $this->defaultAuthPayment();

        $input['force'] = '1';
        $this->refundAuthorizedPayment($payment['id'], $input);

        $hdfcEntity = $this->getLastEntity('hdfc', true);
        $this->assertEquals('authorized', $hdfcEntity['status']);
        $this->assertEquals('APPROVED', $hdfcEntity['result']);

        $txn = $this->getLastTransaction(true);
        $this->assertNull($txn);
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

    public function testPaymentVerify()
    {
        $payment = $this->doAuthPayment();

        $this->verifyPayment($payment['razorpay_payment_id']);
    }

    public function testAuthorizeFailedPayment()
    {
        $this->timeoutHdfcAuthorizePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('failed', $payment['status']);

        $this->succeedPaymentVerify();

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $payment = $this->getLastEntity('hdfc', true);
    }

    protected function timeoutHdfcAuthorizePayment()
    {
        $server = $this->mockServerContentFunction(function (& $content, $action)
                        {
                            if ($action === 'authorize')
                            {
                                throw new Exception\GatewayTimeoutException('Timed out');
                            }

                            return $content;
                        });

        $this->makeRequestAndCatchException(
            function ()
            {
                $content = $this->doAuthPayment();
            });
    }

    protected function succeedPaymentVerify()
    {
        $server = $this->mockServerContentFunction(function (& $content)
                        {
                            $content['RESPCODE'] = '0';
                            $content['RESPMSG'] = 'Transaction succeeded';
                            $content['STATUS'] = 'TXN_SUCCESS';

                            return $content;
                        });
    }
}
