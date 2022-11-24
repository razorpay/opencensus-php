<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\PayuMoney;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Reconciliator\RequestProcessor\Base as Recon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class PayumoneyReconTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;

    private $payment;

    private $sharedTerminal;

    private $wallet = Wallet::PAYUMONEY;

    protected $method = Payment\Method::WALLET;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = $this->getDefaultWalletPaymentArray($this->wallet);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_payumoney_terminal');

        $this->gateway = Payment\Gateway::WALLET_PAYUMONEY;

        $this->fixtures->merchant->enableWallet(Merchant\Account::TEST_ACCOUNT, $this->wallet);
    }

    public function testPaymentReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payments = $this->makePaymentsSince($createdAt);

        $this->ba->h2hAuth();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $response = $this->reconcile($uploadedFile, Recon::PAYUMONEY);

        // We assert that all 3 payments were reconciled
        $this->assertEquals(3, $response['total_count']);
        $this->assertEquals(3, $response['success_count']);
        $this->assertEquals(0, $response['failure_count']);

        $wallets = $this->getEntities('wallet', [], true);

        foreach ($wallets['items'] as $id => $wallet)
        {
            $payment = $this->getEntityById('payment', $payments[$id], true);

            $this->assertEquals(true, $payment['gateway_captured']);

            // we persist date as per recon date
            $this->assertNotNull($wallet['date']);

            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            // Transaction is reconciled
            $this->assertNotNull($transaction['reconciled_at']);

            // We persist gateway settled at
            $this->assertNotNull($transaction['gateway_settled_at']);

            // Service tax and gateway fee are recorded in the
            // transaction entity as per hardcoded values in mock recon file
            $this->assertEquals(2345, $transaction['gateway_service_tax']);
            $this->assertEquals(3000, $transaction['gateway_fee']);
        }
    }

    public function testReconAmountValidationFailed()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payment = $this->makePaymentsSince($createdAt, 1)[0];

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_payu_recon')
                {
                    // Setting amount to 1 will cause payment amount validation to fail
                    $content['Amount'] = 1;
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $response = $this->reconcile($uploadedFile, Recon::PAYUMONEY);

        // Assert that the payment was not reconciled
        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(0, $response['success_count']);
        $this->assertEquals(1, $response['failure_count']);

        $wallet = $this->getLastEntity('wallet', true);

        $payment = $this->getEntityById('payment', $payment, true);

        $this->assertNull($payment['gateway_captured']);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        // Transaction is not reconciled
        $this->assertNull($transaction['reconciled_at']);

        // We persist gateway settled at even though the payment is not reconciled
        // Reference: PaymentReconciliate, line number 80
        $this->assertNotNull($transaction['gateway_settled_at']);

        //
        // Service tax and gateway fee are not recorded in the
        // transaction entity as per hardcoded values in mock recon file
        // Ideally these are null, but we use accessors to cast these attributes to 0
        //
        $this->assertEquals(0, $transaction['gateway_service_tax']);
        $this->assertEquals(0, $transaction['gateway_fee']);
    }

    public function testReconPaymentAlreadyReconciled()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makePaymentsSince($createdAt, 1)[0];

        $payment = $this->getLastEntity('payment', true);

        $transaction = $this->getEntityById('transaction', $payment['transaction_id'], true);

        $this->fixtures->edit('transaction', $transaction['id'], ['reconciled_at' => $createdAt]);

        $this->ba->h2hAuth();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $response = $this->reconcile($uploadedFile, Recon::PAYUMONEY);

        // Total count = 1
        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(1, $response['success_count']);

        // The payment is already reconciled, so failure count = 0
        $this->assertEquals(0, $response['failure_count']);

        $wallet = $this->getLastEntity('wallet', true);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertNull($payment['gateway_captured']);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        // Transaction is already reconciled
        $this->assertNotNull($transaction['reconciled_at']);

        // We persist gateway settled at even though gateway data won't be persisted
        $this->assertNotNull($transaction['gateway_settled_at']);

        //
        // Service tax and gateway fee are not recorded in the
        // transaction entity as per hardcoded values in mock recon file
        // Ideally these are null, but we use accessors to cast these attributes to 0
        //
        $this->assertEquals(0, $transaction['gateway_service_tax']);
        $this->assertEquals(0, $transaction['gateway_fee']);
    }

    public function testReconPaymentIdEmpty()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payment = $this->makePaymentsSince($createdAt, 1)[0];

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_payu_recon')
                {
                    // Setting amount to 1 will cause payment amount validation to fail
                    $content['Merchant Transaction ID'] = 0;
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $response = $this->reconcile($uploadedFile, Recon::PAYUMONEY);

        // Assert that the payment was not reconciled
        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(0, $response['success_count']);
        $this->assertEquals(1, $response['failure_count']);

        $wallet = $this->getLastEntity('wallet', true);

        $payment = $this->getEntityById('payment', $payment, true);

        $this->assertNull($payment['gateway_captured']);

        // Date is not persisted as the payment amount validation failed
        $this->assertNull($wallet['date']);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        // Transaction is not reconciled
        $this->assertNull($transaction['reconciled_at']);

        // When the payment id is empty, we do not persist gateway settled at
        $this->assertNull($transaction['gateway_settled_at']);

        //
        // Service tax and gateway fee are not recorded in the
        // transaction entity as per hardcoded values in mock recon file
        // Ideally these are null, but we use accessors to cast these attributes to 0
        //
        $this->assertEquals(0, $transaction['gateway_service_tax']);
        $this->assertEquals(0, $transaction['gateway_fee']);
    }

    private function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = "text/csv";

        $uploadedFile = new UploadedFile(
            $file,
            $file,
            $mimeType,
            null,
            true
        );

        return $uploadedFile;
    }

    protected function createPayment()
    {
        $attributes = [
            'terminal_id'       => $this->sharedTerminal->getId(),
            'method'            => $this->method,
            'amount'            => $this->payment['amount'],
            'base_amount'       => $this->payment['amount'],
            'amount_authorized' => $this->payment['amount'],
            'status'            => 'captured',
            'gateway'           => $this->gateway
        ];

        $payment = $this->fixtures->create('payment', $attributes);

        $transaction = $this->fixtures->create('transaction', ['entity_id' => $payment->getId(), 'merchant_id' => '10000000000000']);

        $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

        $this->fixtures->create($this->method, ['payment_id' => $payment->getId(), 'action' => 'authorize', 'wallet' => $this->gateway]);

        return $payment->getId();
    }
}
