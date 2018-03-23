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

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal', [
            'gateway'   => Gateway::UPI_MINDGATE
        ]);

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
        $this->assertEquals('100UPIMindgate', $payment['terminal_id']);
        $this->assertNull($payment['vpa']);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'callback')
            {
                $content[8] = 'crims0n@hdfcbank';
            }
        });

        $content = $this->getMockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals($payment['vpa'], 'crims0n@hdfcbank');
    }

    public function testIntentPaymentWithVpa()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['vpa'] = 'crims0n@hdfcbank';

        unset($payment['description']);

        $payment['_']['flow'] = 'intent';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
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

    public function testVerifyPayment()
    {
        // First we test that verification works
        // for a captured payment
        $payment = $this->testPayment();

        $this->payment = $this->verifyPayment($payment['id']);

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

        $refund = $this->retryFailedRefund($refund['id']);

        $this->assertEquals($refund['status'], 'processed');
    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }
}
