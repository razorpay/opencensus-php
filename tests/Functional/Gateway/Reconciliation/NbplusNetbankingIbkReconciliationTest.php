<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Transaction\Entity as Txn;
use RZP\Models\Payment\Entity as Payment;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Gateway\Mozart\NetbankingIbk\ReconFields;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingIbkReconciliationTest extends NbPlusPaymentServiceNetbankingTest
{
    use ReconTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingReconciliationTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_ibk_terminal');

        $this->bank = 'IDIB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testIbkSuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals(Payment::NB_PLUS_SERVICE, $payment[Payment::CPS_ROUTE]);
        $this->assertEquals(Payment::CAPTURED, $payment[Payment::STATUS]);

        $data[] = $this->testData[__FUNCTION__];

        $data[0][ReconFields::MERCHANT_REF_NO] = $payment['id'];

        $data[0][ReconFields::DATE_TIME] = Carbon::createFromTimestamp($payment['created_at'], Timezone::IST)->format('dmY');

        $reconFile = $this->generateReconFile($data);

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'], 'IbkReconTest.txt', 'text/plain');

        $this->reconcile($uploadedFile, Base::NETBANKING_IBK);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);
        $this->assertEquals($transactionEntity[Txn::GATEWAY_AMOUNT], $payment[Payment::AMOUNT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals('processed', $batch['status']);
    }

    protected function generateReconFile($data)
    {
        $formattedData = $this->generateText($data, '|', true);

        return $this->createFile($formattedData);
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

    protected function generateText($data, $glue = '~', $ignoreLastNewline = false)
    {
        $txt = '';

        $count = count($data);

        foreach ($data as $row)
        {
            $txt .= implode($glue, array_values($row));

            $count--;

            if (($ignoreLastNewline === false) or
                (($ignoreLastNewline === true) and ($count > 0)))
            {
                $txt .= "\r\n";
            }
        }

        return $txt;
    }
}
