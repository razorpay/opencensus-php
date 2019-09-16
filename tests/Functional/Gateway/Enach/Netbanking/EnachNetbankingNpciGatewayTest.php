<?php

namespace RZP\Tests\Functional\Gateway\Enach\Netbanking;

use Mail;
use Excel;
use Queue;
Use Carbon\Carbon;

use RZP\Jobs\BeamJob;
use RZP\Models\Feature;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Refund;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Payment\Entity as Payment;
use Illuminate\Http\Testing\File as TestingFile;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Tests\Functional\Partner\PartnerTrait;

class EnachNetbankingNpciGatewayTest extends TestCase
{
    use FileHandlerTrait;
    use DbEntityFetchTrait;
    use AttemptTrait;
    use AttemptReconcileTrait;
    use PartnerTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/EnachNetbankingNpciGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_enach_npci_netbanking_terminal');
        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->enableEmandate();
        $this->fixtures->merchant->addFeatures([Feature\Constants::CHARGE_AT_WILL]);

        $this->gateway = 'enach_npci_netbanking';

        //$this->setupMockDns();
    }

    public function testPayment()
    {
        $payment                 = $this->getEmandatePaymentArray('YESB', 'netbanking', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'yesb0000123',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(0, $payment['amount']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals('initial', $payment['recurring_type']);

        $enach = $this->getLastEntity('enach', true);

        $this->assertNotNull($enach['gateway_reference_id']);
        $this->assertNotNull($enach['gateway_reference_id2']);

        $this->assertEquals('true', $enach['status']);

        $this->assertEquals('true' ,$enach['acknowledge_status']);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals('netbanking', $token['auth_type']);

        $this->assertEquals('initiated', $token['recurring_status']);
    }

    public function testPartnerPayment()
    {
        $payment                 = $this->getEmandatePaymentArray('YESB', 'netbanking', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'yesb0000123',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        list($clientId, $submerchantId) = $this->setUpPartnerAuthForPayment();

        $this->doPartnerAuthPayment($payment, $clientId, $submerchantId);

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame('authorized', $payment['status']);
    }

    public function testPaymentRejectResponse()
    {
        $payment                 = $this->getEmandatePaymentArray('YESB', 'netbanking', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'yesb0000123',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockRejectCallbackResponse();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(0, $payment['amount']);

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals('initial', $payment['recurring_type']);

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals('false', $enach['status']);

        // if we get a non-error but failed registration response, we also get the NPCI reference id
        $this->assertNotNull($enach['gateway_reference_id']);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals('netbanking', $token['auth_type']);

        $this->assertEquals(null , $token['recurring_status']);
    }

    public function testPaymentErrorResponse()
    {
        $payment                 = $this->getEmandatePaymentArray('YESB', 'netbanking', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'yesb0000123',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockFailedCallbackResponse();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals(0, $payment['amount']);

        $this->assertEquals('initial', $payment['recurring_type']);

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals('false', $enach['status']);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals('netbanking', $token['auth_type']);

        $this->assertEquals(null, $token['recurring_status']);
    }

    public function testRegisterReconSuccess()
    {
        $payment                 = $this->getEmandatePaymentArray('YESB', 'netbanking', 0);

        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'yesb0000123',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $batchFile = $this->getBatchFileToUpload($payment, 'Active');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $enach = $this->getDbLastEntityToArray('enach');

        $this->assertNotNull($enach['umrn']);
        $this->assertEquals('Active', $enach['registration_status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNotNull($token['gateway_token']);
        $this->assertEquals('confirmed', $token['recurring_status']);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('captured', $payment['status']);

        $transaction = $this->getDbLastEntityToArray('transaction');

        $this->assertNotNull($transaction['reconciled_at']);
    }

    public function testPaymentSuccessRegisterFileRejected()
    {
        $payment                 = $this->getEmandatePaymentArray('YESB', 'netbanking', 0);

        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'yesb0000123',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $batchFile = $this->getBatchFileToUpload($payment, 'cancel', 'M032', 'Rejected as per customer confirmation');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('refunded', $payment['status']);

        $transaction = $this->getDbLastEntityToArray('transaction');

        $this->assertNotNull($transaction['reconciled_at']);

        $enach = $this->getDbLastEntityToArray('enach');

        $this->assertEquals('M032', $enach['error_code']);
        $this->assertEquals('Rejected as per customer confirmation', $enach['error_message']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertEquals('rejected', $token['recurring_status']);
        $this->assertEquals('E-Mandate registration cancelled by the customer', $token['recurring_failure_reason']);
    }

    public function testPaymentFailedRegisterFileRejected()
    {
        // Since registration during API failed, the payment will not be picked during register batch file processing
        $payment = $this->getEmandatePaymentArray('YESB', 'netbanking', 0);

        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'yesb0000123',
            'name'           => 'Test account',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $this->mockRejectCallbackResponse();

        $testData = $this->testData['testPaymentRejectResponse'];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $batchFile = $this->getBatchFileToUpload($payment, 'cancel', 'M032', 'Rejected as per customer confirmation');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertEquals(null, $token['recurring_status']);
    }

    public function testDebitFileGeneration()
    {
        $response = $this->makeDebitPayment();

        $paymentId = $this->updateCreatedAtOfPayment($response['razorpay_payment_id']);

        $this->ba->adminAuth();

        Queue::fake();

        $content = $this->startTest();

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

        Queue::assertPushed(BeamJob::class, 1);

        Queue::assertPushedOn('beam_test', BeamJob::class);
    }

    public function testDebitFileGenerationMultipleUtilityCode()
    {
        $response = $this->makeDebitPayment();

        $this->updateCreatedAtOfPayment($response['razorpay_payment_id']);

        $this->fixtures->create('terminal:direct_enach_npci_netbanking_terminal');

        $response = $this->makeDebitPayment();

        $this->updateCreatedAtOfPayment($response['razorpay_payment_id']);

        $this->ba->adminAuth();

        Queue::fake();

        $this->testData[__FUNCTION__] = $this->testData['testDebitFileGeneration'];

        $content = $this->startTest();

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', ['count' => 2], true);

        $directTerminalFile = $files['items'][0];
        $sharedTerminalFile = $files['items'][1];

        $fileNamingConvention = 'yesbank/nach/input_file/NACH_DR_{$date}_{$utilityCode}_RAZORPAY_001';
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

        Queue::assertPushed(BeamJob::class, 1);

        Queue::assertPushedOn('beam_test', BeamJob::class);
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

        Carbon::setTestNow(Carbon::now()->addDays(10));

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

        Carbon::setTestNow(Carbon::now()->addDays(10));

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

    public function testDebitFilePendingResponse()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastEntity('payment');

        $fileStatuses = [
            'status'     => 'PENDING',
            'error_code' => '98',
            'error_desc' => 'BANK EXTENDED',
        ];

        Carbon::setTestNow(Carbon::now()->addDays(10));

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('created', $payment['status']);
    }

    public function testRegisterReconLateAuth()
    {
        // Late Auth will not work as we are doing recon based on NPCI Ref Id. Force Auth will be disabled.
        // Depending on NPCI, this may be taken up later based on verify or if they send payment id in response file
        $this->markTestSkipped();

        $payment                 = $this->getEmandatePaymentArray('YESB', 'netbanking', 0);

        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'yesb0000123',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $this->mockRejectCallbackResponse();

        $testData = $this->testData['testPaymentRejectResponse'];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $batchFile = $this->getBatchFileToUpload($payment, 'Active');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $enach = $this->getDbLastEntityToArray('enach');

        $this->assertNotNull($enach['umrn']);
        $this->assertEquals('Active', $enach['registration_status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNotNull($token['gateway_token']);
        $this->assertEquals('confirmed', $token['recurring_status']);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('captured', $payment['status']);

        $transaction = $this->getDbLastEntityToArray('transaction');

        $this->assertNotNull($transaction['reconciled_at']);
    }

    public function testRegisterErrorReconLateAuth()
    {
        // Tests a case where there is no match against gateway_reference_id in enach table
        // Db query will fail and batch gracefully handles this and continues its execution.
        $payment = $this->getEmandatePaymentArray('YESB', 'netbanking', 0);

        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'yesb0000123',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $this->mockRejectCallbackResponse();

        $testData = $this->testData['testPaymentRejectResponse'];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $batchFile = $this->getBatchFileToUpload($payment, 'Active');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertEquals(null, $token['recurring_status']);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);
    }

    protected function makeDebitPayment()
    {
        $payment                 = $this->getEmandatePaymentArray('UTIB', 'netbanking', 0);

        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'UTIB0000123',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $response = $this->doAuthPayment($payment);

        $this->fixtures->stripSign($response['razorpay_payment_id']);

        $paymentEntity = $this->getEntityById('payment', $response['razorpay_payment_id'],true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 3000]);

        $this->fixtures->edit(
            'token',
            $tokenId,
            [
                Token\Entity::GATEWAY_TOKEN    => 'UTIB6000000005844847',
                Token\Entity::RECURRING        => 1,
                Token\Entity::RECURRING_STATUS => Token\RecurringStatus::CONFIRMED,
            ]);

        $payment             = $this->getEmandatePaymentArray('UTIB', null, 3000);

        $payment['token']    = $tokenId;

        $payment['order_id'] = $order->getPublicId();

        unset($payment['auth_type']);

        return $this->doS2SRecurringPayment($payment);
    }

    protected function mockRejectCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize_get_secure_data')
            {
                $content['Accptd'] = 'false';
                $content['ReasonCode'] = 'AP04';
                $content['ReasonDesc'] = 'Account Inoperative';
                $content['RejectBy'] = 'Bank';
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
                        'AC_NO'            => '914010009305862',
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

        $excel = Excel::create(
            $name,
            function($excel) use ($sheets) {
                foreach ($sheets as $sheetName => $data)
                {
                    $excel->sheet(
                        $sheetName,
                        function($sheet) use ($data) {
                            $sheet->fromArray($data['items'], null, $data['config']['start_cell'], true);
                        }
                    );
                }
            }
        );

        $data = $excel->string('xlsx');

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

    public function testDebitFileReconciliationRefund()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastEntity('payment');

        $fileStatuses = [
            'status'     => 'ACCEPTED',
            'error_code' => '',
            'error_desc' => '',
        ];

        Carbon::setTestNow(Carbon::now()->addDays(10));

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('captured', $payment['status']);

        $transaction = $payment->transaction;

        $this->assertNotNull($transaction['reconciled_at']);

        $response = $this->refundPayment('pay_' . $payment['id']);

        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals($response['id'], $refund['id']);

        $this->assertEquals('pay_' . $payment['id'], $refund['payment_id']);

        $this->assertEquals('initiated', $refund['status']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], $refund['id']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals('UTIB0000123', $bankAccount['ifsc_code']);

        $this->assertEquals('test', $bankAccount['beneficiary_name']);

        $this->assertEquals('914010009305862', $bankAccount['account_number']);

        $this->assertEquals($bankAccount['id'], 'ba_' . $refund['bank_account_id']);

        $this->assertEquals('refund', $bankAccount['type']);
    }

    public function testDebitFileReconciliationRefundBankTransfer()
    {
        $this->testDebitFileReconciliationRefund();

        $channel = Channel::YESBANK;

        $content = $this->initiateTransfer(
            $channel,
            Attempt\Purpose::REFUND);

        $data = $this->reconcileOnlineSettlements($channel, false);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertNotNull($attempt['utr']);
        $this->assertEquals(Attempt\Status::INITIATED, $attempt[Attempt\Entity::STATUS]);

        // Process entities
        $this->reconcileEntitiesForChannel($channel);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(Refund\Status::PROCESSED, $refund['status']);
        $this->assertEquals(1, $refund['attempts']);
        $this->assertNotNull($attempt['utr']);
    }

    protected function updateCreatedAtOfPayment($paymentId)
    {
        $this->fixtures->stripSign($paymentId);

        // setting created at to 8am. Payments are picked from 9 to 9 cycle.
        $createdAt = Carbon::today(Timezone::IST)->addHours(8)->getTimestamp();

        $this->fixtures->edit(
            'payment',
            $paymentId,
            [
                'created_at' => $createdAt,
            ]);

        return $paymentId;
    }
}
