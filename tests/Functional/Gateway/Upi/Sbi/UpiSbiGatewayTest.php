<?php

namespace RZP\Tests\Functional\Gateway\Upi\Sbi;

use Mail;
use Excel;
use Mockery;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Refund;
use RZP\Gateway\Upi\Base\Type;
use RZP\Services\RazorXClient;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\PublicEntity;
use RZP\Gateway\Base\VerifyResult;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Upi\Sbi\RefundFile;
use RZP\Models\Payment\PaymentMeta;
use RZP\Excel\Import as ExcelImport;
use RZP\Gateway\Upi\Sbi\RequestFields;
use RZP\Gateway\Upi\Sbi\ResponseFields;
use RZP\Gateway\Upi\Base\Entity as Upi;
use RZP\Gateway\Upi\Sbi\Status as SbiStatus;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class UpiSbiGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    /**
     * @var Payment variable
     */
    protected $payment;

    /**
     * @var Upi Mindgate Sbi terminal
     */
    protected $sharedTerminal;

    protected $upiPaymentService;

    protected function setUp(): void
    {
        $this->testDataFilePath = Constants::MINDGATE_SBI_GATEWAY_TEST_DATA_FILE;

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create(Constants::SHARED_UPI_SBI_MIDGATE_TERMINAL);

        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);

        $this->gateway = Gateway::UPI_SBI;

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->enableMethod(Account::DEMO_ACCOUNT, Method::UPI);

        $this->payment = $this->getDefaultUpiPaymentArray();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);
    }

    /**
     * Tests the flow where the customer is sent the collect request from the razorpay sbi vpa.
     * User then accepts the collect request, and a asynchronous callback is sent to API.
     * We assert the entity is being updated correctly in this flow
     */
    public function testPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        // Coproto must be working
        $this->assertEquals(Constants::ASYNC, $response[Constants::TYPE]);

        $payment = $this->getDbLastPayment();
        $upiEntity = $this->getDbLastEntity(Entity::UPI);

        $this->assertSame(Payment\Status::CREATED, $payment->getStatus());

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('SUCCESS', $response['status']);

        // The payment should now be authorized
        $payment->refresh();
        $upiEntity->refresh();

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment->getStatus());

        $content = ($this->getDecryptedContent($content[ResponseFields::MESSAGE]))[ResponseFields::API_RESPONSE];

        $this->assertEquals($content[ResponseFields::CUSTOMER_REFERENCE_NO], $upiEntity[Upi::NPCI_REFERENCE_ID]);

        $this->assertNotNull($payment[Payment\Entity::REFERENCE16]);
        $this->assertEquals($content[ResponseFields::CUSTOMER_REFERENCE_NO], $payment[Payment\Entity::REFERENCE16]);

        $this->assertEquals($content[ResponseFields::UPI_TRANS_REFERENCE_NO], $upiEntity[Upi::GATEWAY_PAYMENT_ID]);
        $this->assertEquals($content[ResponseFields::STATUS], $upiEntity[Upi::STATUS_CODE]);
        $this->assertEquals(Type::COLLECT, $upiEntity[Upi::TYPE]);
        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);
        $this->assertNotNull($upiEntity[Upi::EXPIRY_TIME]);
        $this->assertNotNull($payment[Payment\Entity::ACQUIRER_DATA]);

        $this->assertNotNull($upiEntity[Upi::GATEWAY_DATA]);
        $this->assertEquals('123456789012',$upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertEquals('7971807546', $upiEntity[Upi::GATEWAY_DATA]['addInfo2']);
    }

    public function testIntentPayment()
    {
        $this->sharedTerminal = $this->fixtures->create(Constants::SHARED_UPI_SBI_INTENT_TERMINAL);

        $this->fixtures->merchant->setCategory('1111');

        $this->payment['description'] = 'intentPayment';
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $this->assertSame("upi://pay?am=100.00&cu=INR&mc=1111&pa=some@sbi&pn=merchantname&tn=TestMerchantintentPayment&tr=pay_someid", $response['data']['intent_url']);

        $paymentId = $response['payment_id'];

        $payment = $this->getDbLastPayment();

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);

        $intentUrl = $response['data']['intent_url'];
        $mccFromIntentUrl = substr($intentUrl, strpos($intentUrl,'&mc=') + 4, 4);

        $this->assertEquals('1111', $mccFromIntentUrl);

        $this->checkPaymentStatus($paymentId, 'created');

        $upiEntity = $this->getDbLastEntity(Entity::UPI);

        $this->assertSame(Payment\Status::CREATED, $payment->getStatus());

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('SUCCESS', $response['status']);

        // The payment should now be authorized
        $payment->refresh();
        $upiEntity->refresh();

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment->getStatus());

        $content = ($this->getDecryptedContent($content[ResponseFields::MESSAGE]))[ResponseFields::API_RESPONSE];

        $this->assertEquals($content[ResponseFields::CUSTOMER_REFERENCE_NO], $upiEntity[Upi::NPCI_REFERENCE_ID]);

        $this->assertNotNull($payment[Payment\Entity::REFERENCE16]);
        $this->assertEquals($content[ResponseFields::CUSTOMER_REFERENCE_NO], $payment[Payment\Entity::REFERENCE16]);

        $this->assertEquals($content[ResponseFields::UPI_TRANS_REFERENCE_NO], $upiEntity[Upi::GATEWAY_PAYMENT_ID]);
        $this->assertEquals($content[ResponseFields::STATUS], $upiEntity[Upi::STATUS_CODE]);
        $this->assertEquals(Type::PAY, $upiEntity[Upi::TYPE]);
        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);

        $this->assertNotNull($upiEntity[Upi::GATEWAY_DATA]);
        $this->assertEquals('99999999',$upiEntity[Upi::NPCI_TXN_ID]);
        $this->assertEquals('7971807546', $upiEntity[Upi::GATEWAY_DATA]['addInfo2']);
    }

    public function testLateAuthorization()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastEntity('upi');

        $this->assertArraySubset([
            'npci_txn_id'   => '99999999',
            'gateway_data'  => [],
        ], $upi->toArray());

        $this->assertEmpty($upi[Upi::GATEWAY_DATA]);

        $this->authorizedFailedPayment($payment->getPublicId());

        $payment->reload();

        $this->assertTrue($payment->isAuthorized());
        $this->assertTrue($payment->isLateAuthorized());

        $upi->reload();
        $this->assertEquals($upi->getPaymentId(), $payment['id']);
        $this->assertSame('vishnu@icici', $upi->getVpa());
        $this->assertSame('icici', $upi->provider);
        $this->assertSame('ICIC', $upi->bank);

        $this->assertArraySubset([
            'npci_txn_id'   => '99999999999',
            'gateway_data'  => [
                'addInfo2'  => '7971807546',
            ],
        ], $upi->toArray());
    }

    public function testPaymentWithRetryOnGatewayRequestExceptions()
    {
        $this->markTestSkipped();

        $this->getGatewayRequestException();

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response[Constants::PAYMENT_ID];

        // Coproto must be working
        $this->assertEquals(Constants::ASYNC, $response[Constants::TYPE]);

        $this->checkPaymentStatus($paymentId, Payment\Status::CREATED);

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity);

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('SUCCESS', $response['status']);

        // The payment should now be authorized
        $payment = $this->getEntityById(Entity::PAYMENT, $paymentId, true);
        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $content = ($this->getDecryptedContent($content[ResponseFields::MESSAGE]))[ResponseFields::API_RESPONSE];

        $this->assertEquals($content[ResponseFields::UPI_TRANS_REFERENCE_NO], $upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertEquals($content[ResponseFields::CUSTOMER_REFERENCE_NO], $upiEntity[Upi::GATEWAY_PAYMENT_ID]);
        $this->assertEquals($content[ResponseFields::STATUS], $upiEntity[Upi::STATUS_CODE]);
        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);
        $this->assertNotNull($upiEntity[Upi::EXPIRY_TIME]);
    }

    public function testPaymentWithExpiryPrivateAuth()
    {
        $this->fixtures->merchant->addFeatures(['s2supi']);

        $payment = $this->getDefaultUpiPaymentArray();

        $payment['upi']['expiry_time'] = 10;

        $this->mockServerRequestFunction(
            function($content, $action)
            {
                if ($action === 'authorize')
                {
                    $this->assertEquals('10', $content[RequestFields::EXPIRY_TIME]);
                }
            });

        $response = $this->doS2SUpiPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $this->checkPaymentStatus($paymentId, 'created');

        $upiEntity = $this->getLastEntity('upi', true);

        $this->assertEquals(10, $upiEntity['expiry_time']);
    }

    /**
     * Force the gateway to raise a failure on trying
     * to initiate web collect
     */
    public function testFailedCollect($vpa = null)
    {
        $this->payment[Payment\Entity::VPA] = $vpa ? $vpa : Constants::FAILED_VPA;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthPaymentViaAjaxRoute($this->payment);
            });

        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $this->assertEquals(Type::COLLECT, $upiEntity[Upi::TYPE]);

        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_FAILED, $payment[Payment\Entity::INTERNAL_ERROR_CODE]);

        return $payment;
    }

    public function testFailedVpaValidation()
    {
        $this->markTestSkipped();

        $this->payment[Payment\Entity::VPA] = Constants::VALIDATION_FAIL_VPA;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthPaymentViaAjaxRoute($this->payment);
            });

        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        // We don't reach the collect request state
        $this->assertNull($upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertNull($upiEntity[Upi::GATEWAY_PAYMENT_ID]);

        $this->assertEquals(SbiStatus::UNAVAILABLE_VPA, $upiEntity[Upi::STATUS_CODE]);
        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA, $payment[Payment\Entity::INTERNAL_ERROR_CODE]);

        return $payment;
    }

    public function testValidateVpaSuccess()
    {
        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIMgateSbi');

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->mockServerRequestFunction(
            function (& $request, $action = null)
            {
                $this->assertEquals('validate_vpa', $action);
                $this->assertStringContainsString('/payments/upi_sbi/v2/validate_vpa', $request['url']);
            }
        );

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testValidateVpaSuccessWithBlockedDBSave()
    {
        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIMgateSbi');

        $this->app->razorx->method('getTreatment')->will($this->returnCallback(
            function ($mid, $feature, $mode)
            {
                if ($feature === 'block_validate_vpa_db_writes')
                {
                    return 'on';
                }

                return 'control';
            })
        );

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->ba->privateAuth();

        $this->startTest();

        $vpa = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertSame(null, $vpa);
    }

    public function testValidateVpaSuccessWithoutBlockedDBSave()
    {
        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIMgateSbi');

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->ba->privateAuth();

        $this->startTest();

        $vpa = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertSame('Test User', $vpa->getName());
        $this->assertSame('valid', $vpa->getStatus());
        $this->assertGreaterThanOrEqual(1600000000, $vpa->getReceivedAt());
    }

    public function testValidateVpaSuccessWithRazorx()
    {
        $this->app->razorx->method('getTreatment')->will($this->returnCallback(
            function ($mid, $feature, $mode)
            {
                if ($feature === 'upi_sbi_v3_migration')
                {
                    return 'v3';
                }

                return 'control';
            })
        );

        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIMgateSbi');

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->mockServerRequestFunction(
            function (& $request, $action = null)
            {
                $this->assertEquals('validate_vpa', $action);
                $this->assertStringContainsString('/payments/upi_sbi/v3/validate_vpa', $request['url']);
            }
        );

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testValidateVpaSuccessWithPrefixSpace()
    {
        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIMgateSbi');

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testValidateVpaSuccessWithPrefixAndSuffixSpace()
    {
        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIMgateSbi');

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testValidateVpaFailure()
    {
        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIMgateSbi');

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testValidateAccountVpa()
    {
        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIMgateSbi');

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testValidateAccountVpaFailed()
    {
        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIMgateSbi');

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testValidateAccountVpaTimeout()
    {
        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIMgateSbi');

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testValidateAccountInvalidInput()
    {
        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIMgateSbi');

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testValidateAccountVpaGatewayError()
    {
        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIMgateSbi');

        $this->ba->publicAuth();

        $this->startTest();
    }

    // Test removal of Customer Name from Validate Account(Public Route) Response.
    public function testValidateAccount()
    {
        $request = [
            'url'    => '/payments/validate/account',
            'method' => 'post',
            'content' => [
                'entity' => 'vpa',
                'value'  => 'success@sbi',
            ],
        ];

        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArrayKeysExist($response, ['vpa', 'success','customer_name']);
        $this->assertEquals(true, $response['success']);
        $this->assertEquals("*********", $response['customer_name']);
    }

    public function testValidateVpa()
    {
        $request   = [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => 'success@sbi',
            ],
        ];

        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIMgateSbi');
        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArrayKeysExist($response, ['vpa', 'success', 'customer_name']);
        $this->assertEquals("Test User", $response['customer_name']);
        $this->assertEquals(true, $response['success']);
    }

    /**
     * When we verify a payment whose vpa validation failed,
     * we should be getting a response that says no transaction found.
     * In this case, we must set $gatewaySuccess = false, as $apiSuccess is already false.
     */
    public function testFailedVpaValidationVerify()
    {
        $this->markTestSkipped();

        $payment = $this->testFailedVpaValidation();

        $this->mockFailedVpaValidationVerify();

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        $this->assertEquals(false, $verify[Constants::GATEWAY][Constants::API_SUCCESS]);
        $this->assertEquals(false, $verify[Constants::GATEWAY][Constants::GATEWAY_SUCCESS]);
        $this->assertEquals(false, $verify[Constants::GATEWAY][Constants::AMOUNT_MISMATCH]);
        $this->assertEquals(VerifyResult::STATUS_MATCH, $verify[Constants::GATEWAY][Constants::STATUS]);
        $this->assertEquals($verify[Entity::PAYMENT][Payment\Entity::ID], $payment[Payment\Entity::ID]);

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        // Status remains in failed state
        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);

        $upiEntity = $this->getLastEntity(ConstantsEntity::UPI, true);

        $this->assertEquals(SbiStatus::VALIDATION_ERROR, $upiEntity[Upi::STATUS_CODE]);
    }

    /**
     * Create a payment, and reject it so callback
     * returns failure
     */
    public function testCollectRejectedFailure()
    {
        $this->payment[Payment\Entity::VPA] = Constants::REJECTED_VPA;

        $response = $this->doAuthPayment($this->payment);

        $paymentId = $response[Constants::PAYMENT_ID];

        $this->checkPaymentStatus($paymentId, Payment\Status::CREATED);

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('SUCCESS', $response['status']);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $this->assertNotNull($upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertNotNull($upiEntity[Upi::GATEWAY_PAYMENT_ID]);

        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED, $payment[Payment\Entity::INTERNAL_ERROR_CODE]);
    }

    public function testCbsDownCollectRequest()
    {
        $this->payment[Payment\Entity::VPA] = Constants::CBS_DOWN_VPA;

        $data = $this->testData[__FUNCTION__];

        $payment = $this->payment;

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            });

        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT, $payment[Payment\Entity::INTERNAL_ERROR_CODE]);
    }

    public function testCbsDownCallback()
    {
        $this->markTestSkipped();
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response[Constants::PAYMENT_ID];

        // Coproto must be working
        $this->assertEquals(Constants::ASYNC, $response[Constants::TYPE]);

        $this->checkPaymentStatus($paymentId, Payment\Status::CREATED);

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $upiEntity[Upi::VPA] = Constants::CBS_DOWN_VPA;

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity);

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('SUCCESS', $response['status']);

        // The payment should now be authorized
        $payment = $this->getEntityById(Entity::PAYMENT, $paymentId, true);
        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $content = ($this->getDecryptedContent($content[ResponseFields::MESSAGE]))[ResponseFields::API_RESPONSE];

        $this->assertEquals($content[ResponseFields::UPI_TRANS_REFERENCE_NO], $upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertEquals($content[ResponseFields::CUSTOMER_REFERENCE_NO], $upiEntity[Upi::GATEWAY_PAYMENT_ID]);

        // The upi entity status will be changed from S to T
        $this->assertEquals(SbiStatus::CBS_DOWN, $upiEntity[Upi::STATUS_CODE]);
        $this->assertEquals($content[ResponseFields::STATUS], $upiEntity[Upi::STATUS_CODE]);

        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);
    }

    /**
     * This verifies the transaction status after a payment has been successfully made.
     */
    public function testPaymentVerify()
    {
        $payment = $this->createCapturedPayment();

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        $this->assertEquals(true, $verify[Constants::GATEWAY][Constants::API_SUCCESS]);
        $this->assertEquals(true, $verify[Constants::GATEWAY][Constants::GATEWAY_SUCCESS]);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        // Status remains in Success after verify
        $this->assertEquals(SbiStatus::SUCCESS, $upiEntity[Upi::STATUS_CODE]);

        $this->assertEquals($verify[Entity::PAYMENT][Payment\Entity::ID], $payment[Payment\Entity::ID]);
        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);
        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);
    }

    public function testPaymentSuccessVerifyFailed()
    {
        $payment = $this->createCapturedWithVpaPayment('failedverify@sbi');

        $data = $this->testData['testVerifyFailed'];

        $this->mockVerifyFailed();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });

        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        // Status changed from S to F
        $this->assertEquals(SbiStatus::FAILED, $upiEntity[Upi::STATUS_CODE]);

        $this->assertEquals(0, $payment[Payment\Entity::VERIFIED]);
    }

    public function testPaymentFailedVerifyFailed()
    {
        $payment = $this->testFailedCollect();

        $this->mockVerifyFailed();

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);
        // This should result in api success and gateway success = false
        $this->assertEquals(false, $verify[Constants::GATEWAY][Constants::API_SUCCESS]);
        $this->assertEquals(false, $verify[Constants::GATEWAY][Constants::GATEWAY_SUCCESS]);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);
    }

    public function testPaymentFailedVerifyFailedWithIncompleteResponse()
    {
        $this->markTestSkipped();
        $this->testPayment();

        $upiEntity = $this->getDbLastEntity(Entity::UPI);
        $payment = $this->getDbLastPayment();

        $this->assertNotNull($upiEntity[Upi::GATEWAY_DATA]);

        $this->mockVerifyFailed();

        $data = $this->testData['testVerifyFailed'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment->getPublicId());
            });

        $payment->refresh();
        $upiEntity->refresh();

        $this->assertNotEquals([], $upiEntity[Upi::GATEWAY_DATA]);
    }

    public function testPaymentVerifyBlock()
    {
        $payment = $this->testFailedCollect('blockverify@sbi');

        $data = $this->testData['testVerifyFailed'];

        $this->mockVerifyBlock();

        $time = Carbon::now(Timezone::IST)->addMinutes(4);

        Carbon::setTestNow($time);

        $this->ba->cronAuth();

        $this->verifyAllPayments();

        $payment = $this->getDbLastPayment();

        $this->assertEquals(9, $payment['verify_bucket']);
    }

    public function testAmountAssertionFailure()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response[Constants::PAYMENT_ID];

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $content = $this->getS2SAmountMismatchContent($upiEntity);

        $response =$this->makeS2SCallbackAndGetContent($content);

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('SUCCESS', $response['status']);

        $upiEntity = $this->getLastEntity(Entity::UPI, true);
        $payment = $this->getEntityById(Entity::PAYMENT, $paymentId, true);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        // Status will be S, because amount assertion failure doesn't rely on status
        $this->assertNotNull($upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertNotNull($upiEntity[Upi::GATEWAY_PAYMENT_ID]);
        $this->assertEquals(SbiStatus::SUCCESS, $upiEntity[Upi::STATUS_CODE]);
        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);
    }

    public function testRefundFileFlow()
    {
        Mail::fake();

        $this->app['config']->set(['applications.upi_payment_service.enabled' => true]);

        $this->upiPaymentService = Mockery::mock('RZP\Services\UpiPayment\Mock\Service', [$this->app])->makePartial();

        $this->app->instance('upi.payments', $this->upiPaymentService);

        $payments = [];

        // Create 3 payments
        $payments[] = $this->createCapturedPayment();

        $upiEntityOld = $this->getDbLastEntity('upi');

        $payments[] = $this->createCapturedPayment();

        $upiEntity = $this->getDbLastEntity('upi');

        $this->fixtures->edit('upi', $upiEntity['id'], [Upi::MERCHANT_REFERENCE => '']);

        $payments[] = $this->createCapturedPayment();

        $upiEntity = $this->getDbLastEntity('upi');

        $this->fixtures->edit('upi', $upiEntity['id'], [Upi::MERCHANT_REFERENCE => 'HDFc9935b57bb584fa493a265fb8723fdbb']);

        // create UPS Payments
        $upsPayments        = [];
        $upsGatewayEntities = [];

        $upsPayments[]          = $this->createCapturedPayment();
        $gatewayEntity          = $this->getDbLastEntity('upi');
        $upsGatewayEntities[]   = [
            File\Constants::GATEWAY_MERCHANT_ID => $gatewayEntity[Upi::GATEWAY_MERCHANT_ID],
            File\Constants::CUSTOMER_REFERENCE  => $gatewayEntity[Upi::NPCI_REFERENCE_ID],
            File\Constants::GATEWAY_DATA        => json_encode($gatewayEntity[Upi::GATEWAY_DATA]),
            File\Constants::PAYMENT_ID          => $gatewayEntity[Upi::PAYMENT_ID],
        ];
        $this->fixtures->edit('upi', $gatewayEntity['id'], [Upi::PAYMENT_ID => 'unknown_pay_id']);
        $this->fixtures->edit('payment', $upsPayments[0]['id'], [Payment\Entity::CPS_ROUTE => 7]);

        $upsPayments[]          = $this->createCapturedPayment();
        $gatewayEntity          = $this->getDbLastEntity('upi');
        $upsGatewayEntities[]   = [
            File\Constants::GATEWAY_MERCHANT_ID => $gatewayEntity[Upi::GATEWAY_MERCHANT_ID],
            File\Constants::MERCHANT_REFERENCE  => 'SBI93FFE55C71C64203824B3E241302B487',
            File\Constants::CUSTOMER_REFERENCE  => $gatewayEntity[Upi::NPCI_REFERENCE_ID],
            File\Constants::GATEWAY_DATA        => json_encode($gatewayEntity[Upi::GATEWAY_DATA]),
            File\Constants::PAYMENT_ID          => $gatewayEntity[Upi::PAYMENT_ID],
        ];
        $this->fixtures->edit('upi', $gatewayEntity['id'], [Upi::PAYMENT_ID => 'unknown_pay_id']);
        $this->fixtures->edit('payment', $upsPayments[1]['id'], [Payment\Entity::CPS_ROUTE => 4]);

        $allPayments = array_merge($payments, $upsPayments);

        // Refund 3 fully and the other 2 partially
        $refundAmount = [50000, 50000, 10000, 50000, 10000];

        $refunds = [];
        $refundEntities = [];

        foreach ($allPayments as $count => $payment)
        {
            $refunds[] = $this->refundPayment($payment[Payment\Entity::ID], $refundAmount[$count]);

            $refundEntity = $this->getDbLastEntity('refund');

            $refundEntities[] = $refundEntity;

            // Upi Sbi refunds have moved to scrooge
            $this->assertEquals(1, $refundEntity['is_scrooge']);
        }

        foreach ($refunds as $refund)
        {
            $createdAt = Carbon::yesterday(Timezone::IST)->timestamp + 5;
            $this->fixtures->edit(Entity::REFUND, $refund[Refund\Entity::ID], [Refund\Entity::CREATED_AT => $createdAt]);
        }

        // Refund a 4th payment
        $payment = $this->createCapturedPayment();
        $this->refundPayment($payment[Payment\Entity::ID]);

        $refundEntity = $this->getDbLastEntity('refund');

        $refundEntities[] = $refundEntity;

        // Upi Sbi refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse($refundEntities);

        // mock ups response
        $this->mockUpsServerContentFunction(function(& $content) use ($upsGatewayEntities) {
            $content['entities'] = $upsGatewayEntities;
            $content['success']  = true;
        });

        $data = $this->generateRefundsExcelForSbiUpi();

        $content = $data['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $time = Carbon::now(Timezone::IST)->format('dmY_Hi');

        $this->assertEquals('file_store', $file['entity']);
        $this->assertEquals('rzp-1415-prod-sftp', $file['bucket']);
        $this->assertEquals('ap-south-1', $file['region']);
        $this->assertEquals('upi/upi_sbi/refund/normal_refund_file/SBI0000000000232_' . $time .'.csv', $file['location']);
        $this->assertEquals('upi/upi_sbi/refund/normal_refund_file/SBI0000000000232_' . $time, $file['name']);

        $refundFileRows = (new ExcelImport)->toArray('storage/files/filestore/'.$file['location'])[0];

        $paymentId = str_replace('pay_', '',$payments[0][Payment\Entity::ID]);

        $paymentId1 = str_replace('pay_', '',$payments[1][Payment\Entity::ID]);

        $refundId = str_replace('rfnd_', "",$refunds[0]['id']);

        $refundId1 = str_replace('rfnd_', "",$refunds[1]['id']);

        $refundId2 = str_replace('rfnd_', "",$refunds[2]['id']);

        $refundId3 = str_replace('rfnd_', "",$refunds[3]['id']);

        $refundId4 = str_replace('rfnd_', "",$refunds[4]['id']);

        $expectedRefundFileForNullMR = [
            'pg_merchant_id' => "SBI0000000000119",
            'refund_req_no' => $refundId,
            'trans_ref_no' => 7971807546,
            'customer_ref_no' => 123456789012,
            'order_no' => $paymentId,
            'refund_req_amt' => 500,
            'refund_remark' =>  "Refund for ".$paymentId
        ];

        $this->assertNull($upiEntityOld[Upi::MERCHANT_REFERENCE]);

        $expectedRefundFileForEmptyMR = [
            'pg_merchant_id' => "SBI0000000000119",
            'refund_req_no' => $refundId1,
            'trans_ref_no' => 7971807546,
            'customer_ref_no' => 123456789012,
            'order_no' => $paymentId1,
            'refund_req_amt' => 500,
            'refund_remark' =>  "Refund for ".$paymentId1
        ];

        $expectedRefundFileContentForMR = [
            'pg_merchant_id' => "SBI0000000000119",
            'refund_req_no' => $refundId2,
            'trans_ref_no' => 7971807546,
            'customer_ref_no' => 123456789012,
            'order_no' => 'HDFc9935b57bb584fa493a265fb8723fdbb',
            'refund_req_amt' => 100,
            'refund_remark' =>  "Refund for ".$upiEntity->getPaymentId()
        ];

        $expectedRefundFileContentForUpsGatewayEntityWithoutCustomerRef = [
            'pg_merchant_id' => $upsGatewayEntities[1][File\Constants::GATEWAY_MERCHANT_ID],
            'refund_req_no' => $refundId3,
            'trans_ref_no' => 7971807546,
            'customer_ref_no' => 123456789012,
            'order_no' => $upsGatewayEntities[0][File\Constants::PAYMENT_ID],
            'refund_req_amt' => 500,
            'refund_remark' =>  "Refund for ". $upsGatewayEntities[0][Upi::PAYMENT_ID],
        ];

        $expectedRefundFileContentForUpsGatewayEntity = [
            'pg_merchant_id' => $upsGatewayEntities[1][File\Constants::GATEWAY_MERCHANT_ID],
            'refund_req_no' => $refundId4,
            'trans_ref_no' => 7971807546,
            'customer_ref_no' => 123456789012,
            'order_no' => $upsGatewayEntities[1][File\Constants::MERCHANT_REFERENCE],
            'refund_req_amt' => 100,
            'refund_remark' =>  "Refund for ". $upsGatewayEntities[1][Upi::PAYMENT_ID],
        ];

        $this->assertArraySelectiveEquals($expectedRefundFileForNullMR, $refundFileRows[0]);
        $this->assertArraySelectiveEquals($expectedRefundFileForEmptyMR, $refundFileRows[1]);
        $this->assertArraySelectiveEquals($expectedRefundFileContentForMR, $refundFileRows[2]);
        $this->assertArraySelectiveEquals($expectedRefundFileContentForUpsGatewayEntityWithoutCustomerRef, $refundFileRows[3]);
        $this->assertArraySelectiveEquals($expectedRefundFileContentForUpsGatewayEntity, $refundFileRows[4]);

        Mail::assertQueued(RefundFileMail::class);
    }

    public function testUpiResponseAssertionFailure()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response[Constants::PAYMENT_ID];

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $content = $this->getS2SUpiIdMismatchContent($upiEntity);

        $this->makeS2SCallbackAndGetContent($content);

        $upiEntity = $this->getLastEntity(Entity::UPI, true);
        $payment = $this->getEntityById(Entity::PAYMENT, $paymentId, true);

        // Status will be S, because assertion failure doesn't rely on status
        $this->assertNotNull($upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertNotNull($upiEntity[Upi::GATEWAY_PAYMENT_ID]);
        $this->assertEquals(SbiStatus::SUCCESS, $upiEntity[Upi::STATUS_CODE]);
        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);
        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
    }

    protected function createCapturedPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response[Constants::PAYMENT_ID];

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity);

        $this->makeS2SCallbackAndGetContent($content);

        $upiEntity = $this->getLastEntity(Entity::UPI, true);
        $payment = $this->getEntityById(Entity::PAYMENT, $paymentId, true);

        $this->assertNotNull($upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertNotNull($upiEntity[Upi::GATEWAY_PAYMENT_ID]);
        $this->assertEquals(SbiStatus::SUCCESS, $upiEntity[Upi::STATUS_CODE]);
        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        // Add a capture as well, just for completeness sake
        $payment = $this->capturePayment($paymentId, $payment[Payment\Entity::AMOUNT]);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);

        return $payment;
    }

    public function testMultipleRefundsWithAmountMismatch()
    {
        Mail::fake();

        $payment1 = $this->createCapturedPayment();

        $paymentId = PublicEntity::stripDefaultSign($payment1['id']);

        //case - amount deficit
        $input = [
            'payment_id'                => $paymentId,
            'gateway_amount'            => 49900,
            'mismatch_amount'           => 100,
            'mismatch_amount_reason'    => 'credit_deficit',
        ];

        $this->createPaymentMetaEntity($input);

        //full refund case
        $this->refundPayment($payment1['id'], 50000);

        $refundEntity1 = $this->getDbLastEntity('refund');

        $gatewayAmount = $refundEntity1->getGatewayAmount();

        $gatewayCurrency = $refundEntity1->getGatewayCurrency();

        $this->assertEquals(49900, $gatewayAmount);

        $this->assertEquals('INR', $gatewayCurrency);

        //amount mismatch case - amount surplus
        $payment2 = $this->createCapturedPayment();

        $paymentId2 = PublicEntity::stripDefaultSign($payment2['id']);

        $input = [
            'payment_id'                => $paymentId2,
            'gateway_amount'            => 50050,
            'mismatch_amount'           => 50,
            'mismatch_amount_reason'    => 'credit_surplus',
        ];

        //payment2 for handling amount mismatch surplus case
        $this->createPaymentMetaEntity($input);

        //partial refund case
        $this->refundPayment($payment2['id'], 25000);

        $refundEntity2 = $this->getDbLastEntity('refund');

        $this->refundPayment($payment2['id'], 25000);

        //we are going to use last refund as we handle amount mismatch in last refund
        $refundEntity3 = $this->getDbLastEntity('refund');

        $gatewayAmount3 = $refundEntity3->getGatewayAmount();

        $gatewayCurrency3 = $refundEntity3->getGatewayCurrency();

        $this->assertEquals(25050, $gatewayAmount3);

        $this->assertEquals('INR', $gatewayCurrency3);

        //handling edge case
        $payment4 = $this->createCapturedPayment();

        $paymentId4 = PublicEntity::stripDefaultSign($payment4['id']);

        //amount mismatch case - amount deficit case
        $input = [
            'payment_id'                => $paymentId4,
            'gateway_amount'            => 49900,
            'mismatch_amount'           => 100,
            'mismatch_amount_reason'    => 'credit_deficit',
        ];

        $this->createPaymentMetaEntity($input);

        //partial refund case
        $this->refundPayment($payment4['id'], 49900);

        $refundEntity4 = $this->getDbLastEntity('refund');

        $this->refundPayment($payment4['id'], 100);

        //we are going to use last refund as we handle amount mismatch in last refund
        $refundEntity5 = $this->getDbLastEntity('refund');

        $refundEntity5Array = $refundEntity5->toArray();

        $this->assertEquals(0, $refundEntity5Array['gateway_amount']);

        $status = $refundEntity5->getStatus();

        //0 rupees refund is marked as processed now
        $this->assertEquals('processed', $status);

        $refundEntities = [$refundEntity1, $refundEntity2, $refundEntity3, $refundEntity4, $refundEntity5];

        $this->setFetchFileBasedRefundsFromScroogeMockResponse($refundEntities);

        $data = $this->generateRefundsExcelForSbiUpi();

        $content = $data['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertQueued(RefundFileMail::class);
    }

    private function createPaymentMetaEntity($input)
    {
        (new PaymentMeta\Core)->create($input);
    }


    public function testUnexpectedPaymentSuccess()
    {
        $content = $this->mockServer()->getUnexpectedAsyncCallbackContent('success');

        $this->makeS2SCallbackAndGetContent($content);

        $paymentEntity = $this->getLastEntity('payment', true);

        $authorizeUpiEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($authorizeUpiEntity['merchant_reference']);

        $this->assertSame('123456789012', $paymentEntity['reference16']);

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
            Account::DEMO_ACCOUNT                  => $paymentEntity['merchant_id'],
            'SBI0000000000119'                     => $authorizeUpiEntity['gateway_merchant_id'],
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }
    }

    public function testUnexpectedPaymentFail()
    {
        $content = $this->mockServer()->getUnexpectedAsyncCallbackContent('failure');

        $this->mockVerifyFailed();

        $this->makeS2SCallbackAndGetContent($content);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertNull($paymentEntity);
    }

    public function testDataCorrectionForUpiEntity()
    {
        $max    = 50;
        $count  = 10;
        $upis = [];
        $gpid = function($id)
        {
            return '111' . str_pad($id, 8, '0', STR_PAD_LEFT);
        };
        $nrid = function($id)
        {
            return '2222' . str_pad($id, 8, '0', STR_PAD_LEFT);
        };

        for ($i = 1 ; $i <= $max; $i++)
        {
            $upi = new Upi();
            $upi->forceFill([
                'action'                => 'authorize',
                'amount'                => 100,
                'acquirer'              => 'SBIN',
                'gateway'               => 'upi_sbi',
                'payment_id'            => 'Pa' . $nrid($i),               // 14 Chars
                'npci_reference_id'     => $gpid($i),                      // 11 Chars
                'gateway_payment_id'    => $nrid($i),                      // 12 Chars
            ]);

            $upi->saveOrFail();

            $upis[$i] = $upi->getId();
        }

        $this->makeDataCorrectionRequest($count);

        // Last of first batch is corrected
        $i = ($max - $count + 1);
        $entityA = $this->getDbEntityById('upi', $upis[$i]);
        $this->assertSame($gpid($i), $entityA->getRawOriginal('gateway_payment_id'));
        $this->assertSame($nrid($i), $entityA->getRawOriginal('npci_reference_id'));

        // before last of first batch is not corrected
        $i = ($max - $count);
        $entityB = $this->getDbEntityById('upi', $upis[$i]);
        $this->assertSame($nrid($i), $entityB->getRawOriginal('gateway_payment_id'));
        $this->assertSame($gpid($i), $entityB->getRawOriginal('npci_reference_id'));

        $this->makeDataCorrectionRequest($count + 5);

        // before last of first batch is now corrected
        $i = ($max - $count);
        $entityB = $this->getDbEntityById('upi', $upis[$i]);
        $this->assertSame($gpid($i), $entityB->getRawOriginal('gateway_payment_id'));
        $this->assertSame($nrid($i), $entityB->getRawOriginal('npci_reference_id'));

        // Last of second batch is corrected
        $i = ($max - ($count * 2) - 4);
        $entityC = $this->getDbEntityById('upi', $upis[$i]);
        $this->assertSame($gpid($i), $entityC->getRawOriginal('gateway_payment_id'));
        $this->assertSame($nrid($i), $entityC->getRawOriginal('npci_reference_id'));

        // before last of second batch is not corrected
        $i = ($max - ($count * 2) - 5);
        $entityD = $this->getDbEntityById('upi', $upis[$i]);
        $this->assertSame($nrid($i), $entityD->getRawOriginal('gateway_payment_id'));
        $this->assertSame($gpid($i), $entityD->getRawOriginal('npci_reference_id'));

        $entityD->setGatewayPaymentId('Not12Chars');
        $entityD->saveOrFail();

        $this->makeRequestAndCatchException(
            function() use ($count)
            {
                $this->makeDataCorrectionRequest($count);
            },
            Exception\LogicException::class,
            'Gateway Payment Id is not RRN');

        $this->makeDataCorrectionRequest($count, [
            ['npci_reference_id', '<', $gpid($i)],
        ]);

        // before last of second batch still not fixed
        $entityD = $this->getDbEntityById('upi', $upis[$i]);
        $this->assertSame('Not12Chars', $entityD->getRawOriginal('gateway_payment_id'));
        $this->assertSame($gpid($i), $entityD->getRawOriginal('npci_reference_id'));

        // First in third batch is fixed
        $i = $i - 1;
        $entityE = $this->getDbEntityById('upi', $upis[$i]);
        $this->assertSame($gpid($i), $entityE->getRawOriginal('gateway_payment_id'));
        $this->assertSame($nrid($i), $entityE->getRawOriginal('npci_reference_id'));
    }

    public function testPaymentWithGstTaxInvoice()
    {
        $this->ba->privateAuth();

        $data = $this->testData[__FUNCTION__];

        $this->mockServerRequestFunction(function (& $input, $action = 'authorize')
        {
            $this->assertCount(1, $input['order_meta']);
        });

        $order = $this->startTest();

        $order = $this->getLastEntity('order', true);
        $orderMeta = $this->getLastEntity('order_meta', true);

        $this->assertNotNull($orderMeta);

        $payment = $this->getDefaultUpiPaymentArray();
        $payment['amount'] = $order['amount'];
        $payment['order_id'] = $order['id'];

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $payment = $this->getDbLastPayment();
        $upiEntity = $this->getDbLastEntity(Entity::UPI);

        $this->assertSame(Payment\Status::CREATED, $payment->getStatus());

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('SUCCESS', $response['status']);

        // The payment should now be authorized
        $payment->refresh();
        $upiEntity->refresh();

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment->getStatus());
    }

    public function testIntentPaymentWithGstTaxInvoice()
    {
        $this->sharedTerminal = $this->fixtures->create(Constants::SHARED_UPI_SBI_INTENT_TERMINAL);

        //Explicitly setting category to null, to test if default value of 5411 is picked up or not.
        $this->fixtures->merchant->setCategory(null);

        $this->ba->privateAuth();

        $data = $this->testData['testPaymentWithGstTaxInvoice'];

        $this->mockServerRequestFunction(function (& $input, $action = 'authorize')
        {
            $this->assertCount(1, $input['order_meta']);
        });

        $order = $this->startTest($data);

        $order = $this->getLastEntity('order', true);
        $orderMeta = $this->getLastEntity('order_meta', true);

        $this->assertNotNull($orderMeta);

        $payment = $this->getDefaultUpiPaymentArray();
        $payment['amount'] = $order['amount'];
        $payment['order_id'] = $order['id'];

        $payment['description'] = 'intentPayment';
        unset($payment['vpa']);

        $payment['_']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertSame("upi://pay?am=100.00&cu=INR&mc=5411&pa=some@sbi&pn=merchantname&tn=TestMerchantintentPayment&tr=pay_someid", $response['data']['intent_url']);

        $paymentId = $response['payment_id'];

        $payment = $this->getDbLastPayment();

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);

        $intentUrl = $response['data']['intent_url'];
        $mccFromIntentUrl = substr($intentUrl, strpos($intentUrl,'&mc=') + 4, 4);

        $this->assertEquals('5411', $mccFromIntentUrl);

        $this->checkPaymentStatus($paymentId, 'created');

        // $this->assertTrue($asserted, '$asserted is false. Control did not reach MockRequest');
        $upiEntity = $this->getDbLastEntity(Entity::UPI);

        $this->assertSame(Payment\Status::CREATED, $payment->getStatus());

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('SUCCESS', $response['status']);

        // The payment should now be authorized
        $payment->refresh();
        $upiEntity->refresh();

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment->getStatus());
    }

    /**
     * Tests unexpected payment creation
     */
    public function testUnexpectedPaymentCreation()
    {
        $content = $this->getDefaultUpiUnexpectedPaymentArray();

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $upi = $this->getDbLastUpi();

        $gatewayData = $upi->getGatewayData();

        $this->assertNotEmpty($gatewayData);

        $this->assertEquals($gatewayData['addInfo2'],$content['upi']['gateway_data']['addInfo2']);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);

        $payment = $this->getLastEntity('payment', true);

    }

    /**
     * Tests unexpected payment creation t+1 refund
     */
    public function testUnexpectedPaymentAutoRefundCheck()
    {
        $content = $this->getDefaultUpiUnexpectedPaymentArray();

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $upi = $this->getDbLastUpi();

        $gatewayData = $upi->getGatewayData();

        $this->assertNotEmpty($gatewayData);

        $this->assertEquals($gatewayData['addInfo2'],$content['upi']['gateway_data']['addInfo2']);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($payment['refund_at']);

        $this->fixtures->payment->edit($payment['id'],
            [
                'refund_at'                => null,
            ]);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment['refund_at']);

        $this->makeRequestAndCatchException(function() use ($content)
        {
             $request = [
                'url' => '/payments/create/upi/unexpected',
                'method' => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);

        },Exception\BadRequestException::class);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($payment['refund_at']);
    }

    /**
     * Tests the duplicate unexpected payment creation
     * for recon edge cases invalid paymentId, rrn mismatch ,Multiple RRN.
     * Amount mismatch case is handled in seperate testcase
     */
    public function testUnexpectedPaymentCreateForAmountMismatch()
    {
        $this->payment[Payment\Entity::VPA] = 'unexpectedPayment@sbi';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastUpi();

        $this->assertSame(Payment\Status::CREATED, $payment->getStatus());

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray());

        $this->makeS2SCallbackAndGetContent($content);

        $content = $this->getDefaultUpiUnexpectedPaymentArray();

        $content['upi']['merchant_reference'] = $upi->getPaymentId();

        $content['upi']['vpa'] = $upi->getVpa();

        //Setting amount to different amount for validating payment creation for amount mismatch
        $content['payment']['amount'] = 10000;
        //First occurence of amount mismatch payment request with matching rrn, paymentId, differing in amount
        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $upi = $this->getDbLastUpi();

        $gatewayData = $upi->getGatewayData();

        $this->assertNotEmpty($gatewayData);

        $this->assertEquals($gatewayData['addInfo2'],$content['upi']['gateway_data']['addInfo2']);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);
    }

    /**s
     * Test unexpected payment request mandatory validation
     */
    public function testUnexpectedPaymentValidationFailure()
    {
        $content = $this->getDefaultUpiUnexpectedPaymentArray();

        // Unsetting the npci_reference_id to mimic validation failure
        unset($content['upi']['npci_reference_id']);
        unset($content['terminal']['gateway_merchant_id']);

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url' => '/payments/create/upi/unexpected',
                'method' => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        },Exception\BadRequestValidationFailureException::class);
    }

    /**
     * Tests the payment create for duplicate unexpected payment
     */
    public function testDuplicateUnexpectedPayment()
    {
        $this->payment[Payment\Entity::VPA] = 'unexpectedPayment@sbi';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastUpi();

        $this->assertSame(Payment\Status::CREATED, $payment->getStatus());

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray());

        $this->makeS2SCallbackAndGetContent($content);

        $content = $this->getDefaultUpiUnexpectedPaymentArray();

        $content['upi']['merchant_reference'] = $upi->getPaymentId();

        $content['upi']['vpa'] = $upi->getVpa();

        // Hit payment create again
        $this->makeRequestAndCatchException(function() use ($content) {
            $request = [
                'url'     => '/payments/create/upi/unexpected',
                'method'  => 'POST',
                'content' => $content,
            ];
            $this->ba->appAuth();
            $this->makeRequestAndGetContent($request);

        }, Exception\BadRequestException::class,
           'Duplicate Unexpected payment with same amount');
    }

    /**
     * Tests the payment create for duplicate unexpected payment
     * for amount mismatch cases
     */
    public function testDuplicateUnexpectedPaymentForAmountMismatch()
    {
        $content = $this->getDefaultUpiUnexpectedPaymentArray();

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);

        //Setting amount to different amount for validating payment creation for amount mismatch
        $content['payment']['amount'] = 10000;
        $content['upi']['vpa'] = 'unexpectedPayment@sbi';
        //First occurence of amount mismatch payment request with matching rrn, paymentId, differing in amount
        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);

        // Hitting the payment create again for same amount mismatch request
        $this->makeRequestAndCatchException(function() use ($content) {
            $request = [
                'url'     => '/payments/create/upi/unexpected',
                'method'  => 'POST',
                'content' => $content,
            ];
            $this->ba->appAuth();
            $this->makeRequestAndGetContent($request);

        }, Exception\BadRequestException::class,
           'Multiple payments with same RRN');
    }

    /**
     * Tests the payment create for multiple payments with same RRN
     */
    public function testUnexpectedPaymentForDuplicateRRN()
    {
        $content = $this->getDefaultUpiUnexpectedPaymentArray();

        //First occurence of amount mismatch payment request with matching rrn, paymentId, differing in amount
        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);

        $this->payment[Payment\Entity::VPA] = 'unexpectedPayment@sbi';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastUpi();

        $this->assertSame(Payment\Status::CREATED, $payment->getStatus());

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upi->toArray());

        $this->makeS2SCallbackAndGetContent($callbackContent,'upi_sbi');

        // Hitting the payment create again for same amount mismatch request
        $this->makeRequestAndCatchException(function() use ($content) {
            $request = [
                'url'     => '/payments/create/upi/unexpected',
                'method'  => 'POST',
                'content' => $content,
            ];
            $this->ba->appAuth();
            $this->makeRequestAndGetContent($request);

        }, Exception\BadRequestException::class,
            'Multiple payments with same RRN');
    }

    /**
     * Authorize the failed payment by force authorizing it
     */
    public function testAuthorizeFailedPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame(Payment\Status::CREATED, $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        // We should have gotten a successful response
        $this->assertArrayHasKey('status', $callbackResponse);
        $this->assertEquals('SUCCESS', $callbackResponse['status']);

        $this->fixtures->payment->edit($payment['id'],
            [
                'status'              => 'failed',
                'authorized_At'       => null,
                'error_code'          => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'   => 'Payment was not completed on time.',
            ]);

        $this->fixtures->edit('upi', $upiEntity['id'], [Upi::STATUS_CODE => '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = true;

        $response = $this->makeAuthorizeFailedPaymentAndGetPayment($content);

        $this->assertNotEmpty($response['payment_id']);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertEquals('123456789013', $upiEntity['npci_reference_id']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertNotNull($updatedPayment['reference16']);

        $this->assertEquals('123456789013', $updatedPayment['reference16']);

        $this->assertEquals('razor.pay@sbi', $updatedPayment['vpa']);

        $this->assertNotEmpty($updatedPayment['transaction_id']);

        $this->assertEquals(true, $response['success']);

        // explicitly capturing the payment to stimulate the auto capture in case DS merchants
        $this->capturePayment('pay_'.$updatedPayment['id'], 50000);

        $this->assertEquals(true, $response['success']);
    }

    /**
     * Validate negative case of authorizing succesfulpayment
     */
    public function testForceAuthorizeSucessfulPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame(Payment\Status::CREATED, $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        // We should have gotten a successful response
        $this->assertArrayHasKey('status', $callbackResponse);

        $this->assertEquals('SUCCESS', $callbackResponse['status']);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('authorized', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = true;

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
           'Non failed payment given for authorization');
    }

    /**
     * Successful farce auth of failed payment with input only containing upi, meta and payment fiedls, not netbanking.
     */
    public function testAuthorizeFailedPaymentWithOnlyUpiInput()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame(Payment\Status::CREATED, $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        // We should have gotten a successful response
        $this->assertArrayHasKey('status', $callbackResponse);
        $this->assertEquals('SUCCESS', $callbackResponse['status']);

        $this->fixtures->payment->edit($payment['id'],
            [
                'status'              => 'failed',
                'authorized_At'       => null,
                'error_code'          => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'   => 'Payment was not completed on time.',
            ]);

        $this->fixtures->edit('upi', $upiEntity['id'], [Upi::STATUS_CODE => '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        // remove netbanking block

        unset($content['netbanking']);

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = true;

        $response = $this->makeAuthorizeFailedPaymentAndGetPayment($content);

        $this->assertNotEmpty($response['payment_id']);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertEquals('123456789013', $upiEntity['npci_reference_id']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertNotNull($updatedPayment['reference16']);

        $this->assertEquals('123456789013', $updatedPayment['reference16']);

        $this->assertEquals('razor.pay@sbi', $updatedPayment['vpa']);

        $this->assertNotEmpty($updatedPayment['transaction_id']);

        $this->assertEquals(true, $response['success']);

        // explicitly capturing the payment to stimulate the auto capture in case DS merchants
        $this->capturePayment('pay_'.$updatedPayment['id'], 50000);

        $this->assertEquals(true, $response['success']);
    }

    /**
     * Checks for validation failure in case of missing payment_id
     */
    public function testForceAuthorizePaymentValidationFailure()
    {
        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
           'The payment.id field is required.');
    }

    /**
     * Checks for validation failure in case of invalid gateway
     */
    public function testForceAuthorizePaymentValidationFailure2()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame(Payment\Status::CREATED, $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        // We should have gotten a successful response
        $this->assertArrayHasKey('status', $callbackResponse);
        $this->assertEquals('SUCCESS', $callbackResponse['status']);

         $this->fixtures->payment->edit($payment['id'],
            [
                'status'              => 'failed',
                'authorized_At'       => null,
                'error_code'          => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'   => 'Payment was not completed on time.',
            ]);

        $this->fixtures->edit('upi', $upiEntity['id'], [Upi::STATUS_CODE => '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = true;

        // Setting the gateway with invalid value to mimic validation failure
        $content['upi']['gateway'] = 'upi_xyz';

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
           'The selected upi.gateway is invalid.');
    }


    //Tests for force authorize with mismatched amount in request.
    public function testForceAuthorizePaymentAmountMismatch()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame(Payment\Status::CREATED, $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        // We should have gotten a successful response
        $this->assertArrayHasKey('status', $callbackResponse);
        $this->assertEquals('SUCCESS', $callbackResponse['status']);

        $this->fixtures->payment->edit($payment['id'],
            [
               'status'              => 'failed',
               'authorized_At'       =>  null,
               'error_code'          => 'BAD_REQUEST_ERROR',
               'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
               'error_description'   => 'Payment was not completed on time.',
            ]);

        $this->fixtures->edit('upi', $upiEntity['id'], [Upi::STATUS_CODE => '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = true;

        // Change amount to 60000 for mismatch scenario
        $content['payment']['amount'] = 60000;

        $this->makeRequestAndCatchException(function() use ($content)
         {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
         }, Exception\BadRequestValidationFailureException::class,
           'The amount does not match with payment amount');
    }

    /**
     * Authorize the failed payment by verifying at gateway
     */
    public function testVerifyAuthorizeFailedPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame(Payment\Status::CREATED, $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        // We should have gotten a successful response
        $this->assertArrayHasKey('status', $callbackResponse);
        $this->assertEquals('SUCCESS', $callbackResponse['status']);

        $this->fixtures->payment->edit($payment['id'],
            [
                'status'              => 'failed',
                'authorized_At'       => null,
                'error_code'          => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'   => 'Payment was not completed on time.',
            ]);

        $this->fixtures->edit('upi', $upiEntity['id'], [Upi::STATUS_CODE => '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = false;

        $response = $this->makeAuthorizeFailedPaymentAndGetPayment($content);

        $this->assertNotEmpty($response['payment_id']);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('123456789012', $upiEntity['npci_reference_id']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertNotNull($updatedPayment['reference16']);

        // asset the late authorized flag for authorizing via verify
        $this->assertTrue($updatedPayment['late_authorized']);

        $this->assertEquals(true, $response['success']);

        // explicitly capturing the payment to stimulate the auto capture in case DS merchants
        $this->capturePayment('pay_'.$updatedPayment['id'], 50000);

        $this->assertEquals(true, $response['success']);

        $this->assertNotEmpty($updatedPayment['transaction_id']);
    }

    protected function makeDataCorrectionRequest($count, $filter = [])
    {
        $request = [
            'url'       => '/gateway/upi/cron/sbi_rrn_correction',
            'method'    => 'POST',
            'content'   => [
                'count'     => $count,
                'match'     => '11%',
                'filter'    => $filter,
            ],
        ];

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertCount($count, $response['ids']);
    }

    protected function createCapturedWithVpaPayment(string $vpa)
    {
        $this->payment[Payment\Entity::VPA] = $vpa;

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response[Constants::PAYMENT_ID];

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity);

        $this->makeS2SCallbackAndGetContent($content);

        $upiEntity = $this->getLastEntity(Entity::UPI, true);
        $payment = $this->getEntityById(Entity::PAYMENT, $paymentId, true);

        $this->assertNotNull($upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertNotNull($upiEntity[Upi::GATEWAY_PAYMENT_ID]);
        $this->assertEquals(SbiStatus::SUCCESS, $upiEntity[Upi::STATUS_CODE]);
        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        // Add a capture as well, just for completeness sake
        $payment = $this->capturePayment($paymentId, $payment[Payment\Entity::AMOUNT]);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);

        return $payment;
    }

    protected function mockVerifyFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content[ResponseFields::API_RESPONSE][ResponseFields::STATUS] = SbiStatus::FAILED;
                    $content[ResponseFields::ADDITIONAL_INFO] = [];
                }
            }
        );
    }

    protected function mockVerifyBlock()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content[ResponseFields::API_RESPONSE][ResponseFields::STATUS] = SbiStatus::REJECTED;
                    $content[ResponseFields::ADDITIONAL_INFO] = [];
                }
            }
        );
    }

    protected function generateRefundsExcelForSbiUpi($date = false)
    {
        $this->ba->adminAuth();

        $request = [
            'url' => '/gateway/files',
            'method' => 'POST',
            'content' => [
                'type'    => 'refund',
                'targets' => ['upi_sbi'],
                'begin'    => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'      => Carbon::tomorrow(Timezone::IST)->getTimestamp()
            ],
        ];

        if ($date)
        {
            $request['content']['on'] = Carbon::now()->format('Y-m-d');
        }

        return $this->makeRequestAndGetContent($request);
    }

    protected function checkPaymentStatus(string $id, string $status)
    {
        $response = $this->getPaymentStatus($id);

        $this->assertEquals($status, $response[Payment\Entity::STATUS]);
    }

    protected function getS2SUpiIdMismatchContent(array $upiEntity)
    {
        $mockServer = $this->mockServer();

        $content = $mockServer->getAsyncCallbackContent($upiEntity);

        $decryptedResp = $this->getDecryptedContent($content[ResponseFields::MESSAGE]);

        $decryptedResp[ResponseFields::API_RESPONSE][ResponseFields::CUSTOMER_REFERENCE_NO] = 'Random';

        $encryptedResp = [
            ResponseFields::RESPONSE       => $mockServer->encrypt($decryptedResp),
            ResponseFields::PG_MERCHANT_ID => $mockServer->getGatewayInstance()->getMerchantId(),
        ];

        $response = \Response::make($encryptedResp);

        $response->headers->set('Content-Type', 'application/text; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return [
            ResponseFields::MESSAGE => $response->content(),
            //We don't get this in call back actually, but for test as we dont actually hit mozart
            //hence wont be able to decrypt the response sent from here.
            'payment_id' => $upiEntity[\RZP\Gateway\Upi\Base\Entity::PAYMENT_ID],
            'vpa' => $upiEntity['vpa'],
            'upiTransRefNo' => 'Random',
            'custRefNo'     => 'Random'
        ];
    }

    protected function getS2SAmountMismatchContent(array $upiEntity)
    {
        $mockServer = $this->mockServer();

        $content = $mockServer->getAsyncCallbackContent($upiEntity);

        $decryptedResp = $this->getDecryptedContent($content[ResponseFields::MESSAGE]);

        $decryptedResp[ResponseFields::API_RESPONSE][ResponseFields::AMOUNT] = 1;

        $encryptedResp = [
            ResponseFields::RESPONSE       => $mockServer->encrypt($decryptedResp),
            ResponseFields::PG_MERCHANT_ID => $mockServer->getGatewayInstance()->getMerchantId(),
        ];

        $response = \Response::make($encryptedResp);

        $response->headers->set('Content-Type', 'application/text; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return [
            ResponseFields::MESSAGE => $response->content(),
            //We don't get this in call back actually, but for test as we dont actually hit mozart
            //hence wont be able to decrypt the response sent from here.
            'payment_id' => $upiEntity[\RZP\Gateway\Upi\Base\Entity::PAYMENT_ID],
            'vpa' => $upiEntity['vpa'],
            'amount' => 1,
        ];
    }

    protected function mockFailedVpaValidationVerify()
    {
        $this->mockServerContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $apiResponse = $content[ResponseFields::API_RESPONSE];

                    $content = [
                        ResponseFields::API_RESPONSE => [
                            ResponseFields::ADDITIONAL_INFO        => [],
                            ResponseFields::PSP_REFERENCE_NO       => $apiResponse[ResponseFields::PSP_REFERENCE_NO],
                            ResponseFields::STATUS                 => SbiStatus::VALIDATION_ERROR,
                            ResponseFields::STATUS_DESCRIPTION     => 'No Transaction record found',
                            ResponseFields::UPI_TRANS_REFERENCE_NO => 0,
                            ResponseFields::TRANSACTION_AUTH_DATE  => $apiResponse[ResponseFields::TRANSACTION_AUTH_DATE]
                        ],
                    ];
                }
            });
    }

    protected function getDecryptedContent(string $json)
    {
        return $this->mockServer()->decrypt(json_decode($json, true)['resp']);
    }

    protected function makeUnexpectedPaymentAndGetContent(array $content)
    {
        $request = [
            'url' => '/payments/create/upi/unexpected',
            'method' => 'POST',
            'content' => $content,
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function makeAuthorizeFailedPaymentAndGetPayment(array $content)
    {
        $request = [
            'url'      => '/payments/authorize/upi/failed',
            'method'   => 'POST',
            'content'  => $content,
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function mockUpsServerContentFunction($closure)
    {
        $this->upiPaymentService->shouldReceive('content')->andReturnUsing($closure);
    }
}
