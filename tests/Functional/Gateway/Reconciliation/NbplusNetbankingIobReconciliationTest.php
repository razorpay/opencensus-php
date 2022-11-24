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
use RZP\Reconciliator\NetbankingIob\Reconciliate;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingIobReconciliationTest extends NbPlusPaymentServiceNetbankingTest
{
    use ReconTrait;

    var $AMOUNT ;
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingReconciliationTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_iob_terminal');

        $this->bank = 'IOBA';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testIobSuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $data = $this->testData[__FUNCTION__];

        $data[Reconciliate::REF_NUM] = $payment['id'];

        $reconFile = $this->generateReconFile($data);

        $fileName = 'RazorPay_'.Carbon::today()->format("mdY").'IOB'.'.IOB';

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'], $fileName, "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_IOB);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    public function testIobSuccessReconWithForceAuthorize()
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

        $data = $this->testData['testIobSuccessRecon'];

        $data[\RZP\Reconciliator\NetbankingIOB\Reconciliate::REF_NUM] = $payment['id'];

        $reconFile = $this->generateReconFile($data);

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'],'RazorPay_'.Carbon::today()->format("mdY").'IOB'.'.IOB', "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_IOB, [$payment['public_id']]);

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->assertEquals($paymentEntity['acquirer_data']['bank_transaction_id'], 123456);

        $this->assertEquals($paymentEntity['reference1'], 123456);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $paymentEntity = $this->getDbEntityById('payment', $payment['public_id']);

        $this->assertEquals($paymentEntity['status'], Status::AUTHORIZED);
        $this->assertEquals($paymentEntity['late_authorized'], true);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }


    protected function generateReconFile($data)
    {
        $out = implode("~", $data);

        $hash = hash("sha512",$out);

        $formattedData = 'Hash :'.$hash.',Count : '."1".',Amount :'.$data[Reconciliate::AMOUNT] . "\n" . implode('~', $data);

        return $this->createFile($formattedData);
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
