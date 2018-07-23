<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Allahabad;

use Mail;
use RZP\Models\Payment;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Gateway\Netbanking\Allahabad\RefundFile;
use RZP\Models\Terminal\Options;
use RZP\Gateway\Netbanking\Allahabad\ResponseFields;
use RZP\Gateway\Netbanking\Allahabad\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class NetbankingAllahabadGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingAllahabadGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_allahabad';

        $this->bank = 'ALLA';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_allahabad_terminal');
    }

    public function testPayment()
    {
        $terminal = $this->getLastEntity('terminal', true);

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

//        $this->assertArraySelectiveEquals(
//            $this->testData['testPaymentNetbankingEntity'], $payment
//        );
        s($payment);
        $this->assertArrayHasKey('bank', $payment);

    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentVerifySuccessEntity');
    }

    public function testAuthorizeFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedCallbackResponse();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });

        // Assert that we don't save any information into the netbanking entity
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentFailedNetbankingEntity');
    }

    public function testAmountTampering()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockAmountTampering();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });

        $gatewayPayment = $this->getLastEntity('netbanking',true);

        $this->assertTestResponse($gatewayPayment,'testPaymentFailedNetbankingEntity');

    }

    public function testFailedChecksum()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedChecksum();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            }
        );

        $gatewayPayment = $this->getLastEntity('netbanking',true);

        $this->assertTestResponse($gatewayPayment,'testPaymentFailedNetbankingEntity');

    }

    public function testPaymentSuccessVerifyFailed()
    {
        $data = $this->testData['testPaymentSuccessVerifyFail'];

        $this->testPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockPaymentVerifyFailed();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testAuthSuccessVerifyFailedNetbankingEntity');

    }


    public function testRefundFileGeneration()
    {
        Mail::fake();

        $this->createRefundForFileGeneration();

        // gateway file generation route is an internal auth
        $this->ba->appAuth();

        $data = $this->generateGatewayFile('alla', 'refund');

        $file = $this->getLastEntity(ConstantsEntity::FILE_STORE, true);

        $this->checkRefundExcelData($data['items'][0], $file);

        $this->checkMailQueue($file);
    }

    protected function mockFailedCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if($action === 'authorize')
            {
                $content['PAID'] = "N";
            }
        });
    }

    protected function mockAmountTampering()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if($action === 'authorize')
            {
                $content['AMT'] = 100;
            }
        });
    }

    protected function mockFailedChecksum()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if($action === 'authorize')
            {
                $content['bank_signature'] = 10000000000000000000000;
            }
        });
    }


    protected function createPaymentsToClaim()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $this->doAuthAndCapturePayment($this->payment);

        $this->doAuthAndCapturePayment($this->payment);

        $payments = $this->getEntities('payment', [], true);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)
            ->addMinutes(30)
            ->timestamp;

        // Ensuring that the created at timestamps are for yesterday
        foreach ($payments['items'] as $payment)
        {
            $this->fixtures->edit('payment', $payment['id'], ['created_at'    => $createdAt,
                'authorized_at' => $createdAt + 10,
                'captured_at'   => $createdAt + 20]);
        }

        return $payments;
    }

    protected function createRefundsForFileGeneration($payments)
    {
        $payment = $payments['items'][0];

        // refund full payment in 2 steps
        $this->refundPayment($payment['id'], 10010);
        $this->refundPayment($payment['id'], 35020);

        $payment = $payments['items'][1];

        // refunding in full
        $this->refundPayment($payment['id']);

        $refunds = $this->getEntities('refund', [], true);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)
            ->addMinutes(45)
            ->timestamp;

        foreach ($refunds['items'] as $refund) {
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }
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
            },
            [50000, 50000, 10000]
        );
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



    protected function mockPaymentVerifyFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content[ResponseFields::PAID] = Status::NO;
                }
            });
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






}