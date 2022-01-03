<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Canara;

use Mail;
use Excel;
use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Gateway\Netbanking\Canara;
use RZP\Tests\Functional\TestCase;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\Payment\Verify\Status as VerifyStatus;
use RZP\Gateway\Netbanking\Base\Entity as Netbanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class NetbankingCanaraGatewayTest extends TestCase
{
    use PaymentTrait;
    use PartnerTrait;

    protected $bank;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingCanaraGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_canara';

        $this->bank = 'CNRB';

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_canara_terminal');

        $this->markTestSkipped('this flow is depricated and is moved to nbplus service');
    }

    public function testPayment()
    {
        $this->doNetbankingCanaraAuthAndCapturePayment();

        $paymententity = $this->getLastEntity('payment', true);

        $this->assertTestResponse($paymententity);

        $this->assertEquals($this->bank, $paymententity['bank']);

        $netbankingentity = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals($this->testData['testPaymentNetbankingEntity'], $netbankingentity);

        $this->assertEquals($this->bank, $netbankingentity['bank']);
    }

    public function testPartnerPayment()
    {
        list($clientId, $submerchantId) = $this->setUpPartnerAuthForPayment();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = $this->bank;

        $this->doPartnerAuthPayment($payment, $clientId, $submerchantId);

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame('authorized', $payment['status']);
    }

    public function testAmountTampering()
    {
        $this->mockamountTampering();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function ()
        {
            $payment = $this->doNetbankingCanaraAuthAndCapturePayment();
        });
    }

    public function testAuthorizeFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedCallbackResponse();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $payment = $this->doNetbankingCanaraAuthAndCapturePayment();
            });

        // Assert that we don't save any information into the netbanking entity
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentFailedNetbankingEntity');

        $this->assertEquals($this->bank, $gatewayPayment['bank']);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doNetbankingCanaraAuthAndCapturePayment();

        $verify = $this->verifyPayment($payment['id']);

        $this->assertEquals($verify['payment']['verified'], 1);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentVerifySuccessEntity');

        $this->assertEquals($this->bank, $gatewayPayment['bank']);
    }

    public function testAuthFailedVerifySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testAuthorizeFailed();

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testAuthFailedVerifyFailed()
    {
        $this->testAuthorizeFailed();

        $payment = $this->getLastEntity('payment', true);

        $this->mockFailedVerifyResponse();

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        $this->assertEquals(false, $verify['gateway']['apiSuccess']);
        $this->assertEquals(false, $verify['gateway']['gatewaySuccess']);

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals($verify[ConstantsEntity::PAYMENT][Payment\Entity::ID], $payment[Payment\Entity::ID]);
        $this->assertEquals(VerifyStatus::SUCCESS, $payment[Payment\Entity::VERIFIED]);
        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);
    }

    public function doNetbankingCanaraAuthAndCapturePayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }
    public function doNetbankingCanaraAuthCaptureAndRefundPayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $refund = $this->doAuthCaptureAndRefundPayment($payment);

        return $refund;
    }

    protected function mockFailedVerifyResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content[Canara\ResponseFields::STATUS][Canara\ResponseFields::RETURN_CODE]   = Canara\Constants::SAMPLE_FAILURE_CODE;
                $content[Canara\ResponseFields::STATUS][Canara\ResponseFields::VERIFY_STATUS] = Canara\Constants::SAMPLE_FAILURE_VERIFY_STATUS;
            }
        });
    }

    protected function mockFailedCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                unset($content[Canara\ResponseFields::BANK_REFERENCE_NUMBER]);
            }
        });
    }

    protected function mockamountTampering()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content[Canara\ResponseFields::AMOUNT] = '30000';
            }
        });
    }

    protected function mockVerifyAmountMismatch()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                s($content[Canara\ResponseFields::VER_AMOUNT]);
                $content[Canara\ResponseFields::VER_AMOUNT] = '300';
            }
        });
    }

    protected function createRefunds()
    {
        $payment = $this->doNetbankingCanaraAuthAndCapturePayment();

        $refund = $this->refundPayment($payment['id']);

        $payment = $this->doNetbankingCanaraAuthAndCapturePayment();

        $refund = $this->refundPayment($payment['id'], 10000);

        $refund = $this->refundPayment($payment['id']);
    }

    protected function alterPaymentsDateToYesterday()
    {
        $payments = $this->getEntities('payment', [], true);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)->addMinutes(30)->timestamp;

        foreach ($payments['items'] as $payment)
        {
            $this->fixtures->edit('payment', $payment['id'], ['created_at' => $createdAt]);
        }
    }

    protected function alterRefundsDateToYesterday()
    {
        // Get all pending refunds
        $refunds = $this->getEntities('refund', [], true);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)->addMinutes(45)->timestamp;

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }
    }

    protected function checkRefundTxtData(array $data, array $file)
    {
        $this->assertNotNull($data[File\Entity::FILE_GENERATED_AT]);

        $this->assertNotNull($data[File\Entity::SENT_AT]);

        $this->assertNull($data[File\Entity::FAILED_AT]);

        $this->assertNull($data[File\Entity::ACKNOWLEDGED_AT]);

        $filePath = storage_path('files/filestore') . '/' . $file['location'];

        $this->assertTrue(file_exists($filePath));

        $refundsFileContents = file($filePath);

        $refundAmounts = [1 => '500.00', 2 => '100.00', 3 => '400.00'];

        $columns = [
                    'TRANSACTION DATE AND TIME',
                    'Refund Date',
                    'BANK_REF_NO',
                    'PG_REF_NUM',
                    'Refund Reference',
                    'Transaction Amount',
                    'Refund Amount'];

        foreach ($refundsFileContents as $key => $row)
        {
            $refundsFileRow = explode('|', $row);

            $lastValue = array_pop($refundsFileRow);

            $lastValue = str_replace(array("\n", "\r"), '', $lastValue);

            array_push($refundsFileRow, $lastValue);

            if($key === 0)
            {
                $this->assertEquals($refundsFileRow, $columns);
            }
            else
            {
                $this->assertEquals($refundsFileRow[6], $refundAmounts[$key]);
            }

            // Asserting that the file contains 7 columns
            $this->assertEquals(count($refundsFileRow), 7);
        }

        $this->assertEquals(4, count($refundsFileContents));

       // unlink($filePath);
    }

    protected function checkRefundFileData($data)
    {
        $filePath = $data['netbanking_canara']['file'];

        $this->assertEquals($data['netbanking_canara']['count'], 3);

        $this->assertTrue(file_exists($filePath));

        $refundsFileContents = file($filePath);

        $refundAmounts = [1 => 50000, 2 => 10000, 3 => 40000];

        $columns = [
                    'TRANSACTION DATE AND TIME',
                    'Refund Date',
                    'BANK_REF_NO',
                    'PG_REF_NUM',
                    'Refund Reference',
                    'Transaction Amount',
                    'Refund Amount'];

        foreach ($refundsFileContents as $key => $row)
        {
            $refundsFileRow = explode('|', $row);

            $lastValue = array_pop($refundsFileRow);

            $lastValue = str_replace(array("\n", "\r"), '', $lastValue);

            array_push($refundsFileRow, $lastValue);

            if($key === 0)
            {
                $this->assertEquals($refundsFileRow, $columns);
            }
            else
            {
                $this->assertEquals($refundsFileRow[6], $refundAmounts[$key]);
            }

            // Asserting that the file contains 7 columns
            $this->assertEquals(count($refundsFileRow), 7);
        }

        unlink($filePath);
    }

    //have to verify what values to enter
    protected function checkMailQueue()
    {
        Mail::assertQueued(RefundFileMail::class, function ($mail)
        {
            $body = 'Please find attached refunds information for Canara Bank';

            $this->assertEquals($body, $mail->viewData['body']);

            $this->assertEquals('emails.message', $mail->view);

            return true;
        });
    }

}
