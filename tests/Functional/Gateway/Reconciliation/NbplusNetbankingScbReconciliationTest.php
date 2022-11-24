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

class NbplusNetbankingScbReconciliationTest extends NbPlusPaymentServiceNetbankingTest
{
    use ReconTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingReconciliationTestData.php';

        parent::setUp();

        $this->bank = 'SCBL';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_scb_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testScbSuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $data = $this->testData[__FUNCTION__];

        $data['payment_id'] = $payment['id'];

        $reconFile = $this->generateReconFile($data);

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'],'trn-'.Carbon::today()->format("dmY").'.txt', "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_SCB);

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->assertEquals(1234, $paymentEntity['acquirer_data']['bank_transaction_id']);

        $this->assertEquals(1234, $paymentEntity['reference1']);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    public function testScbSuccessReconWithForceAuthorize()
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

        $data = $this->testData['testScbSuccessRecon'];

        $data['payment_id'] = $payment['id'];

        $reconFile = $this->generateReconFile($data);

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'],'dailyrecon-'.Carbon::today()->format("Ymd").'.txt', "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_SCB, [$payment['public_id']]);

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
        $formattedData = ''.$data['bank ref no.'].'|'.$data['payment_id'].'|'.$data['bank ref no.'].'|1234|500|'.$data['date'].'';

        return $this->createFile($formattedData);
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
