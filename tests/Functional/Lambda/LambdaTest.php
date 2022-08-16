<?php

namespace RZP\Tests\Functional\Lambda;

use Excel;
use Config;
use RZP\Exception\BadRequestException;
use ZipArchive;

use RZP\Tests\Functional\TestCase;
use RZP\Excel\Export as ExcelExport;
use RZP\Excel\ExportSheet as ExcelSheetExport;
use Illuminate\Http\Testing\File as TestingFile;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

class LambdaTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDataFilePath = __DIR__.'/LambdaTestData.php';

        $this->fixtures->create('terminal:shared_enach_rbl_terminal');

        $this->ba->h2hAuth();
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

    public function testENachRblCancelBatchZipAwsKey()
    {
        $file = $this->getBatchFileToUploadForMandateCancelRes();

        $request = [
            'url'    => '/lambda/emandate',
            'method' => 'POST',
            'content' => [
                'sub_type' => 'cancel',
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

        $sheets = [
            'Acknowledgement_summary' => [
                'config' => [
                    'start_cell' => 'A1',
                ],
                'items'  => [
                    [
                        'random' => '1',
                    ],
                ],
            ],
            'ACKNOWLEDGMENT REPORT'  => [
                'config' => [
                    'start_cell' => 'A2',
                ],
                'items'  => [
                    [
                        'MANDATE_DATE' => 'some date',
                        'BATCH'        => 10,
                        'IHNO'         => 6411,
                        'MANDATE_TYPE' => 'NEW',
                        'UMRN'         => 'UTIB6000000005393968',
                        'REF_1'        => $payment->getId(),
                        'REF_2'        => '',
                        'CUST_NAME'    => 'customer name',
                        'BANK'         => 'UTIB',
                        'BRANCH'       => 'branch',
                        'BANK_CODE'    => 'UTIB0000123',
                        'AC_TYPE'      => 'SAVINGS',
                        'ACNO'         => '914010009305862',
                        'ACK_DATE'     => 'some date',
                        'ACK_DESC'     => 'description',
                        'AMOUNT'       => 99999,
                        'FREQUENCY'    => 'ADHO',
                        'TEL_NO'       => '',
                        'MOBILE_NO'    => '9998887776',
                        'MAIL_ID'      => 'test@enach.com',
                        'UPLOAD_BATCH' => 'ESIGN000001',
                        'UPLOAD_DATE'  => 'some date',
                        'UPDATE_DATE'  => '',
                        'SOLE_ID'      => '',
                    ]
                ],
            ],
        ];

        $data = $this->getExcelString('Acknowledgment Report_15062018_Acknowledgment Report', $sheets);

        $tempFileName = sys_get_temp_dir() . '/Acknowledgment Report_15062018_Acknowledgment Report.xlsx';

        $handle = fopen($tempFileName, 'w+');
        fwrite($handle, $data);
        fseek($handle, 0);
        $file = (new TestingFile('Acknowledgment Report_15062018_Acknowledgment Report.xlsx', $handle));

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

    protected function getExcelString($name, $sheets)
    {
        $excel = (new ExcelExport)->setSheets(function() use ($sheets) {
            $sheetsInfo = [];
            foreach ($sheets as $sheetName => $data)
            {
                $sheetsInfo[$sheetName] = (new ExcelSheetExport($data['items']))->setTitle($sheetName)->setStartCell($data['config']['start_cell'])->generateAutoHeading(true);
            }

            return $sheetsInfo;
        });

        $data = $excel->raw('Xlsx');

        return $data;
    }

    protected function getBatchFileToUploadForMandateCancelRes(): TestingFile
    {
        $xmlData1 = file_get_contents(__DIR__ . '/MMS-CANCEL-RATN-RATNA0001-02052016-ESIGN000001-RES.xml');

        $zip = new ZipArchive();

        $zip->open(__DIR__ . '/MMS-CANCEL-RATN-RATNA0001-02052016-ESIGN000001-RES.zip', ZipArchive::CREATE);

        $zip->addFromString( 'MMS-CANCEL-RATN-RATNA0001-02052016-ESIGN000001-RES.xml', $xmlData1);

        $zip->addFromString( 'MMS-CANCEL-RATN-RATNA0001-02052016-ESIGN000002-RES.xml', $xmlData1);

        $zip->close();

        $handle = fopen(__DIR__ . '/MMS-CANCEL-RATN-RATNA0001-02052016-ESIGN000001-RES.zip', 'r');

        return (new TestingFile('MMS-CANCEL-RATN-RATNA0001-02052016-ESIGN000001-RES.zip', $handle));
    }

    protected function getPosSettlementFile(): TestingFile
    {
        $handle = fopen(__DIR__ . '/POS Sample data consolidated.xlsx', 'r');

        return (new TestingFile('POS Sample data consolidated.xlsx', $handle));
    }

    public function testCreateBatchOfEzetapSettlementType()
    {
        $file = $this->getPosSettlementFile();

        $request = [
            'url'    => '/pos_settlements/validate/file',
            'method' => 'POST',
            'content' => [
                "source" => "lambda",
                'type' => 'ezetap_settlement',
            ],
            'files' => [
                'file' => $file,
            ]
        ];

        $batch = $this->makeRequestAndGetContent($request);

        $this->assertEquals('ezetap_settlement', $batch['type']);

        $this->assertEquals('created', $batch['status']);

        $this->assertEquals(3, $batch['total_count']);
    }

    public function testCreateBatchOfEzetapSettlementTypeFailure()
    {
        $testData = [
            'request' => [
                'url'    => '/pos_settlements/validate/file',
                'method' => 'POST',
                'content' => [
                    "source" => "lambda",
                    'type' => 'ezetap_settlement',
                ],
            ],
            'response'  => [
                'status_code' => 400,
                'content'     => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'invalid input, either bucket key or uploaded file is required',
                    ],
                ],
            ],
            'exception' => [
                'class' => 'RZP\Exception\BadRequestValidationFailureException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
            ],
        ];

        $this->startTest($testData);

    }
}
