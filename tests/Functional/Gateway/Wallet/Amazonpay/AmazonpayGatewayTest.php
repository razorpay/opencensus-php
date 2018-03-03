<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Amazonpay;

use RZP\Models\Payment;
use RZP\Models\Payment\Refund;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Gateway\Wallet\Amazonpay\ResponseFields;
use RZP\Gateway\Wallet\Base\Entity as WalletEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Gateway\Netbanking\Base\Entity as Netbanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class AmazonpayGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    private $payment;

    private $sharedTerminal;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/AmazonpayGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_amazonpay_terminal');

        $this->gateway = Payment\Gateway::WALLET_AMAZONPAY;

        $this->fixtures->merchant->enableWallet(Account::TEST_ACCOUNT, Wallet::AMAZONPAY);

        $this->payment = $this->getDefaultWalletPaymentArray(Wallet::AMAZONPAY);

        $this->ba->publicAuth();
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

        // We store the callback signature in reference1
        $this->assertNotNull($wallet[Netbanking::REFERENCE1]);

        $this->assertNotNull($wallet[Netbanking::DATE]);
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

        // We store the callback signature in reference1
        $this->assertNotNull($wallet[Netbanking::REFERENCE1]);

        $this->assertNotNull($wallet[Netbanking::DATE]);
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

        // We store the Signature in this field during authorize flow
        $this->assertNotNull($verify['gateway']['gatewayPayment'][WalletEntity::REFERENCE1]);

        // We store the verify request id in this field
        $this->assertNotNull($verify['gateway']['gatewayPayment'][WalletEntity::REFERENCE1]);
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

        // We store the Signature in this field during authorize flow
        $this->assertNotNull($wallet[WalletEntity::REFERENCE1]);

        // We store the verify response request id in reference2
        $this->assertNotNull($wallet[WalletEntity::REFERENCE2]);
    }

    public function testPaymentSuccessVerifyInvalidParams()
    {
        $this->runVerifyFailureFlow(__FUNCTION__);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet, 'testPayment');

        // We store the Signature in this field during authorize flow
        $this->assertNotNull($wallet[WalletEntity::REFERENCE1]);

        // We store the verify response request id in reference2
        $this->assertNotNull($wallet[WalletEntity::REFERENCE2]);
    }

    public function testPaymentSuccessVerifyEmptyString()
    {
        // xml converstion to array for empty string '' equals FALSE
        $this->runVerifyFailureFlow(__FUNCTION__);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet, 'testPayment');

        // We store the Signature in this field during authorize flow
        $this->assertNotNull($wallet[WalletEntity::REFERENCE1]);

        // When the response is an empty string, we don't store the request id in the DB
        $this->assertNull($wallet[WalletEntity::REFERENCE2]);
    }

    public function testPaymentSuccessVerifyRandomString()
    {
        // xml converstion to array for random string 'Mayank' results in an exception
        $this->runVerifyFailureFlow(__FUNCTION__);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet, 'testPayment');

        // We store the Signature in this field during authorize flow
        $this->assertNotNull($wallet[WalletEntity::REFERENCE1]);

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

        // We store the Signature in this field during authorize flow
        $this->assertNotNull($wallet[WalletEntity::REFERENCE1]);

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

        // We store the Signature in this field during authorize flow
        $this->assertNotNull($wallet[WalletEntity::REFERENCE1]);

        // Failure response also contains a requestId that we can save
        $this->assertNotNull($wallet[WalletEntity::REFERENCE2]);
    }

    public function testVerifyMutlipleVerifyTablesOneSuccess()
    {
        // Multiple tables, but only one would lead to success
        $payment = $this->runMultipleTablesFlow();

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        $data = $this->testData['testPaymentVerify'];

        $this->assertArraySelectiveEquals($data, $verify);

        // We store the Signature in this field during authorize flow
        $this->assertNotNull($verify['gateway']['gatewayPayment'][WalletEntity::REFERENCE1]);

        // We store the verify request id in this field
        $this->assertNotNull($verify['gateway']['gatewayPayment'][WalletEntity::REFERENCE1]);
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

        // We store the Signature in this field during authorize flow
        $this->assertNotNull($wallet[WalletEntity::REFERENCE1]);

        // Exception is thrown before requestId is saved to the DB
        $this->assertNull($wallet[WalletEntity::REFERENCE2]);
    }

    // ------------------------------------------------- Refund test cases ---------------------------------------------

    public function testPaymentRefundInitiated()
    {
        $this->doAuthCaptureAndRefundPayment($this->payment);

        $refund = $this->getDbLastEntityPublic(ConstantsEntity::REFUND);

        $this->assertEquals(Refund\Status::INITIATED, $refund[Refund\Entity::STATUS]);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet);

        $this->assertEquals($refund[Refund\Entity::ID], Refund\Entity::getSignedId($wallet[WalletEntity::REFUND_ID]));
        $this->assertEquals($refund[Refund\Entity::PAYMENT_ID],
                            Payment\Entity::getSignedId($wallet[WalletEntity::PAYMENT_ID]));

        // Request ID is saved in the reference2 attribute
        $this->assertNotNull($wallet[WalletEntity::REFERENCE2]);
    }

    public function testPaymentRefundInitiationFailed()
    {
        $this->mockRefundResponseFailure(__FUNCTION__);

        $this->doAuthCaptureAndRefundPayment($this->payment);

        $refund = $this->getDbLastEntityPublic(ConstantsEntity::REFUND);

        $this->assertEquals(Refund\Status::FAILED, $refund[Refund\Entity::STATUS]);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet);

        $this->assertEquals($refund[Refund\Entity::ID], Refund\Entity::getSignedId($wallet[WalletEntity::REFUND_ID]));
        $this->assertEquals($refund[Refund\Entity::PAYMENT_ID],
                            Payment\Entity::getSignedId($wallet[WalletEntity::PAYMENT_ID]));

        // Request ID is saved in the reference2 attribute
        $this->assertNotNull($wallet[WalletEntity::REFERENCE2]);
    }

    public function testPaymentRefundInitiateEmptyResult()
    {
        $this->mockRefundResponseFailure(__FUNCTION__);

        $this->doAuthCaptureAndRefundPayment($this->payment);

        $refund = $this->getDbLastEntityPublic(ConstantsEntity::REFUND);

        $this->assertEquals(Refund\Status::FAILED, $refund[Refund\Entity::STATUS]);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet);

        $this->assertEquals($refund[Refund\Entity::ID], Refund\Entity::getSignedId($wallet[WalletEntity::REFUND_ID]));
        $this->assertEquals($refund[Refund\Entity::PAYMENT_ID],
                            Payment\Entity::getSignedId($wallet[WalletEntity::PAYMENT_ID]));

        // Request ID is saved in the reference2 attribute
        $this->assertNotNull($wallet[WalletEntity::REFERENCE2]);
    }

    public function testPaymentRefundInitiateEmptyStatus()
    {
        $this->mockRefundResponseFailure(__FUNCTION__);

        $this->doAuthCaptureAndRefundPayment($this->payment);

        $refund = $this->getDbLastEntityPublic(ConstantsEntity::REFUND);

        $this->assertEquals(Refund\Status::FAILED, $refund[Refund\Entity::STATUS]);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet);

        $this->assertEquals($refund[Refund\Entity::ID], Refund\Entity::getSignedId($wallet[WalletEntity::REFUND_ID]));
        $this->assertEquals($refund[Refund\Entity::PAYMENT_ID],
                            Payment\Entity::getSignedId($wallet[WalletEntity::PAYMENT_ID]));

        // Request ID is saved in the reference2 attribute
        $this->assertNotNull($wallet[WalletEntity::REFERENCE2]);
    }

    public function testPaymentRefundInitiateMultiplePending()
    {
        $this->mockRefundResponseFailure(__FUNCTION__);

        $this->doAuthCaptureAndRefundPayment($this->payment);

        $refund = $this->getDbLastEntityPublic(ConstantsEntity::REFUND);

        $this->assertEquals(Refund\Status::FAILED, $refund[Refund\Entity::STATUS]);

        $wallet = $this->getDbLastEntityPublic(ConstantsEntity::WALLET);

        $this->assertTestResponse($wallet);

        $this->assertEquals($refund[Refund\Entity::ID], Refund\Entity::getSignedId($wallet[WalletEntity::REFUND_ID]));
        $this->assertEquals($refund[Refund\Entity::PAYMENT_ID],
                            Payment\Entity::getSignedId($wallet[WalletEntity::PAYMENT_ID]));

        // Request ID is not saved in the reference2 attribute, as an exception is thrown before that happens
        $this->assertNull($wallet[WalletEntity::REFERENCE2]);
    }

    public function testPaymentRefundVerifyWhenSuccess()
    {
        $this->doAuthCaptureAndRefundPayment($this->payment);

        $refund = $this->getDbLastRefund(ConstantsEntity::REFUND);

        $this->assertEquals(Refund\Status::INITIATED, $refund->getStatus());

        $this->mockServerContentFunction(
            function(&$content) use ($refund)
            {
                 $content = str_replace('random_reference_id', $refund->getId(), $content);
            });

        $this->retryFailedRefund($refund->getPublicId());

        $this->assertEquals(Refund\Status::PROCESSED, $refund->reload()->getStatus());
    }

    public function testPaymentRefundVerifyWhenFailed()
    {
        $this->doAuthCaptureAndRefundPayment($this->payment);

        $refund = $this->getDbLastRefund(ConstantsEntity::REFUND);

        $this->assertEquals(Refund\Status::INITIATED, $refund->getStatus());

        $this->mockServerContentFunction(function(&$content) use ($refund)
        {
            $content = str_replace(['random_reference_id', 'Completed'],
                                   [$refund->getId(), 'Declined'],
                                   $content);
        });

        $this->retryFailedRefund($refund->getPublicId());

        // TODO: Move this failed
        $this->assertEquals(Refund\Status::INITIATED, $refund->reload()->getStatus());
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
    private function runVerifyFailureFlow(string $function)
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        // We are verifying a captured payment
        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);

        $data = $this->testData['testVerifyFailed'];

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
                            $content = file_get_contents(__DIR__ . '/Xml/invalidInputParams.xml');
                            break;

                        case 'testPaymentSuccessVerifyEmptyString':
                            $content = '';
                            break;

                        case 'testPaymentSuccessVerifyRandomString':
                            $content = 'Random string to be converted to array';
                            break;

                        case 'testPaymentVerifyFailureResponse':
                            $content = str_replace('UpfrontChargeSuccess', 'FAILED', $content);
                            break;

                        case 'testPaymentVerifyIncompleteResponse':
                            $content = file_get_contents(__DIR__ . '/Xml/incompleteResponse.xml');
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
                    switch ($multipleSuccess)
                    {
                        case true:
                            $content = file_get_contents(__DIR__ . '/Xml/doubleXmlResponse.xml');
                            $find = ['FAILURE', 'random_payment_id', '1.00'];
                            $replace = ['UpfrontChargeSuccess', $paymentId, $amount];
                            break;

                        case false:
                            $content = file_get_contents(__DIR__ . '/Xml/doubleXmlResponse.xml');
                            $find = ['random_payment_id', '1.00'];
                            $replace = [$paymentId, $amount];
                            break;

                        default:
                            break;
                    }

                    $content = str_replace($find, $replace, $content);
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
                            $content = str_replace('Pending', 'FAILED', $content);
                            break;

                        case 'testPaymentRefundInitiateEmptyResult':
                            $content = file_get_contents(__DIR__ . '/Xml/refundResponseEmptyDetails.xml');
                            break;

                        case 'testPaymentRefundInitiateEmptyStatus':
                            $content = file_get_contents(__DIR__ . '/Xml/refundResponseEmptyStatus.xml');
                            break;

                        case 'testPaymentRefundInitiateMultiplePending':
                            $content = file_get_contents(__DIR__ . '/Xml/refundResponseMultiplePending.xml');
                            break;

                        default:
                            break;
                    }
                }
            });
    }
}
