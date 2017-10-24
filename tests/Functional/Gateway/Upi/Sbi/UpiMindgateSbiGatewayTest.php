<?php

namespace RZP\Tests\Functional\Gateway\Upi\Sbi;

use Excel;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Upi\Sbi\RefundFile;
use RZP\Gateway\Upi\Sbi\ResponseFields;
use RZP\Models\Payment;
use RZP\Constants\Entity;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Upi\Base\Entity as Upi;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiMindgateSbiGatewayTest extends TestCase
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

        $this->sharedTerminal = $this->fixtures->create(Constants::SHARED_UPI_MIDGATE_TERMINAL);

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
        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $upiEntity = $this->getLastEntity(Entity::UPI, true);
        $this->assertNotNull($upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertNotNull($upiEntity[Upi::CUSTOMER_REFERENCE_ID]);
        $this->assertNotNull($upiEntity[Upi::GATEWAY_PAYMENT_ID]);

        // Add a capture as well, just for completeness sake
        $this->capturePayment($paymentId, $payment[Payment\Entity::AMOUNT]);
    }

    /**
     * Force the gateway to raise a failure on trying
     * to initiate web collect
     */
    public function testFailedCollect()
    {
        $this->payment['vpa'] = 'failedcollect@sbi';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPaymentViaAjaxRoute($this->payment);
        });
    }

    // TODO: testCollectRejectedFailure test case

    /**
     * This verifies the transaction status after a payment has been successfully made.
     */
    public function testPaymentVerify()
    {
        $this->testPayment();

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        // TODO: Add more assertions

        $this->assertEquals(true, $verify[Constants::GATEWAY][Constants::API_SUCCESS]);
        $this->assertEquals(true, $verify[Constants::GATEWAY][Constants::GATEWAY_SUCCESS]);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals($verify[Entity::PAYMENT][Payment\Entity::ID], $payment[Payment\Entity::ID]);
        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);
    }

    public function testPaymentPendingVerifyFailed()
    {
        $this->testPayment();

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $data = $this->testData['testVerifyFailed'];

        $this->mockVerifyFailed();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });
    }

    public function testPaymentFailedVerifyFailed()
    {
        $this->testFailedCollect();

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->mockVerifyFailed('F');

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        // This should result in api success and gateway success = false
        $this->assertEquals(false, $verify[Constants::GATEWAY][Constants::API_SUCCESS]);
        $this->assertEquals(false, $verify[Constants::GATEWAY][Constants::GATEWAY_SUCCESS]);
    }

    // TODO: File based refund flow - upload file
    public function testRefundFileFlow()
    {
        // Create 3 payments
        $this->testPayment();
        $this->testPayment();
        $this->testPayment();

        // Refund 2 fully and the other one partially
        $payments = $this->getEntities('payment', [], true);

        $refundAmount = [50000, 50000, 10000];

        foreach ($payments['items'] as $count => $payment)
        {
            $this->refundPayment($payment['id'], $refundAmount[$count]);
        }

        $refunds = $this->getEntities('refund', [], true);

        foreach ($refunds['items'] as $refund)
        {
            $createdAt = Carbon::yesterday(Timezone::IST)->timestamp + 5;
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }

        // Refund a 4th payment
        $this->testPayment();
        $payment = $this->getLastEntity('payment', true);
        $this->refundPayment($payment['id']);

        $data = $this->generateRefundsExcelForSbiUpi();

        $this->assertArrayHasKey('upi_sbi', $data);

        $this->assertEquals(3, $data['upi_sbi']['count']);
        $this->assertTrue(file_exists($data['upi_sbi']['file']));

        $sheet = Excel::load($data['upi_sbi']['file'])->all()->toArray();

        $key = strtolower(RefundFile::REFUND_REQ_AMT);

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
    }

    protected function mockVerifyFailed($status = 'P')
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null) use ($status)
            {
                $content[ResponseFields::API_RESPONSE][ResponseFields::STATUS] = $status;
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
                'method'    => 'upi',
                'bank'      => 'sbi',
                'frequency' => 'daily'
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
}