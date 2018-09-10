<?php

use RZP\Models\Batch\Status;
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

    protected function overrideUpiHdfcPayment(array $upiEntity)
    {
        $facade = $this->testData['upiHdfc'];

        $facade['Order ID'] = $upiEntity['payment_id'];

        $facade['Txn ref no. (RRN)'] = $upiEntity['npci_reference_id'];

        return $facade;
    }
}
