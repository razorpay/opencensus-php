<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Constants\Entity;
use RZP\Models\Feature\Constants;
use RZP\Models\Transaction\Entity as Txn;
use RZP\Models\Payment\Entity as Payment;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Reconciliator\NetbankingKotakV2\Reconciliate;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\StaticCallbackNbplusGatewayTest;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingKotakV2ReconciliationTest extends StaticCallbackNbplusGatewayTest
{
    use ReconTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingReconciliationTestData.php';

        NbPlusPaymentServiceNetbankingTest::setUp();

        $this->bank = Netbanking::KKBK_C;

        $this->fixtures->merchant->addFeatures([Constants::CORPORATE_BANKS]);

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $terminalAttrs = [
            \RZP\Models\Terminal\Entity::CORPORATE => 1,
        ];

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_kotak_terminal', $terminalAttrs);

    }

    public function testKotakV2SuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $data = $this->testData[__FUNCTION__];

        $data[Reconciliate::REFERENCE_NUMBER] = $payment['id'];

        $reconFile = $this->generateReconFile($data);

        $fileName = 'RECONCILIATION_REPORT_ENTITYCODE_' . Carbon::today()->format("dmY") . '.csv';

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'], $fileName, "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_KOTAK_V2);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    protected function generateReconFile($data)
    {
        $fileData = 'Entity Code,Merchant Code,MCC Code,Party Name,Party CRN,From Apac,Transaction Amount,Charges,GST,Net Settlement Amount,Request Date,Entity Reference No,Bank Ref No,Payment Date,fc_process_date'. "\n" .implode(',', $data);

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
}
