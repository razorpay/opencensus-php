<?php

namespace RZP\Tests\Functional\Gateway\Enach\Netbanking;

use Mail;
use Excel;
use Queue;
Use Carbon\Carbon;

use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Customer\Token;
use RZP\Services\Mock\BeamService;
use RZP\Tests\Functional\TestCase;
use RZP\Excel\Export as ExcelExport;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Excel\ExportSheet as ExcelSheetExport;
use Illuminate\Http\Testing\File as TestingFile;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Gateway\Enach\Citi\NachDebitFileHeadings as Headings;

class EnachNetbankingNpciYesbTest extends TestCase
{
    use FileHandlerTrait;
    use DbEntityFetchTrait;
    use AttemptTrait;
    use AttemptReconcileTrait;
    use PartnerTrait;

    const FIXED_WORKING_DAY_TIME     = 1583548200;  // 07-03-2020 8:00 AM
    const FIXED_NON_WORKING_DAY_TIME = 1583634600;  // 08-03-2020 8:00 AM (sunday)

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/EnachNetbankingNpciGatewayTestData.php';

        $fixedTime = (new Carbon())->timestamp(self::FIXED_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create(
                                'terminal:shared_enach_npci_netbanking_yesb_terminal',
                                [
                                    Terminal\Entity::ID               => Terminal\Shared::ENACH_NPCI_NETBANKING_YESB_TERMINAL,
                                    Terminal\Entity::GATEWAY_ACQUIRER => Payment\Gateway::ACQUIRER_YESB
                                ]
                               );

        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->enableEmandate();
        $this->fixtures->merchant->addFeatures([Feature\Constants::CHARGE_AT_WILL]);

        $this->gateway = 'enach_npci_netbanking';

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testDebitFileGenerationYesb()
    {
        $response = $this->makeDebitPayment();

        $paymentId = $this->updateCreatedAtOfPayment($response['razorpay_payment_id']);

        $this->ba->adminAuth();

        Queue::fake();

        $content = $this->startTest($this->testData['testDebitFileGenerationYesb']);

        $content = $content['items'][0];

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'enach_npci_nb_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'csv',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        $enach = $this->getLastEntity('enach', true);

        $this->assertArraySelectiveEquals(
            [
                'payment_id' => $paymentId,
                'action'     => 'authorize',
                'bank'       => 'UTIB',
                'status'     => null,
            ],
            $enach
        );
    }

    public function testDebitFileGenerationYesbTxtFormat()
    {
        $response = $this->makeDebitPayment($amount = 300000, $notes = true);

        $paymentId = $this->updateCreatedAtOfPayment($response['razorpay_payment_id']);

        $this->fixtures->stripSign($response['razorpay_payment_id']);

        $this->ba->adminAuth();

        Queue::fake();

        $this->mockBeamTest(function ($pushData, $intervalInfo, $mailInfo, $synchronous)
        {
            return [
                'failed' => null,
                'success' => $pushData['files'],
            ];
        });

        $this->testData[__FUNCTION__] = $this->testData['testDebitFileGenerationYesbTxtFormat'];

        $content = $this->startTest();

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', [], true);

        $this->assertCount(1, $files['items']);

        $debit = $files['items'][0];


        $expectedFileContentDebit = [
            'type'        => 'enach_npci_nb_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
            'name'        => 'yesbank/nach/input_file/NACH_DR_07032020_shared_utility_code_RAZORPAY_001_test',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentDebit, $debit);

        $fileContent = explode("\n", file_get_contents('storage/files/filestore/' . $debit['location']));

        // since date and amount is fixed for this test header is a constant
        $expectedHeader = '56       RAZORPAY SOFTWARE PVT LTD                                                                 0001000000000000000030000007032020                       shared_utility_cod000000000000000000                                              000000001                                                           ';

        $this->assertEquals($expectedHeader, $fileContent[0]);

        $debitRow = array_map('trim', $this->parseTextRow($fileContent[1], 0, ''));

        s($debitRow);

        $expectedDebitRow = [
            'ACH Transaction Code' => '67',
            'Destination Account Type' => '10',
            'Beneficiary Account Holder\'s Name' => 'Test account',
            'Amount' => '0000000300000',
            'Destination Bank IFSC / MICR / IIN' => 'UTIB0000123',
            'Beneficiary\'s Bank Account number' => '1111111111111',
            'User Number' => 'shared_utility_cod',
            'Transaction Reference' => 'RZPTESTMERCHANT' . $response['razorpay_payment_id'],
            'Product Type' => '10',
            'UMRN' => 'UTIB6000000005844847'
        ];

        $this->assertArraySelectiveEquals($expectedDebitRow, $debitRow);
    }

    public function testDebitFileGenerationForEarlyDebitPresentment()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::EARLY_MANDATE_PRESENTMENT]);

        $response = $this->makeDebitPayment();

        $paymentId = $this->updateCreatedAtOfPayment($response['razorpay_payment_id']);

        $this->ba->adminAuth();

        Queue::fake();

        $content = $this->startTest($this->testData['testDebitFileGenerationYesbEarlyDebit']);

        $content = $content['items'][0];

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'enach_npci_nb_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'csv',
            'name'        => 'Npci/Enach/Netbanking/yesbank/nach/input_file/NACH_DR_07032020_shared_utility_code_RAZORPAY_MUT001'
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        $enach = $this->getLastEntity('enach', true);

        $this->assertArraySelectiveEquals(
            [
                'payment_id' => $paymentId,
                'action'     => 'authorize',
                'bank'       => 'UTIB',
                'status'     => null,
            ],
            $enach
        );
    }

    public function testDebitFileGenerationMultipleUtilityCode()
    {
        $response = $this->makeDebitPayment();

        $this->updateCreatedAtOfPayment($response['razorpay_payment_id']);

        $this->fixtures->create(
                   'terminal:direct_enach_npci_netbanking_terminal',
                   [Terminal\Entity::GATEWAY_ACQUIRER => Payment\Gateway::ACQUIRER_YESB]
                  );

        $response = $this->makeDebitPayment();

        $this->updateCreatedAtOfPayment($response['razorpay_payment_id']);

        $this->ba->adminAuth();

        Queue::fake();

        $this->testData[__FUNCTION__] = $this->testData['testDebitFileGenerationYesb'];

        $content = $this->startTest();

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', ['count' => 2], true);

        $directTerminalFile = $files['items'][0];
        $sharedTerminalFile = $files['items'][1];

        $fileNamingConvention = 'Npci/Enach/Netbanking/yesbank/nach/input_file/NACH_DR_{$date}_{$utilityCode}_RAZORPAY_001';
        $date = Carbon::now(Timezone::IST)->format('dmY');

        $expectedFileContentForDirectTerminal = [
            'type'        => 'enach_npci_nb_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'csv',
            'name'        => strtr($fileNamingConvention, ['{$date}' => $date, '{$utilityCode}' => 'direct_utility_code'])
        ];

        $expectedFileContentForSharedTerminal = [
            'type'        => 'enach_npci_nb_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'csv',
            'name'        => strtr($fileNamingConvention, ['{$date}' => $date, '{$utilityCode}' => 'shared_utility_code'])
        ];

        $this->assertArraySelectiveEquals($expectedFileContentForDirectTerminal, $directTerminalFile);
        $this->assertArraySelectiveEquals($expectedFileContentForSharedTerminal, $sharedTerminalFile);
    }

    public function testDebitFileGenerationMultipleSponsorBanks()
    {
        $yesbTerminalPaymentResponse = $this->makeDebitPayment();

        $this->updateCreatedAtOfPayment($yesbTerminalPaymentResponse['razorpay_payment_id']);

        $this->fixtures->stripSign($yesbTerminalPaymentResponse['razorpay_payment_id']);

        $this->fixtures->terminal->disableTerminal($this->sharedTerminal['id']);

        $sharedCitiTerminal = $this->fixtures->create('terminal:shared_enach_npci_netbanking_terminal');

        $this->fixtures->terminal->enableTerminal($sharedCitiTerminal['id']);

        $this->makeDebitPayment();

        $this->ba->adminAuth();

        Queue::fake();

        $content = $this->startTest($this->testData['testDebitFileGenerationYesb']);

        $content = $content['items'][0];

        $file = $this->getLastEntity('file_store', true);

        $fileContent = file_get_contents('storage/files/filestore/' . $file['location']);

        $fileRows = array_filter(explode("\r\n", $fileContent));

        $this->assertEquals(2, count($fileRows));

        $paymentRow = explode(',', $fileRows[1]);

        $this->assertEquals($yesbTerminalPaymentResponse['razorpay_payment_id'], $paymentRow[0]);

        $expectedFileContent = [
            'type'        => 'enach_npci_nb_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'csv',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        $this->fixtures->terminal->disableTerminal($sharedCitiTerminal['id']);

        $this->fixtures->terminal->enableTerminal($this->sharedTerminal['id']);
    }

    public function testDebitFileReconciliation()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastEntity('payment');

        $fileStatuses = [
            'status'     => 'ACCEPTED',
            'error_code' => '',
            'error_desc' => '',
        ];

        Carbon::setTestNow(Carbon::now()->addDays(29));

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['batch_type_id']);
        $this->assertEquals('CREATED', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('captured', $payment['status']);

        $transaction = $payment->transaction;

        $this->assertNotNull($transaction['reconciled_at']);

        $enach = $this->getDbEntities('enach', ['payment_id' => $payment['id']])->first()->toArray();

        $this->assertArraySelectiveEquals(
            [
                'status' => 'ACCEPTED',
            ],
            $enach
        );
    }

    public function testDebitFileRejectResponse()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastEntity('payment');

        $fileStatuses = [
            'status'     => 'REJECTED',
            'error_code' => '04',
            'error_desc' => 'Balance insufficient',
        ];

        Carbon::setTestNow(Carbon::now()->addDays(29));

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['batch_type_id']);
        $this->assertEquals('CREATED', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals('BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE', $payment['internal_error_code']);

        $enach = $this->getDbEntities('enach', ['payment_id' => $payment['id']])->first()->toArray();

        $this->assertEquals('04', $enach['error_code']);
        $this->assertEquals('Balance insufficient', $enach['error_message']);

        $this->assertEquals('REJECTED', $enach['status']);
    }

    public function testDebitFileRejectResponseWithEmptyErrorCode()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastEntity('payment');

        $fileStatuses = [
            'status'     => 'REJECTED',
            'error_code' => '',
            'error_desc' => 'Record Level Error:Invalid Mandate Info......',
        ];

        Carbon::setTestNow(Carbon::now()->addDays(29));

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['batch_type_id']);
        $this->assertEquals('CREATED', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals('BAD_REQUEST_EMANDATE_INACTIVE', $payment['internal_error_code']);

        $enach = $this->getDbEntities('enach', ['payment_id' => $payment['id']])->first()->toArray();

        $this->assertEquals('', $enach['error_code']);
        $this->assertEquals('Record Level Error:Invalid Mandate Info......', $enach['error_message']);

        $this->assertEquals('REJECTED', $enach['status']);
    }

    public function testDebitFileResponseNarrationYesb()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastEntity('payment');

        $fileStatuses = [
            'status'     => 'REJECTED',
            'error_code' => '04',
            'error_desc' => 'Balance insufficient',
        ];

        Carbon::setTestNow(Carbon::now()->addDays(29));

        $this->fixtures->create(
            'enach',
            [
                'payment_id' => $payment['id'],
                'action'     => 'authorize',
                'bank'       => 'UTIB',
                'amount'     => $payment['amount'],
            ]
        );

        $data = [
            [
                'Presentation Date' => Carbon::now(Timezone::IST)->format('m/d/Y'),
                'UMRN' => 'UTIB6000000005844847',
                'Transaction Ref No' => "RZPTESTMERCHANT" . $payment['id'],
                'Utility Code' => '',
                'Bank A/c Number' => '',
                'Account Holder Name' => '',
                'Bank' => '',
                'IFSC/MICR' => '',
                'Amount' => $payment['amount'] / 100,
                'Reference 1' => '',
                'Reference 2' => '',
                'Status' => $fileStatuses['status'],
                'Reason Code' => $fileStatuses['error_code'],
                'Reason Discription' => $fileStatuses['error_desc'],
                'User Reference' => '',
            ]
        ];

        $entry = [
            "data"        => $data[0],
            'type'        => 'emandate',
            'sub_type'    => 'debit',
            'gateway'     => 'enach_npci_netbanking',
        ];

        $this->runWithData($entry, "test_batch");

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals('BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE', $payment['internal_error_code']);

        $enach = $this->getDbEntities('enach', ['payment_id' => $payment['id']])->first()->toArray();

        $this->assertEquals('04', $enach['error_code']);
        $this->assertEquals('Balance insufficient', $enach['error_message']);

        $this->assertEquals('REJECTED', $enach['status']);
    }

    public function testDebitFilePendingResponse()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastEntity('payment');

        $fileStatuses = [
            'status'     => 'PENDING',
            'error_code' => '98',
            'error_desc' => 'BANK EXTENDED',
        ];

        Carbon::setTestNow(Carbon::now()->addDays(29));

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['batch_type_id']);
        $this->assertEquals('CREATED', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('created', $payment['status']);
    }

    // enach refund migration to scrooge
    public function testDebitFileReconciliationRefund()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastEntity('payment');

        $fileStatuses = [
            'status'     => 'ACCEPTED',
            'error_code' => '',
            'error_desc' => '',
        ];

        Carbon::setTestNow(Carbon::now()->addDays(29));

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('CREATED', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('captured', $payment['status']);

        $transaction = $payment->transaction;

        $this->assertNotNull($transaction['reconciled_at']);

        $response = $this->refundPayment('pay_' . $payment['id'], null, ['is_fta' => true]);

        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals($response['id'], $refund['id']);

        $this->assertEquals('pay_' . $payment['id'], $refund['payment_id']);

        // $this->assertEquals('initiated', $refund['status']);
        $this->assertEquals('created', $refund['status']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], $refund['id']);

        $this->assertEquals('Test Merchant Refund ' . $payment['id'], $fundTransferAttempt['narration']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals('UTIB0000123', $bankAccount['ifsc_code']);

        $this->assertEquals('test', $bankAccount['beneficiary_name']);

        $this->assertEquals('1111111111111', $bankAccount['account_number']);

        $this->assertEquals('refund', $bankAccount['type']);
    }

    public function testCancelEmandateToken()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastEntity('payment');

        $fileStatuses = [
            'status'     => 'ACCEPTED',
            'error_code' => '',
            'error_desc' => '',
        ];

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['batch_type_id']);
        $this->assertEquals('CREATED', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('captured', $payment['status']);

        $transaction = $payment->transaction;

        $this->assertNotNull($transaction['reconciled_at']);

        $enach = $this->getDbEntities('enach', ['payment_id' => $payment['id']])->first()->toArray();

        $this->assertArraySelectiveEquals(['status' => 'ACCEPTED'], $enach);

        $response = $this->deleteCustomerToken('token_' . $payment['token_id'], 'cust_' . $payment['customer_id']);

        $this->assertEquals(true, $response['deleted']);

        $this->ba->adminAuth();

        $this->startTest();

        $fileStore = $this->getDbLastEntityToArray(Entity::FILE_STORE);

        $this->assertEquals("yesbank/nach/input_file/MMS-CANCEL-YESB-shared_utility_code-07032020-000001-INP", $fileStore['name']);

        $this->assertEquals("enach_npci_nb_cancel", $fileStore['type']);
    }

    public function testCancelEmandateTokenWithMutipleUtilityCode()
    {
        $this->markTestSkipped('Scope of the test changed to be updated by recurring team');

        $this->makeDebitPayment();

        $payment1 = $this->getDbLastEntity('payment');

        $this->fixtures->create('terminal:direct_enach_npci_netbanking_terminal',
            [Terminal\Entity::GATEWAY_ACQUIRER => Payment\Gateway::ACQUIRER_YESB]);

        $this->makeDebitPayment();

        $payment2 = $this->getDbLastEntity('payment');

        $fileStatuses = [
            'status'     => 'ACCEPTED',
            'error_code' => '',
            'error_desc' => '',
        ];

        $batch1 = $this->makeBatchDebitPayment($payment1, $fileStatuses);
        $batch2 = $this->makeBatchDebitPayment($payment2, $fileStatuses);

        $this->assertEquals('emandate', $batch1['batch_type_id']);
        $this->assertEquals('CREATED', $batch1['status']);

        $this->assertEquals('emandate', $batch2['batch_type_id']);
        $this->assertEquals('CREATED', $batch2['status']);

        $payment1 = $this->getDbEntityById('payment', $payment1['id']);
        $payment2 = $this->getDbEntityById('payment', $payment2['id']);

        $this->assertEquals('captured', $payment1['status']);
        $this->assertEquals('captured', $payment2['status']);

        $transaction1 = $payment1->transaction;
        $transaction2 = $payment2->transaction;

        $this->assertNotNull($transaction1['reconciled_at']);
        $this->assertNotNull($transaction2['reconciled_at']);

        $enach1 = $this->getDbEntities('enach', ['payment_id' => $payment1['id']])->first()->toArray();
        $enach2 = $this->getDbEntities('enach', ['payment_id' => $payment2['id']])->first()->toArray();

        $this->assertArraySelectiveEquals(['status' => 'ACCEPTED'], $enach1);
        $this->assertArraySelectiveEquals(['status' => 'ACCEPTED'], $enach2);

        $response1 = $this->deleteCustomerToken('token_' . $payment1['token_id'], 'cust_' . $payment1['customer_id']);
        $response2 = $this->deleteCustomerToken('token_' . $payment2['token_id'], 'cust_' . $payment2['customer_id']);

        $this->assertEquals(true, $response1['deleted']);
        $this->assertEquals(true, $response2['deleted']);

        $this->ba->adminAuth();

        $data = $this->testData['testCancelEmandateToken'];

        $this->mockBeam(function ($pushData, $intervalInfo, $mailInfo, $synchronous)
        {
            return [
                'failed'  => null,
                'success' => $pushData['files'],
            ];
        });

        $this->startTest($data);
    }

    protected function runPaymentCallbackFlowNetbanking($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest(
                $url, $method, $content);
        }

        $this->ba->publicCallbackAuth();

        $response = $this->sendRequest($request);

        $this->assertEquals($response->getStatusCode(), '302');

        $data = array(
            'url' => $response->headers->get('location'),
            'method' => 'post');

        if (filter_var($data['url'], FILTER_VALIDATE_URL))
        {
            // Hack: only way to remove IsPartnerAuth from container
            $this->app['basicauth']->checkAndSetKeyId('');

            return $this->submitPaymentCallbackRedirect($data['url']);
        }

        return $this->submitPaymentCallbackRequest($request);
    }

    protected function getBatchFileToUpload($payment, $status = 'Active', $errorCode = '', $errorDesc = '')
    {
        $this->fixtures->stripSign($payment['id']);

        $enach = $this->getDbLastEntity('enach');

        $sheets = [
            'sheet1' => [
                'config' => [
                    'start_cell' => 'A1',
                ],
                'items'  => [
                    [
                        'MANDATE_DATE'    => Carbon::today(Timezone::IST)->format('m/d/Y'),
                        'MANDATE_ID'      => 'NEW',
                        'UMRN'            => 'UTIB6000000005844847',
                        'CUST_REF_NO'     => '',
                        'SCH_REF_NO'      => '',
                        'CUST_NAME'       => 'User name',
                        'BANK'            => '',
                        'BRANCH'          => '',
                        'BANK_CODE'       => 'UTIB0000123',
                        'AC_TYPE'         => 'SAVINGS',
                        'AC_NO'           => '1111111111111',
                        'AMOUNT'          => '99999',
                        'FREQUENCY'       => 'ADHO',
                        'DEBIT_TYPE'      => 'MAXIMUM AMOUNT',
                        'START_DATE'      => Carbon::now(Timezone::IST)->format('m/d/Y'),
                        'END_DATE'        => Carbon::now(Timezone::IST)->addYears(10)->format('m/d/Y'),
                        'UNTIL_CANCEL'    => 'N',
                        'TEL_NO'          => '',
                        'MOBILE_NO'       => '9999999999',
                        'MAIL_ID'         => '',
                        'UPLOAD_DATE'     => Carbon::now(Timezone::IST)->format('m/d/Y'),
                        'RESPONSE_DATE'   => Carbon::now(Timezone::IST)->addDays(2)->format('m/d/Y'),
                        'UTILITY_CODE'    => 'NACH00000000012323',
                        'UTILITY_NAME'    => 'RAZORPAY',
                        'STATUS'          => $status,
                        'STATUS_CODE'     => $errorCode,
                        'REASON'          => $errorDesc,
                        'MANDATE_REQID'   => $enach['gateway_reference_id'],
                        'MESSAGE_ID'      => $payment['id'],
                    ],
                ],
            ],
        ];

        $name = 'RAZORPAYPVTLTD_OutwardMandateMISReport' . Carbon::now(Timezone::IST)->format('dmY');

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

    protected function makeBatchDebitPayment($payment, $fileStatuses)
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

        $data = [
            [
                'Presentation Date' => Carbon::now(Timezone::IST)->format('m/d/Y'),
                'UMRN' => 'UTIB6000000005844847',
                'Transaction Ref No' => $payment['id'],
                'Utility Code' => '',
                'Bank A/c Number' => '',
                'Account Holder Name' => '',
                'Bank' => '',
                'IFSC/MICR' => '',
                'Amount' => $payment['amount'] / 100,
                'Reference 1' => '',
                'Reference 2' => '',
                'Status' => $fileStatuses['status'],
                'Reason Code' => $fileStatuses['error_code'],
                'Reason Discription' => $fileStatuses['error_desc'],
                'User Reference' => '',
            ]
        ];

        $handle = tmpfile();

        $first = true;

        foreach ($data as $row)
        {
            if ($first === true)
            {
                $headers = array_keys($row);

                fputs($handle, implode(',', $headers) . "\n");

                $first = false;
            }

            $row = $this->flatten($row);

            fputs($handle, implode(',', $row) . "\n");
        }

        fseek($handle, 0);

        $file = (new TestingFile('Debit MIS.csv', $handle));

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $file, 'debit');

        $batchEntity = $this->fixtures->create('batch',
            [
                'id'          => $batch['id'],
                'type'        => 'emandate',
                'sub_type'    => 'debit',
                'gateway'     => 'enach_npci_netbanking',
                'total_count' => '1',
            ]);

        $entries = [
            "data"        => $data[0],
            'type'        => 'emandate',
            'sub_type'    => 'debit',
            'gateway'     => 'enach_npci_netbanking',
        ];

        $this->runWithData($entries, $batchEntity['id']);

        return $batch;
    }

    protected function makeRequestWithGivenUrlAndFile($url, $file, $type = 'register')
    {
        $request = [
            'url'     => $url,
            'method'  => 'POST',
            'content' => [
                'type'     => 'emandate',
                'sub_type' => $type,
                'gateway'  => 'enach_npci_netbanking',
            ],
            'files'   => [
                'file' => $file,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function updateCreatedAtOfPayment($paymentId)
    {
        $this->fixtures->stripSign($paymentId);

        // setting created at to 3am. Payments are picked from 9am to 6am cycle.
        $createdAt = Carbon::today(Timezone::IST)->addHours(3)->getTimestamp();

        $this->fixtures->edit(
            'payment',
            $paymentId,
            [
                'created_at' => $createdAt,
            ]);

        return $paymentId;
    }

    public function testDebitFileGenerationOnNonWorkingDay()
    {
        $this->markTestSkipped('not applicable');
    }

    public function testFailureDebitFileGeneration()
    {
        $this->markTestSkipped('not applicable');
    }

    public function testPartialDebitFileGeneration()
    {
        $this->markTestSkipped('not applicable');
    }

    public function testMandateCancellationYesBankSuccessResponseFile()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('confirmed', $payment->localToken->getRecurringStatus());

        $this->assertTrue($payment->isCreated());

        $fileStatuses = [
            'status'     => 'ACCEPTED',
            'error_code' => '',
            'error_desc' => '',
        ];

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('captured', $payment['status']);

        $response = $this->deleteCustomerToken(
            'token_' . $payment['token_id'], 'cust_' . $payment['customer_id']);

        $this->assertTrue($response['deleted']);

        $batchFile = $this->getBatchFileToUploadForMandateCancelRes($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $this->makeRequestWithGivenUrlAndFile($url, $batchFile,'cancel');

        $token = $this->getTrashedDbEntityById('token', $payment->getTokenId());

        $this->assertNotNull($token['deleted_at']);
    }

    protected function getBatchFileToUploadForMandateCancelRes(Payment\Entity $payment): TestingFile
    {
        $paymentId = $payment->getId();

        $xmlData = file_get_contents(__DIR__ . '/MMS-CANCEL-YESB-NACH00000000056369-08122021-000008-INP-RES.xml');

        $responseXml = strtr($xmlData, ['$paymentId' => $paymentId]);

        $handle = tmpfile();
        fwrite($handle, $responseXml);
        fseek($handle, 0);

        return (new TestingFile('MMS-CANCEL-YESB-NACH00000000056369-08122021-000008-INP-RES.xml', $handle));
    }

    // ----------- utilities ---------

    public function runWithData($entries, $batchId)
    {
        $this->ba->batchAppAuth();

        $testData = $this->testData['process_via_batch_service'];

        $testData['request']['server']['HTTP_X_Batch_Id'] = $batchId;

        $testData['request']['content'] = $entries;

        $this->runRequestResponseFlow($testData);
    }

    protected function makeDebitPayment($amount = 300000, $notes = null)
    {
        $payment = $this->getEmandatePaymentArray('UTIB', 'netbanking', 0);

        $payment['bank_account'] = [
            'account_number' => '1111111111111',
            'ifsc'           => 'UTIB0000123',
            'name'           => 'Test account',
            'account_type'   => 'savings',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $response = $this->doAuthPayment($payment);

        $this->fixtures->stripSign($response['razorpay_payment_id']);

        $paymentEntity = $this->getEntityById('payment', $response['razorpay_payment_id'],true);

        $tokenId = $paymentEntity[\RZP\Models\Payment\Entity::TOKEN_ID];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $amount]);

        $this->fixtures->edit(
            'token',
            $tokenId,
            [
                Token\Entity::GATEWAY_TOKEN    => 'UTIB6000000005844847',
                Token\Entity::RECURRING        => 1,
                Token\Entity::RECURRING_STATUS => Token\RecurringStatus::CONFIRMED,
            ]);

        $payment             = $this->getEmandatePaymentArray('UTIB', null, $amount);
        $payment['token']    = $tokenId;
        $payment['order_id'] = $order->getPublicId();

        if($notes === true)
        {
            $payment['notes']    = ['emandate_narration' => "RZPTESTMERCHANT"];
        }

        unset($payment['auth_type']);

        return $this->doS2SRecurringPayment($payment);
    }


    protected function mockVerifyResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['Accptd']     = '';
                $content['AccptRefNo'] = '';
                $content['ErrorCode'] = '605';
                $content['ErrorDesc'] = 'Otp Verification Failure';
                $content['RejectBy']   = 'Customer';
            }
        });
    }

    protected function mockFailedCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content = 'ErrorXML';
            }
        });
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

    public function mockBeam(callable $callback)
    {
        $beamServiceMock = $this->getMockBuilder(BeamService::class)
                                ->setConstructorArgs([$this->app])
                                ->setMethods(['beamPush'])
                                ->getMock();

        $beamServiceMock->method('beamPush')->will($this->returnCallback($callback));

        $this->app['beam']->setMockService($beamServiceMock);
    }

    public function mockBeamTest(callable $callback)
    {
        $beamServiceMock = $this->getMockBuilder(BeamService::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['beamPush'])
            ->getMock();

        $beamServiceMock->method('beamPush')->will($this->returnCallback($callback));

        $this->app['beam'] = $beamServiceMock;
    }
}
