<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\UpiAxis;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Error\Action;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Batch\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class UpiAxisReconTest extends TestCase
{
    use ReconTrait;
    use BatchTestTrait;

    private $payment;
    private $refund;
    private $sharedTerminal;

    public function setUp()
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

        $file = $this->writeToExcelFile($entries, 'Razorpay Software Pvt Ltd');

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiAxis');

        $this->assertBatchStatus(Status::PROCESSED);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->assertEquals($entries[0]['RRN'], $upiEntity['npci_reference_id']);

        $this->assertEquals($entries[0]['TXNID'], $upiEntity['gateway_payment_id']);
    }

    public function testUpiAxisNewPaymentFile()
    {
        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntity = $this->getNewAxisUpiEntity('10000000000000', 'upi_axis');

        $entries[] = $this->overrideNewUpiAxisPayment($upiEntity);

        $file = $this->writeToExcelFile($entries, 'Razorpay Software Pvt Ltd');

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiAxis');

        $this->assertBatchStatus(Status::PROCESSED);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->assertEquals($entries[0]['RRN'], $upiEntity['npci_reference_id']);

        $this->assertEquals($entries[0]['TXNID'], $upiEntity['gateway_payment_id']);
    }

    public function testRefundReconciliation()
    {
        // Disabled refund recon because bank is not sending unique refund identifiers in the refund recon file
        $this->markTestSkipped('Disabled refund recon');

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiAxisRefundsSince($createdAt);

        $this->ba->appAuth();

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

    protected function overrideUpiAxisPayment(array $upiEntity)
    {
        $facade = $this->testData['upiAxis'];

        $facade['ORDER_ID'] = $upiEntity['payment_id'];

        $facade['RRN'] = $upiEntity['npci_reference_id'];

        return $facade;
    }

    protected function overrideNewUpiAxisPayment(array $upiEntity)
    {
        $facade = $this->testData['upiAxisNew'];

        $facade['ORDERID'] = $upiEntity['payment_id'];

        $facade['RRN'] = $upiEntity['npci_reference_id'];

        return $facade;
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

    protected function mockRefundData()
    {
        $array = $this->testData['upiAxisRefund'];

        $paymentDate = Carbon::createFromTimestamp($this->payment['created_at'], Timezone::IST)->format('d-M-y');

        $refundDate = Carbon::createFromTimestamp($this->refund['created_at'], Timezone::IST)->format('d-M-y');

        $array['ORDER_ID']          = $this->refund['id'];
        $array['TRANSACTION_DATE']  = $paymentDate;
        $array['TXN_REF_DATE']      = $refundDate;

        return $array;
    }

    public function createUploadedFile(string $url): UploadedFile
    {
        $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
            $url,
            'Razorpay Software Pvt Ltd.xlsx',
            $mime,
            filesize($url),
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
            filesize($url),
            null,
            true);
    }
}
