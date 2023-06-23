<?php

namespace RZP\Tests\Functional\Gateway\Enach\Netbanking;

use Mail;
use Excel;
use Queue;
Use Carbon\Carbon;

use RZP\Models\Feature;
use RZP\Models\Terminal;
use RZP\Constants\Entity;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Method;
use RZP\Services\Mock\BeamService;
use RZP\Tests\Functional\TestCase;
use RZP\Excel\Export as ExcelExport;
use RZP\Models\Order\Entity as Order;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Excel\ExportSheet as ExcelSheetExport;
use Illuminate\Http\Testing\File as TestingFile;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Gateway\Enach\Citi\NachDebitFileHeadings as Headings;

class EnachNetbankingNpciGatewayTest extends TestCase
{
    use FileHandlerTrait;
    use DbEntityFetchTrait;
    use AttemptTrait;
    use AttemptReconcileTrait;
    use PartnerTrait;

    // Conditions for debit file generation
    // 1. Has to be a working day
    // 2. Payments selected in the file are created within the time interval of 9 AM of previous day to 9 AM today

    const FIXED_WORKING_DAY_TIME     = 1583548200;  // 07-03-2020 8:00 AM
    const FIXED_NON_WORKING_DAY_TIME = 1583634600;  // 08-03-2020 8:00 AM (sunday)

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/EnachNetbankingNpciGatewayTestData.php';

        $fixedTime = (new Carbon())->timestamp(self::FIXED_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_enach_npci_netbanking_terminal');
        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->enableEmandate();
        $this->fixtures->merchant->addFeatures([Feature\Constants::CHARGE_AT_WILL]);

        $this->gateway = 'enach_npci_netbanking';

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testPayment()
    {
        $paymentInput                 = $this->getEmandatePaymentArray('SBIN', 'netbanking', 0);

        $paymentInput['bank_account'] = [
            'account_number' => '1111111111111',
            'ifsc'           => 'sbin0000123',
            'name'           => 'Test account',
            'account_type'   => 'current',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $paymentInput['amount']]);
        $paymentInput['order_id'] = $order->getPublicId();

        $this->doAuthPayment($paymentInput);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(0, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('initial', $payment['recurring_type']);

        $enach = $this->getLastEntity('enach', true);
        $this->assertNotNull($enach['gateway_reference_id']);
        $this->assertNotNull($enach['gateway_reference_id2']);
        $this->assertNotNull($enach['umrn']);
        $this->assertEquals('true', $enach['status']);
        $this->assertEquals($this->sharedTerminal['gateway_acquirer'], $enach['acquirer']);

        $token = $this->getLastEntity('token', true);
        $this->assertEquals('netbanking', $token['auth_type']);
        $this->assertEquals('confirmed', $token['recurring_status']);
        $this->assertNotNull($token['gateway_token']);
        $this->assertEquals($token['gateway_token'], $enach['umrn']);
        $this->assertEquals($token['account_type'], $paymentInput['bank_account']['account_type']);
        $this->assertNull($token['expired_at']);
    }

    public function testRegistrationOrderForDebitOnlyBank()
    {
        $orderInput = [
            Order::AMOUNT          => 0,
            Order::BANK            => IFSC::UTBI,
            Order::METHOD          => Method::EMANDATE,
            Order::PAYMENT_CAPTURE => true,
        ];

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($orderInput) {
            $this->createOrder($orderInput);
        });
    }

    public function testRegistrationPaymentForDebitOnlyBank()
    {
        $paymentInput                 = $this->getEmandatePaymentArray('UTBI', 'netbanking', 0);

        $paymentInput['bank_account'] = [
            'account_number' => '1111111111111',
            'ifsc'           => 'UTIB0000001',
            'name'           => 'Test account',
            'account_type'   => 'current',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $paymentInput['amount']]);
        $paymentInput['order_id'] = $order->getPublicId();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($paymentInput) {
            $this->doAuthPayment($paymentInput);
        });

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment);

        $enach = $this->getLastEntity('enach', true);
        $this->assertNull($enach);

        $token = $this->getLastEntity('token', true);
        $this->assertEquals('netbanking', $token['auth_type']);
        $this->assertEquals(null, $token['recurring_status']);
        $this->assertEquals(null, $token['gateway_token']);
    }

    public function testPreferencesForDebitOnlyBank()
    {
        $orderInput = [
            'amount' => 0,
            'payment_capture' => true,
            'method' => Method::EMANDATE,
        ];

        $order = $this->createOrder($orderInput);

        $this->ba->publicAuth();

        $testData['request']['content'] = ['key_id' => $this->ba->getKey(), 'order_id' => $order['id']];

        $content = $this->startTest($testData);

        $banks = $content['methods']['recurring']['emandate'];

        $this->assertArrayNotHasKey(IFSC::UTBI, $banks);
    }

    public function testPreferencesForEnabledEmandateBanks()
    {
        $orderInput = [
            'amount' => 0,
            'payment_capture' => true,
            'method' => Method::EMANDATE,
        ];

        $order = $this->createOrder($orderInput);

        $this->ba->publicAuth();

        $testData = $this->testData['testPreferencesForDebitOnlyBank'];

        $testData['request']['content'] = ['key_id' => $this->ba->getKey(), 'order_id' => $order['id']];

        $content = $this->startTest($testData);

        $banks = $content['methods']['recurring']['emandate'];

        $authTypeBanks = array_values($banks);

        $debtcount = 0;
        $netcount  = 0;

        foreach($authTypeBanks as $value)
        {
            if (in_array('debitcard', $value['auth_types']))
            {
                $debtcount++;
            }

            if (in_array('netbanking', $value['auth_types']))
            {
                $netcount++;
            }
        }

        $this->assertEquals(47, $debtcount);
        $this->assertEquals(44, $netcount);

    }
    
    public function testEmandatePreferencesWithAccountMasking()
    {
        $orderInput = [
            'amount' => 0,
            'payment_capture' => true,
            'method' => Method::EMANDATE,
            'bank'           => 'HDFC',
            'customer_id'    => 'cust_100000customer',
            'token'          => [
                'method'       => 'emandate',
                'max_amount'   => 2500,
                'bank_account' => [
                    'bank_name'          => 'HDFC Bank',
                    'ifsc_code'          => 'HDFC0001233',
                    'account_number'     => '914010009305862',
                    'account_type'       => 'savings',
                    'beneficiary_name'   => 'test',
                    'beneficiary_email'  => 'test@razorpay.com',
                    'beneficiary_mobile' => '9999999999'
                ],
            ]
        ];
        
        $order = $this->createOrder($orderInput);
        
        $this->ba->publicAuth();
        
        $testData['request']['content'] = ['key_id' => $this->ba->getKey(), 'order_id' => $order['id']];
        
        $content = $this->startTest($testData);
        
        $accountNumber = $content['order']['bank_account']['account_number'];
        
        $this->assertEquals("XXXXXXXXXXX5862", $accountNumber);
    }
    
    public function testEmandateRegistrationWithAccountMasking()
    {
        $payment = $this->getEmandatePaymentArray('HDFC', 'netbanking', 0);
        
        $payment['bank_account'] = [
            'account_number' => 'XXXXXXXXXXX5862',
            'ifsc'           => 'HDFC0001233',
            'name'           => 'Test account',
            'account_type'   => 'savings',
        ];
        
        $orderInput = [
            'amount' => 0,
            'payment_capture' => true,
            'method' => Method::EMANDATE,
            'bank'           => 'HDFC',
            'customer_id'    => 'cust_100000customer',
            'token'          => [
                'method'       => 'emandate',
                'max_amount'   => 2500,
                'bank_account' => [
                    'account_number' => '914010009305862',
                    'account_type'   => 'savings',
                    'bank_name'          => 'HDFC Bank',
                    'ifsc_code'          => 'HDFC0001233',
                    'beneficiary_name'   => 'test',
                    'beneficiary_email'  => 'test@razorpay.com',
                    'beneficiary_mobile' => '9999999999'
                ],
            ]
        ];
        
        $order = $this->createOrder($orderInput);
        
        $payment['order_id'] = $order['id'];
        
        $this->doAuthPayment($payment);
        
        $payment = $this->getLastEntity('payment', true);
        
        $this->assertEquals('captured', $payment['status']);
        
        $token = $this->getLastEntity('token', true);
        
        $this->assertEquals('914010009305862', $token['bank_details']['account_number']);
    }

    public function testPaymentWithDisplayFeature()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::ENACH_INTERMEDIATE]);

        $this->testPayment();

        $this->fixtures->merchant->removeFeatures([Feature\Constants::ENACH_INTERMEDIATE]);
    }

    public function testPaymentAuthCard()
    {
        $payment                 = $this->getEmandatePaymentArray('SBIN', 'debitcard', 0);

        $payment['bank_account'] = [
            'account_number' => '1111111111111',
            'ifsc'           => 'sbin0000123',
            'name'           => 'Test account',
            'account_type'   => 'savings',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(0, $payment['amount']);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals('initial', $payment['recurring_type']);

        $enach = $this->getLastEntity('enach', true);

        $this->assertNotNull($enach['gateway_reference_id']);
        $this->assertNotNull($enach['gateway_reference_id2']);
        $this->assertNotNull($enach['umrn']);
        $this->assertEquals($this->sharedTerminal['gateway_acquirer'], $enach['acquirer']);

        $this->assertEquals('true', $enach['status']);

        $this->assertEquals('true' ,$enach['acknowledge_status']);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals('debitcard', $token['auth_type']);

        $this->assertEquals('confirmed', $token['recurring_status']);

        $this->assertNotNull($token['gateway_token']);

        $this->assertEquals($token['gateway_token'], $enach['umrn']);

        $this->assertNull($token['expired_at']);
    }

    public function testPartnerPayment()
    {
        $payment                 = $this->getEmandatePaymentArray('SBIN', 'netbanking', 0);
        $payment['bank_account'] = [
            'account_number' => '1111111111111',
            'ifsc'           => 'sbin0000123',
            'name'           => 'Test account',
            'account_type'   => 'savings',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        list($clientId, $submerchantId) = $this->setUpPartnerAuthForPayment();

        $this->doPartnerAuthPayment($payment, $clientId, $submerchantId);

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame('captured', $payment['status']);
    }

    public function testPaymentRejectResponse()
    {
        $this->createPaymentFailed();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(0, $payment['amount']);

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals('initial', $payment['recurring_type']);

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals('false', $enach['status']);

        $this->assertEquals(null, $enach['umrn']);

        $this->assertEquals($this->sharedTerminal['gateway_acquirer'], $enach['acquirer']);

        // if we get a non-error but failed registration response, we also get the NPCI reference id
        $this->assertNotNull($enach['gateway_reference_id']);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals('netbanking', $token['auth_type']);

        $this->assertEquals(null , $token['recurring_status']);

        $this->assertEquals(null, $token['gateway_token']);

        $this->assertNull($token['expired_at']);
    }

    public function testPaymentErrorResponse()
    {
        $payment                 = $this->getEmandatePaymentArray('SBIN', 'netbanking', 0);
        $payment['bank_account'] = [
            'account_number' => '1111111111111',
            'ifsc'           => 'sbin0000123',
            'name'           => 'Test account',
            'account_type'   => 'savings',
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

        $this->assertEquals(null, $enach['umrn']);

        $this->assertEquals('false', $enach['status']);

        $this->assertEquals($this->sharedTerminal['gateway_acquirer'], $enach['acquirer']);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals('netbanking', $token['auth_type']);

        $this->assertEquals(null, $token['recurring_status']);

        $this->assertEquals(null, $token['gateway_token']);

        $this->assertNull($token['expired_at']);
    }

    public function testPaymentErrorResponseWithoutCertificate()
    {
        $payment                 = $this->getEmandatePaymentArray('SBIN', 'netbanking', 0);
        $payment['bank_account'] = [
            'account_number' => '1111111111111',
            'ifsc'           => 'sbin0000123',
            'name'           => 'Test account',
            'account_type'   => 'savings',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content = 'ErrorXMLWithoutCert';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals(0, $payment['amount']);

        $this->assertEquals('initial', $payment['recurring_type']);

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals(null, $enach['umrn']);

        $this->assertEquals('false', $enach['status']);

        $this->assertEquals($this->sharedTerminal['gateway_acquirer'], $enach['acquirer']);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals('netbanking', $token['auth_type']);

        $this->assertEquals(null, $token['recurring_status']);

        $this->assertEquals(null, $token['gateway_token']);

        $this->assertNull($token['expired_at']);
    }

    public function testPaymentVerify()
    {
        $payment                 = $this->getEmandatePaymentArray('SBIN', 'netbanking', 0);
        $payment['bank_account'] = [
            'account_number' => '1111111111111',
            'ifsc'           => 'sbin0000123',
            'name'           => 'Test account',
            'account_type'   => 'savings',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $response = $this->doAuthPayment($payment);

        $verify = $this->verifyPayment($response['razorpay_payment_id']);

        assert($verify['payment']['verified'] === 1);
    }

    public function testPaymentVerifyWithoutUMRN()
    {
        $payment = $this->getEmandatePaymentArray('SBIN', 'netbanking', 0);

        $payment['bank_account'] = [
            'account_number' => '1111111111111',
            'ifsc'           => 'sbin0000123',
            'name'           => 'Test account',
            'account_type'   => 'savings',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $this->mockFailedCallbackResponse();

        $testData = $this->testData['testPaymentErrorResponse'];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['tranStatus'][0]['MndtId'] = '';
            }
        });

        $paymentEntity = $this->getDbLastPayment();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($paymentEntity) {
            $this->verifyPayment($paymentEntity->getPublicId());
        });

        $paymentEntity = $this->getDbLastPayment();

        $this->assertTrue($paymentEntity->isFailed());

        $this->assertNull($paymentEntity->getGlobalOrLocalTokenEntity()->getGatewayToken());

        $this->assertNull($paymentEntity->getGlobalOrLocalTokenEntity()->getRecurringStatus());
    }

    public function testPaymentFailedVerifySuccess()
    {
        $this->createPaymentFailed();

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            }
        );

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals($this->sharedTerminal['gateway_acquirer'], $enach['acquirer']);

        $this->assertNotNull($enach['gateway_reference_id']);
        $this->assertEquals('true', $enach['status']);
        $this->assertNotNull($enach['umrn']);
    }

    public function testPaymentFailedWithWrongOtpVerify()
    {
        $this->createPaymentFailedWithWrongOtp();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('emandate',$payment['method']);
        $this->assertEquals('created',$payment['status']);
        $this->assertEquals('enach_npci_netbanking',$payment['gateway']);

        $data = $this->testData[__FUNCTION__];

        $this->mockVerifyResponse();

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals($this->sharedTerminal['gateway_acquirer'], $enach['acquirer']);
        $this->assertNotNull($enach['gateway_reference_id']);
        $this->assertEquals(null, $enach['umrn']);
        $this->assertEquals('605',$enach['error_code']);
        $this->assertEquals('Otp Verification Failure',$enach['error_message']);
    }


    public function testAuthorizeFailedPayment()
    {
        $this->createPaymentFailed();

        $payment = $this->getLastEntity('payment', true);

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);

        $enach = $this->getLastEntity('enach', true);
        $this->assertNotNull($enach['gateway_reference_id']);
        $this->assertNotNull($enach['gateway_reference_id2']);
        $this->assertEquals('true', $enach['status']);
        $this->assertEquals('true' ,$enach['acknowledge_status']);
        $this->assertNotNull($enach['umrn']);
        $this->assertEquals($this->sharedTerminal['gateway_acquirer'], $enach['acquirer']);

        $token = $this->getLastEntity('token', true);
        $this->assertEquals('netbanking', $token['auth_type']);
        $this->assertEquals('confirmed', $token['recurring_status']);
        $this->assertNotNull($token['gateway_token']);
        $this->assertNull($token['expired_at']);
    }

    public function testDebitFileGenerationCiti()
    {
        $response = $this->makeDebitPayment();

        $enach = $this->getLastEntity('enach', true);

        $this->fixtures->stripSign($response['razorpay_payment_id']);

        $this->assertArraySelectiveEquals(
            [
                'payment_id' => $response['razorpay_payment_id'],
                'action'     => 'authorize',
                'bank'       => 'UTIB',
                'status'     => null,
                'amount'     => 300000,
            ],
            $enach
        );

        $this->ba->adminAuth();

        Queue::fake();

        $this->mockBeam(function ($pushData, $intervalInfo, $mailInfo, $synchronous)
        {
            return [
                'failed' => null,
                'success' => $pushData['files'],
            ];
        });

        $this->testData[__FUNCTION__] = $this->testData['testDebitFileGenerationCiti'];

        $content = $this->startTest();

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', [], true);

        $this->assertCount(2, $files['items']);

        $summary = $files['items'][0];
        $debit = $files['items'][1];

        $expectedFileContentSummary = [
            'type'        => 'citi_nach_debit_summary',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xls',
            'name'        => 'citi/nach/RAZORP_SUMMARY_shared_utility_code_07032020_test'
        ];

        $expectedFileContentDebit = [
            'type'        => 'citi_nach_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
            'name'        => 'citi/nach/RAZORP_COLLECT_shared_utility_code_07032020_test',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentSummary, $summary);
        $this->assertArraySelectiveEquals($expectedFileContentDebit, $debit);

        $fileContent = explode("\n", file_get_contents('storage/files/filestore/' . $debit['location']));

        // since date and amount is fixed for this test header is a constant
        $expectedHeader = '56       RAZORPAY SOFTWARE PVT LTD                                                                 0001000000000000000030000007032020                       shared_utility_cod000000000000000000CITI000PIGW000018003                          000000001                                                           ';

        $this->assertEquals($expectedHeader, $fileContent[0]);

        $debitRow = array_map('trim', $this->parseTextRow($fileContent[1], 0, ''));

        $expectedDebitRow = [
            'ACH Transaction Code' => '67',
            'Destination Account Type' => '10',
            'Beneficiary Account Holder\'s Name' => 'Test account',
            'User Name' => 'CTRAZORPAY',
            'Amount' => '0000000300000',
            'Destination Bank IFSC / MICR / IIN' => 'UTIB0000123',
            'Beneficiary\'s Bank Account number' => '1111111111111',
            'Sponsor Bank IFSC / MICR / IIN' => 'CITI000PIGW',
            'User Number' => 'shared_utility_cod',
            'Transaction Reference' => 'TESTMERCHA' . $response['razorpay_payment_id'],
            'Product Type' => '10',
            'UMRN' => 'UTIB6000000005844847'
        ];

        $this->assertArraySelectiveEquals($expectedDebitRow, $debitRow);
    }

    public function testDebitFileGenerationInvalidUMRN()
    {
        // TODO::remove test skip once the test is fixed for nach_icici.
        $this->markTestSkipped();

        $this->makeDebitPayment();

        $paymentEntity = $this->getLastPayment();

        $this->fixtures->edit(
            'token',
            $paymentEntity['token_id'],
            [
                Token\Entity::GATEWAY_TOKEN    => 'UMRNlessthan20char',
                Token\Entity::RECURRING        => 1,
                Token\Entity::RECURRING_STATUS => Token\RecurringStatus::CONFIRMED,
            ]);

        $response = $this->makeDebitPayment();

        $this->fixtures->stripSign($response['razorpay_payment_id']);

        $this->ba->adminAuth();

        Queue::fake();

        $this->testData[__FUNCTION__] = $this->testData['testDebitFileGenerationCiti'];

        $content = $this->startTest();

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', [], true);

        $this->assertCount(2, $files['items']);

        $summary = $files['items'][0];
        $debit = $files['items'][1];

        $expectedFileContentSummary = [
            'type'        => 'citi_nach_debit_summary',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xls',
            'name'        => 'citi/nach/RAZORP_SUMMARY_shared_utility_code_07032020_test'
        ];

        $expectedFileContentDebit = [
            'type'        => 'citi_nach_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
            'name'        => 'citi/nach/RAZORP_COLLECT_shared_utility_code_07032020_test',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentSummary, $summary);
        $this->assertArraySelectiveEquals($expectedFileContentDebit, $debit);

        $enach = $this->getLastEntity('enach', true);

        $this->assertArraySelectiveEquals(
            [
                'payment_id' => $response['razorpay_payment_id'],
                'action'     => 'authorize',
                'bank'       => 'UTIB',
                'status'     => null,
            ],
            $enach
        );

        $fileContent = explode("\n", file_get_contents('storage/files/filestore/' . $debit['location']));

        // since date and amount is fixed for this test header is a constant
        $expectedHeader = '56       RAZORPAY SOFTWARE PVT LTD                                                                 0000050000000000000030000007032020                       shared_utility_cod000000000000000000CITI000PIGW000018003                          000000001                                                           ';

        $this->assertEquals($expectedHeader, $fileContent[0]);

        $debitRow = array_map('trim', $this->parseTextRow($fileContent[1], 0, ''));

        $expectedDebitRow = [
            'ACH Transaction Code' => '67',
            'Destination Account Type' => '10',
            'Beneficiary Account Holder\'s Name' => 'Test account',
            'User Name' => 'CTRAZORPAY',
            'Amount' => '0000000300000',
            'Destination Bank IFSC / MICR / IIN' => 'UTIB0000123',
            'Beneficiary\'s Bank Account number' => '1111111111111',
            'Sponsor Bank IFSC / MICR / IIN' => 'CITI000PIGW',
            'User Number' => 'shared_utility_cod',
            'Transaction Reference' => 'TESTMERCHA' . $response['razorpay_payment_id'],
            'Product Type' => '10',
            'UMRN' => 'UTIB6000000005844847'
        ];

        $this->assertArraySelectiveEquals($expectedDebitRow, $debitRow);
    }

    public function testDebitFileGenerationMultipleUtilityCode()
    {
        $this->makeDebitPayment();

        $this->fixtures->create('terminal:direct_enach_npci_netbanking_terminal');

        $this->makeDebitPayment();

        $this->ba->adminAuth();

        Queue::fake();

        $this->mockBeam(function ($pushData, $intervalInfo, $mailInfo, $synchronous)
        {
            return [
                'failed' => null,
                'success' => $pushData['files'],
            ];
        });

        $this->testData[__FUNCTION__] = $this->testData['testDebitFileGenerationCiti'];

        $content = $this->startTest();

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', [], true);

        $this->assertCount(4, $files['items']);

        $directTerminalSummaryFile = $files['items'][0];
        $directTerminalDebitFile   = $files['items'][1];
        $sharedTerminalSummaryFile = $files['items'][2];
        $sharedTerminalDebitFile   = $files['items'][3];

        // TODO add assertion for file name
        $expectedFileContentForDirectTerminalDebit = [
            'type'        => 'citi_nach_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
        ];

        $expectedFileContentForDirectTerminalSummary = [
            'type'        => 'citi_nach_debit_summary',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xls',
        ];

        $expectedFileContentForSharedTerminalDebit = [
            'type'        => 'citi_nach_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
        ];

        $expectedFileContentForSharedTerminalSummary = [
            'type'        => 'citi_nach_debit_summary',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xls',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentForDirectTerminalDebit, $directTerminalDebitFile);
        $this->assertArraySelectiveEquals($expectedFileContentForDirectTerminalSummary, $directTerminalSummaryFile);
        $this->assertArraySelectiveEquals($expectedFileContentForSharedTerminalDebit, $sharedTerminalDebitFile);
        $this->assertArraySelectiveEquals($expectedFileContentForSharedTerminalSummary, $sharedTerminalSummaryFile);
    }

    public function testDebitFileGenerationOnNonWorkingDay()
    {
        $fixedTime = (new Carbon())->timestamp(self::FIXED_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $response = $this->makeDebitPayment();

        $this->ba->adminAuth();

        Queue::fake();

        $this->startTest();
    }

    public function testDebitFileGenerationMultipleSponsorBanks()
    {
        $citiTerminalPaymentResponse = $this->makeDebitPayment();

        $this->fixtures->stripSign($citiTerminalPaymentResponse['razorpay_payment_id']);

        $this->fixtures->terminal->disableTerminal($this->sharedTerminal['id']);

        $yesbTerminal = $this->fixtures->create(
            'terminal:shared_enach_npci_netbanking_yesb_terminal',
            [
                Terminal\Entity::ID               => Terminal\Shared::ENACH_NPCI_NETBANKING_YESB_TERMINAL,
                Terminal\Entity::GATEWAY_ACQUIRER => 'yesb'
            ]
        );

        $yesbTerminalPaymentResponse = $this->makeDebitPayment();

        $this->fixtures->stripSign($yesbTerminalPaymentResponse['razorpay_payment_id']);

        $this->ba->adminAuth();

        Queue::fake();

        $this->mockBeam(function ($pushData, $intervalInfo, $mailInfo, $synchronous)
        {
            return [
                'failed' => null,
                'success' => $pushData['files'],
            ];
        });

        $content = $this->startTest($this->testData['testDebitFileGenerationCiti']);

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', [], true);

        $this->assertCount(2, $files['items']);

        $summary = $files['items'][0];
        $debit = $files['items'][1];

        $expectedFileContentSummary = [
            'type'        => 'citi_nach_debit_summary',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xls',
        ];

        $expectedFileContentDebit = [
            'type'        => 'citi_nach_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentSummary, $summary);
        $this->assertArraySelectiveEquals($expectedFileContentDebit, $debit);

        $fileContent = file_get_contents('storage/files/filestore/' . $debit['location']);

        $this->assertNotFalse(strpos($fileContent, $citiTerminalPaymentResponse['razorpay_payment_id']));

        $this->assertFalse(strpos($fileContent, $yesbTerminalPaymentResponse['razorpay_payment_id']));

        $this->fixtures->terminal->disableTerminal($yesbTerminal['id']);

        $this->fixtures->terminal->enableTerminal($this->sharedTerminal['id']);
    }

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

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

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

    protected function mockRejectCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize_get_secure_data')
            {
                $content['Accptd']     = 'false';
                $content['AccptRefNo'] = '';
                $content['ReasonCode'] = 'AP04';
                $content['ReasonDesc'] = 'Account Inoperative';
                $content['RejectBy']   = 'Bank';
            }
        });
    }

    protected function mockWrongOtpCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize_get_secure_data')
            {
                $content['Accptd']     = '';
                $content['AccptRefNo'] = '';
                $content['ReasonCode'] = '605';
                $content['ReasonDesc'] = 'Otp Verification Failure';
                $content['RejectBy']   = 'Customer';
            }
        });
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

    protected function makeRequestWithGivenUrlAndFile($url, $file)
    {
        $request = [
            'url'     => $url,
            'method'  => 'POST',
            'content' => [
                'type'     => 'nach',
                'sub_type' => 'debit',
                'gateway'  => 'nach_citi',
            ],
            'files'   => [
                'file' => $file,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    public function testDebitCancel()
    {
        $this->makeDebitPayment();

        $debitPayment = $this->getLastPayment();

        $this->fixtures->stripSign($debitPayment['id']);

        $data['payment_id'] = [
            'payment_id' => $debitPayment['id'],
        ];

        $excel = (new ExcelExport)->setSheets(function() use ($data) {

            $sheetsInfo[] = (new ExcelSheetExport($data))->setTitle('Sheet1');

            return $sheetsInfo;
        });

        $data = $excel->raw('Xlsx');

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);

        $file = (new TestingFile('cancel data.xlsx', $handle));

        $data = $this->testData['testDebitCancel'];

        $data['request']['files']['file'] = $file;

        $this->ba->adminAuth();

        $this->startTest($data);
    }

    protected function createPaymentFailed()
    {
        $payment                 = $this->getEmandatePaymentArray('SBIN', 'netbanking', 0);
        $payment['bank_account'] = [
            'account_number' => '1111111111111',
            'ifsc'           => 'sbin0000123',
            'name'           => 'Test account',
            'account_type'   => 'savings',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockRejectCallbackResponse();

        $testData = $this->testData['testPaymentRejectResponse'];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }
    protected function createPaymentFailedWithWrongOtp()
    {
        $payment                 = $this->getEmandatePaymentArray('SBIN', 'netbanking', 0);
        $payment['bank_account'] = [
            'account_number' => '1111111111111',
            'ifsc'           => 'sbin0000123',
            'name'           => 'Test account',
            'account_type'   => 'savings',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockWrongOtpCallbackResponse();

        $testData = $this->testData['testPaymentFailedWithWrongOtpVerify'];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
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

    public function runWithData($entries, $batchId)
    {
        $this->ba->batchAppAuth();

        $testData = $this->testData['process_via_batch_service'];

        $testData['request']['server']['HTTP_X_Batch_Id'] = $batchId;

        $testData['request']['content'] = $entries;

        $this->runRequestResponseFlow($testData);
    }

    public function testPaymentUjvn()
    {
        $paymentInput = $this->getEmandatePaymentArray('UJVN', 'netbanking', 0);

        $paymentInput['bank_account'] = [
            'account_number' => '1111111111111',
            'ifsc'           => 'UJVN0000001',
            'name'           => 'Test account',
            'account_type'   => 'current',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $paymentInput['amount'], 'currency' => $paymentInput['currency'], 'method' => $paymentInput['method'], 'payment_capture' => '1', 'receipt' => 'test1', 'bank' => $paymentInput['bank']]);
        $paymentInput['order_id'] = $order->getPublicId();

        $this->mockServerRequestFunction(function (& $content)
        {
                $this->assertNotNull($content);
                $this->assertEquals('USFB', $content['BankID']);
        });

        $this->makeRequestAndCatchException(function () use ($paymentInput) {
            $this->doAuthPayment($paymentInput);
        }, \RZP\Exception\BadRequestException::class, 'Bank code provided does not match order bank.');
    }

    public function testOrderCreationWithBankAccount()
    {
        $orderInput = [
            'amount' => 0,
            'payment_capture' => true,
            'method' => Method::EMANDATE,
            'currency' => 'INR',
            'receipt' => 'test1',
            'bank' => 'UJVN',
            'bank_account'=>[
                'name' => 'Test account',
                'account_number' => '1111111111111',
                'ifsc' => 'UJVN0000001'
            ]
        ];

        $order = $this->createOrder($orderInput);

        $this->assertEquals('created', $order['status']);
    }

    public function testCreateEmandateRegistrationOrderWithUSFB()
    {
        $orderInput = [
            Order::AMOUNT          => 0,
            Order::BANK            => IFSC::USFB,
            Order::METHOD          => Method::EMANDATE,
            Order::PAYMENT_CAPTURE => true,
        ];

        $order = $this->createOrder($orderInput);

        $this->assertEquals('created', $order['status']);
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

    public function testFailureDebitFileGeneration()
    {
        $response = $this->makeDebitPayment();

        $this->fixtures->stripSign($response['razorpay_payment_id']);

        $this->ba->adminAuth();

        Queue::fake();

        $this->mockBeam(function ($pushData, $intervalInfo, $mailInfo, $synchronous)
        {
            return [
                'failed' => $pushData['files'],
                'success' => null,
            ];
        });

        $this->startTest();
    }

    public function testPartialDebitFileGeneration()
    {
        $response = $this->makeDebitPayment();

        $this->fixtures->stripSign($response['razorpay_payment_id']);

        $this->ba->adminAuth();

        Queue::fake();

        $this->mockBeam(function ($pushData, $intervalInfo, $mailInfo, $synchronous)
        {
            return [
                'failed'  => $pushData['files'][0],
                'success' => $pushData['files'][1],
            ];
        });

        $this->startTest();
    }
}
