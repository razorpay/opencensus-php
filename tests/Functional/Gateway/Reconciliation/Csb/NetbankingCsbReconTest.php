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

        $response = $this->generateAndUploadReconFile();

        // We assert that all 3 payments were reconciled
        $this->assertEquals(3, $response['total_count']);
        $this->assertEquals(3, $response['success_count']);
        $this->assertEquals(0, $response['failure_count']);

        $netbankings = $this->getEntities('netbanking', [], true);

        array_map(
            function($netbanking, $id) use ($payments)
            {
                $payment = $this->getEntityById('payment', $payments[$id], true);

                $this->assertEquals(true, $payment['gateway_captured']);

                // we persist date into the nb entity as per recon file date
                $this->assertNotNull($netbanking['date']);

                $transactionId = $payment['transaction_id'];

                $transaction = $this->getEntityById('transaction', $transactionId, true);

                // Transaction is reconciled
                $this->assertNotNull($transaction['reconciled_at']);

                //
                // We do not persist gateway settled at
                // This is because the recon file doesn't contain a settled at date
                //
                $this->assertNull($transaction['gateway_settled_at']);

                //
                // Service tax and gateway fee are not returned in the recon file.
                // This is why we do not save these two attributes in the transaction entity
                //
                $this->assertEquals(0, $transaction['gateway_service_tax']);
                $this->assertEquals(0, $transaction['gateway_fee']);
            },
            $netbankings['items'],
            array_keys($netbankings['items'])
        );
    }

    public function testReconRefundAmountValidationFailure()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payment = $this->makePaymentsSince($createdAt, 1)[0];

        $this->ba->appAuth();

        $this->mockReconContentFunction(
            function (& $content, $action = null)
            {
                $content[0][3] = 1;
            });

        $this->generateAndUploadReconFile();

        $netbanking = $this->getLastEntity('netbanking', true);

        $payment = $this->getEntityById('payment', $payment, true);

        $this->assertPaymentReconSkipped($payment, $netbanking);
    }

    public function testPaymentIdAbsentReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payment = $this->makePaymentsSince($createdAt, 1)[0];

        $this->ba->appAuth();

        $this->mockReconContentFunction(
            function (& $content, $action = null)
            {
                $content[0][0] = "";
            });

        $this->generateAndUploadReconFile();

        $netbanking = $this->getLastEntity('netbanking', true);

        $payment = $this->getEntityById('payment', $payment, true);

        $this->assertPaymentReconSkipped($payment, $netbanking);
    }

    public function testReconPaymentFailedReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payment = $this->makePaymentsSince($createdAt, 1)[0];

        $this->ba->appAuth();

        $this->mockReconContentFunction(
            function (& $content, $action = null)
            {
                $content[0][4] = "N";
            });

        $this->generateAndUploadReconFile();

        $netbanking = $this->getLastEntity('netbanking', true);

        $payment = $this->getEntityById('payment', $payment, true);

        $this->assertPaymentReconSkipped($payment, $netbanking);
    }

    private function generateAndUploadReconFile()
    {
        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        return $this->reconcile($uploadedFile, Recon::NETBANKING_CSB);
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

    private function assertPaymentReconSkipped(array $payment, array $netbanking)
    {
        // The payment will not be reconciled as the amount did not match
        $this->assertEquals(false, $payment['gateway_captured']);

        $this->assertNull($netbanking['date']);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        // Transaction is not reconciled
        $this->assertNull($transaction['reconciled_at']);
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

        $transaction = $this->fixtures->create('transaction',
                                              ['entity_id' => $payment->getId(), 'merchant_id' => '10000000000000']);

        $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

        $this->fixtures->create(
            $this->method,
            [
                'payment_id'      => $payment->getId(),
                'bank_payment_id' => 9999999999,
                'reference1'      => 'csb_payee_id',
                'bank'            => IFSC::CSBK,
                'caps_payment_id' => strtoupper($payment->getId()),
                'status'          => 'Y', // Success payments
            ]);

        return $payment->getId();
    }
}
