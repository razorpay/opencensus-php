<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Mockery;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Constants\Mode;
use RZP\Constants\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Transaction\Entity as Txn;
use RZP\Models\Payment\Entity as Payment;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Reconciliator\NetbankingHdfc\Constants;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class NbplusNetbankingHdfcReconciliationTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingReconciliationTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $this->bank = 'HDFC';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->app['rzp.mode'] = Mode::TEST;
        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Netbanking', [$this->app])->makePartial();
        $this->app->instance('nbplus.payments', $this->nbPlusService);
    }

    public function testHdfcSuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals(Payment::NB_PLUS_SERVICE, $payment[Payment::CPS_ROUTE]);
        $this->assertEquals(Payment::CAPTURED, $payment[Payment::STATUS]);

        $data = $this->testData[__FUNCTION__];

        $data[Constants::COLUMN_PAYMENT_ID] = $payment['id'];

        $reconFile = $this->generateReconFile($data);

        $fileName = 'RECONCILIATION_REPORT_ENTITYCODE_' . Carbon::today()->format("dmY") . '.csv';

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'], $fileName, "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_HDFC);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals('processed', $batch['status']);
    }

    protected function generateReconFile($data): array
    {
        $fileData = 'merchant_code,customer_email,currency,transaction_amount,fee,payment_id,error_code,bank_payment_id,transaction_date,error_description'. "\n" .implode('~', $data);

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
