<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\Payumoney;

use Carbon\Carbon;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class PayuReconTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;

    private $payment;

    private $sharedTerminal;

    private $wallet = Wallet::PAYUMONEY;

    protected $method = Payment\Method::WALLET;

    public function setUp()
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

        $payments = $this->makeReconPaymentsSince($createdAt);

        $this->ba->appAuth();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $response = $this->reconcile($uploadedFile, 'PayuMoney');

        // We assert that all 3 payments were reconciled
        $this->assertEquals(3, $response['total_count']);
        $this->assertEquals(3, $response['success_count']);

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

    private function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = "text/csv";

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

    protected function doPayment($id)
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

        $this->fixtures->create($this->method, ['id' => $id, 'payment_id' => $payment->getId(), 'action' => 'authorize', 'wallet' => $this->gateway]);

        return $payment->getId();
    }
}
