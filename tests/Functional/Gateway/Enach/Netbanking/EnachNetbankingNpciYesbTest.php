<?php

namespace RZP\Tests\Functional\Gateway\Enach\Netbanking;

use Excel;
use Queue;
Use Carbon\Carbon;

use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Excel\Export as ExcelExport;
use RZP\Excel\ExportSheet as ExcelSheetExport;
use Illuminate\Http\Testing\File as TestingFile;

class EnachNetbankingNpciYesbTest extends EnachNetbankingNpciGatewayTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->sharedCitiTerminal = $this->sharedTerminal;

        $this->fixtures->terminal->disableTerminal($this->sharedTerminal['id']);

        $this->sharedTerminal = $this->fixtures->create(
                                'terminal:shared_enach_npci_netbanking_yesb_terminal',
                                [
                                    Terminal\Entity::ID               => Terminal\Shared::ENACH_NPCI_NETBANKING_YESB_TERMINAL,
                                    Terminal\Entity::GATEWAY_ACQUIRER => Payment\Gateway::ACQUIRER_YESB
                                ]
                               );
    }

    public function testDebitFileGeneration()
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

        $this->fixtures->terminal->enableTerminal($this->sharedCitiTerminal['id']);

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

        $this->fixtures->terminal->disableTerminal($this->sharedCitiTerminal['id']);

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

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

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

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

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

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals('BAD_REQUEST_EMANDATE_INACTIVE', $payment['internal_error_code']);

        $enach = $this->getDbEntities('enach', ['payment_id' => $payment['id']])->first()->toArray();

        $this->assertEquals('', $enach['error_code']);
        $this->assertEquals('Record Level Error:Invalid Mandate Info......', $enach['error_message']);

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

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

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

        $this->assertEquals('processed', $batch['status']);

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

        $this->assertEquals($bankAccount['id'], 'ba_' . $refund['bank_account_id']);

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

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

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

        $this->assertEquals('emandate', $batch1['type']);
        $this->assertEquals('processed', $batch1['status']);

        $this->assertEquals('emandate', $batch2['type']);
        $this->assertEquals('processed', $batch2['status']);

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

        return $this->getDbEntityById('batch', $batch['id']);
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

        $payment = $this->getDbLastPayment();

        $this->assertEquals('confirmed', $payment->localToken->getRecurringStatus());

        $this->assertTrue($payment->isCreated());

        $fileStatuses = [
            'status'     => 'ACCEPTED',
            'error_code' => '',
            'error_desc' => '',
        ];

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertTrue($payment->isCaptured());

        $response = $this->deleteCustomerToken(
            'token_' . $payment['token_id'], 'cust_' . $payment['customer_id']);

        $this->assertTrue($response['deleted']);

        $batchFile = $this->getBatchFileToUploadForMandateCancelRes($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $this->makeRequestWithGivenUrlAndFile($url, $batchFile,'cancel');

        $token = $this->getTrashedDbEntityById('token', $payment->getTokenId());

        $this->assertEquals('cancelled', $token['recurring_status']);
    }

    protected function getBatchFileToUploadForMandateCancelRes(Payment\Entity $payment): TestingFile
    {
        $tokenId = $payment->getTokenId();

        $xmlData = file_get_contents(__DIR__ . '/MMS-CANCEL-YESB-NACH00000000056369-08122021-000008-INP-RES.xml');

        $responseXml = strtr($xmlData, ['$tokenId' => $tokenId]);

        $handle = tmpfile();
        fwrite($handle, $responseXml);
        fseek($handle, 0);

        return (new TestingFile('MMS-CANCEL-YESB-NACH00000000056369-08122021-000008-INP-RES.xml', $handle));
    }
}
