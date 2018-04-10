<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Obc;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Refund;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Netbanking\Obc;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Models\Payment\Verify\Status as VerifyStatus;
use RZP\Gateway\Netbanking\Base\Entity as Netbanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingObcGatewayTest extends TestCase
{
    use PaymentTrait;

    private $payment;

    private $bank = IFSC::ORBC;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingObcGatewayTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->gateway = Payment\Gateway::NETBANKING_OBC;

        $this->fixtures->create('terminal:shared_netbanking_obc_terminal');
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

    public function testPaymentVerifyMistamtch()
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

    public function testPaymentAmountMismatch()
    {
        $this->payment['amount'] = 2000;

        $payment = $this->doAuthAndCapturePayment($this->payment, 2000);

        $data = $this->testData['testPaymentAmountMismatch'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            });
    }

    public function testRefundFileGeneration()
    {
        list($payments, $refunds) = $this->createRefundsForRefundFileGeneration();

        // Refund a 4th payment
        $payment = $this->doAuthAndCapturePayment($this->payment)[Payment\Entity::ID];
        $this->refundPayment($payment);

        $data = $this->generateRefundsExcelForNbObc();

        $this->assertArrayHasKey(Payment\Gateway::NETBANKING_OBC, $data);

        $this->assertEquals(3, $data[Payment\Gateway::NETBANKING_OBC]['count']);
        $this->assertTrue(file_exists($data[Payment\Gateway::NETBANKING_OBC]['file']));

        $filePath = $data[Payment\Gateway::NETBANKING_OBC]['file'];

        $this->assertRefundFileContents($filePath, $payments, $refunds);

        // We pull out the filestore entity created while creating the refund file
        $file = $this->getLastEntity(ConstantsEntity::FILE_STORE, true);

        // Asserting the properties of the fileStore object that was created and uploaded into the S3 bucket
        $this->assertEquals(FileStore\Type::OBC_NETBANKING_REFUND, $file[FileStore\Entity::TYPE]);
        $this->assertEquals(FileStore\Store::S3, $file[FileStore\Entity::STORE]);
        $this->assertEquals(FileStore\Format::TXT, $file[FileStore\Entity::EXTENSION]);

        unlink($data[Payment\Gateway::NETBANKING_OBC]['file']);
    }

    private function assertRefundFileContents(string $file, array $payments, array $refunds)
    {
        $handle = fopen($file, 'r');

        $currentLineNumber = 0;

        $numRefunds = count($refunds);

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

    private function generateRefundsExcelForNbObc()
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

    private function mockPaymentFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $content[Obc\ResponseFields::PAID] = Obc\Status::FAILED;
            });
    }
}
