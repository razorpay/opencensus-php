<?php

namespace RZP\Tests\Functional\Gateway\Upi\Mindgate;

use RZP\Constants\Entity as ConstantsEntity;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Status;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiMindgateGatewayTest extends TestCase
{
    use PaymentTrait;

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
        $this->testDataFilePath = __DIR__.'/MindgateGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->gateway = Gateway::UPI_MINDGATE;

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->payment = $this->getDefaultUpiPaymentArray();
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

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        // The payment should now be authorized
        $payment = $this->getEntityById('payment', $paymentId, true);
        $this->assertEquals('authorized', $payment['status']);

        $upiEntity = $this->getLastEntity('upi', true);
        $this->assertNotNull($upiEntity['npci_reference_id']);
        $this->assertNotNull($upiEntity['gateway_payment_id']);

        // Add a capture as well, just for completeness sake
        $this->capturePayment($paymentId, $payment['amount']);

        return $payment;
    }

    public function testIntentPayment()
    {
        $this->fixtures->create('terminal:shared_upi_mindgate_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);

        $this->checkPaymentStatus($paymentId, 'created');

        $upiEntity = $this->getLastEntity('upi_mindgate', true);
        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals('pay', $upiEntity['type']);
        $this->assertEquals('1UpiIntMndgate', $payment['terminal_id']);
        $this->assertNull($payment['vpa']);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'callback')
            {
                $content[8] = 'user@hdfcbank';
            }
        });

        $content = $this->getMockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $upi = $this->getLastEntity('upi', true);
        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals($payment['vpa'], 'user@hdfcbank');
        $this->assertEquals('HDFC', $upi['bank']);
        $this->assertEquals('hdfc', $upi['acquirer']);
        $this->assertEquals('hdfcbank', $upi['provider']);
    }

    public function testUpiAmountCap()
    {
        $this->payment['vpa'] = 'vishnu@upi';

        $payment = $this->payment;

        $payment['amount'] = 2100000;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            });
    }

    public function testFailedVpaValidation()
    {
        $this->payment['vpa'] = 'invalidvpa@hdfcbank';

        $payment = $this->payment;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            });

        $upiEntity = $this->getLastEntity(ConstantsEntity::UPI, true);

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);
        $this->assertEquals(Status::FAILED, $payment['status']);

        $this->assertEquals('invalidvpa@hdfcbank', $upiEntity[Entity::VPA]);
        $this->assertNull($upiEntity[Entity::GATEWAY_PAYMENT_ID]);
        $this->assertNull($upiEntity[Entity::NPCI_REFERENCE_ID]);
    }


    public function testVpaWithCapitalPspValidation($status = 'created')
    {
        $this->payment['vpa'] = 'vishnu@ICiCI';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, $status);

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);
        $this->assertEquals('vishnu@icici', $upiEntity[Entity::VPA]);

    }

    public function testVpaWithoutPspValidation()
    {
        $this->payment['vpa'] = 'invalidvpa';

        $payment = $this->payment;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            });
    }

    /**
     * Force the gateway to raise a failure on trying
     * to initiate web collect
     */
    public function testFailedCollect()
    {
        $this->payment['vpa'] = 'failedcollect@hdfcbank';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPaymentViaAjaxRoute($this->payment);
        });
    }

    public function testPaymentViaRedirection()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        // Payment status is a polling API which checkout hits
        // continously. Replicating the same in test case
        $this->checkPaymentStatus($paymentId, 'created');
        $this->checkPaymentStatus($paymentId, 'created');

        return $paymentId;
    }

    public function testTpvPayment()
    {
        $this->fixtures->create('terminal:shared_upi_mindgate_tpv_terminal', ['tpv' => 3]);

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $data = $this->testData[__FUNCTION__];

        $order = $this->startTest();

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultUpiPaymentArray();
        $payment['amount'] = $order['amount'];
        $payment['bank'] = $order['bank'];
        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('100UPIMndgtTpv', $payment['terminal_id']);

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('collect', $gatewayEntity['type']);
        $this->assertEquals('vishnu@icici', $gatewayEntity['vpa']);
    }

    public function testVerifyPayment()
    {
        // First we test that verification works
        // for a captured payment
        $payment = $this->testPayment();

        $this->payment = $this->verifyPayment($payment['id']);

        $upi = $this->getLastEntity('upi', true);

        $this->assertEquals($upi['account_number'], '004001551691');

        $this->assertEquals($upi['ifsc'], 'ICIC0000000');

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    // In case callback does not return the bank account details, we call verify
    // and check that the details are saved in Upi Entity
    public function testSaveBankDetailsLaterInVerify()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        $payment = $this->getEntityById('payment', $paymentId, true);

        $upiEntity = $this->getLastEntity('upi', true);

        $this->mockServerContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'callback')
                {
                    $content[16] = 'NA!NA!NA!NA';
                }
            });

        $content = $this->getMockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $this->makeS2SCallbackAndGetContent($content);

        $this->payment = $this->verifyPayment($payment['id']);

        $upi = $this->getLastEntity('upi', true);

        $this->assertEquals($upi['account_number'], '004001551691');

        $this->assertEquals($upi['ifsc'], 'ICIC0000000');

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    // API Payment = created
    // Gateway = success
    public function testVerificationFailure()
    {
        $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPayment($this->payment);

        $paymentId = $response['payment_id'];

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($paymentId)
        {
            $this->verifyPayment($paymentId);
        });
    }

    /**
     * Create a payment, and reject it so callback
     * returns failure
     */
    public function testCollectRejectedFailure()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['vpa'] = 'failed@hdfcbank';

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['payment_id'];

        $this->checkPaymentStatus($paymentId, 'created');

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($content)
        {
            $this->makeS2SCallbackAndGetContent($content);
        });

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals('failed', $payment['status']);
    }

    public function testPaymentWithExpiryPrivateAuth()
    {
        $this->fixtures->merchant->addFeatures(['s2supi']);

        $payment = $this->getDefaultUpiPaymentArray();

        $payment['upi']['expiry_time'] = 10;

        $response = $this->doS2SUpiPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $this->checkPaymentStatus($paymentId, 'created');

        $upiEntity = $this->getLastEntity('upi', true);

        $this->assertEquals(10, $upiEntity['expiry_time']);
    }

    public function testRefundSuccess()
    {
        $payment = $this->testPayment();

        // Attempt a partial refund
        $this->refundPayment($payment['id'], 10000);
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

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }
}
