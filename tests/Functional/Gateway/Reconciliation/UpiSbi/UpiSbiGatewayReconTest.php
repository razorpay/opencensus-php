<?php

//use Mockery;
use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\Gateway\Upi\Sbi\Constants;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiSbiGatewayReconTest extends TestCase
{
    use PaymentTrait;

    /**
     * @var string
     */
    protected $gateway = Payment\Gateway::UPI_SBI;

    /**
     * @var array
     */
    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/UpiSbiGatewayReconTestData.php';

        parent::setUp();

        $this->ba->appAuth();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->fixtures->create('terminal:shared_upi_mindgate_sbi_terminal');

        $this->fixtures->merchant->enableMethod(Merchant\Account::TEST_ACCOUNT, Payment\Method::UPI);
    }

    public function testUpiSbiReconciliation()
    {
        $this->do3UpiSbiPaymentsYesterday();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile);

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNull($transaction['reconciled_at']);

            // TODO: Potentially more assertions
        }

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        // TODO: Add assertions for gateway entity after discussion with yv and ria
        // TODO: Should the details be persisted only after a dirty check???
        // TODO: Check if this is already being done in the code base.
    }

    protected function createUploadedFile($file)
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

    protected function reconcile(UploadedFile $uploadedFile)
    {
        $input = [
            'manual'           => true,
            'gateway'          => 'UpiSbi',
            'attachment-count' => 1,
        ];

        $request = [
            'url'     => '/reconciliate',
            'content' => $input,
            'method'  => 'POST',
            'files'   => [
                'attachment-1' => $uploadedFile,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function generateReconFile()
    {
        $request = [
            'url'     => '/gateway/mock/reconciliation/' . $this->gateway,
            'content' => [],
            'method'  => 'POST'
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function do3UpiSbiPaymentsYesterday()
    {
        $payments[] = $this->doUpiSbiPayment();
        $payments[] = $this->doUpiSbiPayment();
        $payments[] = $this->doUpiSbiPayment();

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);
        }
    }

    protected function doUpiSbiPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response[Constants::PAYMENT_ID];

        // Coproto must be working
        $this->assertEquals(Constants::ASYNC, $response[Constants::TYPE]);

        $this->checkPaymentStatus($paymentId, Payment\Status::CREATED);

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity);

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals([Constants::SUCCESS => true], $response);

        return $paymentId;
    }

    protected function checkPaymentStatus(string $id, string $status)
    {
        $response = $this->getPaymentStatus($id);

        $this->assertEquals($status, $response[Payment\Entity::STATUS]);
    }
}