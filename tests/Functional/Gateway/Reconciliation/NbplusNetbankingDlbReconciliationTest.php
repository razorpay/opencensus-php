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

class NbplusNetbankingDlbReconciliationTest extends NbPlusPaymentServiceNetbankingTest
{
    use ReconTrait;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingReconciliationTestData.php';

        parent::setUp();

        $this->bank = 'DLXB';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_dlb_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testDlbSuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);

        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $data = $this->testData[__FUNCTION__];

        $data['payment_id'] = $payment['id'];

        $data['amount'] = $payment['amount'] / 100;

        $reconFile = $this->generateReconFile($data);

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'],'DLB_RazorPG_ '.Carbon::today()->format("d-M-Y").'.txt', "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_DLB);

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->assertEquals(1234, $paymentEntity['acquirer_data']['bank_transaction_id']);

        $this->assertEquals(1234, $paymentEntity['reference1']);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    public function testAusfSuccessReconWithForceAuthorize()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === NbPlusPaymentService\Action::AUTHORIZE)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            NbPlusPaymentService\Error::MOZART_ERROR_CODE => 'BAD_REQUEST_PAYMENT_FAILED'
                        ]

                    ],
                ];
            }
        });

        $this->makeRequestAndCatchException(
            function()
            {
                $this->doAuthPayment($this->payment);
            },
            GatewayErrorException::class);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Status::FAILED);

        $data = $this->testData['testDlbSuccessRecon'];

        $data['payment_id'] = $payment['id'];

        $data['amount'] = $this->getFormattedAmount($payment['amount']);

        $reconFile = $this->generateReconFile($data);

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'],'DLB_RazorPG_ '.Carbon::today()->format("d-M-Y").'.txt', "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_DLB);

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

        $formattedData = '1|RAZORPG001|20210303|'. $data['payment_id'] .'|'. $data['BankRefNo'] .'|'. $data['amount'] .'|015500100079670|SAVINGS BANK-RESIDENT|1234';

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
