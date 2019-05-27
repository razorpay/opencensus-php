<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\UpiHdfc;

use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Batch\Status;
use RZP\Models\Base\PublicEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class UpiHdfcReconTest extends TestCase
{
    use ReconTrait;
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/UpiHdfcReconTestData.php';

        parent::setUp();
    }

    public function testUpiHdfcPaymentFile()
    {
        $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntity = $this->getNewUpiEntity('10000000000000', 'upi_mindgate');

        $entries[] = $this->overrideUpiHdfcPayment($upiEntity);

        $file = $this->writeToExcelFile($entries, 'upiHdfc');

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiHdfc');

        $this->assertBatchStatus(Status::PROCESSED);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertNotNull($transactionEntity['gateway_settled_at']);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->assertEquals($entries[0]['Txn ref no. (RRN)'], $upiEntity['npci_reference_id']);
    }

    public function testUpiHdfcRefundFile()
    {
        $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntityPayment = $this->getNewUpiEntity('10000000000000', 'upi_mindgate');

        $paymentId = $upiEntityPayment['payment_id'];

        $this->capturePayment('pay_' . $paymentId, 50000 );

        $this->createUpiHdfcRefund($paymentId, 50000);

        $upiEntityRefund = $this->getDbLastEntityToArray('upi');

        $entries[] = $this->overrideUpiHdfcRefund($paymentId, $upiEntityRefund);

        $file = $this->writeToExcelFile($entries, 'upi_hdfc_refund_report');

        $uploadedFile = $this->createUploadedFile($file, 'upi_hdfc_refund_report.xlsx');

        $refund = $this->getDbLastEntityToArray('refund');

        $this->assertEquals( 'created', $refund['status']);

        $this->assertNull($refund['reference1']);

        $this->reconcile($uploadedFile, 'UpiHdfc');

        $this->assertBatchStatus(Status::PROCESSED);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertNotNull($transactionEntity['gateway_settled_at']);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->assertEquals($entries[0]['Customer Ref No.'], $upiEntity['npci_reference_id']);

        $updatedRefund = $this->getDbLastEntityToArray('refund');

        $this->assertEquals($entries[0]['Customer Ref No.'], $updatedRefund['reference1']);

        $this->assertEquals('processed', $updatedRefund['status']);
    }

    public function testUpiHdfcForceAuthorizePayment()
    {
        $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntity = $this->getNewUpiEntity('10000000000000', 'upi_mindgate');

        $this->fixtures->payment->edit($upiEntity['payment_id'],
            [
                'status'                => 'failed',
                'error_code'            => 'BAD_REQUEST_ERROR',
                'internal_error_code'   => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'     => 'Payment was not completed on time.',
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideUpiHdfcPayment($upiEntity);

        $file = $this->writeToExcelFile($entries, 'UpiHdfc');

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiHdfc', ['pay_'. $payment['id']]);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);
    }

    protected function overrideUpiHdfcPayment(array $upiEntity)
    {
        $facade = $this->testData['upiHdfc'];

        $facade['Order ID'] = $upiEntity['payment_id'];

        $facade['Txn ref no. (RRN)'] = $upiEntity['npci_reference_id'];

        return $facade;
    }

    protected function createUpiHdfcRefund($paymentId, $amount)
    {
        $refund = $this->fixtures->create(
            'refund',
            [
                'payment_id'  => $paymentId,
                'merchant_id' => Merchant\Account::TEST_ACCOUNT,
                'amount'      => $amount,
                'base_amount' => $amount,
                'gateway'     => 'upi_mindgate',
                'is_scrooge'  => 1
            ]);

        $transaction = $this->fixtures->create(
            'transaction',
            [
                'entity_id' => $refund->getId(),
                'merchant_id' => Merchant\Account::TEST_ACCOUNT,
            ]);

        $this->fixtures->edit(
            'refund',
            $refund->getId(),
            [
                'status'    => 'created',
                'transaction_id' => $transaction->getId()
            ]);

        $this->fixtures->create(
            'upi',
            [
                'payment_id' => $paymentId,
                'refund_id'  => PublicEntity::stripDefaultSign($refund['id']),
                'action'     => Payment\Action::REFUND,
            ]);
    }
    protected function overrideUpiHdfcRefund($paymentId, array $upiEntity)
    {
        $facade = $this->testData['upiHdfcRefund'];

        $facade['Order No'] = $paymentId;

        $facade['New Refund Order ID'] = $upiEntity['refund_id'];

        return $facade;
    }
}
