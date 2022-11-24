<?php

namespace RZP\Tests\Functional\Gateway\File;

use Illuminate\Http\UploadedFile;

use Carbon\Carbon;

use RZP\Constants\Entity;
use RZP\Models\Transaction\Entity as Txn;
use RZP\Models\Payment\Entity as Payment;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingNsdlReconciliationTest extends NbPlusPaymentServiceNetbankingTest
{
    use ReconTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingReconciliationTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_nsdl_terminal');

        $this->bank = 'NSPB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNsdlSuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $data = $this->testData[__FUNCTION__];

        $data['PGTXNID'] = $payment['id'];

        $reconFile = $this->generateReconFile($data);

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'], 'NsdlReconTest.txt', "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_NSDL);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $this->assertEquals($transactionEntity[Txn::AMOUNT], $payment[Payment::AMOUNT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    protected function generateReconFile($data)
    {
        $fileData = implode(',', $data);

        return $this->createFile($fileData);
    }

    public function createUploadedFile(string $url, $fileName = 'file.txt', $mime = null): UploadedFile
    {
        $mime = $mime ?? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true);
    }

    public function testNsdlSuccessReconWithForceAuthorize()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);

        $data = $this->testData['testNsdlSuccessRecon'];

        $data['PGTXNID'] = $payment['id'];

        $reconFile = $this->generateReconFile($data);

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'],'Razorpay Tagit txn '.Carbon::today()->format("dmY").'.csv', "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_NSDL);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }
}
