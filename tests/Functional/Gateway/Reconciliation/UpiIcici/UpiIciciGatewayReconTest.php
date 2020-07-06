<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\UpiIcici;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use RZP\Models\Base\PublicEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class UpiIciciGatewayReconTest extends TestCase
{
    use ReconTrait;
    use BatchTestTrait;

    /**
     * @var array
     */
    private $payment;

    private $sharedTerminal;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/UpiIciciReconTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_icici_terminal');

        $this->gateway = Payment\Gateway::UPI_ICICI;

        $this->fixtures->merchant->enableMethod(Merchant\Account::TEST_ACCOUNT, Payment\Method::UPI);
    }

    public function testReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiIciciRefundsSince($createdAt);

        $this->ba->appAuth();

        $fileContents = $this->generateReconFile(['type' => 'refund']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiIcici');

        $refunds = $this->getEntities('refund', [], true);

        foreach ($refunds['items'] as $refund)
        {
            $this->assertEquals('processed', $refund['status']);

            $this->assertEquals($refund['reference1'], '733817298334');

            $transactionId = $refund['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);

            // We hardcode 04-12-2017 05:09 PM in the upi icici reconciliator class
            $this->assertEquals(1512387540, $transaction['gateway_settled_at']);

            $upi = $this->getDbLastEntity('upi');

            $this->assertEquals($upi['npci_reference_id'], '733817298334');
        }

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testReconRefundAmountValidationFailure()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiIciciRefundsSince($createdAt);

        $this->ba->appAuth();

        $this->mockReconContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'col_icici_recon')
                {
                    // Setting random column value
                    $content['Refund Amount'] = 13476475;
                }
            },
            $this->gateway,
            [
                'type' => 'refund'
            ]);

        $fileContents = $this->generateReconFile(['type' => 'refund']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiIcici');

        $refunds = $this->getEntities('refund', [], true);

        // We do not reconcile refunds that fail the amount assertion step
        foreach ($refunds['items'] as $refund)
        {
            $transactionId = $refund['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNull($transaction['reconciled_at']);

            // We hardcode 04-12-2017 05:09 PM in the upi icici reconciliator class
            $this->assertEquals(1512387540, $transaction['gateway_settled_at']);
        }
    }

    public function testReconRefundNotFoundInDb()
    {
        $this->markTestSkipped();

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        list($refunds, $payments) = $this->makeUpiIciciRefundsSince($createdAt);

        $this->ba->appAuth();

        $this->mockReconContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'col_icici_recon')
                {
                    // Setting new unique id as column value
                    $content[4] = UniqueIdEntity::generateUniqueId();
                }
            },
            $this->gateway,
            [
                'type' => 'refund'
            ]);

        $fileContents = $this->generateReconFile(['type' => 'refund']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->updatePaymentStatusToCaptured($payments);

        //
        // The refund id's sent across in the recon file will not be found in the DB.
        // This will force refund recon to create new refunds to reconcile.
        //
        $this->reconcile($uploadedFile, 'UpiIcici');

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

            // TODO: Handle assertion for persisted gateway settled at value
        }
    }

    // Below are the payment recon test cases
    public function testPaymentReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $this->ba->appAuth();

        $fileContents = $this->generateReconFile(['type' => 'payment']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $this->assertNull($payment['reference16']);
        }

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {

            $this->assertEquals(true, $payment['gateway_captured']);

            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);

            $upi = $this->getDbLastEntity('upi');

            $this->assertEquals($upi['npci_reference_id'], '734122607521');

            $this->assertNotNull($payment['reference16']);

            $this->assertEquals($upi['npci_reference_id'], $payment['reference16']);

        }

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testPaymentIdAbsentReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        // We make just one payment
        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $this->ba->appAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_icici_recon')
                {
                    $content['banktranid'] = "";
                }
            },
            $this->gateway,
            [
                'type' => 'payment'
            ]);

        $this->assertFailedPaymentRecon();
    }

    public function testUpiIciciForceAuthorizePayment()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->fixtures->payment->edit($upiEntity['payment_id'],
            [
                'status'                => 'failed',
                'error_code'            => 'BAD_REQUEST_ERROR',
                'internal_error_code'   => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'     => 'Payment was not completed on time.',
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideUpiIciciPayment($upiEntity);

        $file = $this->writeToExcelFile($entries, 'mis_report','files/settlement','Recon MIS');

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payments = $this->getEntities('payment', [], true);

        $payment = $payments['items'][0];

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNotNull($transaction['reconciled_at']);

        $upi = $this->getDbLastEntity('upi');

        $this->assertNotNull($upi['npci_reference_id']);

        $this->assertEquals($upi['npci_reference_id'], '734122607521');

        $this->assertNotNull($payment['reference16']);

        $this->assertEquals($upi['npci_reference_id'], $payment['reference16']);
    }

    public function testPaymentReconciliationUsingRRN()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $upiEntity1 = $this->getDbLastEntityToArray('upi');

        $entries[] = $this->overrideUpiIciciPayment($upiEntity1);

        //passing wrong rrn number so recon for the row should get fail
        $entries[] = $this->overrideUpiIciciPayment($upiEntity1, '734122607522');

        $file = $this->writeToExcelFile($entries, 'mis_report','files/settlement','Recon MIS');

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payments = $this->getEntities('payment', [], true);

        $payment = $payments['items'][0];

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNotNull($transaction['reconciled_at']);

        $upi = $this->getDbLastEntity('upi');

        $this->assertEquals($upi['npci_reference_id'], '734122607521');

        $batch = $this->getLastEntity('batch', true);

        //reconciled for RRN 734122607521, Failed to reconcile for RRN 734122607522
        $this->assertEquals(2, $batch['total_count']);

        $this->assertEquals(1, $batch['success_count']);

        $this->assertEquals(1, $batch['failure_count']);

        $this->assertEquals('{"PAYMENT_ID_NOT_FOUND":1}', $batch['failure_reason']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    protected function overrideUpiIciciPayment(array $upiEntity, $gatewayPaymentId = null)
    {
        $facade                   = $this->testData['upiIcici'];

        $facade['amount']         = $upiEntity['amount'] / 100;

        $facade['bankTranID']     = $gatewayPaymentId ?? $upiEntity['npci_reference_id'];

        $facade['merchantTranID'] = $upiEntity['payment_id'];

        return $facade;
    }

    public function testReconPaymentFailedReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        // We make just one payment
        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $this->ba->appAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_icici_recon')
                {
                    $content['status'] = "failed";
                }
            },
            $this->gateway,
            [
                'type' => 'payment'
            ]);

        $this->assertFailedPaymentRecon();
    }

    public function testReconAmountValidationFailedReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';
        // We make just one payment
        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $this->ba->appAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_icici_recon')
                {
                    $content['amount'] = 1840913;
                }
            },
            $this->gateway,
            [
                'type' => 'payment'
            ]);

        $this->assertFailedPaymentRecon();
    }

    private function assertFailedPaymentRecon()
    {
        $fileContents = $this->generateReconFile(['type' => 'payment']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payment = $this->getLastEntity('payment', true);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNull($transaction['reconciled_at']);
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

    private function makeUpiIciciPaymentsSince(int $createdAt, string $rrn, int $count = 3)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiIciciPayment();

            $upiEntity = $this->getDbLastEntity('upi');

            $this->fixtures->edit('upi', $upiEntity['id'], ['npci_reference_id' => $rrn, 'gateway' => 'upi_icici']);
        }

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);
        }

        return $payments;
    }

    private function makeUpiIciciRefundsSince(int $createdAt, int $count = 3)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiIciciPayment();
        }

        $refunds = [];

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);

            $refund = $this->fixtures->create(
                        'refund',
                        [
                            'payment_id'  => $payment,
                            'merchant_id' => Merchant\Account::TEST_ACCOUNT,
                            'amount'      => $this->payment['amount'],
                            'base_amount' => $this->payment['amount'],
                            'gateway'     => 'upi_icici',
                        ]);

            $transaction = $this->fixtures->create(
                                'transaction',
                                [
                                    'entity_id' => $refund->getId(),
                                    'merchant_id' => '10000000000000'
                                ]);

            $this->fixtures->edit(
                'refund',
                $refund->getId(),
                [
                    'created_at' => $createdAt,
                    'transaction_id' => $transaction->getId()
                ]);

            $this->fixtures->create(
                'upi',
                [
                    'payment_id' => $payment,
                    'refund_id'  => PublicEntity::stripDefaultSign($refund['id']),
                    'action'     => Payment\Action::REFUND
                ]);

            $refunds[] = $refund['id'];
        }

        return [$refunds, $payments];
    }

    private function doUpiIciciPayment()
    {
        $attributes = [
            'terminal_id'       => $this->sharedTerminal->getId(),
            'method'            => 'upi',
            'amount'            => $this->payment['amount'],
            'base_amount'       => $this->payment['amount'],
            'amount_authorized' => $this->payment['amount'],
            'status'            => 'captured',
            'gateway'           => $this->gateway
        ];

        $payment = $this->fixtures->create('payment', $attributes);

        $transaction = $this->fixtures->create('transaction', ['entity_id' => $payment->getId(), 'merchant_id' => '10000000000000']);

        $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

        $this->fixtures->create('upi', ['payment_id' => $payment->getId()]);

        return $payment->getId();
    }
}
