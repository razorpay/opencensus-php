<?php

namespace RZP\Tests\Functional\Gateway\Enach\Netbanking;

use Mail;
use Excel;
use Queue;
use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Terminal;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Customer\Token;
use RZP\Tests\Functional\TestCase;
use RZP\Excel\Export as ExcelExport;
use RZP\Mail\Gateway\Nach\Base as NachMail;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Excel\ExportSheet as ExcelSheetExport;
use Illuminate\Http\Testing\File as TestingFile;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Gateway\Enach\Citi\NachDebitFileHeadings as Headings;

class EnachNetbankingNpciIciciTest extends TestCase
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
            'terminal:shared_enach_npci_netbanking_terminal',
            [
                Terminal\Entity::ID                  => Terminal\Shared::ENACH_NPCI_NETBANKING_ICIC_TERMINAL,
                Terminal\Entity::GATEWAY_ACQUIRER    => Payment\Gateway::ACQUIRER_ICIC,
                Terminal\Entity::GATEWAY_ACCESS_CODE => 'ICIC0TREA00'
            ]
        );

        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->enableEmandate();
        $this->fixtures->merchant->addFeatures([Feature\Constants::CHARGE_AT_WILL]);

        $this->gateway = 'enach_npci_netbanking';

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testDebitFileGenerationIcici()
    {
        $payment1 = $this->makeDebitPayment();

        $this->fixtures->stripSign($payment1['razorpay_payment_id']);

        $payment2 = $this->makeDebitPayment(40000);

        $this->fixtures->stripSign($payment2['razorpay_payment_id']);

        $this->ba->adminAuth();

        Queue::fake();
        Mail::fake();

        $content = $this->startTest($this->testData['testDebitFileGenerationIcici']);

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', [], true);

        $this->assertCount(1, $files['items']);

        $debit = $files['items'][0];

        $expectedFileContentDebit = [
            'type'        => 'icici_nach_combined_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentDebit, $debit);

        $enach = $this->getLastEntity('enach', true);

        $this->assertArraySelectiveEquals(
            [
                'payment_id' => $payment2['razorpay_payment_id'],
                'action'     => 'authorize',
                'bank'       => 'UTIB',
                'status'     => null,
            ],
            $enach
        );

        $fileContent = explode("\n", file_get_contents('storage/files/filestore/' . $debit['location']));

        // since date and amount is fixed for this test header is a constant
        $expectedHeader = '56       RAZORPAY                                                                                  0001000000000000000034000007032020                       shared_utility_cod000000000000000000ICIC0TREA00000205025290                       000000002                                                           ';

        $this->assertEquals($expectedHeader, $fileContent[0]);

        $debit1 = array_map('trim', $this->parseTextRow($fileContent[1], 0, ''));

        $expectedDebitRow1 = [
            'ACH Transaction Code' => '67',
            'Destination Account Type' => '10',
            'Beneficiary Account Holder\'s Name' => 'Test account',
            'User Name' => 'RZPTestMerchant',
            'Amount' => '0000000300000',
            'Destination Bank IFSC / MICR / IIN' => 'UTIB0000123',
            'Beneficiary\'s Bank Account number' => '1111111111111',
            'Sponsor Bank IFSC / MICR / IIN' => 'ICIC0TREA00',
            'User Number' => 'shared_utility_cod',
            'Transaction Reference' => $payment1['razorpay_payment_id'],
            'Product Type' => '10',
            'UMRN' => 'UTIB6000000005844847'
        ];

        $debit2 = array_map('trim', $this->parseTextRow($fileContent[2], 0, ''));

        $expectedDebitRow2 = [
            'ACH Transaction Code' => '67',
            'Destination Account Type' => '10',
            'Beneficiary Account Holder\'s Name' => 'Test account',
            'User Name' => 'RZPTestMerchant',
            'Amount' => '0000000040000',
            'Destination Bank IFSC / MICR / IIN' => 'UTIB0000123',
            'Beneficiary\'s Bank Account number' => '1111111111111',
            'Sponsor Bank IFSC / MICR / IIN' => 'ICIC0TREA00',
            'User Number' => 'shared_utility_cod',
            'Transaction Reference' => $payment2['razorpay_payment_id'],
            'Product Type' => '10',
            'UMRN' => 'UTIB6000000005844847'
        ];

        $this->assertArraySelectiveEquals($expectedDebitRow1, $debit1);
        $this->assertArraySelectiveEquals($expectedDebitRow2, $debit2);

        Mail::assertQueued(NachMail::class, function ($mail)
        {
            $fileName = 'ACH-DR-ICIC-ICIC865719-{$date}-RZ0001-INP.txt';

            $date = Carbon::now(Timezone::IST)->format('dmY');

            $fileName = strtr($fileName, ['{$date}' => $date]);

            $this->assertEquals($fileName, (array_keys($mail->viewData['mailData']))[0]);

            $mailData = $mail->viewData['mailData'];

            $this->assertEquals(1, $mailData[$fileName]['sr_no']);

            $this->assertEquals('3,400.00', $mailData[$fileName]['amount']);

            $this->assertEquals('2', $mailData[$fileName]['count']);

            $this->assertEquals('emails.admin.icici_enach_npci', $mail->view);

            return true;
        });
    }

    public function testDebitFileGenerationMultipleSponsorBanks()
    {
        $iciciTerminalPaymentResponse = $this->makeDebitPayment();

        $this->fixtures->stripSign($iciciTerminalPaymentResponse['razorpay_payment_id']);

        $this->fixtures->terminal->disableTerminal($this->sharedTerminal['id']);

        $sharedCitiTerminal = $this->fixtures->create('terminal:shared_enach_npci_netbanking_terminal');

        $this->fixtures->terminal->enableTerminal($sharedCitiTerminal['id']);

        $citiTerminalPaymentResponse = $this->makeDebitPayment();

        $this->fixtures->stripSign($citiTerminalPaymentResponse['razorpay_payment_id']);

        $this->ba->adminAuth();

        Queue::fake();
        Mail::fake();

        $content = $this->startTest($this->testData['testDebitFileGenerationIcici']);

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', [], true);

        $this->assertCount(1, $files['items']);

        $debit = $files['items'][0];

        $expectedFileContentDebit = [
            'type'        => 'icici_nach_combined_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentDebit, $debit);

        $fileContent = file_get_contents('storage/files/filestore/' . $debit['location']);

        $this->assertNotFalse(strpos($fileContent, $iciciTerminalPaymentResponse['razorpay_payment_id']));

        $this->assertFalse(strpos($fileContent, $citiTerminalPaymentResponse['razorpay_payment_id']));

        $this->fixtures->terminal->disableTerminal($sharedCitiTerminal['id']);

        $this->fixtures->terminal->enableTerminal($this->sharedTerminal['id']);
    }

    public function testDebitFileGenerationMultipleUtilityCode()
    {
        $this->makeDebitPayment();

        $this->fixtures->create(
            'terminal:direct_enach_npci_netbanking_terminal',
            [Terminal\Entity::GATEWAY_ACQUIRER => Payment\Gateway::ACQUIRER_ICIC]
        );

        $this->makeDebitPayment();

        $this->ba->adminAuth();

        Queue::fake();
        Mail::fake();

        $this->testData[__FUNCTION__] = $this->testData['testDebitFileGenerationIcici'];

        $this->startTest();

        $files = $this->getEntities('file_store', [], true);

        $this->assertCount(2, $files['items']);

        $fileName1 = $files['items'][0]['name'];
        $fileName2 = $files['items'][1]['name'];

        // The file naming format - 'icici/nach/debit/ACH-DR-ICIC-ICIC865719-{$date}-{$batchCode}-INP'
        // Batch code is a sequentially increasing number for every file generated
        // Different files are generated for different utility codes
        $seqNo1 = (int) substr($fileName1, -5, 1);
        $seqNo2 = (int) substr($fileName2, -5, 1);

        if ((($seqNo1 - $seqNo2) === 1) or
            (($seqNo2 - $seqNo1) === 1))
        {
            $fileNamesSequential = true;
        }
        else
        {
            $fileNamesSequential = false;
        }

        $this->assertTrue($fileNamesSequential);

        Mail::assertQueued(NachMail::class, function ($mail)
        {
            $this->assertEquals(2, count($mail->viewData['mailData']));

            return true;
        });
    }

    public function testCancelEmandateToken()
    {
        $this->makeDebitPayment();

        $debitPayment = $this->getDbLastPayment();

        $paymentEntity = $this->getDbEntities('payment',
            [
                'token_id' => $debitPayment->getTokenId(),
                'amount'   => 0
            ])->first();

        $this->assertTrue($paymentEntity->isCaptured());

        $token = $paymentEntity->localToken;

        $this->assertEquals('confirmed', $token->getRecurringStatus());
        $this->assertEquals($paymentEntity->getMethod(), $token->getMethod());
        $this->assertEquals($paymentEntity->getTerminalId(), $token->getTerminalId());
        $this->assertNotEmpty($token->getGatewayToken());

        $enach = $this->getDbEntities('enach', ['payment_id' => $paymentEntity->getId()])->first()->toArray();

        $this->assertEquals('true', $enach['status']);
        $this->assertNotEmpty($enach['umrn']);

        $response = $this->deleteCustomerToken('token_' . $paymentEntity->getTokenId());

        $this->assertEquals(true, $response['deleted']);

        $this->ba->adminAuth();

        $data = $this->testData[__FUNCTION__];

        $request = [
            'request' => [
                'content' => [
                    'type'    => 'nach_cancel',
                    'targets' => ['combined_nach_icici']
                ]
            ],
            'response' => [
                'content' => [
                    'items' => [
                        [
                            'status' => 'file_sent',
                            'type'   => 'nach_cancel',
                            'target' => 'combined_nach_icici',
                        ]
                    ]
                ]
            ]
        ];

        $request = array_replace_recursive($data, $request);

        $this->startTest($request);
    }

    protected function getBatchDebitFile($payment, $status)
    {
        $paymentId = $payment['id'];
        $this->fixtures->stripSign($paymentId);

        $data = '56       RAZORPAY SOFTWARE PVT LTD                             000000000                           000005000000000000000020001701202047642224498136619848   NACH00000000013149000000000000000000CITI000PIGW000018003                          00000000227
67         10                  ABIJITO GUHA                            17012020        RAZORPAY SOFTWARE PV             000000030000047642224504081750481'. $status['status'] . $status['error_code']. 'HDFC00024971111111111111                      CITI000PIGWNACH00000000013149' . $paymentId . '                10 000000000000000HDFC0000000010936518
';

        $name = 'temp.txt';

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);

        $file = (new TestingFile($name, $handle));

        return $file;
    }

    protected function makeRequestWithGivenUrlAndFile($url, $file)
    {
        $request = [
            'url'     => $url,
            'method'  => 'POST',
            'content' => [
                'type'     => 'nach',
                'sub_type' => 'debit',
                'gateway'  => 'nach_icici',
            ],
            'files'   => [
                'file' => $file,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    public function testFailureDebitFileGeneration()
    {
        $this->markTestSkipped('not applicable');
    }

    public function testPartialDebitFileGeneration()
    {
        $this->markTestSkipped('not applicable');
    }

    // ----------- utilities ---------

    protected function makeDebitPayment($amount = 300000)
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
                        'AC_NO'            => '1111111111111',
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
}
