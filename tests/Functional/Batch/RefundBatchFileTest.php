<?php

namespace RZP\Tests\Functional\Batch;

use DB;
use Mockery;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Batch\Status;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;
use Illuminate\Http\UploadedFile;

class RefundBatchFileTest extends TestCase
{
    use PaymentTrait;
    use FileHandlerTrait;

    protected $payment = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/RefundBatchFileTestData.php';

        parent::setUp();

        $this->payment = $this->fixtures->create('payment:captured');

        $this->ba->privateAuth();
    }

    public function testUploadRefundFile()
    {
        $this->putBatchFileInTestRequestData();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUploadRefundFileException()
    {
        $entries = $this->getDefaultRefundFileEntries();

        // Put improper format data
        $entries[0][1] = '';

        $this->putBatchFileInTestRequestData($entries);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetRefundFiles()
    {
        $this->ba->proxyAuth();

        $this->createAndUploadBatchRefundFile();

        $this->startTest();
    }

    public function testProcessRefundFile()
    {
        $payment = $this->doAuthAndCapturePayment();

        $testData = $this->testData['testUploadRefundFile'];
        $request = $testData['request'];

        $paymentEntry = array();
        $payemntObj = array($payment['id'], (int) 4000);
        array_push($paymentEntry, $payemntObj);

        $url = $this->writeToExcelFile($paymentEntry, $payment['id']);

        $uploadedFile = $this->createTempFile($url);

        $request['files']['file'] = $uploadedFile;

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        $refundId = $content['id'];

        $url = $this->writeToExcelFile($paymentEntry, substr($refundId, 6));

        $this->assertEquals(Status::CREATED, $content['status']);

        $testData = $this->testData['testProcessRefundFile'];

        $this->ba->appAuthTest();

        $content = $this->makeRequestAndGetContent($testData['request']);

        $resultBody = $content['items'][0];
        $this->assertEquals(Status::PROCESSED, $resultBody['status']);
        $this->assertEquals(4000, $resultBody['amount']);
        $this->assertEquals(0, $resultBody['failure_count']);

    }

    public function testProcessRefundFileWithSuccessAfterAttempts()
    {
        $payment = $this->defaultAuthPayment();

        $testData = $this->testData['testUploadRefundFile'];
        $request = $testData['request'];

        $paymentEntry = array();
        $payemntObj = array($payment['id'], (int) 5000);
        array_push($paymentEntry, $payemntObj);

        $url = $this->writeToExcelFile($paymentEntry, $payment['id']);

        $uploadedFile = $this->createTempFile($url);

        $request['files']['file'] = $uploadedFile;

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        $batchId = $content['id'];

        $url = $this->writeToExcelFile($paymentEntry, substr($batchId, 6));

        $this->assertEquals(Status::CREATED, $content['status']);

        $testData = $this->testData['testProcessRefundFile'];

        $this->ba->appAuth();
        $content = $this->makeRequestAndGetContent($testData['request']);

        $resultBody = $content['items'][0];
        $this->assertEquals(Status::PROCESSING, $resultBody['status']);
        $this->assertEquals(0, $resultBody['processed_amount']);
        $this->assertEquals(1, $resultBody['failure_count']);
        $this->assertEquals(1, $resultBody['attempts']);

        $content = $this->makeRequestAndGetContent($testData['request']);
        $resultBody = $content['items'][0];
        $this->assertEquals(Status::PROCESSING, $resultBody['status']);
        $this->assertEquals(0, $resultBody['processed_amount']);
        $this->assertEquals(2, $resultBody['attempts']);

        $this->ba->privateAuth();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->ba->appAuth();
        $content = $this->makeRequestAndGetContent($testData['request']);
        $resultBody = $content['items'][0];
        $this->assertEquals(Status::PROCESSED, $resultBody['status']);
        $this->assertEquals(5000, $resultBody['processed_amount']);
        $this->assertEquals(3, $resultBody['attempts']);

        $testData = $this->testData['testRetryRefundFilesWithException'];
        $testData['request']['url'] = '/batches/' . $batchId  .'/retry';

        $request = $testData['request'];
        $this->runRequestResponseFlow($testData, function() use ($request) {
            $this->ba->proxyAuth();
            $content = $this->makeRequestAndGetContent($request);

        });
    }

    public function testProcessRefundFileWithFailureAfterAttempts()
    {
        $payment = $this->doAuthAndCapturePayment();

        $testData = $this->testData['testUploadRefundFile'];
        $request = $testData['request'];

        $paymentEntry = array();
        $payemntObj = array($payment['id'], (int) 100000);
        array_push($paymentEntry, $payemntObj);

        $url = $this->writeToExcelFile($paymentEntry, $payment['id']);

        $uploadedFile = $this->createTempFile($url);

        $request['files']['file'] = $uploadedFile;

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        $refundId = $content['id'];

        $url = $this->writeToExcelFile($paymentEntry, substr($refundId, 6));
        $uploadedFile = $this->createTempFile($url);

        $this->assertEquals(Status::CREATED, $content['status']);

        $testData = $this->testData['testProcessRefundFile'];

        $testData['request']['files']['file'] = $uploadedFile;

        $this->ba->appAuth();
        $content = $this->makeRequestAndGetContent($testData['request']);

        $resultBody = $content['items'][0];
        $this->assertEquals(Status::PROCESSING, $resultBody['status']);
        $this->assertEquals(0, $resultBody['processed_amount']);
        $this->assertEquals(1, $resultBody['failure_count']);
        $this->assertEquals(1, $resultBody['attempts']);

        $content = $this->makeRequestAndGetContent($testData['request']);
        $resultBody = $content['items'][0];
        $this->assertEquals(Status::PROCESSING, $resultBody['status']);
        $this->assertEquals(0, $resultBody['processed_amount']);
        $this->assertEquals(2, $resultBody['attempts']);

        $content = $this->makeRequestAndGetContent($testData['request']);
        $resultBody = $content['items'][0];
        $this->assertEquals(Status::PROCESSED, $resultBody['status']);
        $this->assertEquals(0, $resultBody['processed_amount']);
        $this->assertEquals(3, $resultBody['attempts']);
    }

    protected function writeToExcelFile($data, $name)
    {
        \Config::set('excel::export.calculate', true);

        $columnFormat = $this->getColumnFormatForExcel();

        $excel = $this->createExcelObject($data, $name, $columnFormat);

        $fileMetadata = $excel->store('xlsx', storage_path('files/batch_file_download'), true);

        $fullpath = $fileMetadata['full'];

        $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $url = $this->saveToAws($name.'.xlsx', $fullpath, $xlsxMimeType);

        return $url;
    }

    protected function createTempFile($url)
    {
        $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $uploadedFile = new UploadedFile(
                               $url,
                               'file',
                               $mimeType,
                               filesize($url),
                               null,
                               true);
       return $uploadedFile;
    }

    public function startTest($paymentId = null, $amount = null)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        return $this->runRequestResponseFlow($testData);
    }

    protected function putBatchFileInTestRequestData($entries = null)
    {
        if ($entries === null)
        {
            $entries = $this->getDefaultRefundFileEntries();
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $request = & $this->testData[$name]['request'];

        $url = $this->writeToExcelFile($entries, 'upload_refund_test');

        $uploadedFile = $this->createTempFile($url);

        $request['files']['file'] = $uploadedFile;
    }

    protected function createAndUploadBatchRefundFile()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $url = $this->writeToExcelFile($entries, 'upload_refund_test');

        $uploadedFile = $this->createTempFile($url);

        $request = array(
            'url'       => '/batches',
            'method'    => 'post',
            'content' => [
                'type' => 'refund',
            ]);

        $request['files']['file'] = $uploadedFile;

        $this->ba->proxyAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function getDefaultRefundFileEntries()
    {
        $payment = $this->defaultAuthPayment();

        $entries = array();
        $row = array($payment['id'], (int) 2000);

        array_push($entries, $row);
    }
}
