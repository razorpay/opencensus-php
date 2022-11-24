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
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\StaticCallbackNbplusGatewayTest;
use RZP\Reconciliator\NetbankingHdfcCorp\Constants as HDFC_Constants;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingHdfcCorpReconciliationTest extends StaticCallbackNbplusGatewayTest
{
    use ReconTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingReconciliationTestData.php';

        NbPlusPaymentServiceNetbankingTest::setUp();

        $this->bank = Netbanking::HDFC_C;

        $this->fixtures->merchant->addFeatures([Constants::CORPORATE_BANKS]);

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $terminalAttrs = [
            \RZP\Models\Terminal\Entity::CORPORATE => 1,
        ];

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal', $terminalAttrs);

    }

    public function testHdfcCorpSuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $data = $this->testData[__FUNCTION__];

        $data[HDFC_Constants::COLUMN_PAYMENT_ID] = $payment['id'];

        $reconFile = $this->generateReconFile($data);

        $fileName = 'HDFC_Recon_' . Carbon::today()->format("dmY") . '.txt';

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'], $fileName, "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_HDFC_CORP);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');

    }

    protected function generateReconFile($data)
    {
        $fileData = 'Merchant Code~Client Code~Currency Code~Transaction Amount~Service Charge Amount~Merchant reference no~Status~Bank Reference No ~Transaction Date & Time~Error message'. "\n" .implode('~', $data);

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
