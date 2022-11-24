<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\PaylaterIcici;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Gateway\Mozart\WalletPhonepe\ReconFields;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Reconciliator\RequestProcessor\Base as Recon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class PaylaterIciciReconciliationTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    private $payment;

    private $sharedTerminal;

    private $provider = Payment\Processor\PayLater::ICICI;

    protected $method = Payment\Method::PAYLATER;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = $this->getDefaultPayLaterPaymentArray($this->provider);

        $this->sharedTerminal = $this->fixtures->create('terminal:paylater_icici_terminal');

        $this->gateway = Payment\Gateway::PAYLATER_ICICI;

        $this->fixtures->merchant->enablePayLater('10000000000000');
    }

    public function testPaymentReconciliation()
    {
        $paymentId = $this->createPayment($this->gateway);

        $payment = $this->getDbLastEntity('payment');

        $this->createMozartEntity($paymentId, $payment['amount'], 'paylater_icici');

        $fileContents = $this->generateFile('paylater_icici', ['gateway' => 'paylater_icici']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile( $uploadedFile,'NetbankingIcici' );

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['providerReferenceId']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testPaylaterIciciAmountMismatch()
    {
        $paymentId = $this->createPayment($this->gateway);

        $payment = $this->getDbLastEntity('payment');

        $this->createMozartEntity($paymentId, $payment['amount'], 'paylater_icici');

        $this->mockReconContentFunction(
            function(& $content, $action = '')
            {
                if ($action === 'col_payment_icici_paylater_recon')
                {
                    $content[ReconFields::AMOUNT] = 1;
                }
            });

        $fileContents = $this->generateFile('paylater_icici', ['gateway' => 'paylater_icici']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile( $uploadedFile,'PaylaterIcici' );

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $batch['status']);
    }

    protected function createMozartEntity($paymentId, $amount, $gateway)
    {
        $mozartAttributes = [
            'payment_id' => $paymentId,
            'gateway'    => $gateway,
            'amount'     => $amount,
            'raw'        => json_encode(['payment_id' => $paymentId,'providerReferenceId' => 'P00000000001122334455']),
            'action'     => 'authorize',
        ];

        $mozart = $this->fixtures->create('mozart', $mozartAttributes);

        return $mozart;
    }

    protected function generateFile($bank, $input)
    {
        $gateway = $bank;

        $request = [
            'url'     => '/gateway/mock/reconciliation/' . $gateway,
            'content' => $input,
            'method'  => 'POST'
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = 'text/csv';

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
