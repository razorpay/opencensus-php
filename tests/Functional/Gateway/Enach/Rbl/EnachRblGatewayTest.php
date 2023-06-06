<?php

namespace RZP\Tests\Functional\Gateway\Enach\Rbl;

use Mail;
use Excel;
use Cache;
use ZipArchive;
use Carbon\Carbon;
use Illuminate\Http\Testing\File as TestingFile;

use RZP\Exception;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Status;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Refund;
use RZP\Models\Payment\Gateway;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Channel;
use RZP\Gateway\Base\VerifyResult;
use RZP\Excel\Export as ExcelExport;
use RZP\Models\FundTransfer\Attempt;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Payment\Entity as Payment;
use RZP\Exception\GatewayTimeoutException;
use RZP\Mail\Gateway\EMandate\Base as Email;
use RZP\Excel\ExportSheet as ExcelSheetExport;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Fixtures\Entity\TransactionTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;

class EnachRblGatewayTest extends TestCase
{
    use AttemptTrait;
    use TransactionTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use AttemptReconcileTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/EnachRblGatewayTestData.php';

        parent::setUp();

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_enach_rbl_terminal');
        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->enableEmandate();
        $this->fixtures->merchant->addFeatures([Constants::CHARGE_AT_WILL]);

        $this->gateway = 'enach_rbl';

        Cache::put('merchant_enach_configs', '{"auth_gateway":{"10000000000000": "esigner_legaldesk"}}', 120);
    }

    public function testSuccessfulEsignGeneration()
    {
        $payment                 = $this->getEmandatePaymentArray('HDFC', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'utib0000123',
            'name'           => 'Test account',
            'account_type'   => 'current',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals('authorize', $enach['action']);
        $this->assertEquals('HDFC', $enach['bank']);
        $this->assertEquals('ratn', $enach['acquirer']);
        $this->assertEquals(0, $enach['amount']);
        $this->assertNotNull($enach['gateway_reference_id']);
        $this->assertNotNull($enach['signed_xml']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertEquals('current', $token['account_type']);
        $this->assertEquals('initiated', $token['recurring_status']);
        $this->assertNull($token['expired_at']);

        return $enach;
    }

    public function testSuccessfulEsignGenerationOnLegaldesk()
    {
        $config = [
            'auth_gateway' => [
                '10000000000000' => 'esigner_legaldesk'
            ]
        ];

        Cache::put('config:merchant_enach_configs', $config, 120);

        $enach = $this->testSuccessfulEsignGeneration();

        $this->assertEquals('esigner_legaldesk', $enach['authentication_gateway']);
    }

    public function testEsignVerifyOnLegaldesk()
    {
        $config = [
            'auth_gateway' => [
                '10000000000000' => 'esigner_legaldesk'
            ]
        ];

        Cache::put('config:merchant_enach_configs', $config, 120);

        $this->testSuccessfulEsignGeneration();

        $payment = $this->getDbLastEntity('payment');

        $response = $this->verifyPayment($payment->getPublicId());

        $this->assertEquals(1, $response['payment']['verified']);
        $this->assertEquals('authorized', $response['payment']['status']);

        $this->assertEquals('status_match', $response['gateway']['status']);
        $this->assertEquals('esigner_legaldesk', $response['gateway']['gateway']);
        $this->assertNotEmpty($response['gateway']['gatewayPayment']['signed_xml']);
    }

    public function testFailedPaymentVerifyOnLegaldesk()
    {
        $config = [
            'auth_gateway' => [
                '10000000000000' => 'esigner_legaldesk'
            ]
        ];

        Cache::put('config:merchant_enach_configs', $config, 120);

        $payment = $this->getEmandatePaymentArray('HDFC', 'aadhaar', 0);

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'mandate_sign')
            {
                throw new GatewayTimeoutException("Timed out");
            }
        }, 'esigner_legaldesk');

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getDbLastEntity('payment');

        $enach = $this->getDbLastEntityToArray('enach');

        $enachInitialRegistrationDate = $enach['registration_date'];
        $this->assertNotNull($enach['registration_date']);
        $this->assertNull($enach['signed_xml']);
        $this->assertEquals('created', $payment['status']);

        $testData = $this->testData['legaldeskVerifyFailed'];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $response = $this->verifyPayment($payment->getPublicId());

            $this->assertEquals(1, $response['payment']['verified']);

            $this->assertEquals('status_mismatch', $response['gateway']['status']);
            $this->assertEquals('esigner_legaldesk', $response['gateway']['gateway']);

            $this->assertNotEmpty($response['gateway']['gatewayPayment']['signed_xml']);
        });

        $enach = $this->getDbLastEntityToArray('enach');

        $this->assertNotEmpty($enach['signed_xml']);
    }

    public function testSuccessfulEsignGenerationWithVid()
    {
        $payment = $this->getEmandatePaymentArray('HDFC', 'aadhaar', 0);

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        unset($payment['aadhaar']['number']);

        $payment['aadhaar']['vid'] = '1234567890123456';

        $this->doAuthPayment($payment);

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals('authorize', $enach['action']);
        $this->assertEquals('HDFC', $enach['bank']);
        $this->assertEquals('ratn', $enach['acquirer']);
        $this->assertEquals(0, $enach['amount']);
        $this->assertNotNull($enach['gateway_reference_id']);
        $this->assertNotNull($enach['signed_xml']);
    }

    public function testSuccessfulEsignGenerationWithNeitherVidNorAadhaar()
    {
        $payment                 = $this->getEmandatePaymentArray('HDFC', 'aadhaar', 0);

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        unset($payment['aadhaar']);

        $this->doAuthPayment($payment);

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals('authorize', $enach['action']);
        $this->assertEquals('HDFC', $enach['bank']);
        $this->assertEquals('ratn', $enach['acquirer']);
        $this->assertEquals(0, $enach['amount']);
        $this->assertNotNull($enach['gateway_reference_id']);
        $this->assertNotNull($enach['signed_xml']);
    }

    public function testAuthenticationFailed()
    {
        $this->markTestSkipped();

        $payment = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);

        $payment['bank_account'] = [
            'account_number' => '914010009305864',
            'ifsc'           => 'utib0000123',
            'name'           => 'Test account',
            'account_type'   => 'current',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $enach = $this->getLastEntity('enach', true);

        $this->assertNotNull($enach['gateway_reference_id']);
        $this->assertEquals('Invalid Aadhaar id', $enach['error_message']);
        $this->assertEquals('REQUEST_VALIDATION_FAILED', $enach['error_code']);
    }

    public function testDigioVerify()
    {
        $payment = $this->getEmandatePaymentArray('HDFC', 'aadhaar', 0);

        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'utib0000123',
            'name'           => 'Test account',
            'account_type'   => 'current',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $response = $this->doAuthPayment($payment);

        $verify = $this->verifyPayment($response['razorpay_payment_id']);

        $this->assertEquals(true, $verify['gateway']['gatewaySuccess']);

        $this->assertEquals(VerifyResult::STATUS_MATCH, $verify['gateway']['status']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(1, $payment[Payment::VERIFIED]);

        $gateway = $this->getLastEntity('enach', true);

        $this->assertNotNull($gateway['signed_xml']);
    }

    public function testDigioAuthFailedVerifySuccess()
    {
        $this->markTestSkipped();

        $this->setMockGatewayTrue();

        $this->mockAuthFailed();

        $payment = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);

        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'utib0000123',
            'name'           => 'Test account',
            'account_type'   => 'current',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Status::FAILED, $payment['status']);

        $data = $this->testData['testVerifyMismatch'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });

        $gateway = $this->getLastEntity('enach', true);

        $this->assertNotNull($gateway['signed_xml']);
    }

    public function testDigioCallbackFailedVerifySuccess()
    {
        $this->markTestSkipped();

        $this->setMockGatewayTrue();

        $this->mockPaymentRequestTimeout();

        $payment = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);

        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'utib0000123',
            'name'           => 'Test account',
            'account_type'   => 'current',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Status::FAILED, $payment['status']);

        $data = $this->testData['testVerifyMismatch'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });

        $gateway = $this->getLastEntity('enach', true);

        $this->assertNotNull($gateway['signed_xml']);
    }

    public function testAuthorizeFailedPayment()
    {
        $this->markTestSkipped();

        $this->setMockGatewayTrue();

        $this->mockPaymentRequestTimeout();

        $payment = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);

        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'utib0000123',
            'name'           => 'Test account',
            'account_type'   => 'current',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $testData = $this->testData['testDigioCallbackFailedVerifySuccess'];

        $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Status::AUTHORIZED, $payment['status']);
        $this->assertEquals(true, $payment['late_authorized']);

        $gateway = $this->getLastEntity('enach', true);

        $this->assertNotNull($gateway['signed_xml']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertEquals('initiated', $token['recurring_status']);
    }

    public function testAcknowledgementSuccessfulReconciliation()
    {
        list($payment, $token, $order) = $this->createEmandatePayment();

        $batchFile = $this->getAcknowledgeBatchFileToUpload($payment);

        $url = '/admin/batches';
        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'acknowledge');

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $enach = $this->getDbLastEntityToArray('enach');

        $this->assertNotNull($enach['umrn']);
        $this->assertEquals('1', $enach['acknowledge_status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNotNull($token['acknowledged_at']);
        $this->assertEquals('initiated', $token['recurring_status']);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testAcknowledgementFailedReconciliation()
    {
        list($payment, $token, $order) = $this->createEmandatePayment();

        $itemReplace = [
            'UMRN'         => '',
            'ACK_DESC'     => 'so this has failed',
        ];

        $batchFile = $this->getAcknowledgeBatchFileToUpload($payment, $itemReplace);

        $url = '/admin/batches';
        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'acknowledge');

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $enach = $this->getDbLastEntityToArray('enach');

        $this->assertEquals('0', $enach['acknowledge_status']);
        $this->assertNotNull($enach['umrn']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertEquals('rejected', $token['recurring_status']);
    }

    public function testRegisterFileGeneration()
    {
        $dt = Carbon::create(2018, 05, 27, 12, 35, 00, Timezone::IST);

        Carbon::setTestNow($dt);

        $payment                 = $this->getEmandatePaymentArray('HDFC', 'aadhaar', 0);

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals('authorize', $enach['action']);
        $this->assertEquals(0, $enach['amount']);
        $this->assertNotNull($enach['signed_xml']);

        //
        // We choose 28th May because registration happened on 27th May
        // need to be sent on 28th May. This date will change if value
        // of is `$dt` is changed.
        //
        $dt = Carbon::create(2018, 05, 28, 7, 35, 00, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->ba->cronAuth();

        $response = $this->startTest();

        $this->assertNotNull($response['items'][0]['file_generated_at']);

        $file = $this->getDbLastEntityToArray('file_store');

        $this->assertEquals('gateway_file', $file['entity_type']);
        $this->assertEquals('rbl_enach_register', $file['type']);
        $this->assertEquals('zip', $file['extension']);
        $this->assertEquals('application/x-compressed', $file['mime']);
    }

    public function testRegistrationReconWithTestMerchantProxyAuth()
    {
        $payment = $this->createAcknowledgedEnachPayment(false);

        $batchFile = $this->getBatchFileToUpload($payment);

        $url = '/batches';
        $this->ba->proxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($url, $batchFile)
        {
            $this->makeRequestWithGivenUrlAndFile($url, $batchFile);
        });
    }

    public function testRegistrationReconInvalidResponseCode()
    {
        $payment = $this->createAcknowledgedEnachPayment(false);

        $batchFile = $this->getBatchFileToUpload($payment, 'Pending', '123', 'Some error message');

        $url = '/admin/batches';
        $this->ba->adminAuth();

        $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $enach = $this->getDbLastEntityToArray('enach');

        $this->assertNull($enach['registration_status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);
        $this->assertEquals('initiated', $token['recurring_status']);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testRegistrationReconUnknownResponseCode()
    {
        $payment = $this->createAcknowledgedEnachPayment(false);

        $batchFile = $this->getBatchFileToUpload($payment, 'Rejected', '123', 'Some error message');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        // Not yet live for batch flow, so disabling flag.
        $flag = false;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $enach = $this->getDbLastEntityToArray('enach');

        $this->assertEquals('Rejected', $enach['registration_status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);

        $this->assertEquals('rejected', $token['recurring_status']);

        $this->assertEquals(PublicErrorDescription::GATEWAY_ERROR, $token['recurring_failure_reason']);

        $payment = $this->getDbLastEntityToArray('payment');

        // Since, refunds are no longer created in API DB,
        // need to update payments and transactions explicitly.
        if ($flag === true)
        {
            $input = [
                'payment_id'       => $payment['id'],
                'refund_id'        => 'HZETs6HPiyDr8n',
                'amount'           => '0',
                'base_amount'      => '0',
                'gateway'          => $payment['gateway'],
                'speed_decisioned' => 'normal'
            ];

            // create transaction entity, reconcile it as per '/Processor/Emandate/Base.php' and do assertions
            $this->createTransactionForRefunds($input, true);

            $this->updatePaymentStatus($payment['id'], [], true);
            $payment = $this->getDbLastEntityToArray('payment');
        }

        $this->assertEquals('refunded', $payment['status']);
    }

    public function testRegistrationReconWithSharedMerchantProxyAuth()
    {
        $this->markTestSkipped();

        $payment = $this->createAcknowledgedEnachPayment(false);

        $batchFile = $this->getBatchFileToUpload($payment);

        $user = $this->fixtures->user->createUserForMerchant('100000Razorpay');

        $url = '/batches';
        $this->ba->proxyAuth('rzp_test_100000Razorpay', $user->getId());

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($url, $batchFile) {
            $this->makeRequestWithGivenUrlAndFile($url, $batchFile);
        });
    }

    public function testRegisterSuccessReconciliation()
    {
        $payment = $this->createAcknowledgedEnachPayment();

        $batchFile = $this->getBatchFileToUpload($payment);

        $url = '/admin/batches';
        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $enach = $this->getDbLastEntityToArray('enach');

        $this->assertNotNull($enach['umrn']);
        $this->assertEquals('Active', $enach['registration_status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['expired_at']);
        $this->assertNull($token['account_type']);
        $this->assertNotNull($token['gateway_token']);
        $this->assertEquals('confirmed', $token['recurring_status']);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('captured', $payment['status']);

        $transaction = $this->getDbLastEntityToArray('transaction');

        $this->assertNotNull($transaction['reconciled_at']);

        $this->refundPayment($payment['public_id']);

        $payment = $this->getDbLastEntity('payment')->toArray();
        $refund  = $this->getDbLastEntity('refund')->toArray();

        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals(0, $refund['amount']);
        $this->assertEquals(0, $payment['amount_refunded']);
        $this->assertEquals('refunded', $payment['status']);
    }

    public function testRegisterFailureReconciliation()
    {
        $payment = $this->createAcknowledgedEnachPayment(false);

        $batchFile = $this->getBatchFileToUpload($payment, 'Rejected', 'M037', 'Account closed');

        $url = '/admin/batches';
        $this->ba->adminAuth();

        $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $enach = $this->getDbLastEntityToArray('enach');

        $this->assertArraySelectiveEquals(
            [
                'registration_status' => 'Rejected',
                'error_message'       => 'Account closed',
                'error_code'          => 'M037',
            ],
            $enach
        );

        $token = $this->getDbLastEntityToArray('token');

        $this->assertArraySelectiveEquals(
            [
                'recurring_status'         => 'rejected',
                'recurring_failure_reason' => PublicErrorDescription::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
            ],
            $token
        );

        $this->assertNull($token['gateway_token']);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('refunded', $payment['status']);

        $refund = $this->getDbLastEntityToArray('refund');

        $this->assertEquals(Refund\Status::CREATED, $refund['status']);
        $this->assertEquals(0, $refund['amount']);
        $this->assertEquals(0, $payment['amount_refunded']);

        $transaction = $this->getDbLastEntityToArray('transaction');

        $this->assertNotNull($transaction['reconciled_at']);
    }

    public function testDebitFileGeneration()
    {
        $payment                 = $this->getEmandatePaymentArray('HDFC', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'HDFC0000123',
            'name'           => 'Test account',
            'account_type'   => 'current',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 3000]);

        $this->fixtures->edit(
            'token',
            $tokenId,
            [
                Token\Entity::GATEWAY_TOKEN    => 'HDFC6000000005844847',
                Token\Entity::RECURRING        => 1,
                Token\Entity::RECURRING_STATUS => Token\RecurringStatus::CONFIRMED,
            ]);

        $payment             = $this->getEmandatePaymentArray('HDFC', null, 3000);
        $payment['token']    = $tokenId;
        $payment['order_id'] = $order->getPublicId();

        unset($payment['auth_type']);

        $response = $this->doS2SRecurringPayment($payment);

        $lastDebitPayment = $this->getLastEntity('payment', true);

        // setting created at to 8am. Payments are picked from 9 to 9 cycle.
        $createdAt = Carbon::today(Timezone::IST)->addHours(8)->getTimestamp();

        $this->fixtures->edit(
            'payment',
            $lastDebitPayment['id'],
            [
                'created_at' => $createdAt,
            ]);

        $paymentId = substr($response['razorpay_payment_id'], 4);

        $this->ba->adminAuth();

        Mail::fake();

        $content = $this->startTest();
        $content = $content['items'][0];

        $this->assertNotNull($content['file_generated_at']);
        $this->assertEquals("file_sent", $content['status']);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'rbl_enach_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertStringMatchesFormat(
            'rbl-enach/outgoing/TXN_INP/ACH-DR-RATN-RATNA0001-%d-000001-INP_test',
            $file['name']
        );

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        $enach = $this->getLastEntity('enach', true);
        $this->assertArraySelectiveEquals(
            [
                'payment_id' => $paymentId,
                'action'     => 'authorize',
                'bank'       => 'HDFC',
                'status'     => null,
            ],
            $enach
        );

        Mail::assertQueued(Email::class, function($mail) use ($file) {
            $key = Gateway::ENACH_RBL . '_debit';

            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $this->assertNotNull($mail->viewData['file_name']);
            $this->assertNotNull($mail->viewData['signed_url']);

            $this->assertNotEmpty($mail->attachments);

            return (($mail->hasFrom('emandate@razorpay.com')) and
                    ($mail->hasTo('rbl.emandate@razorpay.com')));
        });
    }

    public function testDebitFileReconciliation()
    {
        $payment = $this->makeDebitPayment();

        $fileStatuses = [
            'status'     => 'PAID',
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
                'status' => 'PAID',
            ],
            $enach
        );
    }

    public function testDebitVerify()
    {
        $this->testDebitFileReconciliation();

        $payment = $this->getDbLastEntityToArray('payment');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $verify = $this->verifyPayment('pay_' . $payment['id']);
            });
    }

    public function testDebitFileReconciliationFailure()
    {
        $payment = $this->makeDebitPayment();

        $fileStatuses = [
            'status'     => 'bounce',
            'error_code' => '01',
            'error_desc' => 'Account closed or transferred',
        ];

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id'])->toArray();

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals('BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN', $payment['internal_error_code']);

        $enach = $this->getDbEntities('enach', ['payment_id' => $payment['id']])->first()->toArray();

        $this->assertArraySelectiveEquals(
            [
                'status'        => 'bounce',
                'error_message' => 'Account closed or transferred',
            ],
            $enach
        );
    }

    public function testDebitFileReconciliationInvalidResponse()
    {
        $payment = $this->makeDebitPayment();

        $fileStatuses = [
            'status'     => 'INVALID_RESPONSE',
            'error_code' => '123',
            'error_desc' => 'Account closed or transferred',
        ];

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id'])->toArray();

        $this->assertEquals('created', $payment['status']);

        $enach = $this->getDbEntities('enach', ['payment_id' => $payment['id']])->first()->toArray();

        $this->assertArraySelectiveEquals(
            [
                'status'        => 'INVALID_RESPONSE',
                'error_message' => 'Account closed or transferred',
            ],
            $enach
        );
    }

    public function testDebitFileReconciliationUnknownResponseCode()
    {
        $payment = $this->makeDebitPayment();

        $fileStatuses = [
            'status'     => 'bounce',
            'error_code' => '123',
            'error_desc' => 'Account closed or transferred',
        ];

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $payment = $this->getDbEntityById('payment', $payment['id'])->toArray();

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals('BAD_REQUEST_ERROR', $payment['error_code']);

        $this->assertEquals('BAD_REQUEST_PAYMENT_FAILED', $payment['internal_error_code']);
    }

    public function testDebitFileReconciliationTerminalsCheck()
    {
        $payment = $this->makeDebitPayment();

        $fileStatuses = [
            'status'     => 'PAID',
            'error_code' => '',
            'error_desc' => '',
        ];

        /*
         * Creating a direct terminal for enach, now $payment should go
         * on shared terminal and $payment2 should go on the direct terminal
        */
        $this->fixtures->create('terminal:direct_enach_rbl_terminal');

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('1000EnachRblTl', $payment['terminal_id']);

        $payment2 = $this->makeDebitPayment();

        $batch = $this->makeBatchDebitPayment($payment2, $fileStatuses);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $payment2 = $this->getDbEntityById('payment', $payment2['id']);

        $this->assertEquals('1EnachRblTrmnl', $payment2['terminal_id']);
    }

    public function testDebitFileReconciliationDirectTerminal()
    {
        $this->fixtures->terminal->disableTerminal($this->sharedTerminal->getId());

        $this->fixtures->create('terminal:direct_enach_rbl_terminal');

        $payment = $this->makeDebitPayment();

        $this->assertEquals('1EnachRblTrmnl', $payment['terminal_id']);
    }

    public function testDebitFileReconciliationNoTerminals()
    {
        $this->fixtures->terminal->disableTerminal($this->sharedTerminal->getId());

        $payment                 = $this->getEmandatePaymentArray('HDFC', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'HDFC0000123',
            'name'           => 'Test account',
            'account_type'   => 'current',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->makeRequestAndCatchException(function () use ($payment)
        {
            $this->doAuthPayment($payment);
        }, \RZP\Exception\RuntimeException::class, 'Terminal should not be null');
    }

    // enach_rbl refund migrated to scrooge
    public function testDebitFileReconciliationRefund()
    {
        $payment = $this->makeDebitPayment();

        $fileStatuses = [
            'status'     => 'PAID',
            'error_code' => '',
            'error_desc' => '',
        ];

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/pay_' . $payment['id'] . '/refund';
        $testData['request']['content']['amount'] = $payment['amount'];

        $this->ba->privateAuth();

        //$response = $this->refundPayment('pay_' . $payment['id']);
        $response = $this->refundPayment('pay_' . $payment['id'],null, ['is_fta' => true]);

        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals($response['id'], $refund['id']);

        $this->assertEquals('pay_' . $payment['id'], $refund['payment_id']);

        //$this->assertEquals('initiated', $refund['status']);
        $this->assertEquals('created', $refund['status']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], $refund['id']);

        $this->assertEquals('Test Merchant Refund ' . $payment['id'], $fundTransferAttempt['narration']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals('HDFC0000123', $bankAccount['ifsc_code']);

        $this->assertEquals('test', $bankAccount['beneficiary_name']);

        $this->assertEquals('914010009305862', $bankAccount['account_number']);

        $this->assertEquals('refund', $bankAccount['type']);
    }

    public function testDebitFileReconciliationRefundOld()
    {
        $payment = $this->makeDebitPayment();

        $fileStatuses = [
            'status'     => 'PAID',
            'error_code' => '',
            'error_desc' => '',
        ];

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $attr = [
            'payment' => $payment,
            'status' => 'failed',
            'gateway_refunded' => false,
            'attempts' => 1,
        ];

        $refund = $this->fixtures->create('refund:from_payment', $attr);

        $refund  = $this->getLastEntity('refund', true);

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals('pay_' . $payment['id'], $refund['payment_id']);

        $this->assertEquals('initiated', $refund['status']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], $refund['id']);

        $this->assertEquals('Test Merchant Refund ' . $payment['id'], $fundTransferAttempt['narration']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals('HDFC0000123', $bankAccount['ifsc_code']);

        $this->assertEquals('test', $bankAccount['beneficiary_name']);

        $this->assertEquals('914010009305862', $bankAccount['account_number']);

        $this->assertEquals('refund', $bankAccount['type']);
    }

    public function testDebitFileReconciliationRefundFailedAttempt()
    {
        $payment = $this->makeDebitPayment();

        $fileStatuses = [
            'status'     => 'PAID',
            'error_code' => '',
            'error_desc' => '',
        ];

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $testData = $this->testData['testDebitFileReconciliationRefund'];

        $this->ba->privateAuth();

        $response = $this->refundPayment('pay_' . $payment['id']);

        $refund  = $this->getLastEntity('refund', true);

        $this->fixtures->edit('refund', $refund['id'], ['status' => 'failed']);

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals('pay_' . $payment['id'], $refund['payment_id']);

        $this->assertEquals('initiated', $refund['status']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], $refund['id']);

        $this->assertEquals('Test Merchant Refund ' . $payment['id'], $fundTransferAttempt['narration']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals('HDFC0000123', $bankAccount['ifsc_code']);

        $this->assertEquals('test', $bankAccount['beneficiary_name']);

        $this->assertEquals('914010009305862', $bankAccount['account_number']);

        $this->assertEquals('refund', $bankAccount['type']);
    }

    public function testDebitFileReconciliationRefundBankTransfer()
    {
        $this->testDebitFileReconciliationRefund();

        $channel = Channel::YESBANK;

        $content = $this->initiateTransfer(
            $channel,
            Attempt\Purpose::REFUND, Attempt\Type::REFUND);

        $data = $this->reconcileOnlineSettlements($channel, false);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertNotNull($attempt['utr']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt[Attempt\Entity::STATUS]);

        // Process entities
        $this->reconcileEntitiesForChannel($channel);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);

        $refund = $this->getLastEntity('refund', true);

        //$this->assertEquals(Refund\Status::PROCESSED, $refund['status']);
        $this->assertEquals(Refund\Status::CREATED, $refund['status']);
        $this->assertEquals(1, $refund['attempts']);
        $this->assertNotNull($attempt['utr']);
    }

    public function testTokenMaxExpire()
    {
        $payment = $this->getEmandatePaymentArray('HDFC', 'aadhaar', 0);

        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'HDFC0000123',
            'name'           => 'Test account',
            'account_type'   => 'current',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $payment['recurring_token']['expire_by'] = '9223372036854775807';

        $this->doAuthPayment($payment);

        $tokenEntity = $this->getLastEntity('token', true);

        $this->assertEquals('9223372036854775807', $tokenEntity['expired_at']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $paymentEntity['status']);
    }

    public function testPreferencesForRegisterDisabledBank()
    {
        $this->fixtures->merchant->addFeatures([Constants::ESIGN]);

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);

        $this->ba->publicAuth();

        $testData['request']['content'] = ['key_id' => $this->ba->getKey(), 'order_id' => $order->getPublicId()];

        $content = $this->startTest($testData);

        $banks = $content['methods']['recurring']['emandate'];

        $authTypeBanks = array_values($banks);

        $count = 0;

        foreach($authTypeBanks as $value)
        {
            if (in_array('aadhaar', $value['auth_types']))
            {
                $count++;
            }
        }

        $this->assertEquals(375, $count);

        $this->assertArrayNotHasKey(IFSC::UTBI, $banks);
    }

    public function testRegisterFileGenerationWithReplicationLagError()
    {
        $connector = $this->mockSqlConnectorWithReplicaLag(700000);

        $this->app->instance('db.connector.mysql', $connector);

        $this->app['config']->set('database.connections.live.heartbeat_check.enabled', true);

        $dt = Carbon::create(2018, 05, 27, 12, 35, 00, Timezone::IST);

        Carbon::setTestNow($dt);

        $payment = $this->getEmandatePaymentArray('HDFC', 'aadhaar', 0);

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals('authorize', $enach['action']);
        $this->assertEquals(0, $enach['amount']);
        $this->assertNotNull($enach['signed_xml']);

        //
        // We choose 28th May because registration happened on 27th May
        // need to be sent on 28th May. This date will change if value
        // of is `$dt` is changed.
        //
        $dt = Carbon::create(2018, 05, 28, 7, 35, 00, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testCancelEmandateToken()
    {
        $payment = $this->makeDebitPayment();

        $fileStatuses = [
            'status'     => 'PAID',
            'error_code' => '',
            'error_desc' => '',
        ];

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('captured', $payment['status']);

        $enach = $this->getDbEntities('enach', ['payment_id' => $payment['id']])->first()->toArray();

        $this->assertArraySelectiveEquals(['status' => 'PAID'], $enach);

        $token = $this->getDbEntityById('token',$payment['token_id']);

        $this->assertEquals('confirmed',$token['recurring_status']);

        $response = $this->deleteCustomerToken('token_' . $payment['token_id'], 'cust_' . $payment['customer_id']);

        $this->assertEquals(true, $response['deleted']);

        $this->ba->adminAuth();

        $this->startTest();

        $fileStore = $this->getDbLastEntityToArray(Entity::FILE_STORE);

        $this->assertEquals("rbl_enach_cancel", $fileStore['type']);

        $date = Carbon::now(Timezone::IST)->format('dmY');

        $this->assertEquals("rbl/nach/input_file/MMS-CANCEL-RATN-RATNA0001-$date-ESIGN000001-INP", $fileStore['name']);
    }

    public function testCancelEmandateTokenWithMultipleUtilityCode()
    {
        $payment1 = $this->makeDebitPayment();

        $this->fixtures->create('terminal:direct_enach_rbl_terminal');

        $payment2 = $this->makeDebitPayment();

        $fileStatuses = [
            'status'     => 'PAID',
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

        $enach1 = $this->getDbEntities('enach', ['payment_id' => $payment1['id']])->first()->toArray();
        $enach2 = $this->getDbEntities('enach', ['payment_id' => $payment2['id']])->first()->toArray();

        $this->assertArraySelectiveEquals(['status' => 'PAID'], $enach1);
        $this->assertArraySelectiveEquals(['status' => 'PAID'], $enach2);

        $token1 = $this->getDbEntityById('token',$payment1['token_id']);
        $token2 = $this->getDbEntityById('token',$payment2['token_id']);

        $this->assertEquals('confirmed',$token1['recurring_status']);
        $this->assertEquals('confirmed',$token2['recurring_status']);

        $response1 = $this->deleteCustomerToken('token_' . $payment1['token_id'], 'cust_' . $payment1['customer_id']);
        $response2 = $this->deleteCustomerToken('token_' . $payment2['token_id'], 'cust_' . $payment2['customer_id']);

        $this->assertEquals(true, $response1['deleted']);
        $this->assertEquals(true, $response2['deleted']);

        $this->ba->adminAuth();

        $data = $this->testData['testCancelEmandateToken'];

        $this->startTest($data);

        $date = Carbon::now(Timezone::IST)->format('dmY');

        $fileStore = $this->getDbLastEntityToArray(Entity::FILE_STORE);

        $this->assertEquals("rbl/nach/input_file/MMS-CANCEL-RATN-RATNA0001-$date-ESIGN000001-INP", $fileStore['name']);
    }

    public function testMandateCancellationRblBankSuccessResponseFile()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastPayment();

        $this->assertEquals('confirmed', $payment->localToken->getRecurringStatus());

        $this->assertTrue($payment->isCreated());

        $fileStatuses = [
            'status'     => 'PAID',
            'error_code' => '',
            'error_desc' => '',
        ];

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

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

        $this->assertEquals('cancelled', $token['recurring_status']);
    }

    protected function getBatchFileToUploadForMandateCancelRes(Payment $payment): TestingFile
    {
        $paymentId = $payment->getId();

        $xmlData = file_get_contents(__DIR__ . '/MMS-CANCEL-RATN-RATNA0001-02052016-ESIGN000001-RES.xml');

        $responseXml = strtr($xmlData, ['$paymentId' => $paymentId]);

        $zip = new ZipArchive();

        $zip->open(__DIR__ . '/MMS-CANCEL-RATN-RATNA0001-02052016-ESIGN000001-RES.zip', ZipArchive::CREATE);

        $zip->addFromString( 'MMS-CANCEL-RATN-RATNA0001-02052016-ESIGN000001-RES.xml', $responseXml);

        $zip->close();

        $handle = fopen(__DIR__ . '/MMS-CANCEL-RATN-RATNA0001-02052016-ESIGN000001-RES.zip', 'r');

        return (new TestingFile('MMS-CANCEL-RATN-RATNA0001-02052016-ESIGN000001-RES.zip', $handle));
    }

    protected function makeDebitPayment()
    {
        $payment                 = $this->getEmandatePaymentArray('HDFC', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'HDFC0000123',
            'name'           => 'Test account',
            'account_type'   => 'current',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 5000]);

        $this->fixtures->edit(
            'token',
            $tokenId,
            [
                Token\Entity::GATEWAY_TOKEN    => 'HDFC6000000005844847',
                Token\Entity::RECURRING        => 1,
                Token\Entity::RECURRING_STATUS => Token\RecurringStatus::CONFIRMED,
            ]);

        $payment             = $this->getEmandatePaymentArray('HDFC', null, $order->getAmount());
        $payment['token']    = $tokenId;
        $payment['order_id'] = $order->getPublicId();

        unset($payment['auth_type']);

        $response = $this->doS2SRecurringPayment($payment);

        return $this->getDbEntityById('payment', $response['razorpay_payment_id']);
    }

    protected function makeBatchDebitPayment($payment, $fileStatuses)
    {
        $this->fixtures->create(
            'enach',
            [
                'payment_id' => $payment['id'],
                'action'     => 'authorize',
                'bank'       => 'HDFC',
                'amount'     => $payment['amount'],
            ]
        );

        $content = [
            'sheet1' => [
                'config' => [
                    'start_cell' => 'A1',
                ],
                'items'  => [
                    [
                        'SRNO'               => '1',
                        'ECS_DATE'           => Carbon::today()->format('m/d/Y'),
                        'SETTLEMENT DATE'    => Carbon::today()->format('m/d/Y'),
                        'CUST_REFNO'         => '',
                        'SCH_REFNO'          => '',
                        'CUSTOMER_NAME'      => 'User name',
                        'AMOUNT'             => $payment['amount'] / 100,
                        'REFNO'              => $payment['id'],
                        'UMRN'               => 'HDFC6000000005844847',
                        'UPLOAD_DATE'        => '',
                        'ACKUPD_DATE'        => '',
                        'RESPONSE_RECEIVED'  => '',
                        'STATUS'             => $fileStatuses['status'],
                        'REASON_CODE'        => $fileStatuses['error_code'],
                        'REASON_DESCRIPTION' => $fileStatuses['error_desc'],
                    ],
                ],
            ],
        ];

        $data = $this->getExcelString('Debit MIS', $content);

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);
        $file = (new TestingFile('Debit MIS.xlsx', $handle));

        $request = [
            'url'     => '/admin/batches',
            'method'  => 'POST',
            'content' => [
                'type'     => 'emandate',
                'sub_type' => 'debit',
                'gateway'  => 'enach_rbl',
            ],
            'files'   => [
                'file' => $file,
            ],
        ];

        $this->ba->adminAuth();

        $batch = $this->makeRequestAndGetContent($request);

        return $this->getDbEntityById('batch', $batch['id']);
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

        return $excel->raw('Xlsx');
    }

    protected function runPaymentCallbackFlowEnachRbl($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest(
                $url, $method, $content);
        }

        $response = $this->sendRequest($request);

        $data = array(
            'url' => $response->headers->get('location'),
            'method' => 'get');

        return $this->submitPaymentCallbackRequest($data);
    }

    protected function createEmandatePayment($amount = 0, $recurringType = 'initial')
    {
        $order = $this->fixtures->create('order:emandate_order', [
            'status' => 'attempted',
            'amount' => 0]);

        $token = $this->fixtures->create('customer:emandate_token', [
            'aadhaar_number' => '390051307206',
            'auth_type'      => 'aadhaar']);

        $payment = [
            'auth_type'         => 'aadhaar',
            'terminal_id'       => '1000EnachRblTl',
            'order_id'          => $order->getId(),
            'amount'            => $order->getAmount(),
            'amount_authorized' => $order->getAmount(),
            'gateway'           => 'enach_rbl',
            'bank'              => 'HDFC',
            'recurring'         => '1',
            'customer_id'       => $token->getCustomerId(),
            'token_id'          => $token->getId(),
            'recurring_type'    => $recurringType,
        ];

        $payment = $this->fixtures->create('payment:emandate_authorized', $payment);

        return [$payment, $token, $order];
    }

    protected function createAcknowledgedEnachPayment($webhook = true)
    {
        list($payment, $token, $order) = $this->createEmandatePayment();

        if ($webhook === true)
        {
            $expectedEvent = $this->testData['tokenWebhookData']['event'];
            $this->expectWebhookEventWithContents('token.confirmed', $expectedEvent);
        }

        $gatewayEntity = $this->getLastEntity('enach', true);

        $this->fixtures->edit(
            'enach',
            $gatewayEntity['id'],
            [
                'umrn'               => 'HDFC6000000005844847',
                'acknowledge_status' => 'true',
            ]);

        return $payment;
    }

    protected function getAcknowledgeBatchFileToUpload($payment, $contentToReplace = [])
    {
        $item = [
            'MANDATE_DATE' => 'some date',
            'BATCH'        => 10,
            'IHNO'         => 6411,
            'MANDATE_TYPE' => 'NEW',
            'UMRN'         => 'HDFC6000000005393968',
            'REF_1'        => $payment->getId(),
            'REF_2'        => '',
            'CUST_NAME'    => 'customer name',
            'BANK'         => 'HDFC',
            'BRANCH'       => 'branch',
            'BANK_CODE'    => 'HDFC0000123',
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
        ];

        $item = array_merge($item, $contentToReplace);

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
                    $item
                ],
            ],
        ];

        $data = $this->getExcelString('Acknowledgment Report_15062018_Acknowledgment Report', $sheets);

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);
        $file = (new TestingFile('Acknowledgment Report_15062018_Acknowledgment Report.xlsx', $handle));

        return $file;
    }

    protected function getBatchFileToUpload($payment, $status = 'Active', $errorCode = '', $errorDesc = '')
    {
        $sheets = [
            'sheet1' => [
                'config' => [
                    'start_cell' => 'A1',
                ],
                'items'  => [
                    [
                        'random' => '1',
                    ],
                ],
            ],
            'sheet2' => [
                'config' => [
                    'start_cell' => 'A2',
                ],
                'items'  => [
                    [
                        'SRNO'            => '1',
                        'MANDATE_DATE'    => Carbon::today()->format('m/d/Y'),
                        'MANDATE_ID'      => 'NEW',
                        'UMRN'            => 'HDFC6000000005844847',
                        'CUST_REFNO'      => '',
                        'SCH_REFNO'       => '',
                        'REF_1'           => $payment->getId(),
                        'CUST_NAME'       => 'User name',
                        'BANK'            => '',
                        'BRANCH'          => '',
                        'BANK_CODE'       => 'HDFC0000123',
                        'AC_TYPE'         => 'SAVINGS',
                        'ACNO'            => '914010009305862',
                        'UPDATE_DATE'     => Carbon::now()->addDays(2)->format('m/d/Y'),
                        'AMOUNT'          => '99999',
                        'FREQUENCY'       => 'ADHO',
                        'COLLECTION_TYPE' => 'UPTO MAXIMUM',
                        'START_DATE'      => Carbon::now()->format('m/d/Y'),
                        'END_DATE'        => Carbon::now()->addYears(10)->format('m/d/Y'),
                        'TEL_NO'          => '',
                        'MOBILE_NO'       => '9999999999',
                        'MAIL_ID'         => '',
                        'UPLOAD_BATCH'    => 'ESIGN000001',
                        'UPLOAD_DATE'     => Carbon::now()->format('m/d/Y'),
                        'RESPONSE_DATE'   => Carbon::now()->addDays(2)->format('m/d/Y'),
                        'UTILITY_CODE'    => 'NACH00000000012323',
                        'UTILITY_NAME'    => 'RAZORPAY',
                        'NODAL_ACNO'      => 'RATN3234334',
                        'STATUS'          => $status,
                        'CODE_DESC'       => $errorDesc,
                        'RET_CODE'        => $errorCode,
                    ],
                ],
            ],
        ];

        $data = $this->getExcelString('Response Report-Response Report', $sheets);

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);
        $file = (new TestingFile('Response Report-Response Report.xlsx', $handle));

        return $file;
    }

    protected function makeRequestWithGivenUrlAndFile($url, $file, $type = 'register')
    {
        $request = [
            'url'     => $url,
            'method'  => 'POST',
            'content' => [
                'type'     => 'emandate',
                'sub_type' => $type,
                'gateway'  => 'enach_rbl',
            ],
            'files'   => [
                'file' => $file,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function mockPaymentRequestTimeout()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'callback')
            {
                throw new Exception\GatewayTimeoutException('Gateway timed out');
            }
        }, 'esigner_digio');
    }

    protected function mockAuthFailed()
    {
        $this->mockServerContentFunction(function(& $request, $action = null)
        {
            if ($action === 'sign')
            {
                unset($request['content']['status']);
            }
        }, 'esigner_digio');
    }
}
