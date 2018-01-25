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
