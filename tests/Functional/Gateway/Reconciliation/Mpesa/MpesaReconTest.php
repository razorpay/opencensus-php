<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\Mpesa;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Reconciliator\RequestProcessor\Base as Recon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class MpesaReconTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    private $payment;

    private $sharedTerminal;

    private $wallet = Wallet::MPESA;

    protected $method = Payment\Method::WALLET;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = $this->getDefaultWalletPaymentArray($this->wallet);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_mpesa_terminal');

        $this->gateway = Payment\Gateway::WALLET_MPESA;

        $this->fixtures->merchant->enableWallet(Merchant\Account::TEST_ACCOUNT, $this->wallet);
    }

    public function testPaymentReconciliation()
    {
        $this->markTestSkipped("Mpesa wallet is deprecated");

        $payments = $this->makeMpesaPaymentSince();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, Recon::MPESA);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(3, $batch['total_count']);
        $this->assertEquals(3, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        $wallets = $this->getEntities('wallet', [], true);

        foreach ($wallets['items'] as $id => $wallet)
        {
            $payment = $this->getEntityById('payment', $payments[$id], true);

            $this->assertEquals(true, $payment['gateway_captured']);

            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertEquals($transaction['gateway_fee'], 500);

            $this->assertEquals($transaction['gateway_service_tax'], 200);

            $this->assertNotNull($wallet['gateway_payment_id']);

            $this->assertNotNull($transaction['reconciled_at']);
        }

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testReconAmountValidationFailed()
    {
        $this->markTestSkipped("Mpesa wallet is deprecated");

        $payment = $this->makeMpesaPaymentSince(1)[0];

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_mpesa_recon')
                {
                    // Setting amount to 100 will cause payment amount validation to fail
                    $content['Txn Amount (Rs.)']      = '100.00';
                    $content['Total Amount (Rs.)']    = '100.00';
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, Recon::MPESA);

        $response = $this->getLastEntity('batch', true);

        // Assert that the payment was not reconciled
        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(0, $response['success_count']);
        $this->assertEquals(1, $response['failure_count']);

        $payment = $this->getEntityById('payment', $payment, true);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        // Transaction is not reconciled
        $this->assertNull($transaction['reconciled_at']);

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $response['status']);
    }

    public function testReconPaymentIdAbsent()
    {
        $this->markTestSkipped("Mpesa wallet is deprecated");

        $payment = $this->makeMpesaPaymentSince(1)[0];

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_mpesa_recon')
                {
                    $content['Partner Txn ID']       = '';
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, Recon::MPESA);

        $response = $this->getLastEntity('batch', true);

        // Assert that the payment was not reconciled
        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(0, $response['success_count']);
        $this->assertEquals(1, $response['failure_count']);

        $payment = $this->getEntityById('payment', $payment, true);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        // Transaction is not reconciled
        $this->assertNull($transaction['reconciled_at']);

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $response['status']);
    }

    public function testRefundReconciliation()
    {
        $this->markTestSkipped();

        $payment = $this->makeMpesaPaymentSince(1)[0];

        $payment = $this->getLastEntity('payment', true);

        $refund = $this->refundPayment($payment['id']);

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_mpesa_recon')
                {
                    $refund = $this->getDbLastEntityToArray('wallet', 'test');

                    $content['Service Name']            = 'Txn. ID based reversals';
                    $content['Sender Name']             = '';
                    $content['Sender Mobile No.']       = '';
                    $content['Txn Amount (Rs.)']        = $refund['amount'] / 100;
                    $content['Reversal Method']         = 'API-based';
                    $content['Parent m-pesa Txn ID']    = $refund['gateway_payment_id'];
                    $content['Partner’s Refund Txn ID'] = $refund['payment_id'];
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, Recon::MPESA);

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

    protected function makeMpesaPaymentSince($count = 3)
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
                                        'wallet' => $this->gateway,'received' => true,
                                        'gateway_payment_id' => strval(random_int(11111111111, 99999999999)),
                                    ]);
        }

        return $payment;
    }

    private function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = 'application/vnd.ms-excel';

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
