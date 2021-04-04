<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\UpiSbi;

use Queue;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Services\Scrooge;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\PublicEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Gateway\Upi\Sbi\Constants;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Reconciliator\Base\Foundation\ScroogeReconciliate;

class UpiSbiGatewayReconTest extends TestCase
{
    use ReconTrait;
    use BatchTestTrait;


    /**
     * @var array
     */
    private $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/UpiSbiGatewayReconTestData.php';

        parent::setUp();

        $this->ba->appAuth();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->gateway = Payment\Gateway::UPI_SBI;

        $this->terminal = $this->fixtures->create('terminal:shared_upi_mindgate_sbi_terminal');

        $this->gateway = Payment\Gateway::UPI_SBI;

        $this->fixtures->merchant->enableMethod(Merchant\Account::TEST_ACCOUNT, Payment\Method::UPI);
    }

    public function testUpiSbiReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiSbiPaymentsSince(3, $createdAt);

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiSbi');

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);

            $upi = $this->getDbLastEntity('upi');

            $this->assertNotNull($payment['reference16']);

            $this->assertNotNull($upi->getReconciledAt());

            $this->assertEquals($upi['npci_reference_id'], $payment['reference16']);
        }

        // We assert that the entity's values have changed since recon -
        // as recon persists recon data into the DB
        $this->assertUpiEntityChanged();

        $this->assertBatchStatus(Status::PROCESSED);

    }

    public function testUpiSbiUnexpectedPaymentFile()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiSbiPaymentsSince(1, $createdAt);

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'sbi_recon')
                {
                    $content[0]['PG Merchant ID']           = $this->terminal->getGatewayMerchantId();
                    $content[0]['Order No']                 = 'BB31121900923519425756';
                    $content[0]['Customer Ref No']          = '034102928430';
                    $content[0]['Payer Virtual Address']    = 'vishnu@icici';
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiSbi');

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $this->assertNotNull($payment['reference16']);
        }

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $this->assertNotNull($payment['reference16']);
        }

        $transaction = $this->getDbLastEntityToArray('transaction');

        $this->assertNotNull($transaction['reconciled_at']);

        $upiEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('SBI0000000000119', $upiEntity['gateway_merchant_id']);
    }

    public function testGatewayMismatch()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiSbiPaymentsSince(1, $createdAt);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $payment = $this->getDbLastEntityToArray('payment');

        //creating upisbi terminal to update it later in payment entitiy
        $this->fixtures->create('terminal:shared_upi_axis_terminal');

        //updating gateway and terminal to create gateway mismatch
        $this->fixtures->edit('payment',
            $upiEntity['payment_id'],
            [
                'gateway'       => 'upi_sbi',
                'terminal_id'   => '100UPIAXISTmnl' //upisbi terminal
            ]);

        $this->fixtures->edit('upi',
            $upiEntity['id'],
            [
                'gateway'       => 'upi_sbi'
            ]);

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiSbi');

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);
    }

    public function testUpiSbiForceAuthorizePayment()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiSbiPaymentsSince(1, $createdAt);

        $payments = $this->getEntities('payment', [], true);

        $this->fixtures->payment->edit($payments['items'][0]['id'],
            [
                'status'                => 'failed',
                'error_code'            => 'BAD_REQUEST_ERROR',
                'internal_error_code'   => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'     => 'Payment was not completed on time.',
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'sbi_recon')
                {
                    $content[0]['Payer Virtual Address'] = 'vishnu@icici';
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiSbi', ['pay_'. $payment['id']]);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertNotNull($updatedPayment['reference16']);

        $this->assertEquals('vishnu@icici', $updatedPayment['vpa']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    public function testFailedUpiSbiReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiSbiPaymentsSince(1, $createdAt);

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                $content[0]['Transaction Status'] = 'FAILED';
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiSbi');

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNull($transaction['reconciled_at']);
        }

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testUpiSbiRefundReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiSbiRefundsSince($createdAt);

        $this->ba->appAuth();

        $entries[] = $this->mockRefundData();

        $file = $this->writeToExcelFile($entries, 'refundreport');

        $uploadedFile = $this->createRefundUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiSbi');

        $refunds = $this->getEntities('refund', [], true);

        foreach ($refunds['items'] as $refund)
        {
            $this->assertEquals('processed', $refund['status']);

            $this->assertNotNull($refund['reference1']);

            $transactionId = $refund['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);
        }

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testUpiSbiStatusFailedRefundRecon()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiSbiRefundsSince($createdAt);

        $this->ba->appAuth();

        $entry1 = $this->mockRefundData();

        $entry1['BANKREMARK'] = 'Invalid OrderNo';

        $this->makeUpiSbiRefundsSince($createdAt);

        $entry2 = $this->mockRefundData();

        $entry2['BANKREMARK'] = 'Duplicate request';

        $entries[] = $entry1;

        $entries[] = $entry2;

        $file = $this->writeToExcelFile($entries, 'refundreport');

        $uploadedFile = $this->createRefundUploadedFile($file);

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['initiateRefundRecon'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('initiateRefundRecon')
                           ->will($this->returnCallback(
                               function ($data)
                               {
                                   $this->assertEquals(Payment\Refund\Status::PENDING, $data[ScroogeReconciliate::REFUNDS][0][ScroogeReconciliate::GATEWAY_KEYS]['recon_status']);
                                   $this->assertEquals('Invalid OrderNo', $data[ScroogeReconciliate::REFUNDS][0][ScroogeReconciliate::GATEWAY_KEYS]['gateway_status']);
                                   $this->assertEquals(Payment\Refund\Status::FAILED, $data[ScroogeReconciliate::REFUNDS][1][ScroogeReconciliate::GATEWAY_KEYS]['recon_status']);
                                   $this->assertEquals('Duplicate request', $data[ScroogeReconciliate::REFUNDS][1][ScroogeReconciliate::GATEWAY_KEYS]['gateway_status']);
                               })
                           );

        $this->reconcile($uploadedFile, 'UpiSbi');

        $refunds = $this->getEntities('refund', [], true);

        foreach ($refunds['items'] as $refund)
        {
            $this->assertEquals('processed', $refund['status']);
        }
    }

    private function assertUpiEntityChanged()
    {
        $upiEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('123456789012', $upiEntity['npci_reference_id']);
    }

    private function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = 'application/octet-stream';

        $uploadedFile = new UploadedFile(
            $file,
            $file,
            $mimeType,
            filesize($file),
            null,
            true
        );

        return $uploadedFile;
    }

    private function createRefundUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = 'application/octet-stream';

        $uploadedFile = new UploadedFile(
            $file,
            'refundreport.xlsx',
            $mimeType,
            filesize($file),
            null,
            true
        );

        return $uploadedFile;
    }

    private function makeUpiSbiPaymentsSince(int $count = 3, int $createdAt)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiSbiPayment();
        }

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);
        }
    }

    private function makeUpiSbiRefundsSince(int $createdAt)
    {
        $paymentId = $this->doUpiSbiPayment();

        $this->refundAuthorizedPayment($paymentId);
    }

    private function doUpiSbiPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response[Constants::PAYMENT_ID];

        // Coproto must be working
        $this->assertEquals(Constants::ASYNC, $response[Constants::TYPE]);

        $this->checkPaymentStatus($paymentId, Payment\Status::CREATED);

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity);

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('SUCCESS', $response['status']);

        return $paymentId;
    }

    private function checkPaymentStatus(string $id, string $status)
    {
        $response = $this->getPaymentStatus($id);

        $this->assertEquals($status, $response[Payment\Entity::STATUS]);
    }

    protected function mockRefundData()
    {
        $refund = $this->getDbLastRefund();

        $facade = $this->testData['upiSbiRefund'];

        $facade['REFREQNO']     = PublicEntity::stripDefaultSIgn($refund->getPublicId());

        $facade['REFUNDREQAMT'] = $refund->getAmount()/100;

        return $facade;
    }
}
