<?php

namespace RZP\Tests\Functional\Gateway\Upi\Yesbank;

use RZP\Constants\Mode;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Upi\Base\Secure;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiYesbankGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    /**
     * @var Terminal
     */
    protected $sharedTerminal;

    /**
     * Payment array
     * @var array
     */
    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/YesbankGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_yesbank_terminal');

        $this->gateway = Gateway::UPI_YESBANK;

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->payment = $this->getDefaultUpiPaymentArray();

        //$this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);
    }

    /**
     * Tests the happy-flow of a complete payment
     * @param string $status
     * @return mixed
     */
    public function testPayment($status = 'created')
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, $status);

        $upiEntity = $this->getDbLastEntity('upi');

        $payment = $this->getDbLastPayment();

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeRequestAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        // The payment should now be authorized
        $payment->reload();
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment->getPublicId(), $payment['amount']);

        return $payment;
    }

    public function testRefundSuccess()
    {
        $this->testPayment();

        $payment = $this->getDbLastPayment();
        // Attempt a partial refund
        $this->refundPayment($payment->getPublicId(), 10000);

        $upi = $this->getDbLastEntity('upi');

        \mc::dd($upi);
    }

    public function testRefundFailure()
    {
        $this->payment['vpa'] = 'failedrefund@hdfcbank';

        $payment = $this->testPayment();

        $refund = $this->refundPayment($payment['id'], 10000);

        $entity = $this->getEntityById('refund', $refund['id'], 'admin');

        $this->assertEquals('failed', $entity['status']);
        $this->assertEquals(false, $entity['gateway_refunded']);

    }

    public function testRetryRefund()
    {
        $this->payment['vpa'] = 'failedrefund@hdfcbank';

        $payment = $this->testPayment();

        $refund = $this->refundPayment($payment['id'], 10000);

        $refund = $this->getLastEntity('refund', true);

        $this->mockServerContentFunction(function (& $content, $action = null) use($refund)
        {
            if ($action === 'verify')
            {
                $content['status'] = 'FAILURE';
            }

            if ($action === 'refund')
            {
                $refundId = substr($refund['id'], 5);

                $content[4] = 'SUCCESS';

                $this->assertEquals($refundId . 1, $content[1]);
            }
        });

        $refund = $this->retryFailedRefund($refund['id']);

        $this->assertEquals($refund['status'], 'processed');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($refund['attempts'], 2);
    }

    public function testBankDetailsAreSaved()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->mockServerContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'callback')
                {
                    $content[16] = 'PNB!1000000000!PNB10010010!9800000000';
                }
            });

        $content = $this->getMockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $upi = $this->getLastEntity('upi', true);

        $this->assertEquals($upi['account_number'], '1000000000');

        $this->assertEquals($upi['ifsc'], 'PNB10010010');

    }

    public function testBankDetailsAreNotSavedInCaseFailed()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->mockServerContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'callback')
                {
                    $content[16] = 'NA!109090902020!NA!NA';
                }
            });

        $content = $this->getMockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $this->makeS2SCallbackAndGetContent($content);

        $upi = $this->getLastEntity('upi', true);

        $this->assertNotNull($upi['account_number']);

        $this->assertNull($upi['ifsc']);

    }

    public function testValidateVpaSuccess()
    {
        Gateway::$upiValidateVpaGateways[Mode::TEST] = [Gateway::UPI_MINDGATE];

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testValidateVpaFailure()
    {
        Gateway::$upiValidateVpaGateways[Mode::TEST] = [Gateway::UPI_MINDGATE];

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }

    protected function createUnexpectedPayment($data)
    {
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $data['meRes'] = $this->mockServer()->encrypt($data['meRes']);

        $response = $this->makeS2SCallbackAndGetContent($data);

        return $response;
    }

    public function testUnexpectedPaymentSuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $response = $this->createUnexpectedPayment($data);

        $this->assertTrue($response['success']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $authorizeUpiEntity = $this->getLastEntity('upi', true);

        $paymentTransactionEntity = $this->getLastEntity('transaction', true);

        $assertEqualsMap = [
            'authorized'                           => $paymentEntity['status'],
            'authorize'                            => $authorizeUpiEntity['action'],
            'pay'                                  => $authorizeUpiEntity['type'],
            $paymentEntity['id']                   => 'pay_' . $authorizeUpiEntity['payment_id'],
            $paymentTransactionEntity['id']        => 'txn_' . $paymentEntity['transaction_id'],
            $paymentTransactionEntity['entity_id'] => $paymentEntity['id'],
            $paymentTransactionEntity['type']      => 'payment',
            $paymentTransactionEntity['amount']    => $paymentEntity['amount'],
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }

        $this->assertNull($paymentEntity['verified']);

        $this->verifyPayment($paymentEntity['id']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity['verified'], 1);

        $this->refundAuthorizedPayment($paymentEntity['id']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $refundEntity = $this->getLastEntity('refund', true);

        $refundUpiEntity = $this->getLastEntity('upi', true);

        $refundTransactionEntity = $this->getLastEntity('transaction', true);

        $assertEqualsMap = [
            'refunded'                            => $paymentEntity['status'],
            $paymentEntity['id']                  => 'pay_' . $refundUpiEntity['payment_id'],
            'refund'                              => $refundUpiEntity['action'],
            'collect'                             => $refundUpiEntity['type'],
            $paymentEntity['amount']              => $refundUpiEntity['amount'],
            $refundEntity['id']                   => 'rfnd_' . $refundUpiEntity['refund_id'],
            $paymentEntity['amount']              => $refundEntity['amount'],
            'processed'                           => $refundEntity['status'],
            $refundTransactionEntity['id']        => 'txn_' . $refundEntity['transaction_id'],
            $refundTransactionEntity['entity_id'] => $refundEntity['id'],
            $refundTransactionEntity['type']      => 'refund',
            $refundTransactionEntity['amount']    => $refundEntity['amount'],
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }
    }

    public function testUnexpectedPaymentFail()
    {
        $data = $this->testData[__FUNCTION__];

        $response = $this->createUnexpectedPayment($data);

        $this->assertFalse($response['success']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertNull($paymentEntity);
    }

    public function testDuplicateUnexpectedPayment()
    {
        $data = $this->testData['testUnexpectedPaymentSuccess'];

        $response = $this->createUnexpectedPayment($data);

        /*
            Only api success is checked , as the rest of the validation
            is alreay done in testUnexpectedPaymentSuccess
        */
        $this->assertTrue($response['success']);

        $response = $this->createUnexpectedPayment($data);

        $this->assertFalse($response['success']);

        $paymentEntities = $this->getEntities('payment', array(), true);

        $upiEntities = $this->getEntities('upi', array(), true);

        $transactionEntities = $this->getEntities('transaction', array(), true);

        $this->assertEquals(1, $paymentEntities['count']);

        $this->assertEquals(1, $upiEntities['count']);

        $this->assertEquals(1, $transactionEntities['count']);
    }
}
