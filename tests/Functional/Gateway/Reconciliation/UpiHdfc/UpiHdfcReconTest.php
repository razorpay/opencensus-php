<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\UpiHdfc;

use RZP\Models\QrCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Batch\Status;
use RZP\Models\VirtualAccount;
use RZP\Models\Base\PublicEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Reconciliator\Base\Constants;
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

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $this->assertNull($payment['reference16']);
        }

        $upiEntity1 = $this->getNewUpiEntity('10000000000000', 'upi_mindgate');

        $upiEntity2 = $this->getNewUpiEntity('10000000000000', 'upi_mindgate');

        $entries[] = $this->overrideUpiHdfcPayment($upiEntity1);

        $row = $this->overrideUpiHdfcPayment($upiEntity2);

        // Change the settlement date format for this row, to test
        // that this format is being parsed correctly without error.
        $row['Settlement Date'] = '08/19/2018';

        $entries[] = $row;

        $file = $this->writeToExcelFile($entries, 'upiHdfc');

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiHdfc');

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $this->assertNotNull($payment['reference16']);

            $this->assertEquals($entries[1]['Txn ref no. (RRN)'], $payment['reference16']);
        }

        $this->assertBatchStatus(Status::PROCESSED);

        $updatedPayment1 = $this->getDbEntityById('payment', $upiEntity1['payment_id']);
        $updatedPayment2 = $this->getDbEntityById('payment', $upiEntity2['payment_id']);

        $transactionEntity1 = $this->getDbEntityById('transaction', $updatedPayment1['transaction_id']);
        $transactionEntity2 = $this->getDbEntityById('transaction', $updatedPayment2['transaction_id']);

        $this->assertNotNull($transactionEntity1['reconciled_at']);
        $this->assertNotNull($transactionEntity2['reconciled_at']);

        $this->assertNotNull($transactionEntity1['gateway_settled_at']);
        $this->assertNotNull($transactionEntity2['gateway_settled_at']);

        $upiEntity2 = $this->getDbLastEntityToArray('upi');

        $this->assertNotNull($upiEntity2['reconciled_at']);
        $this->assertEquals($entries[1]['Txn ref no. (RRN)'], $upiEntity2['npci_reference_id']);
    }

    public function testUpiHdfcReconPaymentFileViaBatchServiceRoute()
    {
        $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->payment = $this->getDefaultUpiPaymentArray();

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $this->assertNull($payment['reference16']);
        }

        $upiEntity1 = $this->getNewUpiEntity('10000000000000', 'upi_mindgate');

        $upiEntity2 = $this->getNewUpiEntity('10000000000000', 'upi_mindgate');

        $entries[] = $this->overrideUpiHdfcPayment($upiEntity1);

        $row = $this->overrideUpiHdfcPayment($upiEntity2);

        // Change the settlement date format for this row, to test
        // that this format is being parsed correctly without error.
        $row['Settlement Date'] = '08/19/2018';

        $entries[] = $row;

        // add metadata info to each row
        foreach ($entries as $key => $entry)
        {
            $entry[Constants::GATEWAY]          = 'UpiHdfc';
            $entry[Constants::SUB_TYPE]         = 'payment';
            $entry[Constants::SOURCE]           = 'manual';
            $entry[Constants::SHEET_NAME]       = 'sheet0';
            $entry[Constants::IDEMPOTENT_ID]    = 'batch_' . str_random(14);

            $entries[$key] = $entry;
        }

        $this->runWithData($entries);

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $this->assertNotNull($payment['reference16']);

            $this->assertEquals($entries[1]['Txn ref no. (RRN)'], $payment['reference16']);
        }

        $updatedPayment1 = $this->getDbEntityById('payment', $upiEntity1['payment_id']);
        $updatedPayment2 = $this->getDbEntityById('payment', $upiEntity2['payment_id']);

        $transactionEntity1 = $this->getDbEntityById('transaction', $updatedPayment1['transaction_id']);
        $transactionEntity2 = $this->getDbEntityById('transaction', $updatedPayment2['transaction_id']);

        $this->assertNotNull($transactionEntity1['reconciled_at']);
        $this->assertNotNull($transactionEntity2['reconciled_at']);

        $this->assertNotNull($transactionEntity1['gateway_settled_at']);
        $this->assertNotNull($transactionEntity2['gateway_settled_at']);

        $upiEntity1 = $this->getDbEntityById('upi', $upiEntity1['id']);
        $this->assertNotNull($upiEntity1['reconciled_at']);

        $upiEntity2 = $this->getDbEntityById('upi', $upiEntity2['id']);
        $this->assertNotNull($upiEntity2['reconciled_at']);

        $this->assertEquals($entries[1]['Txn ref no. (RRN)'], $upiEntity2['npci_reference_id']);
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

    public function testUpiHdfcReconRefundFileViaBatchServiceRoute()
    {
        $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntityPayment = $this->getNewUpiEntity('10000000000000', 'upi_mindgate');

        $paymentId = $upiEntityPayment['payment_id'];

        $this->capturePayment('pay_' . $paymentId, 50000 );

        $this->createUpiHdfcRefund($paymentId, 50000);

        $upiEntityRefund = $this->getDbLastEntityToArray('upi');

        $refund = $this->getDbLastEntityToArray('refund');

        $this->assertNull($refund['reference1']);

        $entries[] = $this->overrideUpiHdfcRefund($paymentId, $upiEntityRefund);

        // add metadata info to each row
        foreach ($entries as $key => $entry)
        {
            $entry[Constants::GATEWAY]          = 'UpiHdfc';
            $entry[Constants::SUB_TYPE]         = 'refund';
            $entry[Constants::SOURCE]           = 'manual';
            $entry[Constants::SHEET_NAME]       = 'sheet0';
            $entry[Constants::IDEMPOTENT_ID]    = 'batch_' . str_random(14);

            $entries[$key] = $entry;
        }

        $this->runWithData($entries);

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
                'authorized_at'         => null,
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideUpiHdfcPayment($upiEntity);

        $file = $this->writeToExcelFile($entries, 'UpiHdfc');

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiHdfc', ['pay_'. $payment['id']]);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('paytessy@hdfc', $updatedPayment['vpa']);

        $this->assertEquals('authorized', $updatedPayment['status']);
    }

    public function testUpiHdfcForceAuthorizeQrCodePayment()
    {
        $this->gateway = 'upi_mindgate';

        // First Fix the terminal
        $terminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal', [
            'gateway_merchant_id'   => 'HDFC000000000',
        ]);

        $this->payment = $this->getDefaultUpiPaymentArray();

        $va = new VirtualAccount\Entity();
        $va->forceFill([
            'id'                => 'ThisIsVaNotVpa',
            'merchant_id'       => '10000000000000',
            'notes'             => [],
            'amount_expected'   => 50000,
            'status'            => 'active',
        ]);
        $va->saveOrFail();

        $randomId = str_random(14);

        $qrCode = new QrCode\Entity();
        $qrCode->forceFill([
            'id'            => $randomId,
            'merchant_id'   => '10000000000000',
            'provider'      => 'upi_qr',
            'reference'     => $randomId,
            'entity_id'     => $va->getId(),
            'entity_type'   => 'virtual_account',
        ]);
        $qrCode->saveOrFail();

        $va->setAttribute('qr_code_id', $randomId);
        $va->saveOrFail();

        $this->mockServerContentFunction(
            function(& $content, $action = null) use ($qrCode, $terminal)
            {
                if ($action === 'callback')
                {
                    $content[0] = $terminal['razorpay upi mindgate'];
                    $content[1] = $qrCode->getId();
                }
            });

        $upiEntity = $this->getNewUpiEntity('10000000000000', 'upi_mindgate', $this->getMockServer());

        $this->assertArraySubset([
            'merchant_reference' => $randomId
        ], $upiEntity);

        // Force failing the payment
        $this->fixtures->payment->edit($upiEntity['payment_id'],
            [
                'status'                => 'failed',
                'error_code'            => 'BAD_REQUEST_ERROR',
                'internal_error_code'   => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'     => 'Payment was not completed on time.',
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideUpiHdfcPayment(array_merge($upiEntity, [
            'payment_id'  => $qrCode->getId(),
        ]));

        $file = $this->writeToExcelFile($entries, 'UpiHdfc');

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiHdfc', ['pay_'. $payment['id']]);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);
    }

    public function testUpiHdfcRevalidateMissingQrCodePayment()
    {
        $this->gateway = 'upi_mindgate';

        // First Fix the terminal
        $terminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal', [
            'gateway_merchant_id'   => 'HDFC000000000',
        ]);

        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->payment = $this->getDefaultUpiPaymentArray();

        $va = new VirtualAccount\Entity();
        $va->forceFill([
            'id'                => 'ThisIsVaNotVpa',
            'merchant_id'       => '10000000000000',
            'notes'             => [],
            'amount_expected'   => 50000,
            'status'            => 'active',
        ]);
        $va->saveOrFail();

        $randomId = str_random(14);

        $qrCode = new QrCode\Entity();
        $qrCode->forceFill([
            'id'            => $randomId,
            'merchant_id'   => '10000000000000',
            'provider'      => 'upi_qr',
            'reference'     => $randomId,
            'entity_id'     => $va->getId(),
            'entity_type'   => 'virtual_account',
            'amount'        => 50000,
        ]);
        $qrCode->saveOrFail();

        $va->setAttribute('qr_code_id', $randomId);
        $va->saveOrFail();

        // We are directly initiating the recon with out callback
        $entry = $this->overrideUpiHdfcPayment([
            'npci_reference_id' => '012301230123',
            'payment_id'        => $randomId,
        ]);

        // Which is saved in terminal.gateway_merchant_id
        $entry['Upi Merchant Id'] = 'HDFC000000000';

        // Valid vpa is need to create the payment
        $entry['Payer VPA'] = 'paytest@hdfcbank';

        $entries[] = $entry;

        $file = $this->writeToExcelFile($entries, 'UpiHdfc');

        $uploadedFile = $this->createUploadedFile($file);

        // No payment is there in the system
        $this->assertCount(0, $this->getDbEntities('payment'));

        $this->reconcile($uploadedFile, 'UpiHdfc');

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            'merchant_id'   => '10000000000000',
            'amount'        => 50000,
            'status'        => 'captured',
            'receiver_id'   => $randomId,
            'receiver_type' => 'qr_code',
            'vpa'           => 'paytest@hdfcbank',
            'reference16'   => '012301230123',
        ], $payment->toArray(), true);

        $upi = $this->getDbLastUpi();

        $this->assertArraySubset([
            'payment_id'            => $payment->getId(),
            'action'                => 'authorize',
            'type'                  => 'pay',
            'merchant_reference'    => $randomId,
            'npci_reference_id'     => '012301230123',
        ], $upi->toArray(), true);

        // Not only the payment got created, it will also be reconciled
        $this->assertNotEmpty($upi->getReconciledAt());
        $this->assertNotEmpty($payment->transaction->getReconciledAt());
    }

    public function testUpiHdfcUnexpectedPaymentRrnFormat()
    {
        $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntity = $this->getNewUpiEntity('10000000000000', 'upi_mindgate');

        $this->fixtures->upi->edit($upiEntity['id'],
            [
                 'npci_reference_id' => '001234567890'
            ]);

        $entries[] = $this->overrideUpiHdfcPayment([
            'payment_id'          => 'EHloDoL0yeRPV0123',
            'npci_reference_id'   => '1234567890'
        ]);

        $file = $this->writeToExcelFile($entries, 'UpiHdfc');

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiHdfc');

        $transaction = $this->getDbLastEntityToArray('transaction');

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertNotNull($transaction['gateway_settled_at']);
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

        $facade['Transaction Amount'] = ($upiEntity['amount'] / 100);

        return $facade;
    }

    public function runWithData($entries)
    {
        $this->ba->batchAuth();

        $testData = $this->testData['bulk_reconcile_via_batch_service'];

        $testData['request']['content']= $entries;

        $this->runRequestResponseFlow($testData);
    }
}
