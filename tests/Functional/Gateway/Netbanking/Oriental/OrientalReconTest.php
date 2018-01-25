<?php

namespace RZP\Tests\Functional\Gateway\Oriental;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Reconciliator\RequestProcessor\Base as Recon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

/**
 * Having this recon class extend the nb oriental base class.
 * Class OrientalReconTest
 * @package RZP\Tests\Functional\Gateway\Oriental
 */
class OrientalReconTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;

    protected $method = Payment\Method::NETBANKING;

    protected $bank = IFSC::ORBC;

    protected $payment;

    protected $sharedTerminal;

    public function setUp()
    {
        parent::setUp();

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->gateway = Payment\Gateway::NETBANKING_ORIENTAL;

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_netbanking_oriental_terminal');
    }

    public function testPaymentRecon()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(2)->getTimestamp();

        $payments = $this->makePaymentsSince($createdAt);

        $this->ba->appAuth();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $response = $this->reconcile($uploadedFile, Recon::NETBANKING_ORIENTAL);

        // We assert that all 3 payments were reconciled
        $this->assertEquals(3, $response['total_count']);
        $this->assertEquals(3, $response['success_count']);
        $this->assertEquals(0, $response['failure_count']);

        $gatewayPaymentEntities = $this->getEntities('netbanking', [], true);

        foreach ($gatewayPaymentEntities['items'] as $id => $netbanking)
        {
            $payment = $this->getEntityById('payment', $payments[$id], true);

            $this->assertEquals(true, $payment['gateway_captured']);

            // we persist date as per recon date
            $this->assertNotNull($netbanking['date']);

            $this->assertEquals(9999999999, $netbanking['bank_payment_id']);

            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            // Transaction is reconciled
            $this->assertNotNull($transaction['reconciled_at']);

            // We persist gateway settled at
            $this->assertNull($transaction['gateway_settled_at']);
        }
    }

    public function testReconAmountValidationFailed()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payment = $this->makePaymentsSince($createdAt, 1)[0];

        $this->ba->appAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_oriental_recon')
                {
                    // Setting amount to 1 will cause payment amount validation to fail
                    $content[3] = 1;
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $response = $this->reconcile($uploadedFile, Recon::NETBANKING_ORIENTAL);

        // Assert that the payment was not reconciled
        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(0, $response['success_count']);
        $this->assertEquals(1, $response['failure_count']);

        $netbanking = $this->getLastEntity('netbanking', true);

        $payment = $this->getEntityById('payment', $payment, true);

        $this->assertNull($payment['gateway_captured']);

        // Date is not persisted as the payment amount validation failed
        $this->assertNull($netbanking['date']);

        $this->assertNull($netbanking['bank_payment_id']);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        // Transaction is not reconciled
        $this->assertNull($transaction['reconciled_at']);
    }

    public function testReconPaymentAlreadyReconciled()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makePaymentsSince($createdAt, 1)[0];

        $payment = $this->getLastEntity('payment', true);

        $transaction = $this->getEntityById('transaction', $payment['transaction_id'], true);

        $this->fixtures->edit('transaction', $transaction['id'], ['reconciled_at' => $createdAt]);

        $this->ba->appAuth();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $response = $this->reconcile($uploadedFile, Recon::NETBANKING_ORIENTAL);

        // Total count = 1
        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(1, $response['success_count']);

        // The payment is already reconciled, so failure count = 0
        $this->assertEquals(0, $response['failure_count']);

        $netbanking = $this->getLastEntity('netbanking', true);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertNull($payment['gateway_captured']);

        // Date is not persisted as the payment amount validation failed
        $this->assertNull($netbanking['date']);

        $this->assertNull($netbanking['bank_payment_id']);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        // Transaction is reconciled
        $this->assertNotNull($transaction['reconciled_at']);
    }

    public function testReconPaymentIdEmpty()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payment = $this->makePaymentsSince($createdAt, 1)[0];

        $this->ba->appAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_oriental_recon')
                {
                    // Setting payment id to 0
                    $content[4] = 0;
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $response = $this->reconcile($uploadedFile, Recon::NETBANKING_ORIENTAL);

        // Assert that the payment was not reconciled
        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(0, $response['success_count']);
        $this->assertEquals(1, $response['failure_count']);

        $netbanking = $this->getLastEntity('netbanking', true);

        $payment = $this->getEntityById('payment', $payment, true);

        $this->assertNull($payment['gateway_captured']);

        // Date is not persisted as the payment amount validation failed
        $this->assertNull($netbanking['date']);

        $this->assertNull($netbanking['bank_payment_id']);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        // Transaction is not reconciled
        $this->assertNull($transaction['reconciled_at']);
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

    protected function createGatewayEntity($payment)
    {
        $this->fixtures->create(
            $this->method,
            [
                'payment_id'      => $payment->getId(),
                'bank'            => $this->bank,
                'action'          => 'authorize',
                'caps_payment_id' => strtoupper($payment->getId()),
                'status'          => 'Y',
            ]);
    }
}
