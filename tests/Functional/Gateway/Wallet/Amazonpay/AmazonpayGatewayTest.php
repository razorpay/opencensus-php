<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Amazonpay;

use RZP\Models\Payment;
use RZP\Models\Payment\Refund;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Exception\PaymentVerificationException;
use RZP\Gateway\Wallet\Amazonpay\RequestFields;
use RZP\Gateway\Wallet\Amazonpay\ResponseFields;
use RZP\Gateway\Wallet\Base\Entity as WalletEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Gateway\Wallet\Amazonpay\ResponseFields as AmazonResponse;

class AmazonpayGatewayTest extends TestCase
{
    use PaymentTrait;
    use PartnerTrait;
    use DbEntityFetchTrait;

    private $payment;

    private $route;

    private $sharedTerminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/AmazonpayGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_amazonpay_terminal');

        $this->gateway = Payment\Gateway::WALLET_AMAZONPAY;

        $this->route = $this->app['api.route'];

        $this->fixtures->merchant->enableWallet(Account::TEST_ACCOUNT, Wallet::AMAZONPAY);

        $this->payment = $this->getDefaultWalletPaymentArray(Wallet::AMAZONPAY);

        $this->ba->publicAuth();

        $this->markTestSkipped('this flow is deprecated and is moved to nbplus service');
    }

    // ------------------------------------------------- Merchant test cases -------------------------------------------

    public function testMerchantPrefrences()
    {
        $preferences = $this->makeRequestAndGetContent([
            'url'       => '/preferences',
            'method'    => 'GET',
            'content'   => [
                'currency' => 'INR',
            ]
        ]);

        $this->assertArrayHasKey('amazonpay', $preferences['methods']['wallet']);
        $this->assertTrue($preferences['methods']['wallet']['amazonpay']);
    }

    // ------------------------------------------------- Payment test cases --------------------------------------------

    /**
     * Successful payment case.
     */
    public function testPayment()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(Wallet::AMAZONPAY, $payment[Payment\Entity::WALLET]);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet);

        $this->assertNotNull($wallet[WalletEntity::DATE]);
    }

    public function testAjaxRoutePayment()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'amazonpay_change_callback')
                {
                    $callbackUrl = $this->route->getUrl(
                                                    'gateway_payment_callback_amazonpay',
                                                    ['ajax' => 'ajax']);

                    $content[RequestFields::REDIRECT_URL] = $callbackUrl;
                }
            });

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(Wallet::AMAZONPAY, $payment[Payment\Entity::WALLET]);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet, 'testPayment');

        $this->assertNotNull($wallet[WalletEntity::DATE]);
    }

    public function testPartnerPayment()
    {
        list($clientId, $submerchantId) = $this->setUpPartnerAuthForPayment();

        $response = $this->doPartnerAuthPayment($this->payment, $clientId, $submerchantId);

        $payment = $this->getDbEntityById(ConstantsEntity::PAYMENT, $response['razorpay_payment_id']);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(Wallet::AMAZONPAY, $payment[Payment\Entity::WALLET]);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet, 'testPayment');

        $this->assertNotNull($wallet[WalletEntity::DATE]);
    }

    public function testPartnerPaymentAjaxRoute()
    {
        list($clientId, $submerchantId) = $this->setUpPartnerAuthForPayment();

        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'amazonpay_change_callback')
                {
                    $callbackUrl = $this->route->getUrl(
                        'gateway_payment_callback_amazonpay',
                        ['ajax' => 'ajax']);

                    $content[RequestFields::REDIRECT_URL] = $callbackUrl;
                }
            });

        $response = $this->doPartnerAuthPayment($this->payment, $clientId, $submerchantId);

        $payment = $this->getDbEntityById(ConstantsEntity::PAYMENT, $response['razorpay_payment_id']);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(Wallet::AMAZONPAY, $payment[Payment\Entity::WALLET]);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet, 'testPayment');

        $this->assertNotNull($wallet[WalletEntity::DATE]);
    }

    /**
     * The callback response status is that of a failure.
     */
    public function testPaymentFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->payment;

        $this->mockPaymentFailed();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            });

        $payment = $this->getDbLastEntityPublic(ConstantsEntity::PAYMENT);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(Wallet::AMAZONPAY, $payment[Payment\Entity::WALLET]);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet, __FUNCTION__ . 'Wallet');

        $this->assertNotNull($wallet[WalletEntity::DATE]);
    }

    public function testPaymentCallbackWithoutPaymentId()
    {
        $payment = $this->payment;

        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                unset($content['sellerOrderId']);
            });

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            BadRequestException::class,
            'Payment failed');

        $payment = $this->getDbLastEntityPublic(ConstantsEntity::PAYMENT);

        $this->assertEquals(Payment\Status::CREATED, $payment[Payment\Entity::STATUS]);
    }

    public function testPaymentCallbackWithPaymentId()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'authorize')
                {
                    $content[AmazonResponse::SELLER_ORDER_ID] = 'A3MJ8ZJGR6SLB_' . $content[AmazonResponse::SELLER_ORDER_ID];
                }
            });

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityPublic(ConstantsEntity::PAYMENT);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);
        //$this->assertEquals(payment,$payment)
    }

    public function testPaymentAmountPrecisionCheck()
    {
        $this->payment['amount'] = 52080;

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(Wallet::AMAZONPAY, $payment[Payment\Entity::WALLET]);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        // Wallet does not have casting
        $this->assertSame('52080', $wallet['amount']);

        // Payment has casting to integer
        $this->assertSame(52080, $payment['amount']);
    }

    /**
     * The case where the callback response signature is a mismatch from the calculated one.
     */
    public function testPaymentSignatureVerificationFailure()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->payment;

        $this->mockWrongSignature();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            });

        $payment = $this->getDbLastEntityPublic(ConstantsEntity::PAYMENT);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(Wallet::AMAZONPAY, $payment[Payment\Entity::WALLET]);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet, __FUNCTION__ . 'Wallet');
    }

    // ------------------------------------------------- Verify test cases ---------------------------------------------

    /**
     * Successfully verify a successful payment.
     */
    public function testPaymentVerify()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->doAuthAndCapturePayment($this->payment);

        // We are verifying a captured payment
        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        $this->assertArraySelectiveEquals($data, $verify);
    }

    /**
     * The case where payment verification results in apiSuccess = false, gatewaySuccess = true
     */
    public function testPaymentFailedVerifySuccess()
    {
        $data = $this->testData['testPaymentFailed'];

        $payment = $this->payment;

        $this->mockPaymentFailed();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            });

        $payment = $this->getDbLastEntityPublic(ConstantsEntity::PAYMENT);

        // We are verifying a captured payment
        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $data = $this->testData['testVerifyFailed'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        // Status gets updated to SUCCESS in the DB
        $this->assertTestResponse($wallet, 'testPayment');

        // We store the verify response request id in reference2
        $this->assertNotNull($wallet[WalletEntity::REFERENCE2]);
    }

    public function testPaymentSuccessVerifyInvalidParams()
    {
        $this->runVerifyFailureFlow(__FUNCTION__);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet, 'testPayment');

        // We store the verify response request id in reference2
        $this->assertNotNull($wallet[WalletEntity::REFERENCE2]);
    }

    public function testPaymentSuccessVerifyEmptyString()
    {
        $this->runVerifyFailureFlow(__FUNCTION__);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet, 'testPayment');

        // When the response is an empty string, we don't store the request id in the DB
        $this->assertNull($wallet[WalletEntity::REFERENCE2]);
    }

    public function testPaymentSuccessVerifyRandomString()
    {
        $this->runVerifyFailureFlow(__FUNCTION__);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet, 'testPayment');

        // When the response is an empty string, we don't store the request id in the DB
        $this->assertNull($wallet[WalletEntity::REFERENCE2]);
    }

    /**
     * Verify response is a failure
     */
    public function testPaymentVerifyFailureResponse()
    {
        // Response is a failure causing gatewaySuccess = false
        $this->runVerifyFailureFlow(__FUNCTION__);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet, 'testPayment');

        // Failure response also contains a requestId that we can save
        $this->assertNotNull($wallet[WalletEntity::REFERENCE2]);
    }

    /**
     * Verify response is is incomplete
     */
    public function testPaymentVerifyIncompleteResponse()
    {
        // Incomplete response causes gatewaySuccess = false
        $this->runVerifyFailureFlow(__FUNCTION__);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet, 'testPayment');
    }

    public function testVerifyMutlipleVerifyTablesOneSuccess()
    {
        // Multiple tables, but only one would lead to success
        $payment = $this->runMultipleTablesFlow();

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        $data = $this->testData['testPaymentVerify'];

        $this->assertArraySelectiveEquals($data, $verify);
    }

    public function testVerifyMutlipleVerifyTablesTwoSuccess()
    {
        // Multiple tables, but only one would lead to success
        $payment = $this->runMultipleTablesFlow(true);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet, 'testPayment');

        // Exception is thrown before requestId is saved to the DB
        $this->assertNull($wallet[WalletEntity::REFERENCE2]);
    }

    // ------------------------------------------------- Refund test cases ---------------------------------------------

    public function testPaymentRefundInitiated()
    {
        $this->doAuthCaptureAndRefundPayment($this->payment);

        $refund = $this->getDbLastRefund();

        // Amazonpay refunds are not processed via scrooge, so is_scrooge will be false
        $this->assertTrue($refund->isScrooge());

        $this->assertTrue($refund->IsCreated());

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet);

        $this->assertEquals($refund->getId(), $wallet[WalletEntity::REFUND_ID]);
        $this->assertEquals($refund->getPaymentId(), $wallet[WalletEntity::PAYMENT_ID]);
    }

    public function testPaymentRefundInitiationFailed()
    {
        $this->mockRefundResponseFailure(__FUNCTION__);

        $this->doAuthCaptureAndRefundPayment($this->payment);

        $refund = $this->getDbLastRefund();

        $this->assertEquals(Refund\Status::CREATED, $refund->getStatus());

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet);
    }

    public function testPaymentRefundInitiateEmptyResult()
    {
        $this->mockRefundResponseFailure(__FUNCTION__);

        $this->doAuthCaptureAndRefundPayment($this->payment);

        $refund = $this->getDbLastRefund();

        $this->assertEquals(Refund\Status::CREATED, $refund->getStatus());

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet);
    }

    public function testPaymentRefundInitiateEmptyStatus()
    {
        $this->mockRefundResponseFailure(__FUNCTION__);

        $this->doAuthCaptureAndRefundPayment($this->payment);

        $refund = $this->getDbLastRefund();

        $this->assertEquals(Refund\Status::CREATED, $refund->getStatus());

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet);
    }

    public function testPaymentRefundInitiateMultiplePending()
    {
        $this->mockRefundResponseFailure(__FUNCTION__);

        $this->doAuthCaptureAndRefundPayment($this->payment);

        $refund = $this->getDbLastRefund();

        $this->assertSame(Refund\Status::CREATED, $refund->getStatus());

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet);
    }

    public function testPaymentRefundVerifyWhenSuccess()
    {
        $this->doAuthCaptureAndRefundPayment($this->payment);

        $refund = $this->getDbLastRefund(ConstantsEntity::REFUND);
        $this->assertEquals(Refund\Status::CREATED, $refund->getStatus());

        $gatewayHit = false;

        $this->mockServerContentFunction(
            function(& $content, $action = null) use (& $gatewayHit, $refund)
            {
                if ($action === 'verify_refund')
                {
                    $gatewayHit = true;
                    $content['data']['{{reference_id}}'] = $refund->getId();
                    $content['data']['{{refund_state}}'] = 'Completed';
                }
            });

        // Since the refund is processed, we are checking the gateway is not hit.
        // TODO: We may need to change this to verify refund
        $this->retryFailedRefund($refund->getPublicId(), 'pay_' . $refund->getPaymentId(), [], ['status'=> $refund->getStatus()]);

        $this->assertTrue($gatewayHit);

        $this->assertEquals(Refund\Status::PROCESSED, $refund->reload()->getStatus());
    }

    public function testPaymentRefundVerifyWhenFailed()
    {
        $this->doAuthCaptureAndRefundPayment($this->payment);

        $refund = $this->getDbLastRefund(ConstantsEntity::REFUND);
        $this->assertEquals(Refund\Status::CREATED, $refund->getStatus());

        $refund->setStatus(Refund\Status::FAILED);
        $refund->save();

        $gatewayHit = false;

        $this->mockServerContentFunction(
            function(& $content, $action = null) use (& $gatewayHit, $refund)
            {
                if ($action === 'verify_refund')
                {
                    $gatewayHit = true;
                    $content['data']['{{reference_id}}'] = $refund->getId();
                    $content['data']['{{refund_state}}'] = 'Declined';
                }
            });

        $this->retryFailedRefund($refund->getPublicId(), 'pay_' . $refund->getPaymentId());

        $this->assertTrue($gatewayHit);

        $this->assertEquals(Refund\Status::FAILED, $refund->reload()->getStatus());
    }

    public function testPaymentMultipleRefundsInitiated()
    {
        $this->doAuthCaptureAndRefundPayment($this->payment, 10000);

        $payment = $this->getDbLastPayment();
        $refund1 = $this->getDbLastRefund();

        $this->assertTrue($refund1->isCreated());
        $this->assertTrue($payment->isPartiallyRefunded());

        $wallet1 = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertEquals($refund1->getId(), $wallet1[WalletEntity::REFUND_ID]);
        $this->assertEquals($refund1->getPaymentId(), $wallet1[WalletEntity::PAYMENT_ID]);

        $this->assertEquals($refund1->getAmount(), $wallet1[WalletEntity::AMOUNT]);

        $gatewayHit = false;

        $this->mockServerRequestFunction(
            function($request, $action = null) use (& $gatewayHit)
            {
                $this->assertSame('200.00', $request['RefundAmount_Amount']);

                $gatewayHit = true;
            });

        // Refund the payment again with different amount
        $this->refundPayment($payment->getPublicId(), 20000);

        $this->assertTrue($gatewayHit);

        $payment->reload();
        $refund2 = $this->getDbLastRefund();
        $this->assertSame(Refund\Status::CREATED, $refund2->getStatus());

        $this->assertSame(30000, $payment->getAmountRefunded());
        $this->assertNotEquals($refund1->getId(), $refund2->getId());

        $wallet2 = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertEquals($refund2->getId(), $wallet2[WalletEntity::REFUND_ID]);
        $this->assertEquals($refund2->getPaymentId(), $wallet2[WalletEntity::PAYMENT_ID]);

        $this->assertEquals($refund2->getAmount(), $wallet2[WalletEntity::AMOUNT]);
    }

    // ------------------------------------------------- Helpers -------------------------------------------------------

    /**
     * @override
     * @param null $payment
     * @return bool|mixed|null|string
     */
    protected function doAuthAndCapturePayment($payment = null)
    {
        $paymentAuth = $this->doAuthPayment($payment);

        return $this->capturePayment(
            $paymentAuth['razorpay_payment_id'],
            $payment['amount'], 'INR');
    }

    /**
     * Helper used to run the multiple tables flow with / without multiple success tables
     *
     * @param bool $multipleSuccess
     * @return bool|mixed|null|string
     */
    private function runMultipleTablesFlow(bool $multipleSuccess = false)
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        // We are verifying a captured payment
        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);

        $paymentId = explode('_', $payment[Payment\Entity::ID])[1];

        $this->mockVerifyMultipleTables($multipleSuccess, $paymentId, $payment[Payment\Entity::AMOUNT] / 100);

        return $payment;
    }

    /**
     * Helper used to run the authorize + callback + verify failure steps
     * @param string $function
     */
    private function runVerifyFailureFlow(
        string $function,
        string $expection = PaymentVerificationException::class)
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        // We are verifying a captured payment
        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);

        $data = $this->testData['testVerifyFailed'];

        $data['exception']['class'] = $expection;

        $this->mockVerifyFailedResponse($function);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });
    }

    private function mockPaymentFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'authorize')
                {
                    $content[ResponseFields::REASON_CODE] = '229';
                    $content[ResponseFields::STATUS]= 'FAILED';
                    $content[ResponseFields::DESCRIPTION] = '3d Secure Verification Failed';
                }
            });
    }

    private function mockWrongSignature()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'authorizeSignatureFailed')
                {
                    $content[ResponseFields::SIGNATURE] = 'This is a wrong signature';
                }
            });
    }

    private function mockVerifyFailedResponse(string $testCase)
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null) use ($testCase)
            {
                if ($action === 'verify')
                {
                    switch ($testCase)
                    {
                        case 'testPaymentSuccessVerifyInvalidParams':
                            $content['xml'] = file_get_contents(__DIR__ . '/Xml/invalidInputParams.xml');
                            break;

                        case 'testPaymentSuccessVerifyEmptyString':
                            $content['xml'] = '';
                            break;

                        case 'testPaymentSuccessVerifyRandomString':
                            $content['xml'] = 'Random string to be converted to array';
                            break;

                        case 'testPaymentVerifyFailureResponse':
                            $content['data']['{{reason_code}}'] = 'FAILED';
                            break;

                        case 'testPaymentVerifyIncompleteResponse':
                            $content['xml'] = file_get_contents(__DIR__ . '/Xml/incompleteResponse.xml');
                            break;

                        default:
                            break;
                    }
                }
            });
    }

    private function mockVerifyMultipleTables(bool $multipleSuccess, string $paymentId, string $amount)
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null) use ($multipleSuccess, $paymentId, $amount)
            {
                if ($action === 'verify')
                {
                    $content['xml'] = file_get_contents(__DIR__ . '/Xml/doubleXmlResponse.xml');

                    if ($multipleSuccess === true)
                    {
                        $content['data']['FAILURE'] = 'UpfrontChargeSuccess';
                    }

                    $content['data']['{{amount}}'] = $amount;
                    $content['data']['{{payment_id}}'] = $paymentId;
                }
            });
    }

    private function mockRefundResponseFailure(string $testCase)
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null) use ($testCase)
            {
                if ($action === 'refund')
                {
                    switch ($testCase)
                    {
                        case 'testPaymentRefundInitiationFailed':
                            $content['data']['{{refund_state}}'] = 'Declined';
                            break;

                        case 'testPaymentRefundInitiateEmptyResult':
                            $content['xml'] = file_get_contents(__DIR__ . '/Xml/refundResponseEmptyDetails.xml');
                            break;

                        case 'testPaymentRefundInitiateEmptyStatus':
                            $content['xml'] = file_get_contents(__DIR__ . '/Xml/refundResponseEmptyStatus.xml');
                            break;

                        case 'testPaymentRefundInitiateMultiplePending':
                            $content['xml'] = file_get_contents(__DIR__ . '/Xml/refundResponseMultiplePending.xml');
                            break;

                        default:
                            break;
                    }
                }
            });
    }
}
