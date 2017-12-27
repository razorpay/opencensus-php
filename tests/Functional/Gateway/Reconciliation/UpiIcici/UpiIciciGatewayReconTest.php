<?php

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class UpiIciciGatewayReconTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;

    /**
     * @var string
     */
    protected $gateway = Payment\Gateway::UPI_ICICI;

    /**
     * @var array
     */
    private $payment;

    public function setUp()
    {
        parent::setUp();

        $this->ba->appAuth();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->fixtures->create('terminal:shared_upi_icici_terminal');

        $this->fixtures->merchant->enableMethod(Merchant\Account::TEST_ACCOUNT, Payment\Method::UPI);
    }

    public function testUpiIciciReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiIciciPaymentsSince(3, $createdAt);

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $response = $this->reconcile($uploadedFile, 'UpiSbi');

        // We assert that all 3 payments were reconciled
        $this->assertEquals(3, $response['total_count']);
        $this->assertEquals(3, $response['success_count']);

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);
        }

        // We assert that the entity's values have changed since recon -
        // as recon persists recon data into the DB
        $this->assertUpiEntityChanged();
    }

    private function makeUpiIciciPaymentsSince(int $count = 3, int $createdAt)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiIciciPayment();
        }

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);
        }
    }

    private function doUpiIciciPayment()
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

    private function checkPaymentStatus(string $id, string $status)
    {
        $response = $this->getPaymentStatus($id);

        $this->assertEquals($status, $response[Payment\Entity::STATUS]);
    }
}