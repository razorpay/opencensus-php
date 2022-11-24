<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\Amazonpay;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\Helpers;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Reconciliator\Amazonpay\ReconHeaders;
use RZP\Reconciliator\RequestProcessor\Base as Recon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class AmazonpayReconTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;
    use Helpers\DbEntityFetchTrait;

    private $payment;

    private $sharedTerminal;

    private $mimeType = "text/csv";

    private $wallet = Wallet::AMAZONPAY;

    protected $method = Payment\Method::WALLET;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = $this->getDefaultWalletPaymentArray($this->wallet);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_amazonpay_terminal');

        $this->gateway = Payment\Gateway::WALLET_AMAZONPAY;

        $this->fixtures->merchant->enableWallet(Merchant\Account::TEST_ACCOUNT, $this->wallet);

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

     public function testPaymentReconciliation()
     {
         $payments = $this->makeAmazonPaymentSince();

         $this->fixtures->edit('payment', $payments[0],
                               [
                                   'amount' => 149000,
                                   'base_amount' => 149000,
                                   'amount_authorized' => 149000,
                               ]);

         $this->ba->h2hAuth();

         $fileContents = $this->generateReconFile();

         $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], $this->mimeType);

         $this->reconcile($uploadedFile, Recon::AMAZONPAY);

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
        $payment = $this->makeAmazonPaymentSince(1)[0];

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_amazonpay_recon')
                {
                    // Setting amount to 100 will cause payment amount validation to fail
                    $content[ReconHeaders::ORDER_AMOUNT]       = '100.00';
                    $content[ReconHeaders::NET_TRANSACTION_AMOUNT]   = '100.00';
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], $this->mimeType);

        $this->reconcile($uploadedFile, Recon::AMAZONPAY);

        $response = $this->getLastEntity('batch', true);

        // Assert that the payment was not reconciled
        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(0, $response['success_count']);
        $this->assertEquals(1, $response['failure_count']);

        $payment = $this->getEntityById('payment', $payment, true);

        $this->assertNull($payment['gateway_captured']);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        // Transaction is not reconciled
        $this->assertNull($transaction['reconciled_at']);

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $response['status']);
    }

    public function testReconPaymentIdAbsent()
    {
        $payment = $this->makeAmazonPaymentSince(1)[0];

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_amazonpay_recon')
                {
                    $content[ReconHeaders::MERCHANT_ORDER_ID]       = '';
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], $this->mimeType);

        $this->reconcile($uploadedFile, Recon::AMAZONPAY);

        $response = $this->getLastEntity('batch', true);

        // Assert that the payment was not reconciled
        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(0, $response['success_count']);
        $this->assertEquals(1, $response['failure_count']);

        $payment = $this->getEntityById('payment', $payment, true);

        $this->assertNull($payment['gateway_captured']);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        // Transaction is not reconciled
        $this->assertNull($transaction['reconciled_at']);

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $response['status']);
    }

    public function testRefundReconciliation()
    {
        $payment = $this->makeAmazonPaymentSince(1)[0];

        $payment = $this->getLastEntity('payment', true);

        $refund = $this->refundPayment($payment['id']);

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_amazonpay_recon')
                {
                    $refund = $this->getDbLastEntityToArray('wallet', 'test');
                    $refundAmount = $this->formatAmount((-1) * $refund['amount'] / 100);

                    $content[ReconHeaders::TRANSACTION_TYPE]= 'Refund';
                    $content[ReconHeaders::ORDER_AMOUNT]= $refundAmount;
                    $content[ReconHeaders::NET_TRANSACTION_AMOUNT]= $refundAmount;
                    $content[ReconHeaders::MERCHANT_ORDER_REFERENCE_ID] = $refund['refund_id'];
                    $content[ReconHeaders::AMAZON_ORDER_REFERENCE_ID]= $refund['gateway_refund_id'];
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], $this->mimeType);

        $this->reconcile($uploadedFile, Recon::AMAZONPAY);

        $response = $this->getLastEntity('batch', true);

        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(1, $response['success_count']);
        $this->assertEquals(0, $response['failure_count']);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertNull($payment['gateway_captured']);

        $transactionId = $refund['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertEquals(Status::PROCESSED, $response['status']);
    }

    protected function makeAmazonPaymentSince($count = 3)
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
                                 [   'payment_id' => $paymentId,
                                     'action' => 'authorize',
                                     'wallet' => $this->gateway,
                                     'gateway_payment_id' => 'S04-3441699-5326071',
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

    public function formatAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }
}
