<?php

namespace RZP\Tests\Functional\Gateway\Upi\Sbi;

use Excel;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Refund;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Upi\Sbi\RefundFile;
use RZP\Models\Base\PublicCollection;
use RZP\Gateway\Upi\Sbi\ResponseFields;
use RZP\Gateway\Upi\Base\Entity as Upi;
use RZP\Gateway\Upi\Sbi\Status as SbiStatus;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiSbiGatewayTest extends TestCase
{
    use PaymentTrait;

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

        $this->checkPaymentStatus($paymentId, Status::CREATED);

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $payment = $this->getEntityById(Entity::PAYMENT, $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals([Constants::SUCCESS => true], $response);

        // The payment should now be authorized
        $payment = $this->getEntityById(Entity::PAYMENT, $paymentId, true);
        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $content = $this->getDecryptedContent($content[ResponseFields::MESSAGE], ResponseFields::RESPONSE);

        $this->assertEquals($content[ResponseFields::UPI_TRANS_REFERENCE_NO], $upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertEquals($content[ResponseFields::CUSTOMER_REFERENCE_NO], $upiEntity[Upi::GATEWAY_PAYMENT_ID]);
        $this->assertEquals($content[ResponseFields::STATUS], $upiEntity[Upi::STATUS_CODE]);
        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);
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

        $this->assertEquals(SbiStatus::FAILED, $upiEntity[Upi::STATUS_CODE]);
        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_FAILED, $payment[Payment\Entity::INTERNAL_ERROR_CODE]);

        return $payment;
    }

    public function testFailedVpaValidation()
    {
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

        $payment = $this->getEntityById(Entity::PAYMENT, $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $data = $this->testData['testRejectedCollect'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($content)
            {
                $this->makeS2SCallbackAndGetContent($content);
            });

        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $this->assertNotNull($upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertNotNull($upiEntity[Upi::GATEWAY_PAYMENT_ID]);

        $this->assertEquals(SbiStatus::REJECTED, $upiEntity[Upi::STATUS_CODE]);
        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED, $payment[Payment\Entity::INTERNAL_ERROR_CODE]);
    }

    /**
     * This verifies the transaction status after a payment has been successfully made.
     */
    public function testPaymentVerify()
    {
        $this->createCapturedPayment();

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        $this->assertEquals(true, $verify[Constants::GATEWAY][Constants::API_SUCCESS]);
        $this->assertEquals(true, $verify[Constants::GATEWAY][Constants::GATEWAY_SUCCESS]);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        // Status remains in Success after verify
        $this->assertEquals(SbiStatus::SUCCESS, $upiEntity[Upi::STATUS_CODE]);

        $this->assertEquals($verify[Entity::PAYMENT][Payment\Entity::ID], $payment[Payment\Entity::ID]);
        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);
    }

    public function testPaymentSuccessVerifyFailed()
    {
        $this->createCapturedPayment();

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

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

        // Status changed from S to F
        $this->assertEquals(SbiStatus::FAILED, $upiEntity[Upi::STATUS_CODE]);

        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);
    }

    public function testAmountAssertionFailure()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response[Constants::PAYMENT_ID];

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $payment = $this->getEntityById(Entity::PAYMENT, $paymentId, true);

        $content = $this->getS2SAmountMismatchContent($upiEntity, $payment);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($content)
            {
                $this->makeS2SCallbackAndGetContent($content);
            });

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        // Status will be S, because amount assertion failure doesn't rely on status
        $this->assertNotNull($upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertNotNull($upiEntity[Upi::GATEWAY_PAYMENT_ID]);
        $this->assertEquals(SbiStatus::SUCCESS, $upiEntity[Upi::STATUS_CODE]);
        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);
    }

    public function testRefundFileFlow()
    {
        // Create 3 payments
        $this->createCapturedPayment();
        $this->createCapturedPayment();
        $this->createCapturedPayment();

        // Refund 2 fully and the other one partially
        $payments = $this->getEntities(Entity::PAYMENT, [], true);

        $refundAmount = [50000, 50000, 10000];

        foreach ($payments[PublicCollection::ITEMS] as $count => $payment)
        {
            $this->refundPayment($payment[Payment\Entity::ID], $refundAmount[$count]);
        }

        $refunds = $this->getEntities(Entity::REFUND, [], true);

        foreach ($refunds[PublicCollection::ITEMS] as $refund)
        {
            $createdAt = Carbon::yesterday(Timezone::IST)->timestamp + 5;
            $this->fixtures->edit(Entity::REFUND, $refund[Refund\Entity::ID], [Refund\Entity::CREATED_AT => $createdAt]);
        }

        // Refund a 4th payment
        $this->createCapturedPayment();
        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $this->refundPayment($payment[Payment\Entity::ID]);

        $data = $this->generateRefundsExcelForSbiUpi();

        $this->assertArrayHasKey(Payment\Gateway::UPI_SBI, $data);

        $this->assertEquals(3, $data[Payment\Gateway::UPI_SBI][Constants::COUNT]);
        $this->assertTrue(file_exists($data[Payment\Gateway::UPI_SBI][Constants::FILE]));

        $sheet = Excel::load($data[Payment\Gateway::UPI_SBI][Constants::FILE])->all()->toArray();

        $key = str_replace(' ', '_', strtolower(RefundFile::REFUND_REQ_AMT));

        $count = [
            500 => 0,
            100 => 0,
        ];

        foreach ($sheet as $refund)
        {
            $refundAmount = $refund[$key];

            $count[$refundAmount]++;
        }

        // We assert that there are 2 refunds of 500 rupees, and 1 of 100
        $this->assertEquals(2, $count[500]);
        $this->assertEquals(1, $count[100]);

        $file = $this->getLastEntity(ConstantsEntity::FILE_STORE, true);

        // Asserting the properties of the fileStore object that was created and uploaded into the S3 bucket
        $this->assertEquals(FileStore\Type::SBI_UPI_REFUND, $file[FileStore\Entity::TYPE]);
        $this->assertEquals(FileStore\Store::S3, $file[FileStore\Entity::STORE]);
        $this->assertEquals(FileStore\Format::CSV, $file[FileStore\Entity::EXTENSION]);
    }

    public function testUpiResponseAssertionFailure()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response[Constants::PAYMENT_ID];

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $payment = $this->getEntityById(Entity::PAYMENT, $paymentId, true);

        $content = $this->getS2SUpiIdMismatchContent($upiEntity, $payment);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($content)
            {
                $this->makeS2SCallbackAndGetContent($content);
            });

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        // Status will be S, because assertion failure doesn't rely on status
        $this->assertNotNull($upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertNotNull($upiEntity[Upi::GATEWAY_PAYMENT_ID]);
        $this->assertEquals(SbiStatus::SUCCESS, $upiEntity[Upi::STATUS_CODE]);
        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);
    }

    protected function createCapturedPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response[Constants::PAYMENT_ID];

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $payment = $this->getEntityById(Entity::PAYMENT, $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $this->makeS2SCallbackAndGetContent($content);

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $this->assertNotNull($upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertNotNull($upiEntity[Upi::GATEWAY_PAYMENT_ID]);
        $this->assertEquals(SbiStatus::SUCCESS, $upiEntity[Upi::STATUS_CODE]);
        $this->assertEquals($payment[Payment\Entity::VPA], $upiEntity[Upi::VPA]);

        // Add a capture as well, just for completeness sake
        $this->capturePayment($paymentId, $payment[Payment\Entity::AMOUNT]);
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
            'url' => '/refunds/excel',
            'method' => 'post',
            'content' => [
                'method'    => Method::UPI,
                'bank'      => Payment\Processor\Upi::SBIN,
                'frequency' => Constants::FREQUENCY_DAILY
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

    protected function getS2SUpiIdMismatchContent(array $upiEntity, array $payment)
    {
        $mockServer = $this->mockServer();

        $content = $mockServer->getAsyncCallbackContent($upiEntity, $payment);

        $decryptedResp = $mockServer->decrypt($content[ResponseFields::MESSAGE], ResponseFields::RESPONSE);

        $decryptedResp[ResponseFields::API_RESPONSE][ResponseFields::UPI_TRANS_REFERENCE_NO] = 'Random';

        $encryptedResp = [ResponseFields::RESPONSE => $mockServer->encrypt($decryptedResp)];

        $response = \Response::make($encryptedResp);

        $response->headers->set('Content-Type', 'application/text; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return [ResponseFields::MESSAGE => $response->content()];
    }

    protected function getS2SAmountMismatchContent(array $upiEntity, array $payment)
    {
        $mockServer = $this->mockServer();

        $content = $mockServer->getAsyncCallbackContent($upiEntity, $payment);

        $decryptedResp = $mockServer->decrypt($content[ResponseFields::MESSAGE], ResponseFields::RESPONSE);

        $decryptedResp[ResponseFields::API_RESPONSE][ResponseFields::AMOUNT] = 1;

        $encryptedResp = [ResponseFields::RESPONSE => $mockServer->encrypt($decryptedResp)];

        $response = \Response::make($encryptedResp);

        $response->headers->set('Content-Type', 'application/text; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return [ResponseFields::MESSAGE => $response->content()];
    }

    protected function getDecryptedContent(string $json, $messageKey, $responseKey = ResponseFields::API_RESPONSE)
    {
        return $this->mockServer()->decrypt($json, $messageKey)[$responseKey];
    }
}
