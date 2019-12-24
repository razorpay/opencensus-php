<?php

namespace RZP\Tests\Functional\Gateway\Upi\Sbi;

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
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
use RZP\Gateway\Base\VerifyResult;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Upi\Sbi\RefundFile;
use RZP\Gateway\Upi\Sbi\RequestFields;
use RZP\Gateway\Upi\Sbi\ResponseFields;
use RZP\Gateway\Upi\Base\Entity as Upi;
use RZP\Gateway\Upi\Sbi\Status as SbiStatus;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

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

    public function setUp()
    {
        $this->testDataFilePath = Constants::MINDGATE_SBI_GATEWAY_TEST_DATA_FILE;

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create(Constants::SHARED_UPI_SBI_MIDGATE_TERMINAL);

        $this->gateway = Gateway::UPI_SBI;

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    /**
     * Tests the flow where the customer is sent the collect request from the razorpay sbi vpa.
     * User then accepts the collect request, and a asynchronous callback is sent to API.
     * We assert the entity is being updated correctly in this flow
     */
    public function testPayment()
    {
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
        $this->assertEquals(Type::COLLECT, $upiEntity[Upi::TYPE]);
        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);
        $this->assertNotNull($upiEntity[Upi::EXPIRY_TIME]);
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
    public function testFailedCollect()
    {
        $this->payment[Payment\Entity::VPA] = Constants::FAILED_VPA;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthPaymentViaAjaxRoute($this->payment);
            });

        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $this->assertNotNull($upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertNotNull($upiEntity[Upi::GATEWAY_PAYMENT_ID]);
        $this->assertEquals(Type::COLLECT, $upiEntity[Upi::TYPE]);

        $this->assertEquals(SbiStatus::FAILED, $upiEntity[Upi::STATUS_CODE]);
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
        Gateway::$upiValidateVpaTerminals['test'] = ['100UPIMgateSbi'];

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testValidateVpaFailure()
    {
        Gateway::$upiValidateVpaTerminals['test'] = ['100UPIMgateSbi'];

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testValidateAccountVpa()
    {
        Gateway::$upiValidateVpaTerminals['test'] = ['100UPIMgateSbi'];

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testValidateAccountVpaFailed()
    {
        Gateway::$upiValidateVpaTerminals['test'] = ['100UPIMgateSbi'];

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testValidateAccountInvalidInput()
    {
        Gateway::$upiValidateVpaTerminals['test'] = ['100UPIMgateSbi'];
        
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testValidateAccountVpaGatewayError()
    {
        Gateway::$upiValidateVpaTerminals['test'] = ['100UPIMgateSbi'];

        $this->ba->publicAuth();

        $this->startTest();
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

        $this->assertEquals(SbiStatus::REJECTED, $upiEntity[Upi::STATUS_CODE]);
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

        $this->assertNotNull($upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertNotNull($upiEntity[Upi::GATEWAY_PAYMENT_ID]);

        $this->assertEquals(SbiStatus::CBS_DOWN, $upiEntity[Upi::STATUS_CODE]);
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
        $payment = $this->createCapturedPayment();

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
        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        // Status changed from S to F
        $this->assertEquals(SbiStatus::FAILED, $upiEntity[Upi::STATUS_CODE]);

        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);
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
        $payments = [];

        // Create 3 payments
        $payments[] = $this->createCapturedPayment();
        $payments[] = $this->createCapturedPayment();
        $payments[] = $this->createCapturedPayment();

        // Refund 2 fully and the other one partially
        $refundAmount = [50000, 50000, 10000];

        $refunds = [];
        $refundEntities = [];

        foreach ($payments as $count => $payment)
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

        $data = $this->generateRefundsExcelForSbiUpi();

        $content = $data['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $time = Carbon::now(Timezone::IST)->format('dmY_Hi');

        $this->assertEquals('file_store', $file['entity']);
        $this->assertEquals('SBI_UPI_' . $time .'.csv', $file['location']);
        $this->assertEquals('SBI_UPI_' . $time, $file['name']);
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

    protected function mockVerifyFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $content[ResponseFields::API_RESPONSE][ResponseFields::STATUS] = SbiStatus::FAILED;
            }
        );
    }

    protected function generateRefundsExcelForSbiUpi($date = false)
    {
        $this->ba->appAuth();

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

        $decryptedResp[ResponseFields::API_RESPONSE][ResponseFields::UPI_TRANS_REFERENCE_NO] = 'Random';

        $encryptedResp = [ResponseFields::RESPONSE => $mockServer->encrypt($decryptedResp)];

        $response = \Response::make($encryptedResp);

        $response->headers->set('Content-Type', 'application/text; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return [ResponseFields::MESSAGE => $response->content()];
    }

    protected function getS2SAmountMismatchContent(array $upiEntity)
    {
        $mockServer = $this->mockServer();

        $content = $mockServer->getAsyncCallbackContent($upiEntity);

        $decryptedResp = $this->getDecryptedContent($content[ResponseFields::MESSAGE]);

        $decryptedResp[ResponseFields::API_RESPONSE][ResponseFields::AMOUNT] = 1;

        $encryptedResp = [ResponseFields::RESPONSE => $mockServer->encrypt($decryptedResp)];

        $response = \Response::make($encryptedResp);

        $response->headers->set('Content-Type', 'application/text; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return [ResponseFields::MESSAGE => $response->content()];
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
}
