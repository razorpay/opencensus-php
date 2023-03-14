<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\Payzapp;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Gateway\Wallet\Payzapp\ReconHeaders;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Reconciliator\RequestProcessor\Base as Recon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class PayzappReconTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    private $payment;

    private $sharedTerminal;

    private $wallet = Wallet::PAYZAPP;

    private $mimeType = 'text/csv';

    protected $method = Payment\Method::WALLET;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = $this->getDefaultWalletPaymentArray($this->wallet);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_payzapp_terminal');

        $this->gateway = Payment\Gateway::WALLET_PAYZAPP;

        $this->fixtures->merchant->enableWallet(Merchant\Account::TEST_ACCOUNT, $this->wallet);

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);

        $this->markTestSkipped('disabling payzapp temporarily');
    }

    public function testPaymentReconciliation()
    {
        $this->markTestSkipped('The flakiness in the testcase needs to be fixed. Skipping as its impacting dev-productivity.');

        $payments = $this->makePayzappPaymentSince();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], $this->mimeType);

        $this->reconcile($uploadedFile, Recon::PAYZAPP);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(3, $batch['total_count']);
        $this->assertEquals(3, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        $wallets = $this->getEntities('wallet', [], true);

        foreach ($wallets['items'] as $id => $wallet)
        {
            $this->assertNotNull($payments[$id]);

            $payment = $this->getEntityById('payment', $payments[$id], true);

            $this->assertEquals(true, $payment['gateway_captured']);

            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);
        }

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testReconAmountValidationFailed()
    {
        $payment = $this->makePayzappPaymentSince(1)[0];

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_payzapp_recon')
                {
                    // Setting amount to 100 will cause payment amount validation to fail
                    $content[ReconHeaders::GROSS_AMT]   = '100.00';
                    $content[ReconHeaders::NET_AMT]     = '100.00';
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], $this->mimeType);

        $this->reconcile($uploadedFile, Recon::PAYZAPP);

        $response = $this->getLastEntity('batch', true);

        // Assert that the payment was not reconciled
        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(0, $response['success_count']);
        $this->assertEquals(1, $response['failure_count']);

        $wallet = $this->getLastEntity('wallet', true);

        $payment = $this->getEntityById('payment', $payment, true);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        // Transaction is not reconciled
        $this->assertNull($transaction['reconciled_at']);

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $response['status']);
    }

    public function testRefundReconciliation()
    {
        $payment = $this->makePayzappPaymentSince(1)[0];

        $payment = $this->getLastEntity('payment', true);

        $refund = $this->refundPayment($payment['id']);

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_payzapp_recon')
                {
                    $refund = $this->getDbLastEntityToArray('wallet', 'test');

                    $content[ReconHeaders::TRANSACTION_TYPE]    = 'Refund';
                    $content[ReconHeaders::TRACK_ID]            = $refund['refund_id'];
                    $content[ReconHeaders::PG_TXN_ID]           = $refund['gateway_refund_id'];
                    $content[ReconHeaders::GROSS_AMT]           = $refund['amount'] / 100;
                    $content[ReconHeaders::NET_AMT]             = $refund['amount'] / 100;
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], $this->mimeType);

        $this->reconcile($uploadedFile, Recon::PAYZAPP);

        $response = $this->getLastEntity('batch', true);

        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(1, $response['success_count']);
        $this->assertEquals(0, $response['failure_count']);

        $refund = $this->getLastEntity('refund', true);

        $transactionId = $refund['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertEquals(Status::PROCESSED, $response['status']);
    }

    protected function makePayzappPaymentSince($count = 3)
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $payment = $this->makePaymentsSince($createdAt, $count);

        foreach ($payment as $paymentId)
        {
            $this->fixtures->edit('payment', $paymentId,
                                  [
                                      'wallet' => $this->wallet,
                                  ]);

            $this->fixtures->create($this->method,
                                    [
                                        'payment_id' => $paymentId,
                                        'action' => 'authorize',
                                        'wallet' => $this->gateway,
                                        'gateway_payment_id_2' => mt_rand(111111111, 999999999)
                                    ]);
        }
        return $payment;
    }


    private function createUploadedFile($file, $mimeType)
    {
        $this->assertFileExists($file);

        $uploadedFile = new UploadedFile(
            $file,
            $file,
            $mimeType,
            null,
            true
        );

        return $uploadedFile;
    }
}
