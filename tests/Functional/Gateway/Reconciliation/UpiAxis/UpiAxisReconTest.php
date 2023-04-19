<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\UpiAxis;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use RZP\Exception;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Models\Base\PublicEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Reconciliator\Base\Reconciliate;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Reconciliator\Base\SubReconciliator\ManualReconciliate;

class UpiAxisReconTest extends TestCase
{
    use ReconTrait;
    use BatchTestTrait;

    private $payment;
    private $refund;
    private $sharedTerminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/UpiAxisReconTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_axis_terminal');

        $this->gateway = Payment\Gateway::UPI_AXIS;

        $this->fixtures->merchant->enableMethod(Merchant\Account::TEST_ACCOUNT, Payment\Method::UPI);
    }

    public function testUpiAxisPaymentFile()
    {
        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntity = $this->getNewAxisUpiEntity('10000000000000', 'upi_axis');

        $entries[] = $this->overrideUpiAxisPayment($upiEntity);

        $this->createFileAndReconcile('Razorpay Software Pvt Ltd.xlsx', $entries);
    }

    public function testUpiAxisDirectSettlementPaymentFile()
    {
        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntity = $this->getNewAxisUpiEntity('10000000000000', 'upi_axis');

        $entries[] = $this->overrideUpiAxisPayment($upiEntity);

        $this->createFileAndReconcile('Razorpay Software Private Limited.xlsx', $entries);
    }

    public function testUpiAxisDirectSettlementPaymentFileForceCreate()
    {
        $this->sharedTerminal->fill([
            'gateway_merchant_id' => 'shared_merchant',
        ])->saveOrFail();

        $terminal = $this->fixtures->create('terminal:direct_settlement_upi_axis_terminal');

        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntity = [
            'payment_id'                => 'SomeUnexpectedOrderId',
            'npci_reference_id'         => '000100010001',
        ];

        $entries[] = $this->overrideUpiAxisPayment($upiEntity);

        $entries[0]['amount']                       = '600.00';

        // Adds additional columns as needed for unexpected payment creation
        $entries[0]['unexpected_payment_ref_id']    = '000100010001';
        $entries[0]['upi_merchant_id']              = 'TSTMERCHI';
        $entries[0]['upi_merchant_channel_id']      = 'TSTMERCHIAPP';

        $this->createFileAndReconcile('Razorpay Software Private Limited.xlsx', $entries);

        $upi = $this->getDbLastEntity('upi');

        $this->assertNotNull($upi->getReconciledAt());

        $this->assertArraySubset([
            'merchant_reference'    => 'SomeUnexpectedOrderId',
            'npci_reference_id'     => '000100010001',
            'gateway_merchant_id'   => 'TSTMERCHI'
        ], $upi->toArray());
    }

    public function testUpiAxisNewPaymentFile()
    {
        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntity = $this->getNewAxisUpiEntity('10000000000000', 'upi_axis');

        $entries[] = $this->overrideNewUpiAxisPayment($upiEntity);

        $this->createFileAndReconcile('Razorpay Software Pvt Ltd.xlsx', $entries);
    }

    public function testUpiAxisPaymentFileV2()
    {
        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntity = $this->getNewAxisUpiEntity('10000000000000', 'upi_axis');

        $entries[] = $this->overrideNewUpiAxisPayment($upiEntity, 'upi_axis_payment_format_v2');

        $this->createFileAndReconcile('Razorpay Software Pvt Ltd.xlsx', $entries);
    }

    public function testUpiAxisUnexpectedPaymentFile()
    {
        $this->payment = $this->getDefaultPaymentArray();

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $this->assertNull($payment['reference16']);
        }
        $upiEntity = $this->getNewAxisUpiEntity('10000000000000', 'upi_axis');

        $upiEntity['payment_id'] = 'BB31121900923519425756';

        $entries[] = $this->overrideUpiAxisPayment($upiEntity);

        $this->createFileAndReconcile('Razorpay Software Pvt Ltd.xlsx', $entries);

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $this->assertNotNull($payment['reference16']);

            $this->assertEquals($entries[0]['RRN'], $payment['reference16']);
        }
    }

    protected function createFileAndReconcile($fileName = '', $entries = [])
    {
        $file = $this->writeToExcelFile($entries, $fileName);

        $uploadedFile = $this->createUploadedFile($file, $fileName);

        $this->reconcile($uploadedFile, 'UpiAxis');

        $this->assertBatchStatus(Status::PROCESSED);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->assertEquals($entries[0]['RRN'], $upiEntity['npci_reference_id']);

        $this->assertEquals($entries[0]['TXNID'], $upiEntity['gateway_payment_id']);
    }

    public function testUpiAxisManualReconPaymentFile()
    {
        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntity = $this->getNewAxisUpiEntity('10000000000000', 'upi_axis');

        $entries[] = $this->overrideNewUpiAxisManualReconFile($upiEntity);

        $file = $this->writeToExcelFile($entries, 'Razorpay Software Pvt Ltd');

        $uploadedFile = $this->createUploadedFile($file, 'Razorpay Software Pvt Ltd.xlsx');

        $this->reconcile($uploadedFile, 'UpiAxis', [], true);

        $this->assertBatchStatus(Status::PROCESSED);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->assertEquals($entries[0]['reference_number'], $upiEntity['npci_reference_id']);
    }

    public function testUpiAxisForceAuthorizeFailedPayment()
    {
        $upiEntity = $this->getNewAxisUpiEntity('10000000000000', 'upi_axis');

        $entries[] = $this->overrideNewUpiAxisPayment($upiEntity);

        $file = $this->writeToExcelFile($entries, 'Razorpay Software Pvt Ltd');

        $uploadedFile = $this->createUploadedFile($file, 'Razorpay Software Pvt Ltd.xlsx');

        // set the payment status to 'failed' and try to reconcile it with force authorise
        $this->fixtures->edit('payment', $upiEntity['payment_id'], ['status' => Payment\Status::FAILED]);

        $this->reconcile($uploadedFile, 'UpiAxis', ['pay_' . $upiEntity['payment_id']]);

        $this->assertBatchStatus(Status::PROCESSED);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->assertEquals($entries[0]['RRN'], $upiEntity['npci_reference_id']);

        $this->assertEquals($entries[0]['TXNID'], $upiEntity['gateway_payment_id']);

        $this->assertNotNull($upiEntity['reconciled_at']);

        $updatedPayment = $this->getDbEntityById('payment', $upiEntity['payment_id']);

        $this->assertNotNull($updatedPayment['reference16']);

        $this->assertEquals($entries[0]['VPA'], $upiEntity['vpa']);

        $this->assertEquals($entries[0]['VPA'], $updatedPayment['vpa']);

        $this->assertEquals('authorized', $updatedPayment['status']);
    }

    public function testRefundReconciliation()
    {
        // Disabled refund recon because bank is not sending unique refund identifiers in the refund recon file
        $this->markTestSkipped('Disabled refund recon');

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiAxisRefundsSince($createdAt);

        $this->ba->cronAuth();

        $entries[] = $this->mockRefundData();

        $file = $this->writeToExcelFile($entries, 'REFUND_Razorpay Software Pvt Ltd');

        $uploadedFile = $this->createRefundUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiAxis');

        $refunds = $this->getEntities('refund', [], true);

        foreach ($refunds['items'] as $refund)
        {
            $this->assertEquals('processed', $refund['status']);

            $transactionId = $refund['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);

            $upiEntity = $this->getDbLastEntity('upi');

            $this->assertSame($upiEntity[Entity::ACTION], Payment\Action::REFUND);
            $this->assertNotNull($upiEntity[Entity::NPCI_TXN_ID]);
            $this->assertNotNull($upiEntity[Entity::NPCI_REFERENCE_ID]);
        }

        $this->assertBatchStatus(Status::PROCESSED);
    }

    /**
     * We are allowing recon for refunds, which can
     * be uniquely identified by the given payment id
     * and amount in the MIS, since that's all we get
     * as of the moment.
     */
    public function testUpiAxisUniqueRefundRecon()
    {
        $this->payment = $this->doUpiAxisPayment();

        $this->ba->cronAuth();

        $refund1 = $this->refundPayment('pay_' . $this->payment['id'], 10000);
        $refund2 = $this->refundPayment('pay_' . $this->payment['id'], 20000);
        $refund3 = $this->refundPayment('pay_' . $this->payment['id'], 20000);

        $refundEntity1 = $this->getEntityById('refund', $refund1['id'], true);
        $refundEntity2 = $this->getEntityById('refund', $refund2['id'], true);
        $refundEntity3 = $this->getEntityById('refund', $refund3['id'], true);

        $this->assertNull($refundEntity1['reference1']);
        $this->assertNull($refundEntity2['reference1']);
        $this->assertNull($refundEntity3['reference1']);

        $entries[] = $this->mockRefundData('100.0');
        $entries[] = $this->mockRefundData('200.0');
        $entries[] = $this->mockRefundData('200.0');

        $file = $this->writeToExcelFile($entries, 'REFUND_Razorpay Software Pvt Ltd');
        $uploadedFile = $this->createRefundUploadedFile($file);
        $this->reconcile($uploadedFile, 'UpiAxis');

        $transaction1 = $this->getEntities('transaction', ['type' => 'refund', 'entity_id' => $refund1['id']], true);
        $transaction2 = $this->getEntities('transaction', ['type' => 'refund', 'entity_id' => $refund2['id']], true);
        $transaction3 = $this->getEntities('transaction', ['type' => 'refund', 'entity_id' => $refund3['id']], true);

        $refundEntity1 = $this->getEntityById('refund', $refund1['id'], true);
        $refundEntity2 = $this->getEntityById('refund', $refund2['id'], true);
        $refundEntity3 = $this->getEntityById('refund', $refund3['id'], true);

        $this->assertNotNull($refundEntity1['reference1']);
        $this->assertNull($refundEntity2['reference1']);
        $this->assertNull($refundEntity3['reference1']);

        $this->assertNotNull($transaction1['items'][0]['reconciled_at']);
        $this->assertNull($transaction2['items'][0]['reconciled_at']);
        $this->assertNull($transaction3['items'][0]['reconciled_at']);
    }

    public function testInvalidUpiAxisUpdateReconData()
    {
        $this->payment = $this->doUpiAxisPayment();

        $transaction = $this->getDbLastEntity('transaction');

        $this->fixtures->edit('transaction', $transaction['id'], ['reconciled_at' => Carbon::now(Timezone::IST)->getTimestamp()]);

        $content = $this->getDefaultUpiPostReconArray();

        $content['payment_id'] = $this->payment->getId();

        unset($content['upi']['npci_reference_id']);
        unset($content['reconciled_at']);

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'    => '/reconciliate/data',
                'method' => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class);
    }

    public function testUpiAxisUpdateAlreadyReconciled()
    {
        $this->payment = $this->doUpiAxisPayment();

        $transaction = $this->getDbLastEntity('transaction');

        $this->fixtures->edit('transaction', $transaction['id'], ['reconciled_at' => Carbon::now(Timezone::IST)->getTimestamp()]);

        $content = $this->getDefaultUpiPostReconArray();

        $content['payment_id'] = $this->payment->getId();

        $response = $this->makeUpdatePostReconRequestAndGetContent($content);

        $this->assertFalse($response['success']);

        $this->assertEquals('ALREADY_RECONCILED', $response['error']['code']);
    }

    public function testUpiAxisUpdatePostReconData()
    {
        $this->payment = $this->doUpiAxisPayment();

        $content = $this->getDefaultUpiPostReconArray();

        $content['payment_id'] = $this->payment->getId();

        $content['reconciled_at'] = Carbon::now(Timezone::IST)->getTimestamp();

        $content['upi']['vpa'] = "test@axis";

        $content['upi']['ifsc'] = "AXIS003421Y3T";

        $content['upi']['name'] = "TESTACCOUNT";

        $response = $this->makeUpdatePostReconRequestAndGetContent($content);

        $upiEntity = $this->getDbLastEntity('upi');

        $this->assertEquals('test@axis', $upiEntity['vpa']);

        $this->assertEquals('AXIS003421Y3T', $upiEntity['ifsc']);

        $this->assertEquals('TESTACCOUNT', $upiEntity['name']);

        // Assert empty reconciledAt in gateway entity
        $this->assertEmpty($upiEntity['reconciled_at']);

        $this->assertEmpty($upiEntity['gateway_settled_at']);

        $this->assertEquals($content['upi']['npci_reference_id'], $upiEntity['npci_reference_id']);

        $this->assertEquals($content['upi']['gateway_payment_id'], $upiEntity['gateway_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotEmpty($transactionEntity['reconciled_at']);

        $this->assertEmpty($transactionEntity['gateway_settled_at']);

        $this->assertTrue($response['success']);
    }

    protected function overrideUpiAxisPayment(array $upiEntity)
    {
        $facade = $this->testData['upiAxis'];

        $facade['ORDER_ID'] = $upiEntity['payment_id'];

        $facade['RRN'] = $upiEntity['npci_reference_id'];

        return $facade;
    }

    protected function overrideNewUpiAxisPayment(array $upiEntity, $format = 'upiAxisNew')
    {
        $facade = $this->testData[$format];

        $facade['ORDERID'] = $upiEntity['payment_id'];

        $facade['RRN'] = $upiEntity['npci_reference_id'];

        return $facade;
    }

    protected function overrideNewUpiAxisManualReconFile(array $upiEntity)
    {
        $row = [
            ManualReconciliate::RECON_TYPE      => Reconciliate::PAYMENT,
            ManualReconciliate::RECON_ID        => $upiEntity['payment_id'],
            ManualReconciliate::AMOUNT          => intval($upiEntity['amount'] / 100),
            Reconciliate::GATEWAY_FEE           => intval($upiEntity['amount'] / 1000),
            Reconciliate::GATEWAY_SERVICE_TAX   => intval($upiEntity['amount'] / 2000),
            Reconciliate::REFERENCE_NUMBER      => $upiEntity['npci_reference_id'],
        ];

        return $row;
    }

    private function makeUpiAxisRefundsSince(int $createdAt)
    {
        $this->payment = $this->doUpiAxisPayment();

        $refunds = [];

        $this->refund = $this->fixtures->create(
            'refund',
            [
                'payment_id'  => $this->payment['id'],
                'merchant_id' => Merchant\Account::TEST_ACCOUNT,
                'amount'      => $this->payment['amount'],
                'base_amount' => $this->payment['amount'],
                'gateway'     => 'upi_axis',
            ]);

        $transaction = $this->fixtures->create(
            'transaction',
            [
                'entity_id' => $this->refund->getId(),
                'merchant_id' => '10000000000000'
            ]);

        $this->fixtures->edit(
            'refund',
            $this->refund->getId(),
            [
                'created_at' => $createdAt,
                'transaction_id' => $transaction->getId()
            ]);

        $this->fixtures->create(
            'upi',
            [
                'payment_id' => $this->payment['id'],
                'refund_id'  => PublicEntity::stripDefaultSign($this->refund['id']),
                'action'     => Payment\Action::REFUND,
            ]);

        $refunds[] = $this->refund['id'];

        $payments[] = $this->payment['id'];

        return [$refunds, $payments];
    }

    private function doUpiAxisPayment()
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

        $transaction = $this->fixtures->create
        ('transaction', ['entity_id' => $payment->getId(), 'merchant_id' => '10000000000000']);

        $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

        $this->fixtures->create('upi', ['payment_id' => $payment->getId()]);

        return $payment;
    }

    protected function mockRefundData($amount = null)
    {
        $array = $this->testData['upiAxisRefund'];

        $paymentDate = Carbon::createFromTimestamp($this->payment['created_at'], Timezone::IST)->format('d-M-y');

        $refundDate = Carbon::createFromTimestamp($this->refund['created_at'], Timezone::IST)->format('d-M-y');

        $array['ORDER_ID']          = $this->payment['id'];
        $array['TRANSACTION_DATE']  = $paymentDate;
        $array['TXN_REF_DATE']      = $refundDate;
        $array['REFUND_AMOUNT']     = $amount ?? $array['AMOUNT'];

        return $array;
    }

    public function createUploadedFile(string $url, string $file_name): UploadedFile
    {
        $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
            $url,
            $file_name,
            $mime,
            null,
            true);
    }

    public function createRefundUploadedFile(string $url): UploadedFile
    {
        $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
            $url,
            'REFUND_Razorpay Software Pvt Ltd.xlsx',
            $mime,
            null,
            true);
    }

    private function makeUpdatePostReconRequestAndGetContent(array $input)
    {
        $request = [
            'method'  => 'POST',
            'content' => $input,
            'url'     => '/reconciliate/data',
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }
}
