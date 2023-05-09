<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Hdfc\Emandate;

use Mail;
use Excel;
use Carbon\Carbon;
use Illuminate\Http\Testing\File as TestingFile;

use RZP\Excel\Import;
use RZP\Models\Payment;
use RZP\Mail\Base\Mailable;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Method;
use RZP\Services\RazorXClient;
use RZP\Models\FileStore\Utility;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Order\Entity as Order;
use RZP\Mail\Gateway\EMandate\Base as Email;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Gateway\Netbanking\Base\Entity as Netbanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Mail\Gateway\EMandate\Constants as EmailConstants;

class NetbankingHdfcEmandateTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    const ROW_CHUNK_SIZE = 3000;

    protected $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingHdfcEmandateTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_emandate_hdfc_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableEmandate();

        $this->payment = $this->getNetbankingHdfcEmandateArray();

        $this->gateway = 'netbanking_hdfc';

        $this->mockCardVault();

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    /**
      * The following is a test case for the E Mandate Registration payment for HDFC.
      * The HDFC E Mandate Registration payment is just a normal authorization payment.
      */
    public function testEmandateInitialPayment()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($data, $payment);

        $this->assertEquals('initial', $payment['recurring_type']);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals($payment[Payment\Entity::TOKEN_ID], $token[Token\Entity::ID]);

        $this->assertEquals(Token\RecurringStatus::INITIATED, $token[Token\Entity::RECURRING_STATUS]);

        $this->assertEquals($this->payment['bank_account']['account_number'], $token[Token\Entity::ACCOUNT_NUMBER]);

        $this->assertTestResponse($token, 'matchInitiatedToken');
    }

    public function testDirectDebitFlowSuccess()
    {
        $orderInput = [
            Order::AMOUNT          => 50000,
            Order::METHOD          => Method::EMANDATE,
            Order::PAYMENT_CAPTURE => true,
        ];

        $order = $this->createOrder($orderInput);

        $this->payment[Payment\Entity::ORDER_ID] = $order[Order::ID];

        $this->payment[Payment\Entity::AMOUNT] = $orderInput[Order::AMOUNT];

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($data, $payment);

        $this->assertEquals('initial', $payment['recurring_type']);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals($payment[Payment\Entity::TOKEN_ID], $token[Token\Entity::ID]);

        $this->assertEquals(Token\RecurringStatus::INITIATED, $token[Token\Entity::RECURRING_STATUS]);

        $this->assertEquals($this->payment['bank_account']['account_number'], $token[Token\Entity::ACCOUNT_NUMBER]);

        $this->assertTestResponse($token, 'matchInitiatedToken');
    }

    public function testInitialPaymentAmountGreaterThanTokenMaxAmount()
    {
        $orderInput = [
            Order::AMOUNT          => 50000,
            Order::METHOD          => Method::EMANDATE,
            Order::PAYMENT_CAPTURE => true,
        ];

        $order = $this->createOrder($orderInput);

        $this->payment[Payment\Entity::ORDER_ID] = $order[Order::ID];

        $this->payment[Payment\Entity::AMOUNT] = $orderInput[Order::AMOUNT];

        $this->payment[Payment\Entity::RECURRING_TOKEN][Payment\Entity::MAX_AMOUNT] = 20000;

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData['testDirectDebitFlowSuccess'];

        $this->assertArraySelectiveEquals($data, $payment);

        $this->assertEquals('initial', $payment['recurring_type']);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals($payment[Payment\Entity::TOKEN_ID], $token[Token\Entity::ID]);

        $this->assertEquals(Token\RecurringStatus::INITIATED, $token[Token\Entity::RECURRING_STATUS]);

        $this->assertEquals($this->payment['bank_account']['account_number'], $token[Token\Entity::ACCOUNT_NUMBER]);

        $this->assertTestResponse($token, 'matchInitiatedToken');
    }

    public function testPaymentVerify()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $payment = $this->doAuthPayment($payment);

        $this->verifyPayment($payment['razorpay_payment_id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(1, $payment['verified']);
    }

    public function testEmandateInitialPaymentFailure()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['BankRefNo'] = '';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });

        $payment = $this->getDbLastEntity('payment')->toArrayPublic();

        $this->assertEquals(Payment\Status::FAILED, $payment['status']);
    }

    public function testEmandateRegistration()
    {
        Mail::fake();

        $this->testEmandateInitialPayment();

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);
        $date = Carbon::now(Timezone::IST)->format('dmYHis');

        $expectedFileContent = [
            'type'        => 'hdfc_emandate_register',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(Email::class, function (Mailable $mail) use ($file)
        {
            $key = Payment\Gateway::NETBANKING_HDFC . '_register';

            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $expectedSubj = EmailConstants::SUBJECT_MAP[$key] . $today;

            $this->assertEquals($expectedSubj, $mail->subject);

            $this->assertNotNull($mail->viewData['file_name']);
            $this->assertNotNull($mail->viewData['signed_url']);
            $this->assertEquals(EmailConstants::BODY_MAP[$key], $mail->viewData['body']);

            $this->assertNotEmpty($mail->attachments);

            return ($mail->hasFrom('emandate@razorpay.com') and
                ($mail->hasTo(EmailConstants::RECIPIENT_EMAILS_MAP[$key])));
        });
    }

    public function testEmandateRegistrationForLateAuth()
    {
        Mail::fake();

        $this->testEmandateInitialPayment();

        $payment = $this->getDbLastEntity('payment')->toArray();

        $this->fixtures->base->editEntity(
            'payment',
            $payment['id'],
            [
                'authorized_at' => null,
                'status'        => 'created',
            ]
        );

        $this->ba->adminAuth();

        $testData = $this->testData['testEmandateRegistrationForLateAuthFailure'];

        $this->runRequestResponseFlow($testData);

        $dayBeforeYesterday = Carbon::now()->subDays(2)->getTimestamp();

        $this->fixtures->base->editEntity(
            'payment',
            $payment['id'],
            [
                'created_at'    => $dayBeforeYesterday,
                'authorized_at' => Carbon::now()->getTimestamp(),
                'status'        => 'authorized',
            ]
        );

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $token = $this->getDbLastEntity('token')->toArray();

        $expectedFileContent = [
            'mandate_id'                   => $token['id'],
            'merchant_unique_reference_no' => $payment['id'],
        ];

        Mail::assertQueued(Email::class, function (Mailable $mail) use ($expectedFileContent)
        {
            $fileName = $mail->viewData['signed_url'];

            $fileContents = (new Import)->toArray($fileName)[0];

            // Assert that the late auth payment actually exists in the file we send
            $this->assertArraySelectiveEquals($expectedFileContent, $fileContents[0]);

            $key = Payment\Gateway::NETBANKING_HDFC . '_register';

            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $expectedSubj = EmailConstants::SUBJECT_MAP[$key] . $today;

            $this->assertEquals($expectedSubj, $mail->subject);

            $this->assertNotNull($mail->viewData['file_name']);
            $this->assertNotNull($mail->viewData['signed_url']);
            $this->assertEquals(EmailConstants::BODY_MAP[$key], $mail->viewData['body']);

            $this->assertNotEmpty($mail->attachments);

            return ($mail->hasFrom('emandate@razorpay.com') and
                ($mail->hasTo(EmailConstants::RECIPIENT_EMAILS_MAP[$key])));
        });
    }

    public function testEmandateRegistrationRecon()
    {
        Mail::fake();

        $entities = [];

        $entities[] = $this->createRegistrationInitiatedEntities();
        $entities[0]['status_in_file'] = 'success';

        $entities[] = $this->createRegistrationInitiatedEntities();
        $entities[1]['status_in_file'] = 'failure';
        $entities[1]['remark_in_file'] = 'Some reject reason';

        $file = $this->generateEmandateRegisterReconFile($entities);

        $this->makeBatchRequest(
            [
                'type'     => 'emandate',
                'sub_type' => 'register',
                'gateway'  => 'hdfc',
            ],
            $file
        );

        $this->assertRegistrationReconEntities($entities);
    }

    public function testEmandateDirectDebitRegistrationRecon()
    {
        Mail::fake();

        $orderInput = [
            Order::AMOUNT          => 50000,
            Order::METHOD          => Method::EMANDATE,
            Order::PAYMENT_CAPTURE => true,
        ];

        $order = $this->createOrder($orderInput);

        $this->payment[Payment\Entity::ORDER_ID] = $order[Order::ID];
        $this->payment[Payment\Entity::AMOUNT]   = $orderInput[Order::AMOUNT];

        $this->doAuthPayment($this->payment);

        $entities[0]['payment']        = $this->getDbLastPayment();
        $entities[0]['token']          = $this->getLastEntity('token', true);
        $entities[0]['netbanking']     = $this->getLastEntity('netbanking', true);
        $entities[0]['status_in_file'] = 'success';

        $order = $this->createOrder($orderInput);

        $this->payment[Payment\Entity::ORDER_ID] = $order[Order::ID];
        $this->payment[Payment\Entity::AMOUNT]   = $orderInput[Order::AMOUNT];

        $this->doAuthPayment($this->payment);

        $entities[1]['payment']        = $this->getDbLastPayment();
        $entities[1]['token']          = $this->getLastEntity('token', true);
        $entities[1]['netbanking']     = $this->getLastEntity('netbanking', true);
        $entities[1]['status_in_file'] = 'failure';
        $entities[1]['remark_in_file'] = 'Some reject reason';

        $file = $this->generateEmandateRegisterReconFile($entities);

        $this->assertEquals('captured', $entities[0]['payment']['status']);
        $this->assertEquals('captured', $entities[1]['payment']['status']);
        $this->assertEquals('initiated', $entities[0]['token']['recurring_status']);
        $this->assertEquals('initiated', $entities[1]['token']['recurring_status']);

        $this->makeBatchRequest(
            [
                'type'     => 'emandate',
                'sub_type' => 'register',
                'gateway'  => 'hdfc',
            ],
            $file
        );

        $entities[0]['payment']    = $this->getEntityById('payment', $entities[0]['payment']['id'], true);
        $entities[0]['token']      = $this->getEntityById('token', $entities[0]['token']['id'], true);
        $entities[0]['netbanking'] = $this->getEntityById('netbanking', $entities[0]['netbanking']['id'], true);
        $entities[1]['payment']    = $this->getEntityById('payment', $entities[1]['payment']['id'], true);
        $entities[1]['token']      = $this->getEntityById('token', $entities[1]['token']['id'], true);
        $entities[1]['netbanking'] = $this->getEntityById('netbanking', $entities[1]['netbanking']['id'], true);

        $this->assertRegistrationReconEntities($entities, 'captured');
    }

    public function testEmandateRegistrationReconInvalidStatus()
    {
        Mail::fake();

        $entities = [];

        $entities[] = $this->createRegistrationInitiatedEntities();
        $entities[0]['status_in_file'] = 'processed';

        $file = $this->generateEmandateRegisterReconFile($entities);

        $this->makeBatchRequest(
            [
                'type'     => 'emandate',
                'sub_type' => 'register',
                'gateway'  => 'hdfc',
            ],
            $file
        );

        $this->assertRegistrationReconInvalidStatusEntities($entities);
    }

    public function testEmandateRegistrationLateAuthAfterRecon()
    {
        $this->testEmandateInitialPaymentFailure();

        $entities = [
            [
                'payment'        => $this->getDbLastEntity('payment'),
                'token'          => $this->getDbLastEntity('token'),
                'netbanking'     => $this->getDbLastEntity('netbanking'),
                'status_in_file' => 'success'
            ]
        ];

        $file = $this->generateEmandateRegisterReconFile($entities);

        $this->makeBatchRequest(
            [
                'type'     => 'emandate',
                'sub_type' => 'register',
                'gateway'  => 'hdfc',
            ],
            $file
        );

        // Validate registration success entities
        $token = $this->getDbEntityById('token', $entities[0]['token']['id'])->toArray();

        $this->assertEquals(Token\RecurringStatus::CONFIRMED, $token['recurring_status']);

        $payment = $this->getDbEntityById('payment', $entities[0]['payment']['id'])->toArray();

        $this->assertEquals(Payment\Status::CAPTURED, $payment['status']);

        $netbanking = $this->getDbEntityById('netbanking', $entities[0]['netbanking']['id'])->toArray();

        $this->assertEquals('confirmed', $netbanking[Netbanking::SI_STATUS]);

        $this->assertEquals($payment['terminal_id'], $token['terminal_id']);
    }

    protected function assertRegistrationReconEntities($entities, $status = 'refunded')
    {
        // Validate registration success entities
        $token = $this->getDbEntityById('token', $entities[0]['token']['id'])->toArray();

        $this->assertEquals(Token\RecurringStatus::CONFIRMED, $token['recurring_status']);

        $this->assertNotNull($token['expired_at']);

        $payment = $this->getDbEntityById('payment', $entities[0]['payment']['id'])->toArray();

        $this->assertEquals(Payment\Status::CAPTURED, $payment['status']);

        $netbanking = $this->getDbEntityById('netbanking', $entities[0]['netbanking']['id'])->toArray();

        $this->assertEquals('confirmed', $netbanking[Netbanking::SI_STATUS]);

        // Validate registration failure entities
        $token = $this->getDbEntityById('token', $entities[1]['token']['id'])->toArray();

        $this->assertEquals(Token\RecurringStatus::REJECTED, $token['recurring_status']);

        $payment = $this->getDbEntityById('payment', $entities[1]['payment']['id'])->toArray();

        $this->assertEquals($status, $payment['status']);

        $netbanking = $this->getDbEntityById('netbanking', $entities[1]['netbanking']['id'])->toArray();

        $this->assertEquals('rejected', $netbanking[Netbanking::SI_STATUS]);
    }

    protected function assertRegistrationReconInvalidStatusEntities($entities)
    {
        $token = $this->getDbEntityById('token', $entities[0]['token']['id'])->toArray();

        // Since the status was invalid, the token recurring status should not be changed
        $this->assertEquals(Token\RecurringStatus::INITIATED, $token['recurring_status']);
    }

    public function testEmandateDebit()
    {
        $this->doDebitPayment();

        $debitPayment = $this->getLastEntity('payment', true);

        $this->ba->adminAuth();

        // setting created at to 8am. Payments are picked from 9 to 9 cycle.
        $createdAt = Carbon::today(Timezone::IST)->addHours(8)->getTimestamp();

        $this->fixtures->edit(
            'payment',
            $debitPayment[Payment\Entity::ID],
            [
                Payment\Entity::CREATED_AT => $createdAt,
            ]);

        Mail::fake();

        $content = $this->startTest();

        // Verify response
        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        // Verify file_store entity
        $file = $this->getLastEntity('file_store', true);
        $date = Carbon::now(Timezone::IST)->format('dmYHis');

        $expectedFileContent = [
            'type'        => 'hdfc_emandate_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        // Verify gateway payment entity
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'matchAuthGatewayPayment');

        $debitPaymentId = substr($debitPayment['id'], 4);

        $this->assertEquals($gatewayPayment['payment_id'], $debitPaymentId);
        $this->assertEquals($gatewayPayment['amount'], $debitPayment['amount']);

        // Verify email
        Mail::assertQueued(Email::class, function (Mailable $mail) use ($file)
        {
            $key = Payment\Gateway::NETBANKING_HDFC . '_debit';

            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $expectedSubj = EmailConstants::SUBJECT_MAP[$key] . $today;

            $this->assertEquals($expectedSubj, $mail->subject);

            $this->assertNotNull($mail->viewData['file_name']);
            $this->assertNotNull($mail->viewData['signed_url']);
            $this->assertEquals(EmailConstants::BODY_MAP[$key], $mail->viewData['body']);

            $this->assertNotEmpty($mail->attachments);

            return ($mail->hasFrom('emandate@razorpay.com') and
                    ($mail->hasTo(EmailConstants::RECIPIENT_EMAILS_MAP[$key])));
        });
    }

    public function testEmandateDebitSucessRecon()
    {
        $registrationEntities = $this->createRegistrationConfirmedEntities();

        $entities[] = $this->createDebitInitiatedEntities($registrationEntities);
        $entities[0]['status_in_file'] = 'success';

        $file = $this->generateEmandateDebitReconFile($entities);

        $this->mockRazorxTreatment('on');

        $batch = $this->makeBatchRequest(
            [
                'type'     => 'emandate',
                'sub_type' => 'debit',
                'gateway'  => 'hdfc',
            ],
            $file
        );

        $this->assertEquals('emandate', $batch['batch_type_id']);
        $this->assertEquals('CREATED', $batch['status']);

        $entries1 = $this->createBatchRequestData($entities[0], "emandate", "debit", "hdfc", 1);

        $this->runWithData($entries1, $batch['id']);

        // Validate debit success entities
        $payment = $this->getDbEntityById('payment', $entities[0]['payment']['id']);

        $transaction = $payment->transaction;

        $payment = $payment->toArray();

        $this->assertEquals(Payment\Status::CAPTURED, $payment['status']);

        $this->assertNotNull($transaction['reconciled_at']);

        $netbanking = $this->getDbEntityById('netbanking', $entities[0]['netbanking']['id'])->toArray();

        $this->assertEquals('success', $netbanking[Netbanking::STATUS]);
    }

    public function testEmandateDebitFailureRecon()
    {
        $registrationEntities = $this->createRegistrationConfirmedEntities();

        $entities[] = $this->createDebitInitiatedEntities($registrationEntities);
        $entities[0]['status_in_file'] = 'failure';

        $file = $this->generateEmandateDebitReconFile($entities);

        $this->mockRazorxTreatment('on');

        $batch = $this->makeBatchRequest(
            [
                'type'     => 'emandate',
                'sub_type' => 'debit',
                'gateway'  => 'hdfc',
            ],
            $file
        );

        $this->assertEquals('emandate', $batch['batch_type_id']);
        $this->assertEquals('CREATED', $batch['status']);

        $entries = $this->createBatchRequestData($entities[0], "emandate", "debit", "hdfc", 1);

        $this->runWithData($entries, $batch['id']);

        // Validate registration failure entities
        $payment = $this->getDbEntityById('payment', $entities[0]['payment']['id'])->toArray();

        $this->assertEquals(Payment\Status::FAILED, $payment['status']);

        $netbanking = $this->getDbEntityById('netbanking', $entities[0]['netbanking']['id'])->toArray();

        $this->assertEquals('failure', $netbanking[Netbanking::STATUS]);
    }

    public function testEmandateDebitRejectedRecon()
    {
        $registrationEntities = $this->createRegistrationConfirmedEntities();

        $entities[] = $this->createDebitInitiatedEntities($registrationEntities);
        $entities[0]['status_in_file'] = 'rejected';

        $file = $this->generateEmandateDebitReconFile($entities);

        $this->mockRazorxTreatment('on');

        $batch = $this->makeBatchRequest(
            [
                'type'     => 'emandate',
                'sub_type' => 'debit',
                'gateway'  => 'hdfc',
            ],
            $file
        );

        $this->assertEquals('emandate', $batch['batch_type_id']);
        $this->assertEquals('CREATED', $batch['status']);

        $entries = $this->createBatchRequestData($entities[0], "emandate", "debit", "hdfc", 1);

        $this->runWithData($entries, $batch['id']);

        // Validate registration failure entities
        $payment = $this->getDbEntityById('payment', $entities[0]['payment']['id'])->toArray();

        $this->assertEquals(Payment\Status::FAILED, $payment['status']);

        $netbanking = $this->getDbEntityById('netbanking', $entities[0]['netbanking']['id'])->toArray();

        $this->assertEquals('rejected', $netbanking[Netbanking::STATUS]);
    }

    public function testSecondRecurringPaymentVerify()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment\Entity::TOKEN_ID];

        $this->fixtures->edit(
            'token',
            $tokenId,
            [
                Token\Entity::RECURRING => 1,
                Token\Entity::RECURRING_STATUS => Token\RecurringStatus::CONFIRMED
            ]);

        $payment[Payment\Entity::TOKEN] = $tokenId;
        $payment['amount'] = 3000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        // Second recurring payment request
        $this->doS2SRecurringPayment($payment);

        $secondPayment = $this->getLastEntity('payment', true);

        $this->assertEquals('auto', $secondPayment['recurring_type']);

        $secondPaymentId = substr($secondPayment['id'], 4);

        $this->fixtures->create('netbanking',
            [
                Netbanking::PAYMENT_ID          => $secondPaymentId,
                Netbanking::BANK                => $secondPayment['bank'],
                Netbanking::AMOUNT              => $secondPayment['amount'],
                Netbanking::CAPS_PAYMENT_ID     => strtoupper($secondPaymentId),
            ]);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($secondPayment)
            {
                $this->verifyPayment($secondPayment['id']);
            });

        $secondPayment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Verify\Status::UNKNOWN, $secondPayment['verified']);
    }

    /**
     * It tests that scenario in which a debit file is requested to be sent for
     * a payment for which a netbanking entity has already been created.
     * The expectation is that it will skip the creation of netbanking entity
     * and continue with writing to file, and then sending it.
     */
    public function testEmandateDebitOnRetry()
    {
        $this->doDebitPayment();

        $debitPayment = $this->getLastEntity('payment', true);

        $debitPaymentId = substr($debitPayment['id'], 4);

        // setting created at to 8am. Payments are picked from 9 to 9 cycle.
        $createdAt = Carbon::today(Timezone::IST)->addHours(8)->getTimestamp();

        $this->fixtures->edit(
            'payment',
            $debitPaymentId,
            [
                Payment\Entity::CREATED_AT => $createdAt,
            ]);

        $this->ba->adminAuth();

        // Email send will throw exception, but file, and gateway-entity will still be created
        Mail::shouldReceive('send')->andThrow(new \Exception('mail_send_exceptiopn'));

        $testData = $this->testData['testEmandateDebitCreateFileFailure'];

        $content = $this->startTest($testData);

        // Verify response after file-create fails
        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);

        $gatewayPayment = $this->getLastEntity('netbanking', true);
        $this->assertEquals($gatewayPayment['payment_id'], $debitPaymentId);

        // Verify file_store entity
        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_emandate_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        // Retry
        Mail::fake();

        // Email send should succeed
        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        // Verify gateway payment entity
        $gatewayPaymentLast = $this->getLastEntity('netbanking', true);

        $this->assertEquals($gatewayPaymentLast['id'], $gatewayPayment['id']);

        // Verify email
        Mail::assertQueued(Email::class, function (Mailable $mail) use ($file)
        {
            $key = Payment\Gateway::NETBANKING_HDFC . '_debit';

            $this->assertNotNull($mail->viewData['file_name']);
            $this->assertNotNull($mail->viewData['signed_url']);

            $this->assertNotEmpty($mail->attachments);

            return true;
        });
    }

    public function testRefundDebitPayment()
    {
        Mail::fake();

        $this->doDebitPayment();

        $debitPayment = $this->getLastEntity('payment', true);

        $this->authorizeEmandateFileBasedDebitPayment($debitPayment);

        $this->capturePayment($debitPayment['id'], $debitPayment['amount']);

        $this->refundPayment($debitPayment['id'], $debitPayment['amount'], ['is_fta' => true]);

        $debitPayment = $this->getLastEntity('payment', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($debitPayment['id'], $refund['payment_id']);
        $this->assertEquals($debitPayment['amount_refunded'], $refund['amount']);
        $this->assertEquals($debitPayment['amount'], $refund['amount']);

        $this->assertEquals('processed', $refund['status']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], $refund['id']);

        $this->assertEquals('Test Merchant Refund ' . substr($debitPayment['id'], 4), $fundTransferAttempt['narration']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals('HDFC0000186', $bankAccount['ifsc_code']);

        $this->assertEquals('test', $bankAccount['beneficiary_name']);

        $this->assertEquals('0123456789', $bankAccount['account_number']);

        $this->assertEquals('refund', $bankAccount['type']);
    }

    protected function doDebitPayment(): array
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment\Entity::TOKEN_ID];

        $this->fixtures->edit(
            'token',
            $tokenId,
            [
                Token\Entity::RECURRING => 1,
                Token\Entity::RECURRING_STATUS => Token\RecurringStatus::CONFIRMED
            ]);

        $payment[Payment\Entity::TOKEN] = $tokenId;

        $payment['amount'] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        // Second recurring payment request
        $content = $this->doS2SRecurringPayment($payment);

        return $content;
    }

    protected function getNetbankingHdfcEmandateArray(): array
    {
        $payment = $this->getEmandateNetbankingRecurringPaymentArray('HDFC');

        $payment['bank_account'] = [
            'account_number'    => '0123456789',
            'ifsc'              => 'HDFC0000186',
            'name'              => 'Test Account',
            'account_type'      => 'savings',
        ];

        return $payment;
    }

    protected function createRegistrationInitiatedEntities()
    {
        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);

        $token = $this->fixtures->create(
            'token:emandate_registration_initiated',
            [
                'terminal_id'    => 'NHdRecurringTl',
                'bank'           => 'HDFC',
                'ifsc'           => 'HDFC0000186',
                'account_number' => '50100100708641',
                'expired_at'     => '1608278260',
            ]
        );

        $payment = $this->fixtures->create(
            'payment:emandate_registration_initial',
            [
                'bank'        => 'HDFC',
                'order_id'    => $order['id'],
                'terminal_id' => 'NHdRecurringTl',
                'token_id'    => $token['id'],
                'gateway'     => 'netbanking_hdfc',
            ]
        );

        $netbanking = $this->fixtures->create(
            'netbanking',
            [
                'payment_id'      => $payment['id'],
                'action'          => 'authorize',
                'amount'          => '1',
                'bank'            => 'HDFC',
                'received'        => true,
                'bank_payment_id' => '938361',
                'caps_payment_id' => strtoupper($payment['id']),
            ]
        );

        return [
            'token'      => $token,
            'payment'    => $payment,
            'netbanking' => $netbanking,
        ];
    }

    protected function generateEmandateRegisterReconFile(array $entities)
    {
        $items = [];

        foreach ($entities as $entityList)
        {
            $amount = ($entityList['payment']['amount'] === 0) ? '1' : $entityList['payment']['amount'];

            $items[] = [
                'Client Name'                  => 'RAZORPAY',
                'Sub-merchant Name'            => 'ABC',
                'Customer Name'                => 'User Name',
                'Customer Account Number'      => '50100100708641',
                'Amount'                       => $amount,
                'Amount Type'                  => 'Maximum',
                'Start_Date'                   => '07/05/2018',
                'End_Date'                     => '07/05/2028',
                'Frequency'                    => 'As & when Presented',
                'Mandate ID'                   => $entityList['token']['id'],
                'Merchant Unique Reference No' => $entityList['payment']['id'],
                'Mandate Serial Number'        => $entityList['token']['id'],
                'Merchant Request No'          => $entityList['payment']['id'],
                'STATUS'                       => $entityList['status_in_file'],
                'REMARK'                       => '',
            ];
        }

        $content = [
            'sheet1' => [
                'config' => [
                    'start_cell' => 'A1',
                ],
                'items' => $items
            ]
        ];

        $data = $this->getExcelString('HDFC_Emandate_Registration', $content);

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);
        return (new TestingFile('HDFC_Emandate_Registration.xlsx', $handle));
    }

    protected function generateEmandateDebitReconFile(array $entities)
    {
        $items    = [];
        $serialNo = 0;

        foreach ($entities as $entityList)
        {
            $items[] = [
                'Sr'                 => $serialNo++,
                'Transaction_Ref_No' => $entityList['payment']['id'],
                'Sub-merchant Name'  => 'ABC',
                'Mandate ID'         => $entityList['token']['id'],
                'Account_NO'         => $entityList['token']['account_number'],
                'Amount'             => ($entityList['payment']['amount'] / 100),
                'SIP_Date'           => '09/05/2018',
                'Frequency'          => 'As & when Presented',
                'FROM_DATE'          => '09/05/2018',
                'TO_DATE'            => '31/12/2099',
                'Status'             => $entityList['status_in_file'],
                'Remark'             => '',
                'Narration'          => '',
            ];
        }

        $content = [
            'sheet1' => [
                'config' => [
                    'start_cell' => 'A1',
                ],
                'items' => $items
            ]
        ];

        $data = $this->getExcelString('HDFC_Emandate_Debit', $content);

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);
        return (new TestingFile('HDFC_Emandate_Debit.xlsx', $handle));
    }

    protected function makeBatchRequest($content, $file)
    {
        $request = [
            'url' => '/admin/batches',
            'method' => 'POST',
            'content' => $content,
            'files' => [
                'file' => $file,
            ]
        ];

        $this->ba->adminAuth();

        return $this->makeRequestAndGetContent($request);
    }

    /**
     * We do not need to create order or netbanking entities , since those
     * does not matter once the token registration is confirmed
     *
     * @return array
     */
    protected function createRegistrationConfirmedEntities(): array
    {
        $token = $this->fixtures->create(
            'token:emandate_registration_confirmed',
            [
                'terminal_id'    => 'NHdRecurringTl',
                'bank'           => 'HDFC',
                'ifsc'           => 'HDFC0000186',
                'account_number' => '50100100708641',
            ]
        );

        $payment = $this->fixtures->create(
            'payment:emandate_registration_confirmed',
            [
                'bank'        => 'HDFC',
                'terminal_id' => 'NHdRecurringTl',
                'token_id'    => $token['id'],
                'gateway'     => 'netbanking_hdfc',
            ]
        );

        return [
            'token'   => $token,
            'payment' => $payment,
        ];
    }

    /**
     * We create the payment and netbanking entities
     * based on the token value we've got in param
     *
     * @param $entities - Fill the current payment's token entity from here
     * @return array
     */
    protected function createDebitInitiatedEntities($entities): array
    {
        $order = $this->fixtures->create('order:emandate_order', ['amount' => 4000]);

        $payment = $this->fixtures->create(
            'payment:emandate_debit',
            [
                'amount'      => 4000,
                'status'      => 'created',
                'bank'        => 'HDFC',
                'terminal_id' => 'NHdRecurringTl',
                'token_id'    => $entities['token']['id'],
                'gateway'     => 'netbanking_hdfc',
                'order_id'    => $order['id'],
            ]
        );

        $netbanking = $this->fixtures->create(
            'netbanking',
            [
                'payment_id'      => $payment['id'],
                'action'          => 'authorize',
                'amount'          => 4000,
                'bank'            => 'HDFC',
                'received'        => false,
                'caps_payment_id' => strtoupper($payment['id']),
            ]
        );

        return [
            'payment'    => $payment,
            'netbanking' => $netbanking,
            'token'      => $entities['token'],
        ];
    }

    public function runWithData($entries, $batchId)
    {
        $this->ba->batchAppAuth();

        $testData = $this->testData['process_via_batch_service'];

        $testData['request']['server']['HTTP_X_Batch_Id'] = $batchId;

        $testData['request']['content'] = $entries;

        $this->runRequestResponseFlow($testData);
    }

    public function createBatchRequestData($entityList, $type, $subType, $gateway, $count): array
    {

        $item = [
            'Sr'                 => $count,
            'Transaction_Ref_No' => $entityList['payment']['id'],
            'Sub-merchant Name'  => 'ABC',
            'Mandate ID'         => $entityList['token']['id'],
            'Account_NO'         => $entityList['token']['account_number'],
            'Amount'             => ($entityList['payment']['amount'] / 100),
            'SIP_Date'           => '09/05/2018',
            'Frequency'          => 'As & when Presented',
            'FROM_DATE'          => '09/05/2018',
            'TO_DATE'            => '31/12/2099',
            'Status'             => $entityList['status_in_file'],
            'Remark'             => '',
            'Narration'          => '',
        ];

        return [
            'data'        => $item,
            'type'        => $type,
            'sub_type'    => $subType,
            'gateway'     => $gateway,
        ];
    }

    protected function mockRazorxTreatment(string $returnValue = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn($returnValue);
    }

    protected function runPaymentCallbackFlowNetbanking($response, &$callback = null, $gateway)
    {
        if (strpos($response->getContent(), 'Mandate') != null)
        {
            list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);
            $data = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);
            return $this->submitPaymentCallbackRedirect($data);
        }

        return $this->runPaymentCallbackFlowForNbplusGateway($response, $gateway, $callback);
    }
}
