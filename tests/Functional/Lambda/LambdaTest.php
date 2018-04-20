<?php

namespace RZP\Tests\Functional\Lambda;

use Config;
use Carbon\Carbon;
use ZipArchive;

use Illuminate\Http\Testing\File as TestingFile;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class LambdaTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        parent::setUp();

        $this->testDataFilePath = __DIR__.'/LambdaTestData.php';

        $this->fixtures->create('terminal:shared_enach_rbl_terminal');

        $this->ba->appAuth();
    }

    public function testENachRblAckBatchXmlUploadedFile()
    {
        $file = $this->getFileToUpload();

        $request = [
            'url'    => '/lambda/emandate',
            'method' => 'POST',
            'content' => [
                'sub_type' => 'acknowledge',
                'gateway'  => 'enach_rbl',
            ],
            'files' => [
                'file' => $file,
            ]
        ];

        $batches = $this->makeRequestAndGetContent($request);

        $this->validateBatch($batches);
    }

    public function testENachRblAckBatchAwsKey()
    {
        $file = $this->getFileToUpload();

        $request = [
            'url'    => '/lambda/emandate',
            'method' => 'POST',
            'content' => [
                'sub_type' => 'acknowledge',
                'gateway'  => 'enach_rbl',
                'key'      => $file->getRealPath()
            ],
        ];

        $batches = $this->makeRequestAndGetContent($request);

        $this->validateBatch($batches);
    }

    public function testENachRblAckBatchZipAwsKey()
    {
        $file = $this->getFileToUpload();

        $file = $this->createZipFile($file);

        $request = [
            'url'    => '/lambda/emandate',
            'method' => 'POST',
            'content' => [
                'sub_type' => 'acknowledge',
                'gateway'  => 'enach_rbl',
                'key'      => $file
            ],
        ];

        $batches = $this->makeRequestAndGetContent($request);

        $this->validateBatch($batches);
    }

    protected function validateBatch($batches)
    {
        $this->assertEquals('collection', $batches['entity']);

        $this->assertEquals(1, $batches['count']);

        $this->assertEquals('emandate', $batches['items'][0]['type']);
    }

    protected function createZipFile($file)
    {
        $zip = new ZipArchive();

        $tempFileName = sys_get_temp_dir() . '/test.zip';

        $zip->open($tempFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFile($file->getRealPath(), $file->getFileName());

        $zip->close();

        return $tempFileName;
    }

    protected function getFileToUpload()
    {
        list($payment, $token, $order) = $this->createEmandatePayment();

        $replacePair = [
            '{$date}' => Carbon::now()->toIso8601String(),
            '{$paymentId}' => $payment->getId(),
            '{$status}' => 'true',
            '{$mandateId}' => 'UTIB6000000005844847',
            '{$firstCol}' => Carbon::now()->addDay()->format('Y-m-d'),
            '{$finalCol}' => Carbon::now()->addDay()->addYears(5)->format('Y-m-d'),
            '{$currency}' => 'INR',
            '{$maxAmount}' => '0',
            '{$accountNumber}' => $token->getAccountNumber(),
            '{$ifsc}' => $token->getIfsc(),
        ];

        $reconFileStub = file_get_contents(__DIR__ . '/../Gateway/Enach/Rbl/acknowledge.stub');

        $reconFileContent = strtr($reconFileStub, $replacePair);

        $tempFileName = sys_get_temp_dir() . '/MMS-CREATE-RATN-RATNA0001-06032018-ESIGN6000001-INP-ACK.xml';//tmpfile();

        $handle = fopen($tempFileName, 'w+');

        fwrite($handle, $reconFileContent);
        fseek($handle, 0);
        $file = (new TestingFile('MMS-CREATE-RATN-RATNA0001-06032018-ESIGN6000001-INP-ACK.xml', $handle));

        return $file;
    }

    protected function createEmandatePayment($amount = 0, $recurringType = 'initial')
    {
        $order = $this->fixtures->create('order:emandate_order', [
            'status' => 'attempted',
            'amount' => 0]);

        $token = $this->fixtures->create('customer:emandate_token', [
            'aadhaar_number' => '390051307206',
            'auth_type' => 'aadhaar']);

        $payment = [
            'auth_type'         => 'aadhaar',
            'terminal_id'       => '1000EnachRblTl',
            'order_id'          => $order->getId(),
            'amount'            => $order->getAmount(),
            'amount_authorized' => $order->getAmount(),
            'gateway'           => 'enach_rbl',
            'bank'              => 'UTIB',
            'recurring'         => '1',
            'customer_id'       => $token->getCustomerId(),
            'token_id'          => $token->getId(),
            'recurring_type'    => $recurringType,
        ];

        $payment = $this->fixtures->create('payment:emandate_authorized', $payment);

        return [$payment, $token, $order];
    }

}
