<?php

use Mail;
use Queue;
use Illuminate\Http\UploadedFile;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use \RZP\Mail\CardlessEmiNceAdjustment\SuccessAxioAdjustmentSettlement as SuccessAxioAdjustmentSettlement;
use \RZP\Mail\CardlessEmiNceAdjustment\FailAxioAdjustmentSettlement as FailAxioAdjustmentSettlement;

class CardlessEmiNceAdjustmentTest extends TestCase
{
    use FileHandlerTrait;

    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/AutomatedCardlessEmiNceAdjustmentTestData.php';

        parent::setUp();

    }

    public function testAxioPolicyBazaarSuccessAdjustment(){
        Mail::fake();
        Queue::fake();

        $data[] = $this->testData['testAxioPolicyBazaarAdjustmentData'];

        $file = $this->writeToExcelFile($data, 'PolicyBazaarSettlement', 'files/filestore');

        $uploadedFile = $this->createUploadedFile($file, 'earlysalary_recon_file.xlsx');

        $input = [
            'X-Original-Sender' => 'capitalfloat.com@holistics.io',
            'subject'           => 'PolicyBazaar MIS for 2024-04-10 Mon',
            'recipient'         => 'Pg-settlements@razorpay.com',
            'timestamp'         => '1624300396',
        ];

        $request = [
            'url'     => '/cardless_emi/insurance_nce_adjustments/file',
            'content' => $input,
            'method'  => 'POST',
            'files'   => [
                'attachment-1' => $uploadedFile,
            ],
        ];

        $this->fixtures->merchant->createAccount(Account::AXIO_DEMO_ACCOUNT);
        $this->fixtures->merchant->createAccount(Account::PB_DEMO_ACCOUNT);

        $axioBalance = $this->getDbEntityById('balance', Account::AXIO_DEMO_ACCOUNT);
        $pbBalance = $this->getDbEntityById('balance', Account::PB_DEMO_ACCOUNT);

        $this->ba->cronAuth();

        Mail::assertQueued(SuccessAxioAdjustmentSettlement::class);
        //Mail::assertQueued(SuccessPolicyBazaarAdjustmentSettlement::class);
        Mail::assertQueued(FailAxioAdjustmentSettlement::class);


        $this->makeRequestAndGetContent($request);

        $this->assertEquals(1100000, $axioBalance->reload()['balance']);
        $this->assertEquals(900000, $pbBalance->reload()['balance']);


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
