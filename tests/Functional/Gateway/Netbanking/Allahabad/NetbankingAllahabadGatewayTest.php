<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Allahabad;

use Mail;
use Excel;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile;
use RZP\Tests\Functional\TestCase;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Gateway\Netbanking\Allahabad\Status;
use RZP\Gateway\Netbanking\Allahabad\ResponseFields;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;


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
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentNetbankingEntity');
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentNetbankingEntity');
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

        $this->assertTestResponse($gatewayPayment,'testTamperedPaymentNetbankingEntity');
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
            });

        $gatewayPayment = $this->getLastEntity('netbanking',true);

        $this->assertTestResponse($gatewayPayment,'testTamperedPaymentNetbankingEntity');
    }

    public function testAuthFailedVerifySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testAuthorizeFailed();

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });
    }

    public function testPaymentFailedVerifyFailed()
    {
        $this->testAuthorizeFailed();

        $payment = $this->getLastEntity('payment', true);

        $this->mockPaymentVerifyFailed();

        $this->verifyPayment($payment['id']);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testAuthFailedVerifyFailedEntity');
    }

    public function testUserCancelledPayments()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockCancelledPaymentResponse();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testUserCancelledNetbankingEntity');
    }

    public function testRefundFileGeneration()
    {
        Mail::fake();

        $this->createRefundForFileGeneration();

        $this->ba->appAuth();

        $data = $this->generateGatewayFile('allahabad', 'combined');

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

    protected function mockCancelledPaymentResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if($action === 'authorize')
            {
                $content['PAID'] = 'C';
                $content['CRN'] = 'INR';
                unset($content['BID']);
                $content['PID'] = 'Razor';
            }
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

        $refundFileContent = file($filePath);

        $refundAmounts = ['500.00', '500.00', '100.00'];

        foreach($refundFileContent as $row)
        {
            $refundsFileRow = explode('|', $row);

            assert(count($refundsFileRow) === 10);

            $rowRefundAmount = trim($refundsFileRow[9]);

            assert(in_array($rowRefundAmount, $refundAmounts, true));
        }

        $this->assertEquals(3, count($refundFileContent));

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
}
