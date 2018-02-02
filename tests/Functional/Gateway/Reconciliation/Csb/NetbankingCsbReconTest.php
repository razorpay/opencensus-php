<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\Csb;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Reconciliator\RequestProcessor\Base as Recon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class NetbankingCsbReconTest extends TestCase
{
    private $payment;

    private $sharedTerminal;

    private $method = Payment\Method::NETBANKING;

    use ReconTrait;
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingCsbReconTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultNetbankingPaymentArray(IFSC::CSBK);

        $this->gateway = Payment\Gateway::NETBANKING_CSB;

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_netbanking_csb_terminal');
    }

    public function testPaymentReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payments = $this->makePaymentsSince($createdAt);

        $this->ba->appAuth();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $response = $this->reconcile($uploadedFile, Recon::NETBANKING_CSB);

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

    private function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = "text/plain";

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

    protected final function createPayment()
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

        $this->fixtures->create(
            $this->method,
            [
                'payment_id'      => $payment->getId(),
                'bank'            => IFSC::CSBK,
                'caps_payment_id' => strtoupper($payment->getId()),
                'status'          => 'Y', // Success payments
            ]);

        return $payment->getId();
    }
}
