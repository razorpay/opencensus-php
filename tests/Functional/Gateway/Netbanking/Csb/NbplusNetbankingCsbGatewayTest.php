<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile;
use RZP\Excel\Import as ExcelImport;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Models\Transaction\Statement\Entity;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingCsbGatewayTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingCsbGatewayTestData.php';

        parent::setUp();

        $this->bank = 'CSBK';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_csb_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray('CSBK');
    }

    public function testNetbankingCsbCombinedFile()
    {
        Mail::fake();

        $paymentArray = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->doAuthCaptureAndRefundPayment($paymentArray);

        $refundEntity1 = $this->getDbLastEntity('refund');

        $transaction1 = $this->getDbLastEntityToArray('transaction');

        $this->doAuthCaptureAndRefundPayment($paymentArray, 500);

        $refundEntity2 = $this->getDbLastEntity('refund');

        $transaction2 = $this->getDbLastEntityToArray('transaction');

        $this->assertEquals(1, $refundEntity1['is_scrooge']);
        $this->assertEquals(1, $refundEntity2['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1, $refundEntity2]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $tran1 = $this->getDbEntityById('transaction', $transaction1['id']);
        $tran2 = $this->getDbEntityById('transaction', $transaction2['id']);

        $this->assertNotNull($tran1[Entity::RECONCILED_AT]);
        $this->assertNotNull($tran2[Entity::RECONCILED_AT]);

        $file = $this->getLastEntity(ConstantsEntity::FILE_STORE, true);

        $this->checkRefundExcelData($content['items'][0], $file);

        $this->checkMailQueue($file);
    }

    protected function checkRefundExcelData(array $data, array $file)
    {
        $this->assertNotNull($data[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($data[File\Entity::SENT_AT]);
        $this->assertNull($data[File\Entity::FAILED_AT]);
        $this->assertNull($data[File\Entity::ACKNOWLEDGED_AT]);

        $filePath = storage_path('files/filestore') . '/' . $file['location'];

        $this->assertTrue(file_exists($filePath));

        $refundsFileContents = (new ExcelImport)->toArray($filePath)[0];

        $refundAmounts = [500, 5];

        array_map(
            function($amount, $index) use ($refundsFileContents)
            {
                // We increment $ind in the local scope so that srno = $ind = 1
                $refund = $refundsFileContents[$index];

                $this->assertEquals(++$index, $refund['srno']);
                $this->assertEquals(500, $refund['txn_amountrs_ps']);
                $this->assertEquals($amount, $refund['refund']);
            },
            $refundAmounts,
            array_keys($refundAmounts)
        );

        $this->assertEquals(2, count($refundsFileContents));

        unlink($filePath);
    }

    protected function checkMailQueue(array $file)
    {
        Mail::assertSent(DailyFile::class, function ($mail) use ($file)
        {
            $this->assertEquals(1000, $mail->viewData['amount']['claims']);
            $this->assertEquals(505, $mail->viewData['amount']['refunds']);
            $this->assertEquals(495, $mail->viewData['amount']['total']);

            $this->assertEquals('2', $mail->viewData['count']['claims']);
            $this->assertEquals('2', $mail->viewData['count']['refunds']);
            $this->assertEquals('4', $mail->viewData['count']['total']);

            return true;
        });
    }

}
