<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\CardlessEmi;

use RZP\Constants\Entity;
use RZP\Models\Payment\Gateway;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Transaction\Entity as Txn;
use RZP\Models\Payment\Entity as Payment;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

Class CardlessEmiEarlySalaryReconTest extends TestCase
{
    use ReconTrait;

    use FileHandlerTrait;

    use PaymentTrait;

    use DbEntityFetchTrait;

    private $payment;

    protected $provider;

    private $method = 'cardless_emi';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/CardlessEmiEarlySalaryReconTestData.php';

        parent::setUp();

        $this->provider = 'earlysalary';

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_cardless_emi_terminal');

        $this->payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        $this->gateway = Gateway::CARDLESS_EMI;

        $this->fixtures->merchant->enableMethod('10000000000000', 'cardless_emi');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testEarlySalarySuccessRecon()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::API);
        $this->assertEquals($payment[Payment::STATUS], 'authorized');

        $data[] = $this->testData['testEarlySalarySuccessRecon'];

        $data[0]['Payment id / order id'] = substr($payment['id'], 4);
        $data[0]['Bank charges'] = '449.34';

        $file = $this->writeToExcelFile($data, 'earlysalary_recon_file', 'files/filestore');

        $uploadedFile = $this->createUploadedFile($file, 'earlysalary_recon_file.xlsx');

        $this->reconcile($uploadedFile, Base::CARDLESS_EMI_EARLYSALARY);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);
        $this->assertEquals($transactionEntity[Txn::AMOUNT], $payment[Payment::AMOUNT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }


    public function createUploadedFile(string $url, $fileName = 'file.xlsx', $mime = null): UploadedFile
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

