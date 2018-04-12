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
use RZP\Gateway\Netbanking\Csb\Mode;
use RZP\Gateway\Netbanking\Csb\Status;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Gateway\Netbanking\Csb\ResponseFields;
use RZP\Gateway\Netbanking\Base\Entity as Netbanking;
use RZP\Models\Payment\Verify\Status as VerifyStatus;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class NetbankingCsbGatewayTest extends TestCase
{
    protected $payment;

    protected $sharedTerminal;

    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingCsbGatewayTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultNetbankingPaymentArray(IFSC::CSBK);

        $this->gateway = Payment\Gateway::NETBANKING_CSB;

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_netbanking_csb_terminal');
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

    public function testPaymentFailedVerifyCallbackSuccess()
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

        // Since BID is not null, we send V as the Mode for verify
        $this->assertEquals(Mode::VERIFY, $verify['gateway']['verifyRequest'][7]);

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

        $this->createRefundForFileGeneration();

        // gateway file generation route is an internal auth
        $this->ba->appAuth();

        $data = $this->generateRefundsGatewayFile('csbk');

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

        $refundsFileContents = Excel::load($filePath)->all()->toArray();

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
        Mail::assertQueued(RefundFileMail::class, function ($mail) use ($file)
        {
            $body = 'Please forward the CSB Netbanking refunds file to UBPS operations team';

            $this->assertEquals($body, $mail->viewData['body']);

            $this->assertEquals('1100.00', $mail->viewData['amount']);

            $this->assertEquals('3', $mail->viewData['count']);

            $this->assertEquals('emails.message', $mail->view);

            return true;
        });
    }

    protected function createRefundForFileGeneration()
    {
        return array_map(
            function($amount)
            {
                $refund = $this->doAuthCaptureAndRefundPayment($this->payment, $amount);

                $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)
                                                                ->addMinutes(45)
                                                                ->getTimestamp();

                $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
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
                }
                else if ($action === 'verify')
                {
                    $content[ResponseFields::VERIFICATION] = Status::FAILURE;
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
                    $content[ResponseFields::VERIFICATION] = Status::FAILURE;
                }
            });
    }
}
