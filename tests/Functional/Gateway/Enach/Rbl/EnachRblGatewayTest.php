<?php

namespace RZP\Tests\Functional\Gateway\Enach\Rbl;

use Mail;
use Excel;
use Closure;
use Mockery;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Customer\Token;
use RZP\Error\PublicErrorCode;
use RZP\Models\Payment\Refund;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Webhook;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Channel;
use RZP\Models\Settlement\Holidays;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Payment\Entity as Payment;
use RZP\Mail\Gateway\EMandate\Base as Email;
use Illuminate\Http\Testing\File as TestingFile;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Fixtures\Entity\TransactionTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;

class EnachRblGatewayTest extends TestCase
{
    use AttemptTrait;
    use TransactionTrait;
    use DbEntityFetchTrait;
    use AttemptReconcileTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/EnachRblGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_enach_rbl_terminal');
        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->enableEmandate();
        $this->fixtures->merchant->addFeatures([Constants::CHARGE_AT_WILL]);

        $this->gateway = 'enach_rbl';
    }

    public function testSuccessfulEsignGeneration()
    {
        $payment                 = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'utib0000123',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals('authorize', $enach['action']);
        $this->assertEquals('UTIB', $enach['bank']);
        $this->assertEquals('ratn', $enach['acquirer']);
        $this->assertEquals(0, $enach['amount']);
        $this->assertNotNull($enach['gateway_reference_id']);
        $this->assertNotNull($enach['signed_xml']);
    }

    public function testAuthenticationFailed()
    {
        $payment = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);

        $payment['bank_account'] = [
            'account_number' => '914010009305864',
            'ifsc'           => 'utib0000123',
            'name'           => 'Test account',
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

        $payment                 = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'utib0000123',
            'name'           => 'Test account',
        ];

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

        $this->assertNotNull($response['items'][0]['sent_at']);

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

        $this->runRequestResponseFlow($testData, function() use ($url, $batchFile) {
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

    public function testRegistrationReconWithSharedMerchantProxyAuth()
    {
        $payment = $this->createAcknowledgedEnachPayment(false);

        $batchFile = $this->getBatchFileToUpload($payment);

        $url = '/batches';
        $this->ba->proxyAuth('rzp_test_100000Razorpay');

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

        $this->assertNotNull($token['gateway_token']);
        $this->assertEquals('confirmed', $token['recurring_status']);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('captured', $payment['status']);

        $this->refundPayment($payment['public_id']);

        $payment = $this->getDbLastEntity('payment')->toArray();
        $refund  = $this->getDbLastEntity('refund')->toArray();

        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals(0, $refund['amount']);
        $this->assertEquals(0, $payment['amount_refunded']);
        $this->assertEquals('refunded', $payment['status']);
    }

    public function testDebitFileGeneration()
    {
        $payment                 = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'UTIB0000123',
            'name'           => 'Test account',
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
                Token\Entity::GATEWAY_TOKEN    => 'UTIB6000000005844847',
                Token\Entity::RECURRING        => 1,
                Token\Entity::RECURRING_STATUS => Token\RecurringStatus::CONFIRMED,
            ]);

        $payment             = $this->getEmandatePaymentArray('UTIB', null, 3000);
        $payment['token']    = $tokenId;
        $payment['order_id'] = $order->getPublicId();

        unset($payment['auth_type']);

        $response = $this->doS2SRecurringPayment($payment);

        $paymentId = substr($response['razorpay_payment_id'], 4);

        $this->ba->adminAuth();

        Mail::fake();

        $content = $this->startTest();
        $content = $content['items'][0];

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
                'bank'       => 'UTIB',
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

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('captured', $payment['status']);

        $enach = $this->getDbEntities('enach', ['payment_id' => $payment['id']])->first()->toArray();

        $this->assertArraySelectiveEquals(
            [
                'status' => 'PAID',
            ],
            $enach
        );
    }

    public function testDebitFileReconciliationFailure()
    {
        $payment = $this->makeDebitPayment();

        $fileStatuses = [
            'status'     => 'bounce',
            'error_code' => '1',
            'error_desc' => 'Account closed or transferred',
        ];

        $batch = $this->makeBatchDebitPayment($payment, $fileStatuses);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $payment = $this->getDbEntityById('payment', $payment['id'])->toArray();

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals('BAD_REQUEST_PAYMENT_INVALID_ACCOUNT', $payment['internal_error_code']);

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

        $payment                 = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'UTIB0000123',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->makeRequestAndCatchException(function () use ($payment)
        {
            $this->doAuthPayment($payment);
        }, \RZP\Exception\RuntimeException::class, 'Terminal should not be null');
    }

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

        $this->retryFailedRefund($refund['id']);

        $refund  = $this->getLastEntity('refund', true);

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

        $this->retryFailedRefund($refund['id']);

        $refund  = $this->getLastEntity('refund', true);

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

    protected function makeDebitPayment()
    {
        $payment                 = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'UTIB0000123',
            'name'           => 'Test account',
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
                Token\Entity::GATEWAY_TOKEN    => 'UTIB6000000005844847',
                Token\Entity::RECURRING        => 1,
                Token\Entity::RECURRING_STATUS => Token\RecurringStatus::CONFIRMED,
            ]);

        $payment             = $this->getEmandatePaymentArray('UTIB', null, $order->getAmount());
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
                'bank'       => 'UTIB',
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
                        'UMRN'               => 'UTIB6000000005844847',
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

        return $excel->string('xlsx');
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

        return $this->submitPaymentCallbackRequest($request);
    }

    protected function mockInfernoFire(Closure $closure)
    {
        $inferno = Mockery::mock(Webhook\Inferno::class, [])->makePartial();

        $inferno->shouldReceive('fire')
                ->once()
                ->with(
                    Mockery::type('RZP\Jobs\WebHook'),
                    Mockery::on($closure));

        $this->app->instance('webhook.inferno', $inferno);
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
            'bank'              => 'UTIB',
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

        $this->createWebhook(['events' => ['token.confirmed' => '1']]);

        $testData = $this->testData['tokenWebhookData'];

        if ($webhook === true)
        {
            $this->mockInfernoFire(function($data) use ($testData) {
                $data['event'] = json_decode($data['event'], true);

                $this->assertEquals('token.confirmed', $data['event']['event']);

                $this->assertArraySelectiveEquals($testData, $data);

                return true;
            });
        }

        $gatewayEntity = $this->getLastEntity('enach', true);

        $this->fixtures->edit(
            'enach',
            $gatewayEntity['id'],
            [
                'umrn'               => 'UTIB6000000005844847',
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
                        'UMRN'            => 'UTIB6000000005844847',
                        'CUST_REFNO'      => '',
                        'SCH_REFNO'       => '',
                        'REF_1'           => $payment->getId(),
                        'CUST_NAME'       => 'User name',
                        'BANK'            => '',
                        'BRANCH'          => '',
                        'BANK_CODE'       => 'UTIB0000123',
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
                        'RETURN_CODE'     => $errorCode,
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
}
