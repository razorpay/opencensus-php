<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Constants\Entity;
use RZP\Models\Payment\Status;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Transaction\Entity as Txn;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingTmbReconciliationTest extends NbPlusPaymentServiceNetbankingTest
{
    use ReconTrait;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingReconciliationTestData.php';

        parent::setUp();

        $this->bank = 'TMBL';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_tmb_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testTmbSuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);

        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $data = $this->testData[__FUNCTION__];

        $data['Merchant TRN'] = $payment['id'];

        $data['Transaction Amount'] = $payment['amount'] / 100;

        $reconFile = $this->generateReconFile($data);

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'],'TMB_RazorPG_ '.Carbon::today()->format("d-M-Y").'.txt', "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_TMB);

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->assertEquals(1234, $paymentEntity['acquirer_data']['bank_transaction_id']);

        $this->assertEquals(1234, $paymentEntity['reference1']);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    protected function generateReconFile($data)
    {

        $formattedData = '1,'. $data['Merchant TRN'] .','. $data['Transaction Amount'] .','. $data['Payment Remarks'] .','.$data['Bank Reference No'].',08122021,Test message';

        return $this->createFile($formattedData);
    }

    public function createUploadedFile(string $url, $fileName = 'file.txt', $mime = "text/plain"): UploadedFile
    {
        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true);
    }

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
