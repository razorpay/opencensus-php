<?php

namespace RZP\Tests\Functional\Gateway\Enach\Nach;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\PaperMandate;
use RZP\Constants\Entity as E;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Excel\Export as ExcelExport;
use RZP\Excel\Import as ExcelImport;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Excel\ExportSheet as ExcelSheetExport;
use Illuminate\Http\Testing\File as TestingFile;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Gateway\Enach\Citi\NachDebitFileHeadings as Headings;

class NachGatewayTest extends TestCase
{
    use FileHandlerTrait;
    use DbEntityFetchTrait;
    use AttemptTrait;
    use AttemptReconcileTrait;
    use PartnerTrait;

    // 09-02-2020 Sunday
    const FIXED_NON_WORKING_DAY_TIME = 1581223905;
    // 10-02-2020 Monday
    const FIXED_WORKING_DAY_AFTER_NON_WORKING_DAY_TIME = 1581313905;
    // 10-02-2020 Tuesday
    const FIXED_WORKING_DAY_AFTER_WORKING_DAY_TIME = 1581385905;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NachGatewayTestData.php';

        $fixedTime = (new Carbon())->timestamp(self::FIXED_WORKING_DAY_AFTER_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'nach');

        $this->fixtures->create('terminal:nach');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testGatewayFileDebitBankResponsePending()
    {
        $payment = $this->createRecurringNachPayment();

        $batchFile = $this->getBatchFileToUploadForBankDebitResponse($payment, "3");

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'debit');

        $this->assertEquals('nach', $batch['batch_type_id']);
        $this->assertEquals('CREATED', $batch['status']);

        $batchEntity = $this->fixtures->create('batch',
            [
                'id'          => '00000000000001',
                'type'        => 'nach',
                'sub_type'    => 'debit',
                'gateway'     => 'nach_citi',
                'total_count' => '1',
            ]);

        $paymentId = $payment['razorpay_payment_id'];

        $this->fixtures->stripSign($paymentId);

        $entries = $this->createBatchRequestData($paymentId, "nach", "debit", "nach_citi", "3", "00");

        $this->runWithData($entries, $batchEntity['id']);
        $payment = $this->getEntityById('payment', $payment['razorpay_payment_id'], true);

        $this->assertEquals('created', $payment['status']);
    }

    // enach refund migration to scrooge
    public function testNachDebitRefund()
    {
        $this->testGatewayFileDebitBankResponseSuccess();

        $payment = $this->getLastEntity('payment', true);

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'nach_citi';

        // $this->refundPayment($payment['id']);
        $this->refundPayment($payment['id'], $payment['amount'], ['is_fta' => true]);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);

        // $this->assertEquals('initiated', $refund['status']);
        $this->assertEquals('created', $refund['status']);
    }

    public function testGatewayFileRegister()
    {
        $payment = $this->createDummyRegisterToken();

        $this->fixtures->stripSign($payment['id']);

        $this->ba->cronAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', [], true);

        $zipFile = $files['items'][0];
        $registerFile = $files['items'][1];

        $expectedFileContentZip = [
            'type'        => 'citi_nach_register',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'zip',
            'name'        => 'RAZORP_EMANDATE_NACH00000000013149_10022020_test'
        ];

        $expectedFileContentRegister = [
            'type'        => 'citi_nach_register',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xls',
            'name'        => 'RAZORP_EMANDATE_NACH00000000013149_11022020_test',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentZip, $zipFile);
        $this->assertArraySelectiveEquals($expectedFileContentRegister, $registerFile);

        $registerFileRows = (new ExcelImport)->toArray('storage/files/filestore/' . $registerFile['location'])[0];

        $expectedRegisterFileContent = [
            'category_code'        => "U099",
            'category_description' => "Others",
            'start_date'           => "16/02/2020",
            'end_date'             => "Until cancelled",
            'client_code'          => "CTRAZORPAY",
            'unique_reference_no'  => $payment['id'],
            'account_no'           => "1111111111111",
            'account_holder_name'  => "dead pool",
            'account_type'         => "savings",
            'bank_name'            => "HDFC",
            'bank_micr_ifsc'       => "HDFC0001233",
            'amount'               => "10000",
        ];

        $this->assertArraySelectiveEquals($expectedRegisterFileContent, $registerFileRows[0]);
    }

    public function testGatewayFileRegisterForCashCredit()
    {
        $payment = $this->createDummyRegisterToken(['account_type' => 'cc']);

        $this->fixtures->stripSign($payment['id']);

        $this->ba->cronAuth();

        $content = $this->startTest($this->testData['testGatewayFileRegister']);

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', [], true);

        $zipFile = $files['items'][0];
        $registerFile = $files['items'][1];

        $expectedFileContentZip = [
            'type'        => 'citi_nach_register',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'zip',
            'name'        => 'RAZORP_EMANDATE_NACH00000000013149_10022020_test'
        ];

        $expectedFileContentRegister = [
            'type'        => 'citi_nach_register',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xls',
            'name'        => 'RAZORP_EMANDATE_NACH00000000013149_11022020_test',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentZip, $zipFile);
        $this->assertArraySelectiveEquals($expectedFileContentRegister, $registerFile);

        $registerFileRows = (new ExcelImport)->toArray('storage/files/filestore/' . $registerFile['location'])[0];

        $expectedRegisterFileContent = [
            'category_code'        => "U099",
            'category_description' => "Others",
            'start_date'           => "16/02/2020",
            'end_date'             => "Until cancelled",
            'client_code'          => "CTRAZORPAY",
            'unique_reference_no'  => $payment['id'],
            'account_no'           => "1111111111111",
            'account_holder_name'  => "dead pool",
            'account_type'         => "cc",
            'bank_name'            => "HDFC",
            'bank_micr_ifsc'       => "HDFC0001233",
            'amount'               => "10000",
        ];

        $this->assertArraySelectiveEquals($expectedRegisterFileContent, $registerFileRows[0]);
    }

    public function testGatewayFileRegisterWithIfscMapping()
    {
        $payment = $this->createDummyRegisterToken(['ifsc' => 'ORBC0100326']);

        $this->fixtures->stripSign($payment['id']);

        $this->ba->cronAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', [], true);

        $zipFile = $files['items'][0];
        $registerFile = $files['items'][1];

        $expectedFileContentZip = [
            'type'        => 'citi_nach_register',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'zip',
            'name'        => 'RAZORP_EMANDATE_NACH00000000013149_10022020_test'
        ];

        $expectedFileContentRegister = [
            'type'        => 'citi_nach_register',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xls',
            'name'        => 'RAZORP_EMANDATE_NACH00000000013149_11022020_test',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentZip, $zipFile);
        $this->assertArraySelectiveEquals($expectedFileContentRegister, $registerFile);

        $registerFileRows = (new ExcelImport)->toArray('storage/files/filestore/' . $registerFile['location'])[0];

        $expectedRegisterFileContent = [
            'category_code'        => "U099",
            'category_description' => "Others",
            'start_date'           => "16/02/2020",
            'end_date'             => "Until cancelled",
            'client_code'          => "CTRAZORPAY",
            'unique_reference_no'  => $payment['id'],
            'account_no'           => "1111111111111",
            'account_holder_name'  => "dead pool",
            'account_type'         => "savings",
            'bank_name'            => "ORBC",
            'bank_micr_ifsc'       => "PUNB0244200",
            'amount'               => "10000",
        ];

        $this->assertArraySelectiveEquals($expectedRegisterFileContent, $registerFileRows[0]);
    }

    public function testGatewayFileRegisterOnNonWorkingDay()
    {
        $fixedTime = (new Carbon())->timestamp(self::FIXED_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $response = $this->createDummyRegisterToken();

        $this->fixtures->edit('payment', $response['id'], [
            'created_at' => Carbon::today(Timezone::IST)->addHours(5)->timestamp
        ]);

        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testGatewayFileDebitForPaymentCreatedOnNonWorkingDay()
    {
        $fixedTime = (new Carbon())->timestamp(self::FIXED_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $this->createDummyRegisterToken();

        $fixedTime = (new Carbon())->timestamp(self::FIXED_WORKING_DAY_AFTER_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testGatewayFileDebit()
    {
        $response = $this->createRecurringNachPayment();

        $this->fixtures->stripSign($response['razorpay_payment_id']);

        $this->ba->cronAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', ['count' => 2], true);

        $summary = $files['items'][0];
        $debit = $files['items'][1];

        $expectedFileContentSummary = [
            'type'        => 'citi_nach_debit_summary',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xls',
            'name'        => 'citi/nach/RAZORP_SUMMARY_NACH00000000013149_11022020_test'
        ];

        $expectedFileContentDebit = [
            'type'        => 'citi_nach_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
            'name'        => 'citi/nach/RAZORP_COLLECT_NACH00000000013149_11022020_test',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentSummary, $summary);
        $this->assertArraySelectiveEquals($expectedFileContentDebit, $debit);

        $fileContent = explode("\n", file_get_contents('storage/files/filestore/' . $debit['location']));

        // since date and amount is fixed for this test header is a constant
        $expectedHeader = '56       RAZORPAY SOFTWARE PVT LTD                                                                 0001000000000000000030000011022020                       NACH00000000013149000000000000000000CITI000PIGW000018003                          000000001                                                           ';

        $this->assertEquals($expectedHeader, $fileContent[0]);

        $debitRow = array_map('trim', $this->parseTextRow($fileContent[1], 0, ''));

        $expectedDebitRow = [
            'ACH Transaction Code' => '67',
            'Destination Account Type' => '10',
            'Beneficiary Account Holder\'s Name' => 'dead pool',
            'User Name' => 'CTRAZORPAY',
            'Amount' => '0000000300000',
            'Destination Bank IFSC / MICR / IIN' => 'HDFC0001233',
            'Beneficiary\'s Bank Account number' => '1111111111111',
            'Sponsor Bank IFSC / MICR / IIN' => 'CITI000PIGW',
            'User Number' => 'NACH00000000013149',
            'Transaction Reference' => 'TESTMERCHA' . $response['razorpay_payment_id'],
            'Product Type' => '10',
            'UMRN' => 'UTIB6000000005844847'
        ];

        $this->assertArraySelectiveEquals($expectedDebitRow, $debitRow);
    }

    public function testGatewayFileDebitCashCredit()
    {
        $response = $this->createRecurringNachPayment(['account_type' => 'cc']);

        $this->fixtures->stripSign($response['razorpay_payment_id']);

        $this->ba->cronAuth();

        $content = $this->startTest($this->testData['testGatewayFileDebit']);

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', ['count' => 2], true);

        $summary = $files['items'][0];
        $debit = $files['items'][1];

        $expectedFileContentSummary = [
            'type'        => 'citi_nach_debit_summary',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xls',
            'name'        => 'citi/nach/RAZORP_SUMMARY_NACH00000000013149_11022020_test'
        ];

        $expectedFileContentDebit = [
            'type'        => 'citi_nach_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
            'name'        => 'citi/nach/RAZORP_COLLECT_NACH00000000013149_11022020_test',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentSummary, $summary);
        $this->assertArraySelectiveEquals($expectedFileContentDebit, $debit);

        $fileContent = explode("\n", file_get_contents('storage/files/filestore/' . $debit['location']));

        // since date and amount is fixed for this test header is a constant
        $expectedHeader = '56       RAZORPAY SOFTWARE PVT LTD                                                                 0001000000000000000030000011022020                       NACH00000000013149000000000000000000CITI000PIGW000018003                          000000001                                                           ';

        $this->assertEquals($expectedHeader, $fileContent[0]);

        $debitRow = array_map('trim', $this->parseTextRow($fileContent[1], 0, ''));

        $expectedDebitRow = [
            'ACH Transaction Code' => '67',
            'Destination Account Type' => '13',
            'Beneficiary Account Holder\'s Name' => 'dead pool',
            'User Name' => 'CTRAZORPAY',
            'Amount' => '0000000300000',
            'Destination Bank IFSC / MICR / IIN' => 'HDFC0001233',
            'Beneficiary\'s Bank Account number' => '1111111111111',
            'Sponsor Bank IFSC / MICR / IIN' => 'CITI000PIGW',
            'User Number' => 'NACH00000000013149',
            'Transaction Reference' => 'TESTMERCHA' . $response['razorpay_payment_id'],
            'Product Type' => '10',
            'UMRN' => 'UTIB6000000005844847'
        ];

        $this->assertArraySelectiveEquals($expectedDebitRow, $debitRow);
    }

    public function testDebitCreationWithMultipleTerminalsForMerchant()
    {
        $terminal = $this->fixtures->create('terminal:nach_shared_terminal');

        $this->createRecurringNachPayment();

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->assertEquals('auto', $paymentEntity['recurring_type']);

        $this->assertNotEquals($paymentEntity->getTerminalId(), $terminal->getId());

        $this->assertEquals('1citinachDTmnl', $paymentEntity->getTerminalId());

        $this->ba->adminAuth();

        $tokenEntity = $this->getEntityById('token', $paymentEntity['token_id'], true);

        $this->assertEquals('1citinachDTmnl', $tokenEntity['terminal_id']);
    }

    public function testGatewayFileDebitOnNonWorkingDay()
    {
        $fixedTime = (new Carbon())->timestamp(self::FIXED_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $this->createRecurringNachPayment();

        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testGatewayFileDebitBankResponseSuccess()
    {
        $payment = $this->createRecurringNachPayment();

        $batchFile = $this->getBatchFileToUploadForBankDebitResponse($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'debit');

        $this->assertEquals('nach', $batch['batch_type_id']);
        $this->assertEquals('CREATED', $batch['status']);
        $this->assertEquals(0, $batch['amount']);

        $batchEntity = $this->fixtures->create('batch',
            [
                'id'          => '00000000000001',
                'type'        => 'nach',
                'sub_type'    => 'debit',
                'gateway'     => 'nach_citi',
                'total_count' => '1',
            ]);

        $paymentId = $payment['razorpay_payment_id'];

        $this->fixtures->stripSign($paymentId);

        $entries = $this->createBatchRequestData($paymentId, "nach", "debit", "nach_citi", "1", "00");

        $this->runWithData($entries, $batchEntity['id']);

        $payment = $this->getEntityById('payment', $payment['razorpay_payment_id'], true);

        $this->assertEquals('authorized', $payment['status']);

        // already processed
        $this->runWithData($entries, $batchEntity['id']);
    }

    public function testDebitResponseFileProcessingViaBatchServiceSuccess()
    {
        $payment = $this->createRecurringNachPayment();

        $batchFile = $this->getBatchFileToUploadForBankDebitResponse($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'debit');

        $this->assertEquals('nach', $batch['batch_type_id']);
        $this->assertEquals('CREATED', $batch['status']);
        $this->assertEquals(0, $batch['amount']);

        $batchEntity = $this->fixtures->create('batch',
            [
                'id'          => '00000000000001',
                'type'        => 'nach',
                'sub_type'    => 'debit',
                'gateway'     => 'nach_citi',
                'total_count' => '1',
            ]);

        $paymentId = $payment['razorpay_payment_id'];

        $this->fixtures->stripSign($paymentId);

        $entries = $this->createBatchRequestData($paymentId, "nach", "debit", "nach_citi", "1", "00");

        $this->runWithData($entries, $batchEntity['id']);

        $payment = $this->getEntityById('payment', $payment['razorpay_payment_id'], true);

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testDebitResponseFileProcessingViaBatchServiceSuccessAsync()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
                  ->method('getTreatment')
                  ->will($this->returnCallback(
                        function ($mid, $feature, $mode)
                        {
                            if ($feature === RazorxTreatment::EMANDATE_ASYNC_PAYMENT_PROCESSING_ENABLED)
                            {
                                return 'on';
                            }
                            return "default";
                        })
                    );

        $payment = $this->createRecurringNachPayment();

        $batchFile = $this->getBatchFileToUploadForBankDebitResponse($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'debit');

        $this->assertEquals('nach', $batch['batch_type_id']);
        $this->assertEquals('CREATED', $batch['status']);
        $this->assertEquals(0, $batch['amount']);

        $batchEntity = $this->fixtures->create('batch',
            [
                'id'          => '00000000000001',
                'type'        => 'nach',
                'sub_type'    => 'debit',
                'gateway'     => 'nach_citi',
                'total_count' => '1',
            ]);

        $paymentId = $payment['razorpay_payment_id'];

        $this->fixtures->stripSign($paymentId);

        $entries = $this->createBatchRequestData($paymentId, "nach", "debit", "nach_citi", "1", "00");

        $this->runWithData($entries, $batchEntity['id']);

        $payment = $this->getEntityById('payment', $payment['razorpay_payment_id'], true);

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testGatewayFileDebitBankResponseSuccessAfterPaymentTimeout()
    {
        $payment = $this->createRecurringNachPayment();

        $createdAt = Carbon::today(Timezone::IST)->subDays(10)->getTimestamp();

        $this->fixtures->edit('payment', $payment['razorpay_payment_id'], ['created_at' => $createdAt]);

        $this->timeoutOldPayment();

        $paymentEntity = $this->getDbEntityById('payment', $payment['razorpay_payment_id']);

        $this->assertEquals('failed', $paymentEntity['status']);
        $this->assertEquals('BAD_REQUEST_PAYMENT_TIMED_OUT', $paymentEntity['internal_error_code']);
        $this->assertEquals('nach', $paymentEntity['method']);
        $this->assertEquals('nach_citi', $paymentEntity['gateway']);
        $this->assertEquals(300000, $paymentEntity['amount']);

        $batchFile = $this->getBatchFileToUploadForBankDebitResponse($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'debit');

        $this->assertEquals('nach', $batch['batch_type_id']);
        $this->assertEquals('CREATED', $batch['status']);
        $this->assertEquals(0, $batch['amount']);

        $batchEntity = $this->fixtures->create('batch',
            [
                'id'          => '00000000000001',
                'type'        => 'nach',
                'sub_type'    => 'debit',
                'gateway'     => 'nach_citi',
                'total_count' => '1',
            ]);

        $paymentId = $payment['razorpay_payment_id'];

        $this->fixtures->stripSign($paymentId);

        $entries = $this->createBatchRequestData($paymentId, "nach", "debit", "nach_citi", "1", "00");

        $this->runWithData($entries, $batchEntity['id']);

        $payment = $this->getEntityById('payment', $payment['razorpay_payment_id'], true);

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testGatewayFileDebitBankResponseFailure()
    {
        $payment = $this->createRecurringNachPayment();

        $batchFile = $this->getBatchFileToUploadForBankDebitResponse($payment, "0", "04");

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'debit');

        $this->assertEquals('nach', $batch['batch_type_id']);
        $this->assertEquals('CREATED', $batch['status']);

        $batchEntity = $this->fixtures->create('batch',
            [
                'id'          => '00000000000001',
                'type'        => 'nach',
                'sub_type'    => 'debit',
                'gateway'     => 'nach_citi',
                'total_count' => '1',
            ]);

        $paymentId = $payment['razorpay_payment_id'];

        $this->fixtures->stripSign($paymentId);

        $entries = $this->createBatchRequestData($paymentId, "nach", "debit", "nach_citi", "0", "04");

        $this->runWithData($entries, $batchEntity['id']);

        $payment = $this->getEntityById('payment', $payment['razorpay_payment_id'], true);

        $this->assertEquals('BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE', $payment['internal_error_code']);
        $this->assertEquals('failed', $payment['status']);

        // already processed
        $this->runWithData($entries, $batchEntity['id']);
    }

    public function testGatewayFileSplitDebit()
    {
        $initialPayment = $this->createAcceptedToken();

        for ($i = 0; $i <= 10; $i++)
        {
            $order = $this->fixtures->create('order', [
                'amount' => 300000,
                'method' => 'nach',
            ]);

            $payment = [
                'contact'     => '9876543210',
                'email'       => 'r@g.c',
                'customer_id' => 'cust_1000000000cust',
                'currency'    => 'INR',
                'method'      => 'nach',
                'amount'      => 300000,
                'recurring'   => true,
                'token'       => $initialPayment[Payment::TOKEN_ID],
                'order_id'    => $order->getPublicId(),
            ];

            $request = [
                'method'  => 'POST',
                'url'     => '/payments/create',
                'content' => $payment
            ];

            $this->ba->privateAuth();

            $this->makeRequestAndGetContent($request);
        }

        $this->ba->cronAuth();

        $data = $this->testData['testGatewayFileDebit'];

        $this->startTest($data);

        $files = $this->getEntities('file_store', ['type' => 'citi_nach_debit'], true);

        $this->assertCount(4, $files['items']);

        $files = $this->getEntities('file_store', ['type' => 'citi_nach_debit_summary'], true);

        $this->assertCount(4, $files['items']);
    }

    public function testGatewaySuccessRegistrationResponseFile()
    {
        $payment = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('captured', $payment['status']);
        $this->assertEmpty($payment['internal_error_code'], "Payment should not have some Error Code");
        $this->assertEmpty($payment['error_description'], "Payment should not have some Error Reason");

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNotNull($token['gateway_token']);
        $this->assertEquals('confirmed', $token['recurring_status']);
        $this->assertEmpty($token['recurring_failure_reason'], "Token should not have some Error Reason");
    }

    public function testGatewayFailureRegistrationResponseFile()
    {
        $paymentResponse = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($paymentResponse, 'Rejected', 'No such account');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getEntityById('payment', $paymentResponse['id'], true);

        $this->assertEquals('failed', $payment['status']);
        $this->assertStringStartsWith("BAD_REQUEST_", $payment['internal_error_code']);
        $this->assertNotEmpty($payment['error_description'], "Payment should have some Error Reason");

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);
        $this->assertEquals('rejected', $token['recurring_status']);
        $this->assertEquals('No such account', $token['recurring_details']['failure_reason']);

        $batchEntity = $this->getEntityById('batch', $batch['id'], true);

        $this->assertEquals(1, $batchEntity['success_count']);

        // already processed test
        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($paymentResponse, 'Rejected', 'No such account');
        $batch     = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $batchEntity = $this->getEntityById('batch', $batch['id'], true);

        $this->assertEquals(1, $batchEntity['success_count']);
    }

    public function testGatewayFailureRegistrationResponseFileInitialReject()
    {
        $payment = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment, 'Initial Reject', 'END DATE BEFORE CURENT BUSINESS DATE NOT ALLOWED');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals('failed', $payment['status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);
        $this->assertEquals('rejected', $token['recurring_status']);
        $this->assertEquals('END DATE BEFORE CURENT BUSINESS DATE NOT ALLOWED', $token['recurring_details']['failure_reason']);
    }

    public function testGatewayInitialRegistrationResponseFile()
    {
        $payment = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment, 'Initial');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals('created', $payment['status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);
        $this->assertEquals('initiated', $token['recurring_status']);
    }

    public function testGatewayInitialRegistrationResponseFilePendingResponse()
    {
        $payment = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment, 'Pending');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals('created', $payment['status']);
        $this->assertEmpty($payment['internal_error_code'], "Payment should not have some Error Code");
        $this->assertEmpty($payment['error_description'], "Payment should not have some Error Reason");

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);
        $this->assertEquals('initiated', $token['recurring_status']);
        $this->assertEmpty($token['recurring_failure_reason'], "Token should not have some Error Reason");
    }

    public function testGatewayInitialRegistrationResponseFilePendingFromBankResponse()
    {
        $payment = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment, 'Pending for confirmation from Destination Bank');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals('created', $payment['status']);
        $this->assertEmpty($payment['internal_error_code'], "Payment should not have some Error Code");
        $this->assertEmpty($payment['error_description'], "Payment should not have some Error Reason");

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);
        $this->assertEquals('initiated', $token['recurring_status']);
        $this->assertEmpty($token['recurring_failure_reason'], "Token should not have some Error Reason");
    }

    public function testTokenUpdateBatchFile()
    {
        $payment = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('captured', $payment['status']);
        $this->assertEmpty($payment['internal_error_code'], "Payment should not have some Error Code");
        $this->assertEmpty($payment['error_description'], "Payment should not have some Error Reason");

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNotNull($token['gateway_token']);
        $this->assertEquals('confirmed', $token['recurring_status']);
        $this->assertEmpty($token['recurring_failure_reason'], "Token should not have some Error Reason");

        $data[] = [
            'token_id' => $token['id'],
            'old_ifsc' => 'HDFC0001233',
            'new_ifsc' => 'MAHG0004000'
        ];

        $excel = (new ExcelExport)->setSheets(function() use ($data) {

            $sheetsInfo[] = (new ExcelSheetExport($data))->setTitle('Sheet1');

            return $sheetsInfo;
        });

        $data = $excel->raw('Xlsx');

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);

        $file = (new TestingFile('update data.xlsx', $handle));

        $request = [
            'url'     => $url,
            'method'  => 'POST',
            'content' => [
                'type'     => 'nach',
                'sub_type' => 'update',
                'gateway'  => 'ifsc',
            ],
            'files'   => [
                'file' => $file,
            ],
        ];

        $this->makeRequestAndGetContent($request);

        $updatedToken = $this->getDbLastEntityToArray('token');

        $this->assertEquals('MAHG0004000', $updatedToken['ifsc']);

        $this->assertEquals('HDFC0001233', $token['ifsc']);
    }

    protected function createRecurringNachPayment($overideData = [])
    {
        $initialPayment = $this->createAcceptedToken($overideData);

        $tokenId = $initialPayment[Payment::TOKEN_ID];

        $order = $this->fixtures->create('order', [
            'amount' => 300000,
            'method' => 'nach',
        ]);

        $payment = [
            'contact'     => '9876543210',
            'email'       => 'r@g.c',
            'customer_id' => 'cust_1000000000cust',
            'currency'    => 'INR',
            'method'      => 'nach',
            'amount'      => 300000,
            'recurring'   => true,
            'token'       => $tokenId,
            'order_id'    => $order->getPublicId(),
        ];

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function createNachDebitPayment(string $tokenID)
    {
        $order = $this->fixtures->create('order', [
            'amount' => 300000,
            'method' => 'nach',
        ]);

        $payment = [
            'contact'     => '9876543210',
            'email'       => 'r@g.c',
            'customer_id' => 'cust_1000000000cust',
            'currency'    => 'INR',
            'method'      => 'nach',
            'amount'      => 300000,
            'recurring'   => true,
            'token'       => $tokenID,
            'order_id'    => $order->getPublicId(),
        ];

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function createAcceptedToken($overideData = [])
    {
        $payment = $this->createDummyRegisterToken($overideData);

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        return $payment;
    }

    protected function makeRequestWithGivenUrlAndFile($url, $file, $type = 'register')
    {
        $request = [
            'url'     => $url,
            'method'  => 'POST',
            'content' => [
                'type'     => 'nach',
                'sub_type' => $type,
                'gateway'  => 'nach_citi',
            ],
            'files'   => [
                'file' => $file,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function getBatchFileToUploadForBankDebitResponse($payment, $status = '1', $errorCode = '00')
    {
        $paymentId = $payment['razorpay_payment_id'];
        $this->fixtures->stripSign($paymentId);

        $data = '56       RAZORPAY SOFTWARE PVT LTD                             000000000                           000005000000000000000020001701202047642224498136619848   NACH00000000013149000000000000000000CITI000PIGW000018003                          00000000227
67         10                  ABIJITO GUHA                            17012020        RAZORPAY SOFTWARE PV             000000030000047642224504081750481'. $status . $errorCode . 'HDFC00024971111111111111                      CITI000PIGWNACH00000000013149CTTATAAIAA' . $paymentId . '      10 000000000000000HDFC0000000010936518
';

        $name = 'temp.txt';

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);

        $file = (new TestingFile($name, $handle));

        return $file;
    }

    protected function getBatchFileToUploadForBankRegisterResponse($payment, $status = 'Accepted', $failureReason = '')
    {
        $paymentId = $payment['id'];

        $this->fixtures->stripSign($paymentId);

        $sheets = [
            'sheet1' => [
                'config' => [
                    'start_cell' => 'A1',
                ],
                'items'  => [
                    [
                        'Sr.no'                  => '1',
                        'Category Code'          => 'U099',
                        'Category Description'   => 'Others',
                        'Start date'             => '21/11/2019',
                        'End date'               => '21/11/2029',
                        'Client code'            => 'CTRAZORPAY',
                        'Unique reference no'    => $paymentId,
                        'Account No'             => '1111111111111',
                        'Account Holder name'    => 'dead pool',
                        'Account type'           => 'savings',
                        'Bank Name'              => 'HDFC',
                        'Bank MICR / IFSC'       => 'HDFC0001233',
                        'Amount'                 => '10000',
                        'Lot'                    => '1',
                        'Softcopy Received Date' => '06/12/19',
                        'Status'                 => $status,
                        'UMRN'                   => 'UTIB6000000005844847',
                        'Remark'                 => $failureReason,
                    ],
                ],
            ],
        ];

        $name = 'RAZORP_EMANDATE_NACH00000000010000_21112019_test';

        $excel = (new ExcelExport)->setSheets(function() use ($sheets) {
                $sheetsInfo = [];
                foreach ($sheets as $sheetName => $data)
                {
                    $sheetsInfo[$sheetName] = (new ExcelSheetExport($data['items']))->setTitle($sheetName)->setStartCell($data['config']['start_cell'])->generateAutoHeading(true);
                }

                return $sheetsInfo;
            });

        $data = $excel->raw('Xlsx');

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);

        $file = (new TestingFile('Register MIS.xlsx', $handle));

        return $file;
    }

    protected function createDummyRegisterToken(array $overideData = [])
    {
        $this->createOrder([
            'amount' => 0,
            'method' => 'nach',
            E::INVOICE => [
                'amount' => 0,
                E::SUBSCRIPTION_REGISTRATION => [
                    'token_id'   => '100000000token',
                    'max_amount' => 1000000,
                    'auth_type'  => 'physical',
                    E::PAPER_MANDATE => [
                        'amount' => 1000000,
                        'status' => PaperMandate\Status::AUTHENTICATED,
                        'uploaded_file_id' => '1000000000file',
                        E::BANK_ACCOUNT => [
                            'ifsc_code'    => $overideData['ifsc'] ?? 'HDFC0001233',
                            'account_type' => $overideData['account_type'] ?? 'savings',
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->doAuthPayment([
            "amount"      => 0,
            "currency"    => "INR",
            "method"      => "nach",
            "order_id"    => "order_100000000order",
            "customer_id" => "cust_1000000000cust",
            "recurring"   => true,
            "contact"     => "9483159238",
            "email"       => "r@g.c",
            "auth_type"   => "physical",
        ]);

        return $this->getEntityById('payment', $response['razorpay_payment_id'], true);
    }

    protected function createToken(array $overrideWith = [])
    {
        $payment = array_pull($overrideWith, E::PAYMENT, []);

        $token = $this->fixtures
            ->create(
                E::TOKEN,
                array_merge(
                    [
                        'id'              => '100000000token',
                    ],
                    $overrideWith
                )
            );

        $this->createPayment($payment);

        return $token;
    }

    protected function createPayment(array $overrideWith = [])
    {
        $order = array_pull($overrideWith, E::ORDER, []);

        $this->createOrder($order);

        $payment = $this->fixtures
            ->create(
                E::PAYMENT,
                array_merge(
                    [
                        'id'              => '1000000payment',
                    ],
                    $overrideWith
                )
            );

        return $payment;
    }

    protected function createOrder(array $overrideWith = [])
    {
        $invoice = array_pull($overrideWith, E::INVOICE, []);

        $order = $this->fixtures
            ->create(
                E::ORDER,
                array_merge(
                    [
                        'id'              => '100000000order',
                        'amount'          => 100000,
                    ],
                    $overrideWith
                )
            );

        $this->createInvoiceForOrder($invoice);

        return $order;
    }

    protected function createInvoiceForOrder(array $overrideWith = [])
    {
        $subscriptionRegistration = array_pull($overrideWith, E::SUBSCRIPTION_REGISTRATION, []);

        $subscriptionRegistrationId = UniqueIdEntity::generateUniqueId();

        $order = $this->fixtures
            ->create(
                'invoice',
                array_merge(
                    [
                        'id'              => '1000000invoice',
                        'order_id'        => '100000000order',
                        'entity_type'     => 'subscription_registration',
                        'entity_id'       => $subscriptionRegistrationId,
                    ],
                    $overrideWith
                )
            );

        $subscriptionRegistration['id'] = $subscriptionRegistrationId;

        $this->createSubscriptionRegistration($subscriptionRegistration);

        return $order;
    }

    protected function createSubscriptionRegistration(array $overrideWith = [])
    {
        $paperMandate = array_pull($overrideWith, E::PAPER_MANDATE, []);

        $paperMandateId = UniqueIdEntity::generateUniqueId();

        $subscriptionRegistration = $this->fixtures
            ->create(
                'subscription_registration',
                array_merge(
                    [
                        'method'          => 'nach',
                        'notes'           => [],
                        'entity_type'     => 'paper_mandate',
                        'entity_id'       => $paperMandateId,
                    ],
                    $overrideWith
                )
            );

        $paperMandate['id'] = $paperMandateId;

        $this->createPaperMandate($paperMandate);

        return $subscriptionRegistration;
    }

    protected function createPaperMandate(array $overrideWith = [])
    {
        $bankAccountId = UniqueIdEntity::generateUniqueId();

        $bankAccount = array_pull($overrideWith, E::BANK_ACCOUNT, []);

        $this->fixtures->create(
            E::CUSTOMER,
            ['id' => '1000000000cust']
        );

        $paperMandate = $this->fixtures
            ->create(
                'paper_mandate',
                array_merge(
                    [
                        'bank_account_id'   => $bankAccountId,
                        'amount'            => 1000,
                        'status'            => PaperMandate\Status::CREATED,
                        'debit_type'        => PaperMandate\DebitType::MAXIMUM_AMOUNT,
                        'type'              => PaperMandate\Type::CREATE,
                        'frequency'         => PaperMandate\Frequency::YEARLY,
                        'start_at'          => (new Carbon('+5 day'))->timestamp,
                        'utility_code'      => 'NACH00000000013149',
                        'sponsor_bank_code' => 'RATN0TREASU',
                        'terminal_id'       => '1citinachDTmnl',
                    ],
                    $overrideWith
                )
            );

        $bankAccount['id'] = $bankAccountId;

        $this->createBankAccount($bankAccount);

        return $paperMandate;
    }

    protected function createBankAccount(array $overrideWith = [])
    {
        $bankAccount = $this->fixtures
            ->create(
                'bank_account',
                array_merge(
                    [
                        'beneficiary_name' => 'dead pool',
                        'ifsc_code'        => 'HDFC0001233',
                        'account_number'   => '1111111111111',
                        'account_type'     => 'savings',

                    ],
                    $overrideWith
                )
            );

        return $bankAccount;
    }

    protected function parseTextRow(string $row, int $ix, string $delimiter, array $headings = null)
    {
        $values=[
            Headings::ACH_TRANSACTION_CODE             =>  substr($row, 0, 2),
            Headings::CONTROL_9S                       =>  substr($row, 2, 9),
            Headings::DESTINATION_ACCOUNT_TYPE         =>  substr($row, 11, 2),
            Headings::LEDGER_FOLIO_NUMBER              =>  substr($row, 13, 3),
            Headings::CONTROL_15S                      =>  substr($row, 16, 15),
            Headings::BENEFICIARY_ACCOUNT_HOLDER_NAME  =>  substr($row, 31, 40),
            Headings::CONTROL_9SS                      =>  substr($row, 71, 9),
            Headings::CONTROL_7S                       =>  substr($row, 80, 7),
            Headings::USER_NAME                        =>  substr($row, 87, 20),
            Headings::CONTROL_13S                      =>  substr($row, 107, 13),
            Headings::AMOUNT                           =>  substr($row, 120, 13),
            Headings::ACH_ITEM_SEQ_NO                  =>  substr($row, 133, 10),
            Headings::CHECKSUM                         =>  substr($row, 143, 10),
            Headings::FLAG                             =>  substr($row, 153, 1),
            Headings::REASON_CODE                      =>  substr($row, 154, 2),
            Headings::DESTINATION_BANK_IFSC            =>  substr($row, 156, 11),
            Headings::BENEFICIARY_BANK_ACCOUNT_NUMBER  =>  substr($row, 167, 35),
            Headings::SPONSOR_BANK_IFSC                =>  substr($row, 202, 11),
            Headings::USER_NUMBER                      =>  substr($row, 213, 18),
            Headings::TRANSACTION_REFERENCE            =>  substr($row, 231, 30),
            Headings::PRODUCT_TYPE                     =>  substr($row, 261, 3),
            Headings::BENEFICIARY_AADHAR_NUMBER        =>  substr($row, 264, 15),
            Headings::UMRN                             =>  substr($row, 279, 20),
            Headings::FILLER                           =>  substr($row, 299, 7),
        ];

        return $values;
    }

    public function runWithData($entries, $batchId)
    {
        $this->ba->batchAppAuth();

        $testData = $this->testData['process_via_batch_service'];

        $testData['request']['server']['HTTP_X_Batch_Id'] = $batchId;

        $testData['request']['content'] = $entries;

        $this->runRequestResponseFlow($testData);
    }

    public function createBatchRequestData($paymentId, $type, $subType, $gateway, $flag, $reasonCode)
    {
        $entries = [
            "data" => [
                Headings::ACH_TRANSACTION_CODE             =>  '67',
                Headings::CONTROL_9S                       =>  '         ',
                Headings::DESTINATION_ACCOUNT_TYPE         =>  '10',
                Headings::LEDGER_FOLIO_NUMBER              =>  '   ',
                Headings::CONTROL_15S                      =>  '               ',
                Headings::BENEFICIARY_ACCOUNT_HOLDER_NAME  =>  'ABIJITO GUHA                            ',
                Headings::CONTROL_9SS                      =>  '17012020 ',
                Headings::CONTROL_7S                       =>  '       ',
                Headings::USER_NAME                        =>  'RAZORPAY SOFTWARE PV',
                Headings::CONTROL_13S                      =>  '             ',
                Headings::AMOUNT                           =>  '0000000300000',
                Headings::ACH_ITEM_SEQ_NO                  =>  '4764222450',
                Headings::CHECKSUM                         =>  '4081750481',
                Headings::FLAG                             =>  $flag,
                Headings::REASON_CODE                      =>  $reasonCode,
                Headings::DESTINATION_BANK_IFSC            =>  'HDFC0001233',
                Headings::BENEFICIARY_BANK_ACCOUNT_NUMBER  =>  '1111111111111                      ',
                Headings::SPONSOR_BANK_IFSC                =>  'CITI000PIGW',
                Headings::USER_NUMBER                      =>  'NACH00000000013149',
                Headings::TRANSACTION_REFERENCE            =>  'CTTATAAIAA' . $paymentId . '      ',
                Headings::PRODUCT_TYPE                     =>  '10 ',
                Headings::BENEFICIARY_AADHAR_NUMBER        =>  '000000000000000',
                Headings::UMRN                             =>  'HDFC0000000010936518',
                Headings::FILLER                           =>  '       ',
            ],
            'type'        => $type,
            'sub_type'    => $subType,
            'gateway'     => $gateway,
        ];

        return $entries;
    }

    protected function makeBatchDebitPayment($payment, $status)
    {
        $this->fixtures->create(
            'enach',
            [
                'payment_id' => $payment['id'],
                'action'     => 'authorize',
                'bank'       => 'UTIB',
                'amount'     => $payment['amount'],
            ]
        );

        $file = $this->getBatchDebitFile($payment, $status);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $fileContents = file($file);

        $debitResponseRow = $fileContents[1];

        $flag = substr($debitResponseRow, 153, 1);

        $errCode = substr($debitResponseRow, 154, 2);

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $file, 'debit');

        $batchEntity = $this->fixtures->create('batch',
            [
                'id'          => '00000000000001',
                'type'        => 'nach',
                'sub_type'    => 'debit',
                'gateway'     => 'nach_citi',
                'total_count' => '1',
            ]);

        $paymentId = $payment['id'];

        $entries = [
            "data" => [
                Headings::ACH_TRANSACTION_CODE             =>  '67',
                Headings::CONTROL_9S                       =>  '         ',
                Headings::DESTINATION_ACCOUNT_TYPE         =>  '10',
                Headings::LEDGER_FOLIO_NUMBER              =>  '   ',
                Headings::CONTROL_15S                      =>  '               ',
                Headings::BENEFICIARY_ACCOUNT_HOLDER_NAME  =>  'ABIJITO GUHA                            ',
                Headings::CONTROL_9SS                      =>  '17012020 ',
                Headings::CONTROL_7S                       =>  '       ',
                Headings::USER_NAME                        =>  'RAZORPAY SOFTWARE PV',
                Headings::CONTROL_13S                      =>  '             ',
                Headings::AMOUNT                           =>  '0000000300000',
                Headings::ACH_ITEM_SEQ_NO                  =>  '4764222450',
                Headings::CHECKSUM                         =>  '4081750481',
                Headings::FLAG                             =>  $flag,
                Headings::REASON_CODE                      =>  $errCode,
                Headings::DESTINATION_BANK_IFSC            =>  'HDFC0002497',
                Headings::BENEFICIARY_BANK_ACCOUNT_NUMBER  =>  '1111111111111                      ',
                Headings::SPONSOR_BANK_IFSC                =>  'CITI000PIGW',
                Headings::USER_NUMBER                      =>  'NACH00000000013149',
                Headings::TRANSACTION_REFERENCE            =>  'CTTATAAIAA' . $paymentId . '      ',
                Headings::PRODUCT_TYPE                     =>  '10 ',
                Headings::BENEFICIARY_AADHAR_NUMBER        =>  '000000000000000',
                Headings::UMRN                             =>  'HDFC0000000010936518',
                Headings::FILLER                           =>  '       ',
            ],
            'type'        => 'nach',
            'sub_type'    => 'debit',
            'gateway'     => 'nach_citi',
        ];

        $this->runWithData($entries, $batchEntity['id']);

        return $batch;
    }

    protected function getBatchDebitFile($payment, $status)
    {
        $paymentId = $payment['id'];
        $this->fixtures->stripSign($paymentId);

        $data = '56       RAZORPAY SOFTWARE PVT LTD                             000000000                           000005000000000000000020001701202047642224498136619848   NACH00000000013149000000000000000000CITI000PIGW000018003                          00000000227
67         10                  ABIJITO GUHA                            17012020        RAZORPAY SOFTWARE PV             000000030000047642224504081750481'. $status['status'] . $status['error_code']. 'HDFC00024971111111111111                      CITI000PIGWNACH00000000013149CTTATAAIAA' . $paymentId . '      10 000000000000000HDFC0000000010936518
';

        $name = 'temp.txt';

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);

        $file = (new TestingFile($name, $handle));

        return $file;
    }

}
