<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\UpiHulkGatewayReconTest;

use RZP\Models\Batch\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class UpiHulkReconTest extends TestCase
{
    use BatchTestTrait;
    use ReconTrait;

    protected $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/UpiHulkReconTestData.php';

        $this->gateway = 'upi_hulk';

        parent::setUp();
    }

    public function testUpiHulkPaymentFile()
    {
        $this->fixtures->create('terminal:shared_upi_hulk_terminal');

        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntity = $this->getNewUpiHulkEntity('10000000000000', 'upi_hulk');

        $entries[] = $this->overrideUpiHulkPayment($upiEntity);

        $this->fixtures->create('terminal:shared_upi_hulk_intent_terminal');

        $this->payment = $this->getDefaultUpiPaymentArray();

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $upiEntity = $this->getNewUpiHulkEntity('10000000000000', 'upi_hulk');

        $entries[] = $this->overrideUpiHulkPayment($upiEntity);

        $file = $this->writeToCsvFile($entries, 'Razorpay_Transaction_Details');

        $uploadedFile = $this->createUploadedFileCsv($file);

        $this->reconcile($uploadedFile, 'UpiHulk');

        $this->assertBatchStatus(Status::PROCESSED);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertNotNull($transactionEntity['gateway_settled_at']);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->assertEquals($entries[1]['CUSTOMER_REF_NO'], $upiEntity['npci_reference_id']);

        $this->assertEquals($entries[1]['PAYER_VIRTUAL_ADDR'], $upiEntity['vpa']);

        $this->assertEquals($entries[1]['PAYER_IFSC_CODE'], $upiEntity['ifsc']);
    }

    public function testUpiHulkPaymentFileDuplicateTxnId()
    {
        $this->fixtures->create('terminal:shared_upi_hulk_terminal');

        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntity = $this->getNewUpiHulkEntity('10000000000000', 'upi_hulk');

        $entries[] = $this->overrideUpiHulkPayment($upiEntity);

        $this->fixtures->create('terminal:shared_upi_hulk_intent_terminal');

        $this->payment = $this->getDefaultUpiPaymentArray();

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $upiEntity = $this->getNewUpiHulkEntity('10000000000000', 'upi_hulk', false);

        $entries[] = $this->overrideUpiHulkPayment($upiEntity);

        $file = $this->writeToCsvFile($entries, 'Razorpay_Transaction_Details');

        $uploadedFile = $this->createUploadedFileCsv($file);

        $response = $this->reconcile($uploadedFile, 'UpiHulk');

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);
    }

    protected function overrideUpiHulkPayment($upiEntity)
    {
        $facade = $this->testData['upiHulk'];

        $facade['UPI_TRANSACTION_ID'] = $upiEntity['npci_txn_id'];

        $facade['CUSTOMER_REF_NO'] = $upiEntity['npci_reference_id'];

        $facade['PAYER_VIRTUAL_ADDR'] = $upiEntity['vpa'] ?? 'recon@razor';

        $facade['PAYER_IFSC_CODE'] = $upiEntity['ifsc'] ?? 'RAZP';

        return $facade;
    }
}
