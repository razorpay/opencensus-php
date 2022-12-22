<?php

namespace RZP\Tests\Functional\Gateway\File;

use Carbon\Carbon;
use RZP\Exception;
use Illuminate\Http\UploadedFile;
use Mail;

use RZP\Constants\Entity;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Payment as PaymentClass;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Transaction\Entity as Txn;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingIciciReconciliationTest extends NbPlusPaymentServiceNetbankingTest
{
    use ReconTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingReconciliationTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_icici_terminal');

        $this->bank = 'ICIC';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testIciciSuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals(Payment::NB_PLUS_SERVICE, $payment[Payment::CPS_ROUTE]);
        $this->assertEquals(Payment::CAPTURED, $payment[Payment::STATUS]);

        $data = $this->testData[__FUNCTION__];

        $data['ITC'] = $payment['id'];
        $data['PRN'] = $payment['id'];

        //Creating a paylater payment here as paylater and netbanking icici recon file is the same. This is to test that paylater payments are also reconciled.
        $paymentId = $this->createPaylaterPayment();

        $payment = $this->getDbLastEntity('payment');

        $this->createMozartEntity($paymentId, $payment['amount'], 'paylater_icici');

        $data2 = $this->testData[__FUNCTION__];

        $data2['ITC'] = $payment['id'];
        $data2['PRN'] = $payment['id'];

        $reconFile = $this->generateReconFile($data, $data2);

        $fileName = 'razorpayreports_test_'.Carbon::today()->format("m-d-Y").'.rpt';

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'], $fileName, 'text/csv');

        $this->reconcile($uploadedFile, Base::NETBANKING_ICICI);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals('processed', $batch['status']);
        $this->assertEquals(2, $batch['total_count']);
        $this->assertEquals(2, $batch['processed_count']);
        $this->assertEquals(2, $batch['success_count']);
    }

    public function testIciciEmiSuccessReconMailgun()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment1 = $this->getDbLastPayment();

        $this->assertEquals(Payment::NB_PLUS_SERVICE, $payment1[Payment::CPS_ROUTE]);
        $this->assertEquals(Payment::CAPTURED, $payment1[Payment::STATUS]);
        $this->assertEquals($this->terminal->getId(), $payment1->terminal->getId());

        $data = $this->testData['testIciciSuccessRecon'];

        $data['ITC'] = $payment1['id'];
        $data['PRN'] = $payment1['id'];

        $this->doAuthAndCapturePayment($this->payment);

        $payment2 = $this->getDbLastPayment();

        $this->assertEquals(Payment::NB_PLUS_SERVICE, $payment2[Payment::CPS_ROUTE]);
        $this->assertEquals(Payment::CAPTURED, $payment2[Payment::STATUS]);
        $this->assertEquals($this->terminal->getId(), $payment1->terminal->getId());

        $data2 = $this->testData['testIciciSuccessRecon'];

        $data2['ITC'] = $payment2['id'];
        $data2['PRN'] = $payment2['id'];

        $reconFile = $this->generateReconFile($data, $data2);

        $fileName = 'razorpayreports_test_'.Carbon::today()->format("m-d-Y").'.txt';

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'], $fileName, "text/plain");

        $input = [
            'X-Original-Sender' => 'infinitydatacenter@icicibank.com',
            'subject'           => 'Consumer Durable Loan booking Razorpay Reports for 21-06-2021',
            'recipient'         => 'reconciliate@mg.razorpay.com',
            'timestamp'         => '1624300396',
        ];

        $request = [
            'url'     => '/reconciliate',
            'content' => $input,
            'method'  => 'POST',
            'files'   => [
                'attachment-1' => $uploadedFile,
            ],
        ];

        $this->ba->cronAuth();

        $this->makeRequestAndGetContent($request);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals('processed', $batch['status']);
    }

    public function testIciciRefundRecon()
    {
        $capturedPayment = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->refundPayment($capturedPayment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $data = $this->testData[__FUNCTION__];
        $data['ITC'] = 'ITC';
        $data['PRN'] = 'PRN';
        $data['Reversal Date'] = 'Reversal Date';

        $data2 = $this->testData[__FUNCTION__];
        $data2['ITC'] = $payment['id'];
        $data2['PRN'] = substr($refund['id'], 5);

        $reconFile = $this->generateReconFile($data, $data2, '|');

        $fileName = 'OFPR_Daily_Report_'.Carbon::today()->format("dmY").'.txt';

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'], $fileName, "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_ICICI);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    public function testInvalidNetbankingIciciUpdateReconData()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $content = $this->getDefaultNetbankingPostReconArray();

        $content['payment_id'] = $payment['id'];

        unset($content['reconciled_at']);

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url' => '/reconciliate/data',
                'method' => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class);
    }

    public function testNetbankingIciciUpdateAlreadyReconciled()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $transaction = $this->getDbLastEntity('transaction');

        $this->fixtures->edit('transaction', $transaction['id'], ['reconciled_at' => Carbon::now(Timezone::IST)->getTimestamp()]);

        $content = $this->getDefaultNetbankingPostReconArray();

        $content['payment_id'] = $payment['id'];

        $response = $this->makeUpdatePostReconRequestAndGetContent($content);

        $this->assertFalse($response['success']);

        $this->assertEquals('ALREADY_RECONCILED', $response['error']['code']);
    }

    public function testNetbankingIciciUpdatePostReconData()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $content = $this->getDefaultNetbankingPostReconArray();

        $content['payment_id'] = $payment['id'];

        $content['reconciled_at'] = Carbon::now(Timezone::IST)->getTimestamp();

        $response = $this->makeUpdatePostReconRequestAndGetContent($content);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotEmpty($transactionEntity['reconciled_at']);

        $this->assertTrue($response['success']);
    }

    private function makeUpdatePostReconRequestAndGetContent(array $content)
    {
        $request = [
            'method'  => 'POST',
            'content' => $content,
            'url'     => '/reconciliate/data',
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function createPaylaterPayment()
    {
        $this->payment = $this->getDefaultPayLaterPaymentArray('icic');

        $this->sharedTerminal = $this->fixtures->create('terminal:paylater_icici_terminal');

        $this->fixtures->merchant->enablePayLater('10000000000000');
        $this->method  = PaymentClass\Method::PAYLATER;

        return $this->createPayment(PaymentClass\Gateway::PAYLATER_ICICI);
    }

    protected function createMozartEntity($paymentId, $amount, $gateway)
    {
        $mozartAttributes = [
            'payment_id' => $paymentId,
            'gateway'    => $gateway,
            'amount'     => $amount,
            'raw'        => json_encode(['payment_id' => $paymentId,'providerReferenceId' => 'P00000000001122334455']),
            'action'     => 'authorize',
        ];

        $mozart = $this->fixtures->create('mozart', $mozartAttributes);

        return $mozart;
    }

    protected function generateReconFile($data, $data2, $separator = ',')
    {
        $fileData = implode($separator, $data);
        $fileData2 = implode($separator, $data2);

        $data = nl2br($fileData."\n".$fileData2);


        return $this->createFile($data);
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

    protected function createFile($content): array
    {
        $creator = new FileStore\Creator;

        $creator->extension(FileStore\Format::TXT)
                ->mime('text/csv')
                ->content($content)
                ->name('testReconFile')
                ->store(FileStore\Store::S3)
                ->type(FileStore\Type::MOCK_RECONCILIATION_FILE)
                ->save();

        $file = $creator->get();

        return ['local_file_path' => $file['local_file_path']];
    }
}
