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

class NbplusNetbankingKotakReconciliationTest extends StaticCallbackNbplusGatewayTest
{
    use ReconTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingReconciliationTestData.php';

        NbPlusPaymentServiceNetbankingTest::setUp();

        $this->bank = "KKBK";

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_kotak_terminal');

    }

    public function testKotakSuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);
        $data = $this->testData[__FUNCTION__];

        $data[Reconciliate::REFERENCE_NUMBER] = $payment['id'];
        $data['Int Payment ID'] = $payment['id'];

        $reconFile = $this->generateReconFile($data);

        $fileName = 'RECONCILIATION_REPORT_ENTITYCODE_osrazorpay_' . Carbon::today()->format("dmY") . '.csv';

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'], $fileName, "text/plain");

        $this->reconcile($uploadedFile, Base::KOTAK);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    public function testKotakRefundRecon()
    {
        $capturedPayment = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->refundPayment($capturedPayment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $data = $this->testData[__FUNCTION__];
        $data['REFUND MERCHANT REF NO'] = 'REFUND MERCHANT REF NO';
        $data['AMOUNT'] = 'AMOUNT';
        $data['AUTHORIZED DATE'] = 'AUTHORIZED DATE';
        $data['BANK REF NO'] = 'BANK REF NO';

        $data2 = $this->testData[__FUNCTION__];
        $data2['REFUND MERCHANT REF NO'] = substr($refund['id'], 5);

        $reconFile = $this->generateRefundReconFile($data, $data2);

        $fileName = 'PG_ONLINE_REFUND_'.Carbon::today()->format("d-m-Y").'.txt';

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'], $fileName, "text/plain");

        $this->reconcile($uploadedFile, Base::KOTAK);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    protected function generateReconFile($data)
    {
        $fileData = implode(',', $data);

        return $this->createFile($fileData);
    }

    protected function generateRefundReconFile($data, $data2)
    {
        $fileData = implode(',', $data);
        $fileData2 = implode(',', $data2);

        $data = nl2br($fileData."\n".$fileData2);

        return $this->createFile($data);
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
