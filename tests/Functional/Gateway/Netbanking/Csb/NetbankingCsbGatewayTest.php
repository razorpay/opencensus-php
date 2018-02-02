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
use RZP\Gateway\Netbanking\Csb\Status;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Gateway\Netbanking\Csb\ResponseFields;
use RZP\Gateway\Netbanking\Base\Entity as Netbanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class NetbankingCsbGatewayTest extends TestCase
{
    private $payment;

    private $sharedTerminal;

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
        $this->assertEquals($netbanking[Netbanking::BANK_PAYMENT_ID], $payment[Payment\Entity::ACQUIRER_DATA]['bank_transaction_id']);

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

    public function testPaymentFailedVerify()
    {
        $payment = $this->testPaymentFailed();

        $data = $this->testData['testVerifyMismatch'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $testData = $this->testData['testPaymentFailedNetbankingEntity'];

        // The status changes from 'N' to 'Y' after verification
        $testData['status'] = Status::SUCCESS;

        $this->assertArraySelectiveEquals($testData, $netbanking);
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
        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);
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

        $data = $this->generateRefundsGatewayFile(IFSC::CSBK);

        $file = $this->getLastEntity(ConstantsEntity::FILE_STORE, true);

        $this->checkRefundTextData($data['items'][0], $file);

        $this->checkMailQueue($file);
    }

    private function checkRefundTextData(array $data, array $file)
    {
        $this->assertNotNull($data[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($data[File\Entity::SENT_AT]);
        $this->assertNull($data[File\Entity::FAILED_AT]);
        $this->assertNull($data[File\Entity::ACKNOWLEDGED_AT]);

        $filePath = storage_path('files/filestore') . '/' . $file['location'];

        $this->assertTrue(file_exists($filePath));

        $refundsFileContents = Excel::load($filePath)->all()->toArray();

        $refundAmounts = [500, 500, 100];

        foreach ($refundAmounts as $ind => $amount)
        {
            // We increment $ind in the local scope so that srno = $ind = 1
            $refund = $refundsFileContents[$ind++];

            $this->assertEquals($ind, $refund['srno']);
            $this->assertEquals(500, $refund['txn_amountrs_ps']);
            $this->assertEquals($amount, $refund['refund']);
        }

        $this->assertEquals(3, count($refundsFileContents));

        unlink($filePath);
    }

    private function checkMailQueue(array $file)
    {
        Mail::assertSent(RefundFileMail::class, function ($mail) use ($file)
        {
            $body = 'Please forward the CSB Netbanking refunds file to UBPS operations team';

            $this->assertEquals($body, $mail->viewData['body']);

            $this->assertEquals('1100.00', $mail->viewData['amount']);

            $this->assertEquals('3', $mail->viewData['count']);

            $this->assertEquals('emails.message', $mail->view);

            return true;
        });
    }

    private function createRefundForFileGeneration()
    {
        $refunds = [];

        $refunds[] = $this->doAuthCaptureAndRefundPayment($this->payment);
        $refunds[] = $this->doAuthCaptureAndRefundPayment($this->payment);

        // One partial refund of 100 rupees
        $refunds[] = $this->doAuthCaptureAndRefundPayment($this->payment, 10000);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)
                                                         ->addMinutes(45)
                                                         ->getTimestamp();

        foreach($refunds as $refund)
        {
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }

        return $refunds;
    }

    private function mockPaymentFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $content[ResponseFields::STATUS] = Status::FAILURE;
                $content[ResponseFields::NARRATION] = 'Payment failed';
            });
    }

    private function mockPaymentVerifyFailed()
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
