<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use RZP\Encryption;
use RZP\Constants\Entity;
use RZP\Models\FileStore;
use RZP\Models\Payment\Status;
use Illuminate\Http\UploadedFile;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Transaction\Entity as Txn;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingDbsReconciliationTest extends NbPlusPaymentServiceNetbankingTest
{
    use ReconTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingReconciliationTestData.php';

        parent::setUp();

        $this->bank = 'DBSS';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_dbs_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testDbsSuccessRecon()
    {
        $capturedPayment = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->refundPayment($capturedPayment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $data = $this->testData[__FUNCTION__];

        $data['MERCHANT_ORDER_ID'] = 'MERCHANT_ORDER_ID';
        $data['TRANSACTION_AMOUNT'] = 'TRANSACTION_AMOUNT';
        $data['TRANSACTION_REFERENCE_NUMBER'] = 'TRANSACTION_REFERENCE_NUMBER';
        $data['ORDER_TYPE'] = 'ORDER_TYPE';
        $data ['TRANSACTION_STATUS'] = 'TRANSACTION_STATUS';
        $data ['TRANSACTION_REQUESTED_DATE'] = 'TRANSACTION_REQUESTED_DATE';

        $data2 = $this->testData[__FUNCTION__];

        $data2['MERCHANT_ORDER_ID'] = $payment['id'];
        $data2['TRANSACTION_AMOUNT'] = $payment['amount'] / 100;
        $data2['TRANSACTION_REFERENCE_NUMBER'] = '123';
        $data2['ORDER_TYPE'] = 'MERCHANT_PAYMENT';
        $data2['TRANSACTION_STATUS'] = 'SUCCESS';
        $data2['TRANSACTION_REQUESTED_DATE'] = '2022-04-25T17:13:52';

        $data3 = $this->testData[__FUNCTION__];

        $data3['MERCHANT_ORDER_ID'] = substr($refund['id'], 5);
        $data3['TRANSACTION_AMOUNT'] = $refund['amount']/100;
        $data3['TRANSACTION_REFERENCE_NUMBER'] = '123';
        $data3['ORDER_TYPE'] = 'MERCHANT_PAYMENT_REFUND';
        $data3['TRANSACTION_STATUS'] = 'SUCCESS';
        $data3['TRANSACTION_REQUESTED_DATE'] = '2022-04-25T17:13:52';

        $reconFile = $this->generateReconFile($data, $data2, $data3);

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'],'INRAZORPAY01.Digi2RZPrecon.'.Carbon::today()->format("d-m-Y_H_i_s").'.csv.pgp', "application/pgp");

        $this->reconcile($uploadedFile, Base::NETBANKING_DBS);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    public function testDbsSuccessReconWithForceAuthorize()
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

        $data = $this->testData['testDbsSuccessRecon'];

        $data['MERCHANT_ORDER_ID'] = 'MERCHANT_ORDER_ID';
        $data['TRANSACTION_AMOUNT'] = 'TRANSACTION_AMOUNT';
        $data['TRANSACTION_REFERENCE_NUMBER'] = 'TRANSACTION_REFERENCE_NUMBER';
        $data['ORDER_TYPE'] = 'ORDER_TYPE';
        $data ['TRANSACTION_STATUS'] = 'TRANSACTION_STATUS';
        $data ['TRANSACTION_REQUESTED_DATE'] = 'TRANSACTION_REQUESTED_DATE';

        $data2 = $this->testData['testDbsSuccessRecon'];

        $data2['MERCHANT_ORDER_ID'] = $payment['id'];
        $data2['TRANSACTION_AMOUNT'] = $payment['amount'] / 100;
        $data2['TRANSACTION_REFERENCE_NUMBER'] = '123';
        $data2['ORDER_TYPE'] = 'MERCHANT_PAYMENT';
        $data2['TRANSACTION_STATUS'] = 'SUCCESS';
        $data2['TRANSACTION_REQUESTED_DATE'] = '2022-04-25T17:13:52';

        $reconFile = $this->generateReconFile($data, $data2);

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'],'INRAZORPAY01.Digi2RZPrecon.'.Carbon::today()->format("d-m-Y_H_i_s").'.csv.pgp', "application/pgp");

        $this->reconcile($uploadedFile, Base::NETBANKING_DBS);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $paymentEntity = $this->getDbEntityById('payment', $payment['public_id']);

        $this->assertEquals($paymentEntity['status'], Status::AUTHORIZED);
        $this->assertEquals($paymentEntity['late_authorized'], true);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    protected function generateReconFile($data, $data2, $data3 = null)
    {
        $fileData = implode(',', $data);
        $fileData2 = implode(',', $data2);
        $fileData3 = $data3 !== null ? implode(',', $data3) : null;

        $data = $fileData . "\n" . $fileData2 . "\n" . $fileData3;

        return $this->createFile($data);
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

    protected function createFile($content, string $type = FileStore\Type::MOCK_RECONCILIATION_FILE, string $store = FileStore\Store::S3)
    {
        $creator = new FileStore\Creator;

        $config = $this->app['config'];

        $configKeys = $config['gateway.netbanking_dbs'];

        $publicKey  = trim(str_replace('\n', "\n", $configKeys['files_encryption_key']));

        $creator->extension(FileStore\Format::TXT)
            ->content($content)
            ->name('testReconFile')
            ->sheetName('Sheet 1')
            ->store($store)
            ->type($type)
            ->headers(true)
            ->encrypt(Encryption\Type::PGP_ENCRYPTION,
                [
                    Encryption\PGPEncryption::PUBLIC_KEY => $publicKey
                ]
            )
            ->save();

        $file = $creator->get();

        return ['local_file_path' => $file['local_file_path']];
    }
}
