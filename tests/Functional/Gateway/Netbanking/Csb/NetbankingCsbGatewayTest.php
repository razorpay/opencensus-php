<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Csb;

use Mail;
use Excel;
use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Excel\Import as ExcelImport;
use RZP\Gateway\Netbanking\Csb\Status;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Gateway\Netbanking\Csb\ResponseFields;
use RZP\Gateway\Netbanking\Base\Entity as Netbanking;
use RZP\Models\Payment\Verify\Status as VerifyStatus;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Mail\Gateway\DailyFile;

class NetbankingCsbGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $payment;

    protected $sharedTerminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingCsbGatewayTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultNetbankingPaymentArray(IFSC::CSBK);

        $this->gateway = Payment\Gateway::NETBANKING_CSB;

        $this->fixtures->create('terminal:shared_netbanking_csb_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);

        $this->markTestSkipped('this flow is depricated and is moved to nbplus service');
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertEquals(Payment\TwoFactorAuth::UNAVAILABLE, $payment[Payment\Entity::TWO_FACTOR_AUTH]);
        $this->assertEquals(
            $netbanking[Netbanking::BANK_PAYMENT_ID],
            $payment[Payment\Entity::ACQUIRER_DATA]['bank_transaction_id']
        );

        $this->assertTestResponse($netbanking);

        return $payment;
    }

    public function testPaymentFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockPaymentFailed();

        $payment = $this->payment;

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, __FUNCTION__ . 'NetbankingEntity');

        return $payment;
    }

    public function testVerifyCallbackFailure()
    {
        $this->mockPaymentVerifyFailed();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->testPayment();
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        // The payment status is updated to failed due to the verify callback error
        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        // The status doesn't get updated from Y to N
        $testData = $this->testData[__FUNCTION__ . 'Entity'];

        $this->assertArraySelectiveEquals($testData, $netbanking);
    }

    public function testPaymentFailedVerifyCallbackFailure()
    {
        $this->mockPaymentFailed();

        $data = $this->testData['testPaymentFailed'];

        $payment = $this->payment;

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                // Payment is a failure, but verify callback is a success
                $this->doAuthAndCapturePayment($payment);
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'testPaymentFailedNetbankingEntity');
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        $this->assertEquals(true, $verify['gateway']['apiSuccess']);
        $this->assertEquals(true, $verify['gateway']['gatewaySuccess']);

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);
        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        // Status remains in Success after verify
        $this->assertEquals(Status::SUCCESS, $netbanking[Netbanking::STATUS]);

        $this->assertEquals($verify[ConstantsEntity::PAYMENT][Payment\Entity::ID], $payment[Payment\Entity::ID]);
        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);
        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);
    }

    public function testPaymentFailedVerifyFailed()
    {
        $payment = $this->testPaymentFailed();

        $this->mockPaymentVerifyFailed();

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        $this->assertEquals(false, $verify['gateway']['apiSuccess']);
        $this->assertEquals(false, $verify['gateway']['gatewaySuccess']);

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);
        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        // Status remains in failed after verify
        $this->assertEquals(Status::FAILURE, $netbanking[Netbanking::STATUS]);

        $this->assertEquals($verify[ConstantsEntity::PAYMENT][Payment\Entity::ID], $payment[Payment\Entity::ID]);
        $this->assertEquals(VerifyStatus::SUCCESS, $payment[Payment\Entity::VERIFIED]);
        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
    }

    public function testPaymentSuccessVerifyFailed()
    {
        $payment = $this->testPayment();

        $data = $this->testData['testVerifyMismatch'];

        $this->mockPaymentVerifyFailed();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);
        $this->assertEquals(VerifyStatus::FAILED, $payment[Payment\Entity::VERIFIED]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        // The status doesn't get updated from Y to N
        $testData = $this->testData['testPayment'];

        $this->assertArraySelectiveEquals($testData, $netbanking);
    }

    public function testRefundFileGeneration()
    {
        Mail::fake();

        $refunds = $this->createRefundForFileGeneration();

        $this->setFetchFileBasedRefundsFromScroogeMockResponse($refunds);

        $this->assertEquals(1, $refunds[0]['is_scrooge']);
        $this->assertEquals(1, $refunds[1]['is_scrooge']);
        $this->assertEquals(1, $refunds[2]['is_scrooge']);

        // gateway file generation route is an internal auth
        $this->ba->adminAuth();

        $data = $this->generateGatewayFile('csb', 'combined');

        $file = $this->getLastEntity(ConstantsEntity::FILE_STORE, true);

        $this->checkRefundExcelData($data['items'][0], $file);

        $this->checkMailQueue($file);
    }

    protected function checkRefundExcelData(array $data, array $file)
    {
        $this->assertNotNull($data[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($data[File\Entity::SENT_AT]);
        $this->assertNull($data[File\Entity::FAILED_AT]);
        $this->assertNull($data[File\Entity::ACKNOWLEDGED_AT]);

        $filePath = storage_path('files/filestore') . '/' . $file['location'];

        $this->assertTrue(file_exists($filePath));

        $refundsFileContents = (new ExcelImport)->toArray($filePath)[0];

        $refundAmounts = [500, 500, 100];

        array_map(
            function($amount, $index) use ($refundsFileContents)
            {
                // We increment $ind in the local scope so that srno = $ind = 1
                $refund = $refundsFileContents[$index];

                $this->assertEquals(++$index, $refund['srno']);
                $this->assertEquals(500, $refund['txn_amountrs_ps']);
                $this->assertEquals($amount, $refund['refund']);
            },
            $refundAmounts,
            array_keys($refundAmounts)
        );

        $this->assertEquals(3, count($refundsFileContents));

        unlink($filePath);
    }

    protected function checkMailQueue(array $file)
    {
        Mail::assertSent(DailyFile::class, function ($mail) use ($file)
        {
            $this->assertEquals(1500, $mail->viewData['amount']['claims']);
            $this->assertEquals(1100, $mail->viewData['amount']['refunds']);
            $this->assertEquals(400, $mail->viewData['amount']['total']);

            $this->assertEquals('3', $mail->viewData['count']['claims']);
            $this->assertEquals('3', $mail->viewData['count']['refunds']);
            $this->assertEquals('6', $mail->viewData['count']['total']);

            return true;
        });
    }

    protected function createRefundForFileGeneration()
    {
        return array_map(
            function($amount)
            {
                $refund = $this->doAuthCaptureAndRefundPayment($this->payment, $amount);

                $payment = $this->getDbLastEntity('payment');

                $createdAt = Carbon::yesterday(Timezone::IST)
                                ->addHours(10)
                                ->addMinutes(45)
                                ->getTimestamp();

                $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
                $this->fixtures->edit('payment', $payment['id'], ['authorized_at' => $createdAt]);

                return $this->getDbLastRefund();
            },
            [50000, 50000, 10000]
        );
    }

    protected function mockPaymentFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'authorize')
                {
                    $content[ResponseFields::STATUS] = Status::FAILURE;
                    $content[ResponseFields::NARRATION] = 'Payment failed';
                    $content[ResponseFields::TRAN_REF_NUM] = "0";
                }
                else if ($action === 'verify')
                {
                    $content[ResponseFields::STATUS] = Status::FAILURE;
                }
            });
    }

    protected function mockPaymentVerifyFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content[ResponseFields::STATUS] = Status::FAILURE;
                }
            });
    }

   public function testTpvPayment()
   {
       $terminal = $this->fixtures->create('terminal:csb_tpv_terminal');

       $this->ba->privateAuth();

       $this->fixtures->merchant->enableTPV();

       $data = $this->testData[__FUNCTION__];

       $order = $this->startTest();

       $this->payment['amount'] = $order['amount'];

       $this->payment['order_id'] = $order['id'];

       $this->doAuthPayment($this->payment);

       $payment = $this->getLastEntity('payment', true);

       $this->assertEquals($payment['terminal_id'], $terminal->getId());

       $this->fixtures->merchant->disableTPV();

       $gatewayEntity = $this->getLastEntity('netbanking', true);

       $this->assertArraySelectiveEquals(
           $this->testData['testPayment'], $gatewayEntity);

       $this->assertEquals($gatewayEntity['account_number'],
           $data['request']['content']['account_number']);

       $order = $this->getLastEntity('order', true);

       $this->assertArraySelectiveEquals($data['request']['content'], $order);
   }
}
