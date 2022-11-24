<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Constants\Entity;
use RZP\Models\Payment\Status;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Transaction\Entity as Txn;
use RZP\Models\Payment\Entity as Payment;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Reconciliator\NetbankingSaraswat\Reconciliate;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingSrcbReconciliationTest extends NbPlusPaymentServiceNetbankingTest
{
    use ReconTrait;

    var $AMOUNT ;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingReconciliationTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_saraswat_terminal');

        $this->bank = 'SRCB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testSrcbSuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $data = $this->testData[__FUNCTION__];

        $data[Reconciliate::PAYMENT_ID] = $payment['id'];

        $reconFile = $this->generateReconFile($data);

        $fileName = 'Aggregator_PG_'.Carbon::today()->format("Ymd").'.txt';

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'], $fileName, "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_SARASWAT);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }


    protected function generateReconFile($data)
    {
        $fileData = "ENTRY_RMKS||'^'||TXN_ID||'^'||ENTRY_AMT||'^'||'Y'||'^'||TO_CHAR(VALUE_DATE,'YYYYMMDD')". "\n" .implode('^', $data);

        return $this->createFile($fileData);
    }
    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
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
