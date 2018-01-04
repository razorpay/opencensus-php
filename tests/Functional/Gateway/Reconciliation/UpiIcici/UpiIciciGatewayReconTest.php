<?php

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class UpiIciciGatewayReconTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;

    /**
     * @var array
     */
    private $payment;

    public function setUp()
    {
        parent::setUp();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->fixtures->create('terminal:shared_upi_icici_terminal');

        $this->gateway = Payment\Gateway::UPI_ICICI;

        $this->fixtures->merchant->enableMethod(Merchant\Account::TEST_ACCOUNT, Payment\Method::UPI);
    }

    public function testReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiIciciRefundsSince(3, $createdAt);

        $this->ba->appAuth();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $response = $this->reconcile($uploadedFile, 'UpiIcici');

        // We assert that all 3 payments were reconciled
        $this->assertEquals(3, $response['total_count']);
        $this->assertEquals(3, $response['success_count']);

        $refunds = $this->getEntities('refund', [], true);

        foreach ($refunds['items'] as $refund)
        {
            $transactionId = $refund['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);
        }
    }

    public function testReconRefundAmountValidationFailure()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiIciciRefundsSince(3, $createdAt);

        $this->ba->appAuth();

        $this->mockReconContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'col_icici_recon')
                {
                    // Setting random column value
                    $content[9] = 13476475;
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $response = $this->reconcile($uploadedFile, 'UpiIcici');

        // We assert that all 3 payments were reconciled
        $this->assertEquals(3, $response['total_count']);
        $this->assertEquals(3, $response['failure_count']);

        $refunds = $this->getEntities('refund', [], true);

        // We do not reconcile refunds that fail the amount assertion step
        foreach ($refunds['items'] as $refund)
        {
            $transactionId = $refund['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNull($transaction['reconciled_at']);
        }
    }

    public function testReconRefundNotFoundInDb()
    {
        $this->markTestSkipped();

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        list($refunds, $payments) = $this->makeUpiIciciRefundsSince(3, $createdAt);

        $this->ba->appAuth();

        $this->mockReconContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'col_icici_recon')
                {
                    // Setting new unique id as column value
                    $content[4] = UniqueIdEntity::generateUniqueId();
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->updatePaymentStatusToCaptured($payments);

        //
        // The refund id's sent across in the recon file will not be found in the DB.
        // This will force refund recon to create new refunds to reconcile.
        //
        $response = $this->reconcile($uploadedFile, 'UpiIcici');

        // We assert that all 3 payments were reconciled
        $this->assertEquals(3, $response['total_count']);
        $this->assertEquals(3, $response['success_count']);

        $refunds = $this->getEntities('refund', [], true);

        foreach ($refunds['items'] as $index => $refund)
        {
            $transactionId = $refund['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            //
            // First 3 refunds were not reconciled as they were not found in the DB.
            // Recon created 3 new refunds and reconciled them instead.
            //
            $assertFunction = ($index < 3) ? 'assertNotNull' : 'assertNull';

            $this->$assertFunction($transaction['reconciled_at']);
        }
    }

    private function updatePaymentStatusToCaptured(array $payments)
    {
        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment,
                [
                    'status'               => 'captured',
                    'amount_refunded'      => 0,
                    'base_amount_refunded' => 0,
                    'refund_status'        => null
                ]);
        }
    }

    private function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = "application/octet-stream";

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

    private function makeUpiIciciRefundsSince(int $count = 3, int $createdAt)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiIciciPayment();
        }

        $refunds = [];

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);

            $refunds[] = $this->refundPayment($payment)['id'];
        }

        return [$refunds, $payments];
    }

    private function doUpiIciciPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response[Constants::PAYMENT_ID];

        // Coproto must be working
        $this->assertEquals(Constants::ASYNC, $response[Constants::TYPE]);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(Payment\Status::CREATED, $payment[Payment\Entity::STATUS]);

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals([Constants::SUCCESS => true], $response);

        $this->capturePayment($paymentId, $this->payment[Payment\Entity::AMOUNT]);

        return $paymentId;
    }
}