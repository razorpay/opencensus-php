<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Hdfc\Emandate;

use Mail;
use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Customer\Token;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\EMandate\Base as Email;
use Illuminate\Http\Testing\File as TestingFile;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Gateway\Netbanking\Base\Entity as Netbanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Mail\Gateway\EMandate\Constants as EmailConstants;

class NetbankingHdfcEmandateTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;
//    use BatchTestTrait;
    use DbEntityFetchTrait;

    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingHdfcEmandateTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_emandate_hdfc_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableEmandate();

        $this->payment = $this->getNetbankingHdfcEmandateArray();

        $this->gateway = 'netbanking_hdfc';

        $this->mockTokenex();
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

        $expectedFileContent = [
            'type'        => 'hdfc_emandate_register',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(Email::class, function ($mail) use ($file)
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

    public function testEmandateRegistrationRecon()
    {
        Mail::fake();

        $entities = [];

        $entities[] = $this->createRegistrationInitiatedEntities();
        $entities[0]['status_in_file'] = 'success';

        $entities[] = $this->createRegistrationInitiatedEntities();
        $entities[1]['status_in_file'] = 'reject';

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

        // Validate registration failure entities
        $token = $this->getDbEntityById('token', $entities[1]['token']['id'])->toArray();

        $this->assertEquals(Token\RecurringStatus::REJECTED, $token['recurring_status']);

        $payment = $this->getDbEntityById('payment', $entities[1]['payment']['id'])->toArray();

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment['status']);

        $netbanking = $this->getDbEntityById('netbanking', $entities[1]['netbanking']['id'])->toArray();

        $this->assertEquals('rejected', $netbanking[Netbanking::SI_STATUS]);
    }

    public function testEmandateDebit()
    {
        $this->doDebitPayment();

        $debitPayment = $this->getLastEntity('payment', true);

        $this->ba->adminAuth();

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
        Mail::assertQueued(Email::class, function ($mail) use ($file)
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

        $this->ba->appAuth();

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
        Mail::assertQueued(Email::class, function ($mail) use ($file)
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
        $this->doDebitPayment();

        $debitPayment = $this->getLastEntity('payment', true);

        $this->authorizeEmandateFileBasedDebitPayment($debitPayment);

        $this->capturePayment($debitPayment['id'], $debitPayment['amount']);

        $this->refundPayment($debitPayment['id']);

        $debitPayment = $this->getLastEntity('payment', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($debitPayment['id'], $refund['payment_id']);
        $this->assertEquals($debitPayment['amount_refunded'], $refund['amount']);
        $this->assertEquals($debitPayment['amount'], $refund['amount']);

        $this->assertEquals(Payment\Status::REFUNDED, $debitPayment['status']);
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
            'name'              => 'Test Account'
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
            $items[] = [
                'Client Name'             => 'RAZORPAY',
                'Customer Name'           => 'User Name',
                'Customer Account Number' => '50100100708641',
                'Amount'                  => '1.00',
                'Amount Type'             => 'Maximum',
                'Start_Date'              => '07/05/2018',
                'End_Date'                => '07/05/2028',
                'Frequency'               => 'As & when Presented',
                'Mandate ID'              => $entityList['token']['id'],
                'Status'                  => $entityList['status_in_file'],
                'Remark'                  => '',
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

    protected function makeBatchRequest($content, $file)
    {
        $request = [
            'url' => '/batches',
            'method' => 'POST',
            'content' => $content,
            'files' => [
                'file' => $file,
            ]
        ];

        $this->ba->proxyAuth('rzp_test_100000Razorpay');

        return $this->makeRequestAndGetContent($request);
    }
}
