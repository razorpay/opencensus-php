<?php

namespace RZP\Tests\Functional\Gateway\Oriental;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Refund;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Netbanking\Oriental;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Models\Payment\Verify\Status as VerifyStatus;
use RZP\Gateway\Netbanking\Base\Entity as Netbanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingOrientalGatewayTest extends TestCase
{
    use PaymentTrait;

    private $payment;

    private $bank = IFSC::ORBC;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingOrientalGatewayTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->gateway = Payment\Gateway::NETBANKING_ORIENTAL;

        $this->fixtures->create('terminal:shared_netbanking_oriental_terminal');
    }

    public function testPayment()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);

        // For netbanking payments, acquirer data contains bank_transaction_id which is equal to reference1 attribute
        $this->assertEquals(9999999999, $payment[Payment\Entity::ACQUIRER_DATA]['bank_transaction_id']);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking);
    }

    public function testPaymentFailed()
    {
        $payment = $this->createPaymentFailed();

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        // The payment fails and an exception is thrown before acquirer data is updated
        $this->assertNull($payment[Payment\Entity::ACQUIRER_DATA]['bank_transaction_id']);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'netbankingPaymentFailed');
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment[Payment\Entity::ID]);

        $this->assertEquals(VerifyStatus::SUCCESS, $verify[ConstantsEntity::PAYMENT][Payment\Entity::VERIFIED]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'netbankingVerify');
    }

    public function testApiSuccessGatewayFailed()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $data = $this->testData['testVerifyMismatch'];

        $this->mockVerifyFailed();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(VerifyStatus::FAILED, $payment[Payment\Entity::VERIFIED]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'netbankingVerify');
    }

    public function testApiFailedGatewaySuccess()
    {
        $payment = $this->createPaymentFailed();

        $data = $this->testData['testVerifyMismatch'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        $this->assertEquals(VerifyStatus::FAILED, $payment[Payment\Entity::VERIFIED]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $this->assertTestResponse($netbanking, 'netbankingPaymentFailedVerifySuccess');
    }

    public function testPaymentFailedVerifyFailed()
    {
        $payment = $this->createPaymentFailed();

        $this->mockVerifyFailed();

        $this->verifyPayment($payment[Payment\Entity::ID]);

        $payment = $this->getLastEntity(ConstantsEntity::PAYMENT, true);

        // apiSuccess = failed and gatewaySuccess = failed so VerifyStatus = Success
        $this->assertEquals(VerifyStatus::SUCCESS, $payment[Payment\Entity::VERIFIED]);

        $netbanking = $this->getLastEntity(ConstantsEntity::NETBANKING, true);

        $testData = $this->testData['netbankingVerify'];

        $testData[Netbanking::STATUS] = Oriental\Status::FAILED;

        $this->assertArraySelectiveEquals($testData, $netbanking);
    }

    public function testRefundFileGeneration()
    {
        list($payments, $refunds) = $this->createRefundsForRefundFileGeneration();

        // Refund a 4th payment
        $payment = $this->doAuthAndCapturePayment($this->payment)[Payment\Entity::ID];
        $this->refundPayment($payment);

        $data = $this->generateRefundsExcelForNbOriental();

        $this->assertArrayHasKey(Payment\Gateway::NETBANKING_ORIENTAL, $data);

        $this->assertEquals(3, $data[Payment\Gateway::NETBANKING_ORIENTAL][Constants::COUNT]);
        $this->assertTrue(file_exists($data[Payment\Gateway::NETBANKING_ORIENTAL][Constants::FILE]));

        $filePath = $data[Payment\Gateway::NETBANKING_ORIENTAL][Constants::FILE];

        $this->assertRefundFileContents($filePath, $payments, $refunds);

        // We pull out the filestore entity created while creating the refund file
        $file = $this->getLastEntity(ConstantsEntity::FILE_STORE, true);

        // Asserting the properties of the fileStore object that was created and uploaded into the S3 bucket
        $this->assertEquals(FileStore\Type::ORIENTAL_NETBANKING_REFUND, $file[FileStore\Entity::TYPE]);
        $this->assertEquals(FileStore\Store::S3, $file[FileStore\Entity::STORE]);
        $this->assertEquals(FileStore\Format::TXT, $file[FileStore\Entity::EXTENSION]);

        unlink($data[Payment\Gateway::NETBANKING_ORIENTAL][Constants::FILE]);
    }

    private function assertRefundFileContents(string $file, array $payments, array $refunds)
    {
        $handle = fopen($file, 'r');

        $currentLineNumber = 0;

        $numRefunds = sizeof($refunds);

        while (($row = fgetcsv($handle, 0, '|')) !== false)
        {
            $date = Carbon::now(Timezone::IST)->format('Ymd');

            if ($currentLineNumber === 0)
            {
                $this->assertEquals('HOBCUTLPRFD', $row[0]);
                $this->assertEquals($date, $row[1]);
                $this->assertEquals('random_merchant_id', $row[2]);
            }
            else if ($currentLineNumber === ($numRefunds + 1))
            {
                $this->assertEquals('TOBCUTLPRFD', $row[0]);
                $this->assertEquals($date, $row[1]);
                $this->assertEquals($numRefunds, $row[2]);
                $this->assertEquals('1100', $row[3]);
            }
            else
            {
                $paymentId = explode('_', $payments[$currentLineNumber - 1])[1];
                $payment = $this->getEntityById(ConstantsEntity::PAYMENT, $paymentId, true);

                $refund = $refunds[$currentLineNumber - 1];

                $paymentId = explode('_', $payment[Payment\Entity::ID])[1];
                $refundId = explode('_', $refund[Payment\Refund\Entity::ID])[1];

                $this->assertEquals($paymentId, $row[0]);
                $this->assertEquals('R', $row[1]);
                $this->assertEquals($refund[Payment\Refund\Entity::AMOUNT] / 100, $row[2]);
                $this->assertEquals('9999999999', $row[3]);
                $this->assertEquals($date, $row[4]);
                $this->assertEquals($payment[Payment\Entity::AMOUNT] / 100, $row[5]);
                $this->assertEquals($refundId, $row[6]);
            }

            $currentLineNumber++;
        }
    }

    private function createRefundsForRefundFileGeneration()
    {
        $payments = [];

        // Create 3 payments
        $payments[] = $this->doAuthAndCapturePayment($this->payment)[Payment\Entity::ID];
        $payments[] = $this->doAuthAndCapturePayment($this->payment)[Payment\Entity::ID];
        $payments[] = $this->doAuthAndCapturePayment($this->payment)[Payment\Entity::ID];

        // Refund 2 fully and the other one partially
        $refundAmount = [50000, 50000, 10000];

        $refunds = [];

        foreach ($payments as $count => $payment)
        {
            $refunds[] = $this->refundPayment($payment, $refundAmount[$count]);
        }

        foreach ($refunds as $refund)
        {
            $createdAt = Carbon::yesterday(Timezone::IST)->timestamp + 5;
            $this->fixtures->edit(Entity::REFUND, $refund[Refund\Entity::ID], [Refund\Entity::CREATED_AT => $createdAt]);
        }

        return [$payments, $refunds];
    }

    private function generateRefundsExcelForNbOriental()
    {
        $this->ba->appAuth();

        $request = [
            'url' => '/refunds/excel',
            'method' => 'post',
            'content' => [
                'method'    => Payment\Method::NETBANKING,
                'bank'      => IFSC::ORBC,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    private function createPaymentFailed()
    {
        $data = $this->testData['testPaymentFailed'];

        $payment = $this->payment;

        $this->mockPaymentFailed();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            });

        return $this->getLastEntity(ConstantsEntity::PAYMENT, true);
    }

    private function mockVerifyFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content[Oriental\ResponseFields::TXN_STATUS] = Oriental\Status::FAILED;
                }
            });
    }

    private function mockPaymentFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $content[Oriental\ResponseFields::PAID] = Oriental\Status::FAILED;
            });
    }
}
