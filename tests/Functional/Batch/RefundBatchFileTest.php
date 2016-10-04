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
        $entries[0]['Amount'] = '';

        $this->putBatchFileInTestRequestData($entries);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetRefundFiles()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund', $entries);

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testProcessRefundFile()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund', $entries);

        $payment = $this->capturePayment($entries[0]['Payment Id'], 50000);

        $this->ba->appAuth();
        $this->startTest();
    }

    public function testProcessRefundWithOneAttempt()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund_with_one_attempt', $entries);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testProcessRefundWithTwoAttempt()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund_with_two_attempt', $entries);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testProcessRefundWithThreeAttempt()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund_with_three_attempt', $entries);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testProcessRefundRetryAfterProccessed()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund_with_three_attempt', $entries);


        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/batches/' . 'batch_'.$batch->getId()  .'/retry';

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testProcessRefundWithThreeAttemptSuccess()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund_with_three_attempt', $entries);

        $payment = $this->capturePayment($entries[0]['Payment Id'], 50000);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testProcessRetryRefundWithThreeAttempt()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund_with_three_attempt', $entries);

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/batches/' . 'batch_'.$batch->getId()  .'/retry';

        $this->ba->proxyAuth();
        $this->startTest();
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

        $url = $this->writeToExcelFile($entries, 'upload_refund_test', 'files/batch_file_download');

        $uploadedFile = $this->createTempFile($url);

        $request['files']['file'] = $uploadedFile;
    }

    protected function createAndUploadBatchRefundFile()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $paymentId = $entries[0][0];
        $url = $this->writeToExcelFile($entries, $paymentId .'xlsx', 'files/batch_file_download');

        $uploadedFile = $this->createTempFile($url);

        $request = array(
            'url'       => '/batches',
            'method'    => 'post',
            'content' => [
                'type' => 'refund',
            ]);

        $request['files']['file'] = $uploadedFile;

        $this->ba->proxyAuth();

        return array($this->makeRequestAndGetContent($request), $entries);

    }

    protected function getDefaultRefundFileEntries()
    {
        $payment = $this->defaultAuthPayment();

        $entries = [
            [
                'Payment Id' => $payment['id'],
                'Amount'     => 4000
            ]
        ];

        return $entries;
    }
}
