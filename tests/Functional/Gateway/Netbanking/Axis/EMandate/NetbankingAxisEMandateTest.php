<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Axis\EMandate;

use Carbon\Carbon;
use Mail;
use Excel;

use RZP\Models\Payment;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\FileStore\Type;
use RZP\Models\FileStore\Format;
use RZP\Tests\Functional\TestCase;
use RZP\Excel\Export as ExcelExport;
use RZP\Error\PublicErrorDescription;
use RZP\Gateway\Netbanking\Axis\Emandate;
use RZP\Exception\GatewayTimeoutException;
use RZP\Mail\Gateway\EMandate\Base as Email;
use RZP\Excel\ExportSheet as ExcelSheetExport;
use RZP\Models\Customer\Token\RecurringStatus;
use Illuminate\Http\Testing\File as TestingFile;
use RZP\Models\Customer\Token\Entity as TokenEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\EMandate\Constants as EmailConstants;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class NetbankingAxisEMandateTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $payment;

    const ACCOUNT_NUMBER    = '914010009305862';
    const IFSC              = 'UTIB0002766';
    const NAME              = 'Test account';
    const ACCOUNT_TYPE      = 'savings';

    protected function setUp(): void
    {
        $this->gateway = 'netbanking_axis';

        $this->testDataFilePath = __DIR__.'/NetbankingAxisEMandateTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_emandate_axis_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableEmandate('10000000000000');

        $this->payment = $this->getEmandateNetbankingRecurringPaymentArray('UTIB');

        $this->payment['bank_account'] = [
                                            'account_number'    => self::ACCOUNT_NUMBER,
                                            'ifsc'              => self::IFSC,
                                            'name'              => self::NAME,
                                            'account_type'      => self::ACCOUNT_TYPE,
                                         ];

        unset($this->payment[Entity::CARD]);

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testEmandateInitialPayment()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(0, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);
    }

    public function testEmandateInitialPaymentLateAuth()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'emandateauth')
            {
                throw new GatewayTimeoutException('Gateway timed out');
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->authorizedFailedPayment($payment['id']);

        $token = $this->getLastEntity(Entity::TOKEN, true);

        $this->assertArraySelectiveEquals(
            [
                TokenEntity::RECURRING_STATUS => RecurringStatus::CONFIRMED,
                TokenEntity::METHOD           => 'emandate',
                TokenEntity::BANK             => 'UTIB',
                TokenEntity::GATEWAY_TOKEN    => '123123123',
            ],
            $token
        );

        $expiredAt = Carbon::createFromTimestamp($payment['created_at'])->addYears(10)->getTimestamp();
        $this->assertEquals($token['expired_at'], $expiredAt);

        $netbanking = $this->getLastEntity(Entity::NETBANKING, true);

        $this->assertArraySelectiveEquals(
            [
                NetbankingEntity::STATUS   => '000',
                NetbankingEntity::SI_TOKEN => '123123123',
                NetbankingEntity::BANK     => 'UTIB',
            ],
            $netbanking
        );
    }

    public function testRefundEmandateInitialPaymentWithFeeCredit()
    {
        $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 10000,
        ]);

        $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');

        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(0, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $paymentTxn = $this->getLastEntity(Entity::TRANSACTION, true);

        $this->assertEquals(0, $paymentTxn['credit']);
        $this->assertEquals(1180, $paymentTxn['fee']);
        $this->assertEquals(180, $paymentTxn['tax']);
        $this->assertEquals('payment', $paymentTxn['type']);
        $this->assertEquals('prepaid', $paymentTxn['fee_model']);
        $this->assertEquals('fee', $paymentTxn['credit_type']);

        $merchantBalance = $this->getEntityById(Entity::BALANCE, '10000000000000', true);

        $this->assertEquals(8820, $merchantBalance['fee_credits']);

        $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $refund = $this->getLastEntity(Entity::REFUND, true);

        $this->assertEquals('processed', $refund['status']);
        $this->assertNotNull($refund['processed_at']);

        $this->assertEquals(0, $payment['amount_refunded']);
        $this->assertEquals('refunded', $payment['status']);

        $refundTxn = $this->getLastEntity(Entity::TRANSACTION, true);

        $this->assertEquals(0, $refundTxn['debit']);
        $this->assertEquals(0, $refundTxn['fee']);
        $this->assertEquals(0, $refundTxn['tax']);
        $this->assertEquals('refund', $refundTxn['type']);
        $this->assertEquals('na', $refundTxn['fee_model']);
    }

    //
    // This test is to ensure that amount credit flow is not executed for
    // zero ruppee payments
    //
    public function testRefundEmandateInitialPaymentWithAmountCredit()
    {
        $credit = $this->fixtures->create('credits', [
               'type'        => 'amount',
               'value'       => 10000,
           ]);

        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(0, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $paymentTxn = $this->getLastEntity(Entity::TRANSACTION, true);

        $this->assertEquals(0, $paymentTxn['credit']);
        $this->assertEquals(1180, $paymentTxn['fee']);
        $this->assertEquals(180, $paymentTxn['tax']);
        $this->assertEquals('payment', $paymentTxn['type']);
        $this->assertEquals('prepaid', $paymentTxn['fee_model']);
        $this->assertEquals('default', $paymentTxn['credit_type']);

        $merchantBalance = $this->getEntityById(Entity::BALANCE, '10000000000000', true);

        $this->assertEquals(998820, $merchantBalance['balance']);

        $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(0, $payment['amount_refunded']);
        $this->assertEquals('refunded', $payment['status']);

        $refundTxn = $this->getLastEntity(Entity::TRANSACTION, true);

        $this->assertEquals(0, $refundTxn['debit']);
        $this->assertEquals(0, $refundTxn['fee']);
        $this->assertEquals(0, $refundTxn['tax']);
        $this->assertEquals('refund', $refundTxn['type']);
        $this->assertEquals('na', $refundTxn['fee_model']);
    }

    public function testRefundEmandateInitialPaymentWithNormalPricing()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(0, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $paymentTxn = $this->getLastEntity(Entity::TRANSACTION, true);

        $this->assertEquals(0, $paymentTxn['credit']);
        $this->assertEquals(1180, $paymentTxn['fee']);
        $this->assertEquals(180, $paymentTxn['tax']);
        $this->assertEquals('payment', $paymentTxn['type']);
        $this->assertEquals('prepaid', $paymentTxn['fee_model']);
        $this->assertEquals('default', $paymentTxn['credit_type']);

        $merchantBalance = $this->getEntityById(Entity::BALANCE, '10000000000000', true);

        $this->assertEquals(998820, $merchantBalance['balance']);

        $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(0, $payment['amount_refunded']);
        $this->assertEquals('refunded', $payment['status']);

        $refundTxn = $this->getLastEntity(Entity::TRANSACTION, true);

        $this->assertEquals(0, $refundTxn['debit']);
        $this->assertEquals(0, $refundTxn['fee']);
        $this->assertEquals(0, $refundTxn['tax']);
        $this->assertEquals('refund', $refundTxn['type']);
        $this->assertEquals('na', $refundTxn['fee_model']);
    }

    public function testEmandateDifferentPricing()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(0, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $paymentTxn = $this->getLastEntity(Entity::TRANSACTION, true);

        $this->assertEquals(0, $paymentTxn['credit']);
        $this->assertEquals(1180, $paymentTxn['fee']);
        $this->assertEquals(180, $paymentTxn['tax']);
        $this->assertEquals('payment', $paymentTxn['type']);
        $this->assertEquals('prepaid', $paymentTxn['fee_model']);
        $this->assertEquals('default', $paymentTxn['credit_type']);

        $merchantBalance = $this->getEntityById(Entity::BALANCE, '10000000000000', true);

        $this->assertEquals(998820, $merchantBalance['balance']);

        $token = $this->getLastEntity('token', true);

        $payment = $this->payment;

        $payment[Payment\Entity::TOKEN] = $token['id'];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 3000]);
        $payment['amount'] = 3000;
        $payment['order_id'] = $order->getPublicId();

        $this->doS2SRecurringPayment($payment);

        $debitPayment = $this->getLastEntity('payment', true);

        $this->assertEquals(3000, $debitPayment['amount']);
        $this->assertEquals('created', $debitPayment['status']);
    }

    public function testEmandateInitialPaymentFailure()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'emandateauth')
            {
                $content[Emandate\ResponseFields::STATUS_CODE] = Emandate\StatusCode::FAILED;
                $content[Emandate\ResponseFields::REMARKS] = 'Account Mismatch/Failed';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getDbLastEntityToArray('payment');
        $this->assertArraySelectiveEquals(
            [
                'status'              => 'failed',
                'method'              => 'emandate',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_INVALID_ACCOUNT',
                'error_description'   => "Your payment could not be completed as the bank account details you've entered are incorrect. Try again with another account.",
            ],
            $payment
        );
    }

    public function testPaymentVerify()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $response = $this->doAuthPayment($payment);

        $verify = $this->verifyPayment($response['razorpay_payment_id']);

        assert($verify['payment']['verified'] === 1);

        $verifyResponseContent = $verify['gateway']['verifyResponseContent'];

        $this->assertTestResponse($verifyResponseContent);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertEquals($gatewayPayment[NetbankingEntity::STATUS], Emandate\StatusCode::SUCCESS);

        $this->assertEquals($gatewayPayment[NetbankingEntity::RECEIVED], true);
    }

    public function testPaymentVerifyFailure()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $response = $this->doAuthPayment($payment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify_emandate')
            {
                $content[Emandate\ResponseFields::STATUS_CODE] = Emandate\StatusCode::FAILED;
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($response)
        {
            $this->verifyPayment($response['razorpay_payment_id']);
        });
    }

    public function testEmandateDebit()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $this->doAuthPayment($payment);

        $token = $this->getLastEntity('token', true);

        $payment[Payment\Entity::TOKEN] = $token['id'];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 3000]);
        $payment['amount'] = 3000;
        $payment['order_id'] = $order->getPublicId();

        $this->doS2SRecurringPayment($payment);

        $debitPayment = $this->getLastEntity('payment', true);

        // setting created at to 8am. Payments are picked from 9 to 9 cycle.
        $createdAt = Carbon::today(Timezone::IST)->addHours(8)->getTimestamp();

        $this->fixtures->edit('payment', $debitPayment['id'], ['created_at' => $createdAt]);

        $this->ba->adminAuth();

        Mail::fake();

        $content = $this->startTest();

        $this->assertEquals(1, count($content['items']));

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => Type::AXIS_EMANDATE_DEBIT,
            'entity_type' => Entity::GATEWAY_FILE,
            'entity_id'   => $content['id'],
            'extension'   => Format::CSV,
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        // Verify gateway payment entity
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'matchAuthGatewayPayment');

        $debitPaymentId = substr($debitPayment['id'], 4);

        $this->assertEquals($gatewayPayment['payment_id'], $debitPaymentId);
        $this->assertEquals($gatewayPayment['amount'], $debitPayment['amount']);

        Mail::assertQueued(Email::class, function ($mail) use ($file)
        {
            $key = Payment\Gateway::NETBANKING_AXIS . '_debit';

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

    public function testEmandateDebitReconBatch()
    {
        // TODO: Debug and Find why this test fails with new PHPSpreadSheet Library
        $this->markTestSkipped();
        $payment = $this->createInitialPayment();

        $entities = [];

        $entities[] = [
            'payment' => $this->createSecondReccuringPayment($payment),
            'status'  => 'Success'
        ];

        $entities[] = [
            'payment'       => $this->createSecondReccuringPayment($payment),
            'status'        => 'Failure',
            'return_reason' => 'Mandate does not Exist / Expired',
        ];

        $entities[] = [
            'payment' => $this->createSecondReccuringPayment($payment),
            'status'  => 'Rejected'
        ];

        $this->createAndSendDebitFile();

        $file = $this->createMockExcelFIle($entities);

        $this->makeBatchRequest(
            [
                'type'     => 'emandate',
                'sub_type' => 'debit',
                'gateway'  => 'axis',
            ],
            $file
        );

        $this->assertDebitReconEntities($entities);
    }

    protected function assertDebitReconEntities($entities)
    {
        $payment = $this->getDbEntityById('payment', $entities[0]['payment']['id']);

        $transaction = $payment->transaction;

        $this->assertEquals(Payment\Status::CAPTURED, $payment['status']);

        $this->assertEquals('3000', $payment['amount']);

        $this->assertNotNull($transaction['reconciled_at']);

        $payment = $this->getDbEntityById('payment', $entities[1]['payment']['id'])->toArray();

        $this->assertArraySelectiveEquals(
            [
                'status'              => Payment\Status::FAILED,
                'method'              => 'emandate',
                'internal_error_code' => 'BAD_REQUEST_EMANDATE_CANCELLED_INACTIVE',
                'error_description'   => PublicErrorDescription::BAD_REQUEST_EMANDATE_CANCELLED_INACTIVE,
            ],
            $payment
        );

        $payment = $this->getDbEntityById('payment', $entities[2]['payment']['id'])->toArray();

        $this->assertEquals(Payment\Status::FAILED, $payment['status']);
    }

    protected function createInitialPayment()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);

        $payment['order_id'] = $order->getPublicId();

        $payment['amount'] = 0;

        $this->doAuthPayment($payment);

        return $payment;
    }

    protected function createSecondReccuringPayment($payment)
    {
        $token = $this->getLastEntity('token', true);

        $payment[Payment\Entity::TOKEN] = $token['id'];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 3000]);

        $payment['amount'] = 3000;

        $payment['order_id'] = $order->getPublicId();

        $this->doS2SRecurringPayment($payment);

        return $this->getDbLastEntityToArray('payment');
    }

    protected function createAndSendDebitFile()
    {
        $this->ba->adminAuth();

        Mail::fake();

        $testData = $this->testData['testEmandateDebit'];

        $this->runRequestResponseFlow($testData);
    }

    protected function createMockExcelFile($entities)
    {
        $items = [];

        foreach ($entities as $entity)
        {
            if($entity['payment']['recurring_type'] === 'auto')
            {
                $items[] = [
                    'Txn Reference'           => $entity['payment']['id'],
                    'Execution Date'          => Carbon::today()->format('d-m-Y'),
                    'Originator ID'           => 'RAZORPA',
                    'Mandate Ref/UMR'         => '111118156255274',
                    'Customer Name'           => 'Random Name',
                    'Customer Bank Account'   => '914010009305862',
                    'Paid In Amount'          => 30,
                    'MIS_INFO3'               => '111118156255274',
                    'MIS_INFO4'               => '3533',
                    'File_Ref'                => 'RAZOR06082018',
                    'Status'                  => $entity['status'],
                    'Return reason'           => $entity['return_reason'] ?? 'random reason',
                    'Record Identifier'       => 'D',
                ];
            }
        }

        $sheets = [
            'sheet1' => [
                'config' => [
                    'start_cell' => 'A1',
                ],
                'items' => $items
            ]
        ];

        $data = $this->getExcelString('Axis Debit Recon Emandate', $sheets);

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);
        $file = (new TestingFile('Axis-Debit-Recon-Emandate.xls', $handle));

        return $file;
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

    protected function makeRequestWithGivenUrlAndFile($url, $file)
    {
        $request = [
            'url'     => $url,
            'method'  => 'POST',
            'content' => [],
            'files'   => [
                'file' => $file,
            ],
        ];
        return $this->makeRequestAndGetContent($request);
    }

    protected function runPaymentCallbackFlowNetbanking($response, &$callback = null, $gateway)
    {
        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);
        $data = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);
        $result = $this->submitPaymentCallbackRedirect($data);
        return $result;
    }
}
