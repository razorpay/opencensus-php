<?php

namespace Functional\QrCode;

use Queue;
use Carbon\Carbon;

use RZP\Exception\LogicException;
use RZP\Mail\Payment\Authorized as AuthorizedMail;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Account;
use RZP\Models\Order;
use RZP\Error\ErrorCode;
use RZP\Jobs\QrStatusCheck;
use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use RZP\Models\QrCode\Type;
use RZP\Models\Payment\Gateway;
use RZP\Services\Mock\Reminders;
use RZP\Gateway\Upi\Icici\Fields;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\RazorxTreatment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Factory;
use RZP\Models\QrPayment\UnexpectedPaymentReason;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Status;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\QrCode\NonVirtualAccountQrCode\UsageType;
use RZP\Models\QrCode\NonVirtualAccountQrCode\CloseReason;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;
use RZP\Tests\Traits\TestsWebhookEvents;


class QrCodeStatusCheckTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use NonVirtualAccountQrCodeTrait;
    use TestsWebhookEvents;

    private $vpaTerminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NonVirtualAccountQrCodeTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['qr_codes', 'bharat_qr_v2', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'more-megastore-account']);

        $this->fixtures->merchant->activate();

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->merchant->createAccount('LiveAccountMer');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes', 'bharat_qr_v2', 'bharat_qr'], 'LiveAccountMer');
        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', 'upi');
        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal');

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal');

        $this->vpaTerminal = $this->fixtures->create('terminal:vpa_shared_terminal_icici');
    }

    /**
     * @return void
     *
     * testQrStatusCheckReminderRequest tests if a request is made to the reminders service or not after QR create.
     */
    public function testQrStatusCheckReminderRequest()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            ['gateway_merchant_id2' => 'rzp.razorpay1234@icici']
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);
    }

    /**
     * Tests how failure in sending create reminders request is handled.
     * This failure should not affect QR create.
     *
     * @return void
     */
    public function testQrStatusCheckReminderRequestFailure()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            ['gateway_merchant_id2' => 'rzp.razorpay1234@icici']
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount, true);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(0, $remindersCallCount);
    }

    /**
     * Tests that Reminder create request is not sent for static QRs.
     *
     * @return void
     */
    public function testQrStatusCheckReminderRequestForMultipleUseQr()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            ['gateway_merchant_id2' => 'rzp.razorpay1234@icici']
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(0, $remindersCallCount);
    }

    /**
     * Tests if splitz experiment is working or not.
     *
     * @return void
     */
    public function testQrStatusCheckReminderRequestWhenSplitzIsDisabled()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            ['gateway_merchant_id2' => 'rzp.razorpay1234@icici']
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck('off');

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(0, $remindersCallCount);
    }

    public function testQrStatusCheckReminderRequestWhenCreatedOnSharedQr()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            ['gateway_merchant_id2' => 'rzp.razorpay1234@icici']
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck('on', 'off');

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->assertEquals(0, $remindersCallCount);
    }

    /**
     * Tests that we are not trying status check for Bharat QRs.
     *
     * @return void
     */
    public function testQrStatusCheckReminderRequestForBharatQr()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            ['gateway_merchant_id2' => 'rzp.razorpay1234@icici']
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'type'           => 'bharat_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->assertEquals(0, $remindersCallCount);
    }

    /**
     * testReminderCallbackForQrStatusCheck tests if processing the callback from Reminders is working fine or not.
     * When we dispatch, we do not check immediately if a payment exists or not, so the response to Reminders will be
     * ['success' => false]. This means, Reminders service can send one more callback.
     *
     * @return void
     */
    public function testReminderCallbackForQrStatusCheck()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            ['gateway_merchant_id2' => 'rzp.razorpay1234@icici']
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        Queue::fake();

        $this->ba->reminderAppAuth();

        $this->startTest();

        Queue::assertPushed(QrStatusCheck::class, 1);
    }

    /**
     * Test whether the closure of a QR code is handled properly for status check.
     * The response will be ['success' => true], as we want to stop reminders once a QR code is closed.
     *
     * @return void
     */
    public function testReminderCallbackForQrStatusCheckWhenQrIsAlreadyClosed()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            ['gateway_merchant_id2' => 'rzp.razorpay1234@icici']
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        $this->closeQrCode($qrCodeId, 'live', 'LiveAccountMer');

        Queue::fake();

        $this->ba->reminderAppAuth();

        $this->startTest();

        Queue::assertPushed(QrStatusCheck::class, 0);
    }

    /**
     * Test that QR status check is not triggered if it has been more than 12 Hours since QR code creation.
     * The response will be ['success' => true], as we want to stop reminders after 12 hours have passed.
     *
     * @return void
     */
    public function testReminderCallbackForQrStatusCheckWhenItHasBeenMoreThan12Hours()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            ['gateway_merchant_id2' => 'rzp.razorpay1234@icici']
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $currentTime = Carbon::now();

        Carbon::setTestNow($currentTime);

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        Queue::fake();

        $newTime = $currentTime->addHours(13);

        Carbon::setTestNow($newTime);

        $this->ba->reminderAppAuth();

        $this->startTest();

        Queue::assertPushed(QrStatusCheck::class, 0);
    }

    /**
     * Test that QR status check is not triggered if a payment for that QR already exists.
     * The response will be ['success' => true], as we want to stop reminders once a payment is already created.
     *
     * @return void
     */
    public function testReminderCallbackForQrStatusCheckWhenAPaymentAlreadyExists()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@icici',
                'gateway_merchant_id'  => '403343',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        // Marking as multiple use so that the payment creation step (needed for test setup) does not close the QR.
        // Ideally, in such scenarios, QR status check is not supposed to work.
        // Treat this as a way to mock the way of creating an already present payment
        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'multiple_use',
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $qrCodeId = $qrCode['id'];

        $request = [
            'url'     => '/callback/upi_icici',
            'method'  => 'post',
            'content' => [
                Fields::MERCHANT_ID         => '403343',
                Fields::SUBMERCHANT_ID      => '78965412',
                Fields::TERMINAL_ID         => '5411',
                Fields::BANK_RRN            => '000011100101',
                Fields::MERCHANT_TRAN_ID    => 'RZP' . substr($qrCodeId, strlen('qr_')) . 'qrv2',
                Fields::PAYER_NAME          => 'Ria Garg',
                Fields::PAYER_VA            => 'random@icici',
                Fields::PAYER_AMOUNT        => '1.00',
                Fields::TXN_STATUS          => 'SUCCESS',
                Fields::TXN_INIT_DATE       => '20200601085714',
                Fields::TXN_COMPLETION_DATE => '20200601085715',
                Fields::RESPONSE_CODE       => '',
            ],
        ];

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment   = $this->getDbLastEntity('payment', 'live');
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(substr($qrCodeId, strlen('qr_')), $qrPayment['merchant_reference']);

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        Queue::fake();

        $this->ba->reminderAppAuth();

        $this->startTest();

        Queue::assertPushed(QrStatusCheck::class, 0);
    }

    public function testQrStatusCheckDispatchWhenDuplicateCallbacksAreReceivedAtTheSameTime()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            ['gateway_merchant_id2' => 'rzp.razorpay1234@icici']
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        Queue::fake();

        $this->ba->reminderAppAuth();

        $this->startTest();
        $this->startTest();

        // Assert that only one job was pushed.
        Queue::assertPushed(QrStatusCheck::class, 1);
    }

    // Pending state response from ICICI Status Check API
    public function testStatusCheckApiPendingResponse()
    {
        $this->config['gateway.mock_upi_mozart'] = true;
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@icici',
                'gateway_merchant_id'  => '403343',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        $this->mockServerContentFunction(function (&$content, $action = null) use ($qrCodeId) {
            if ($action === 'verify')
            {
                $content = $this->getMockedQrStatusCheckResponse("PENDING",$qrCodeId,"");
            }
        }, 'upi_mozart');

        putenv("IS_WORKER_POD=true");

        $this->ba->reminderAppAuth();

        $qrPaymentCount = count($this->getDbEntities('qr_payment', [],'live'));
        $paymentCount = count($this->getDbEntities('payment', [],'live'));
        $qrPaymentReqCount = count($this->getDbEntities('qr_payment_request', [],'live'));

        $this->startTest();

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');
        $this->assertEquals(0, $qrCodeEntity['payments_received_count']);
        $this->assertEquals($qrPaymentCount, count($this->getDbEntities('qr_payment', [],'live')));
        $this->assertEquals($paymentCount, count($this->getDbEntities('payment', [],'live')));
        $this->assertEquals($qrPaymentReqCount, count($this->getDbEntities('qr_payment_request', [],'live')));

        // To unset the env key variable
        putenv("IS_WORKER_POD");

    }

    //No payment should get created in case of Invalid response from mozart for status check api
    public function testStatusCheckApiInvalidResponse()
    {
        $this->config['gateway.mock_upi_mozart'] = true;
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@icici',
                'gateway_merchant_id'  => '403343',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $reminderDeleteCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount, false, $reminderDeleteCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        $this->mockServerContentFunction(function (&$content, $action = null) use ($qrCodeId) {
            if ($action === 'verify')
            {
                $content = [
                    "data" => []
                ];
            }
        }, 'upi_mozart');

        putenv("IS_WORKER_POD=true");

        $this->ba->reminderAppAuth();

        $qrPaymentCount = count($this->getDbEntities('qr_payment', [],'live'));
        $paymentCount = count($this->getDbEntities('payment', [],'live'));
        $qrPaymentReqCount = count($this->getDbEntities('qr_payment_request', [],'live'));

        $this->startTest();

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');
        $this->assertEquals(0, $qrCodeEntity['payments_received_count']);
        $this->assertEquals($qrPaymentCount, count($this->getDbEntities('qr_payment', [],'live')));
        $this->assertEquals($paymentCount, count($this->getDbEntities('payment', [],'live')));
        $this->assertEquals($qrPaymentReqCount, count($this->getDbEntities('qr_payment_request', [],'live')));

        $this->assertEquals(0, $reminderDeleteCallCount);

        // To unset the env key variable
        putenv("IS_WORKER_POD");

    }

    //Create payment from status check api
    public function testStatusCheckApiSuccessResponse()
    {
        $this->config['gateway.mock_upi_mozart'] = true;
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@icici',
                'gateway_merchant_id'  => '403343',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $reminderDeleteCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount, false, $reminderDeleteCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        $requestData['content']['BankRRN'] = '326414338959';
        $requestData['content']['merchantTranId'] = str_after($qrCodeId, 'qr_') . "qrv2";

        $this->mockServerContentFunction(function (&$content, $action = null) use ($qrCodeId, $requestData) {
            if ($action === 'verify')
            {
                $content = $this->getMockedQrStatusCheckResponse("SUCCESS", $qrCodeId,
                                                                 $requestData['content']['BankRRN']);
            }
        }, 'upi_mozart');

        putenv("IS_WORKER_POD=true");

        $this->ba->reminderAppAuth();

        $this->startTest();

        $this->runQrPaymentAssertions(str_after($qrCodeId, 'qr_'), $requestData, 'live');

        $this->assertEquals(1, $reminderDeleteCallCount);

        // To unset the env key variable
        putenv("IS_WORKER_POD");
    }

    //Test to verify that only 1 payment gets created even after multiple attempts for success scenerio
    // We explicitly force set the payment received count to 0 to test duplicate payment creation for this scenerio.
    public function testStatusCheckApiSuccessResponseMultipleAttempts()
    {
        $this->config['gateway.mock_upi_mozart'] = true;
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@icici',
                'gateway_merchant_id'  => '403343',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'multiple_use',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        $requestData['content']['BankRRN'] = '326414338959';
        $requestData['content']['merchantTranId'] = str_after($qrCodeId, 'qr_') . "qrv2";

        $this->mockServerContentFunction(function (&$content, $action = null) use ($qrCodeId, $requestData) {
            if ($action === 'verify')
            {
                $content = $this->getMockedQrStatusCheckResponse("SUCCESS", $qrCodeId,
                                                                 $requestData['content']['BankRRN']);
            }
        }, 'upi_mozart');

        putenv("IS_WORKER_POD=true");

        $this->ba->reminderAppAuth();

        $this->startTest();

        $this->runQrPaymentAssertions(str_after($qrCodeId, 'qr_'), $requestData, 'live');

        $this->fixtures->edit('qr_code',str_after($qrCodeId, 'qr_'),['payments_received_count'=>0],'live');

        $qrPaymentCount = count($this->getDbEntities('qr_payment', [],'live'));
        $paymentCount = count($this->getDbEntities('payment', [],'live'));
        $qrPaymentReqCount = count($this->getDbEntities('qr_payment_request', [],'live'));

        $this->startTest();

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');
        $this->assertEquals(0, $qrCodeEntity['payments_received_count']);
        $this->assertEquals($qrPaymentCount, count($this->getDbEntities('qr_payment', [],'live')));
        $this->assertEquals($paymentCount, count($this->getDbEntities('payment', [],'live')));
        $this->assertEquals($qrPaymentReqCount + 1, count($this->getDbEntities('qr_payment_request', [],'live')));

        $qrPaymentReqEntity = $this->getDbLastEntity('qr_payment_request', 'live');
        $this->assertEquals("QR_PAYMENT_DUPLICATE_NOTIFICATION", $qrPaymentReqEntity['failure_reason']);

        // To unset the env key variable
        putenv("IS_WORKER_POD");
    }

    //Test to verify that exceptions are not propogated to response from mozart for status check api
    public function testStatusCheckApiExceptionFromMozart()
    {
        $this->config['gateway.mock_upi_mozart'] = true;
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@icici',
                'gateway_merchant_id'  => '403343',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $reminderDeleteCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount, false, $reminderDeleteCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        $this->mockServerContentFunction(function (&$content, $action = null) use ($qrCodeId) {
            if ($action === 'verify')
            {
                 throw new \ErrorException("invalid response");
            }
        }, 'upi_mozart');

        putenv("IS_WORKER_POD=true");

        $this->ba->reminderAppAuth();

        $qrPaymentCount = count($this->getDbEntities('qr_payment', [],'live'));
        $paymentCount = count($this->getDbEntities('payment', [],'live'));
        $qrPaymentReqCount = count($this->getDbEntities('qr_payment_request', [],'live'));

        $this->startTest();

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');
        $this->assertEquals(0, $qrCodeEntity['payments_received_count']);
        $this->assertEquals($qrPaymentCount, count($this->getDbEntities('qr_payment', [],'live')));
        $this->assertEquals($paymentCount, count($this->getDbEntities('payment', [],'live')));
        $this->assertEquals($qrPaymentReqCount, count($this->getDbEntities('qr_payment_request', [],'live')));

        $this->assertEquals(0, $reminderDeleteCallCount);

        // To unset the env key variable
        putenv("IS_WORKER_POD");
    }

    // Failed verify status response from Yesbank Status Check API
    public function testStatusCheckApiVerifyFailedResponseYesbank()
    {
        $this->config['gateway.mock_upi_mozart'] = true;

        $this->fixtures->create('terminal:dedicated_upi_yesbank_terminal', ['vpa' => 'randomvpa@yesbank']);

        $remindersCallCount = 0;
        $reminderDeleteCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount, false, $reminderDeleteCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', []));

        $yesbGatewayMock = \Mockery::mock(\RZP\Gateway\Upi\Yesbank\Mock\Gateway::class)->makePartial();

        $gatewayMock = \Mockery::mock('RZP\Gateway\GatewayManager')->makePartial();
        $gatewayMock->shouldReceive('gateway')->andReturnUsing(function($input) use ($yesbGatewayMock) {
            if ($input === 'upi_yesbank')
            {
                return $yesbGatewayMock;
            }
        });

        $this->app->instance('gateway', $gatewayMock);

        $status = 'verify_failed';
        $yesbGatewayMock->shouldReceive('getQrPaymentStatus')
                        ->andReturnUsing(function($input) use ($status) {
                            $content['data']['status'] = $status;
                        });

        $qrCode = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ]
        );

        $newCount = count($this->getDbEntities('qr_code', []));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->assertEquals(1, $remindersCallCount);

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        putenv("IS_WORKER_POD=true");

        $this->ba->reminderAppAuth();

        $qrPaymentCount    = count($this->getDbEntities('qr_payment', []));
        $paymentCount      = count($this->getDbEntities('payment', []));
        $qrPaymentReqCount = count($this->getDbEntities('qr_payment_request', []));

        $this->startTest();

        $qrCodeEntity = $this->getLastEntity('qr_code', true);
        $this->assertEquals(0, $qrCodeEntity['payments_received_count']);
        $this->assertEquals($qrPaymentCount, count($this->getDbEntities('qr_payment', [])));
        $this->assertEquals($paymentCount, count($this->getDbEntities('payment', [])));
        $this->assertEquals($qrPaymentReqCount, count($this->getDbEntities('qr_payment_request', [])));

        $this->assertEquals(0, $reminderDeleteCallCount);

        // To unset the env key variable
        putenv("IS_WORKER_POD");

    }

    // Success verify status response from Yesbank Status Check API
    public function testStatusCheckApiVerifySuccessResponseYesbank()
    {
        $this->app['config']->set('gateway.mock_upi_yesbank', true);

        $this->fixtures->create('terminal:dedicated_upi_yesbank_terminal', ['vpa' => 'randomvpa@yesbank']);

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code'));

        $qrCode = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ]
        );
        $newCount = count($this->getDbEntities('qr_code'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->assertEquals(1, $remindersCallCount);

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        putenv("IS_WORKER_POD=true");

        $this->ba->reminderAppAuth();

        $this->startTest();

        $requestData['content']['BankRRN']        = '326836533213';
        $requestData['content']['merchantTranId'] = 'RZPY' . str_after($qrCodeId, 'qr_') . "qrv2";
        $this->runQrPaymentAssertions(str_after($qrCodeId, 'qr_'), $requestData);

        // To unset the env key variable
        putenv("IS_WORKER_POD");
    }

    // Test to verify that exceptions are not propogated to response from mozart for status check api
    public function testStatusCheckApiYesbankErrorResponse()
    {
        $this->config['gateway.mock_upi_mozart'] = true;

        $this->fixtures->create('terminal:dedicated_upi_yesbank_terminal', ['vpa' => 'randomvpa@yesbank']);

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', []));

        $yesbGatewayMock = \Mockery::mock(\RZP\Gateway\Upi\Yesbank\Mock\Gateway::class)->makePartial();

        $gatewayMock = \Mockery::mock('RZP\Gateway\GatewayManager')->makePartial();
        $gatewayMock->shouldReceive('gateway')->andReturnUsing(function($input) use ($yesbGatewayMock) {
            if ($input === 'upi_yesbank')
            {
                return $yesbGatewayMock;
            }
        });

        $this->app->instance('gateway', $gatewayMock);

        $status = 'verify_failed';
        $yesbGatewayMock->shouldReceive('getQrPaymentStatus')
                        ->andThrow(new ServerErrorException('Test error', ErrorCode::SERVER_ERROR));


        $qrCode = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ]
        );

        $newCount = count($this->getDbEntities('qr_code', []));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->assertEquals(1, $remindersCallCount);

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        putenv("IS_WORKER_POD=true");

        $this->ba->reminderAppAuth();

        $qrPaymentCount    = count($this->getDbEntities('qr_payment', []));
        $paymentCount      = count($this->getDbEntities('payment', []));
        $qrPaymentReqCount = count($this->getDbEntities('qr_payment_request', []));

        $this->startTest();

        $qrCodeEntity = $this->getLastEntity('qr_code', true);
        $this->assertEquals(0, $qrCodeEntity['payments_received_count']);
        $this->assertEquals($qrPaymentCount, count($this->getDbEntities('qr_payment', [])));
        $this->assertEquals($paymentCount, count($this->getDbEntities('payment', [])));
        $this->assertEquals($qrPaymentReqCount, count($this->getDbEntities('qr_payment_request', [])));

        // To unset the env key variable
        putenv("IS_WORKER_POD");

    }

    public function testQrStatusCheckDispatchViaFetchPaymentsApiWithoutAnyQrPayments()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@icici',
                'gateway_merchant_id'  => '403343',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $currentTime = Carbon::now();

        // Adding 4 minutes to make sure that sufficient time has passed for dispatch
        Carbon::setTestNow($currentTime);

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        Queue::fake();

        $this->testData[__FUNCTION__]['request']['url'] =
            str_replace('RandomQrCodeId', $qrCode['id'], $this->testData[__FUNCTION__]['request']['url']);

        $this->ba->privateAuth('rzp_live_LiveAccountMer');

        // Adding 4 minutes to make sure that sufficient time has passed for dispatch
        Carbon::setTestNow($currentTime->addMinutes(4));

        $this->startTest();

        Queue::assertPushed(QrStatusCheck::class, 1);
    }

    public function testQrStatusCheckDispatchViaFetchPaymentsApiWithoutAnyQrPaymentsAndExperimentOff()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@icici',
                'gateway_merchant_id'  => '403343',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $currentTime = Carbon::now();

        // Adding 4 minutes to make sure that sufficient time has passed for dispatch
        Carbon::setTestNow($currentTime);

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        Queue::fake();

        $this->testData[__FUNCTION__]['request']['url'] =
            str_replace('RandomQrCodeId', $qrCode['id'], $this->testData[__FUNCTION__]['request']['url']);

        $this->ba->privateAuth('rzp_live_LiveAccountMer');

        // Adding 4 minutes to make sure that sufficient time has passed for dispatch
        Carbon::setTestNow($currentTime->addMinutes(4));

        $this->mockSplitzTreatmentForStatusCheck('off');

        $this->startTest();

        Queue::assertPushed(QrStatusCheck::class, 0);
    }

    public function testQrStatusCheckDispatchViaFetchPaymentsApiWithoutAnyQrPaymentsAndBefore3MinutesOfCreation()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@icici',
                'gateway_merchant_id'  => '403343',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        Queue::fake();

        $this->testData[__FUNCTION__]['request']['url'] =
            str_replace('RandomQrCodeId', $qrCode['id'], $this->testData[__FUNCTION__]['request']['url']);

        $this->ba->privateAuth('rzp_live_LiveAccountMer');

        $currentTime = Carbon::now();

        // Adding 4 minutes to make sure that sufficient time has passed for dispatch
        Carbon::setTestNow($currentTime->addMinute());

        $this->startTest();

        Queue::assertPushed(QrStatusCheck::class, 0);
    }

    public function testQrStatusCheckDispatchViaFetchPaymentsApiWithQrPayments()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@icici',
                'gateway_merchant_id'  => '403343',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        // Marking as multiple use so that the payment creation step (needed for test setup) does not close the QR.
        // Ideally, in such scenarios, QR status check is not supposed to work.
        // Treat this as a way to mock the way of creating an already present payment
        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'multiple_use',
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $qrCodeId = $qrCode['id'];

        $request = [
            'url'     => '/callback/upi_icici',
            'method'  => 'post',
            'content' => [
                Fields::MERCHANT_ID         => '403343',
                Fields::SUBMERCHANT_ID      => '78965412',
                Fields::TERMINAL_ID         => '5411',
                Fields::BANK_RRN            => '000011100101',
                Fields::MERCHANT_TRAN_ID    => 'RZP' . substr($qrCodeId, strlen('qr_')) . 'qrv2',
                Fields::PAYER_NAME          => 'Ria Garg',
                Fields::PAYER_VA            => 'random@icici',
                Fields::PAYER_AMOUNT        => '1.00',
                Fields::TXN_STATUS          => 'SUCCESS',
                Fields::TXN_INIT_DATE       => '20200601085714',
                Fields::TXN_COMPLETION_DATE => '20200601085715',
                Fields::RESPONSE_CODE       => '',
            ],
        ];

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment   = $this->getDbLastEntity('payment', 'live');
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(substr($qrCodeId, strlen('qr_')), $qrPayment['merchant_reference']);

        Queue::fake();

        $this->testData[__FUNCTION__]['request']['url'] =
            str_replace('RandomQrCodeId', $qrCode['id'], $this->testData[__FUNCTION__]['request']['url']);

        $this->ba->privateAuth('rzp_live_LiveAccountMer');

        $currentTime = Carbon::now();

        // Adding 4 minutes to make sure that sufficient time has passed for dispatch
        Carbon::setTestNow($currentTime->addMinute());

        $this->startTest();

        Queue::assertPushed(QrStatusCheck::class, 0);
    }

    public function testQrStatusCheckDispatchViaFetchPaymentsApiWithoutAnyQrPaymentsWhenLockAlreadyAcquired()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@icici',
                'gateway_merchant_id'  => '403343',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $currentTime = Carbon::now();

        Carbon::setTestNow($currentTime);

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        Queue::fake();

        $this->testData[__FUNCTION__]['request']['url'] =
            str_replace('RandomQrCodeId', $qrCode['id'], $this->testData[__FUNCTION__]['request']['url']);

        $this->ba->privateAuth('rzp_live_LiveAccountMer');

        Carbon::setTestNow($currentTime->addSeconds(190));

        $this->startTest();
        Carbon::setTestNow($currentTime->addSeconds(30));
        $this->startTest();

        // Assert that only one job was pushed.
        Queue::assertPushed(QrStatusCheck::class, 1);
    }

    public function testStatusCheckApiVerifySuccessResponseForUpiMindgate()
    {
        $this->config['gateway.mock_upi_mozart'] = true;

        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::HDFC_QR_EXPIRY => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_mindgate_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@hdfcbank',
                'gateway_merchant_id'  => 'HDFC12345POS',
                'gateway_terminal_id'  => '5411',
            ]
        );
        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);
        $this->mockSplitzTreatmentForStatusCheck();
        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));

        $qrCode = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer',
            [
                'X-Razorpay-Request-Source' => 'ezetap'
            ]
        );

        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        Queue::fake();

        $this->testData[__FUNCTION__]['request']['url'] =
            str_replace('RandomQrCodeId', $qrCode['id'], $this->testData[__FUNCTION__]['request']['url']);

        $this->ba->privateAuth('rzp_live_LiveAccountMer');
        $this->startTest();
        Queue::assertPushed(QrStatusCheck::class, 1);
    }

    public function testStatusCheckApiVerifyWhenPaymentIsAlreadyExistsForUpiMindgate()
    {
        $this->config['gateway.mock_upi_mozart'] = true;
        $this->setMockRazorxTreatment(['api_upi_mindgate_pre_process_v1' => 'upi_mindgate']);


        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_mindgate_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@hdfcbank',
                'gateway_merchant_id'  => 'HDFC12345POS',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);
        $this->mockSplitzTreatmentForStatusCheck();
        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));

        $qrCode        = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true,'live');
        $response = $this->makeUpiMindgatePayment($qrCodeEntity, $terminal);

        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        $qrPayment = $this->getLastEntity('qr_payment', true,'live');
        $upi = $this->getLastEntity('upi', true,'live');
        $payment   = $this->getDbLastEntity('payment', 'live');
        $qrCodeId = $qrCode['id'];;

        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(substr($qrCodeId, strlen('qr_')), $qrPayment['merchant_reference']);
        $this->assertEquals(substr($qrCodeId, strlen('qr_')), $upi['merchant_reference']);

        Queue::fake();

        $this->testData[__FUNCTION__]['request']['url'] =
            str_replace('RandomQrCodeId', $qrCodeId, $this->testData[__FUNCTION__]['request']['url']);

        $this->ba->privateAuth('rzp_live_LiveAccountMer');

        $currentTime = Carbon::now();
        Carbon::setTestNow($currentTime->addMinutes(4));
        $this->startTest();
        Queue::assertPushed(QrStatusCheck::class, 0);

    }

    public function testQrStatusCheckDispatchViaFetchPaymentsApiWithoutAnyQrPaymentsAndBefore3MinutesOfCreationForUpiMindgateWithEzetapSource()
    {
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::HDFC_QR_EXPIRY => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );

        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_mindgate_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@hdfcbank',
                'gateway_merchant_id'  => 'HDFC12345POS',
                'gateway_terminal_id'  => '5411',
            ]
        );
        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);
        $this->mockSplitzTreatmentForStatusCheck();
        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));

        $qrCode        = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer',
            [
                'X-Razorpay-Request-Source' => 'ezetap'
            ]
        );

        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        Queue::fake();

        $this->testData[__FUNCTION__]['request']['url'] =
            str_replace('RandomQrCodeId', $qrCode['id'], $this->testData[__FUNCTION__]['request']['url']);

        $this->ba->privateAuth('rzp_live_LiveAccountMer');
        $this->startTest();
        Queue::assertPushed(QrStatusCheck::class, 1);
    }
    public function testQrStatusCheckDispatchViaFetchPaymentsApiWithoutAnyQrPaymentsAndBefore3MinutesOfCreationForUpiMindgate()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_mindgate_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@hdfcbank',
                'gateway_merchant_id'  => 'HDFC12345POS',
                'gateway_terminal_id'  => '5411',
            ]
        );
        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);
        $this->mockSplitzTreatmentForStatusCheck();
        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));

        $qrCode        = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer'
        );

        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        Queue::fake();

        $this->testData[__FUNCTION__]['request']['url'] =
            str_replace('RandomQrCodeId', $qrCode['id'], $this->testData[__FUNCTION__]['request']['url']);

        $this->ba->privateAuth('rzp_live_LiveAccountMer');
        $this->startTest();
        Queue::assertPushed(QrStatusCheck::class, 0);
    }

    public function testQrStatusCheckDispatchViaFetchPaymentsApiWithoutAnyQrPaymentsWhenLockAlreadyAcquiredForUpiMindgate()
    {
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_mindgate_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@hdfcbank',
                'gateway_merchant_id'  => 'HDFC12345POS',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $currentTime = Carbon::now();

        Carbon::setTestNow($currentTime);

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        Queue::fake();

        $this->testData[__FUNCTION__]['request']['url'] =
            str_replace('RandomQrCodeId', $qrCode['id'], $this->testData[__FUNCTION__]['request']['url']);

        $this->ba->privateAuth('rzp_live_LiveAccountMer');

        Carbon::setTestNow($currentTime->addSeconds(190));

        $this->startTest();
        Carbon::setTestNow($currentTime->addSeconds(30));
        $this->startTest();

        // Assert that only one job was pushed.
        Queue::assertPushed(QrStatusCheck::class, 1);
    }

    public function testQrStatusCheckDispatchViaFetchPaymentsApiWithoutAnyQrPaymentsWhenLockAlreadyAcquiredForUpiMindgateWithEzetapRequestSource()
    {

        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_mindgate_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@hdfcbank',
                'gateway_merchant_id'  => 'HDFC12345POS',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $currentTime = Carbon::now();

        Carbon::setTestNow($currentTime);

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 10000,
            ],
            'live',
            'LiveAccountMer',
            [
                'X-Razorpay-Request-Source' => 'ezetap'
            ]
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        Queue::fake();

        $this->testData[__FUNCTION__]['request']['url'] =
            str_replace('RandomQrCodeId', $qrCode['id'], $this->testData[__FUNCTION__]['request']['url']);

        $this->ba->privateAuth('rzp_live_LiveAccountMer');

        Carbon::setTestNow($currentTime->addSeconds(190));

        $this->startTest();
        Carbon::setTestNow($currentTime->addSeconds(30));
        $this->startTest();

        // Its pushing one time only bcz of its QrStatusCheck implements ShouldBeUnique
        Queue::assertPushed(QrStatusCheck::class, 1);
    }

    public function testStatusCheckApiSuccessResponseForUpiMindgate()
    {
        $this->config['gateway.mock_upi_mozart'] = true;
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_mindgate_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@hdfcbank',
                'gateway_merchant_id'  => 'HDFC12345POS',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $reminderDeleteCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount, false, $reminderDeleteCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));

        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        $requestData['content']['BankRRN'] = '326414338959';
        $requestData['content']['merchantTranId'] = str_after($qrCodeId, 'qr_');

        $this->mockServerContentFunction(function (&$content, $action = null) use ($qrCodeId, $requestData) {
            if ($action === 'verify')
            {
                $content = $this->getMockedUpiMindgateQrStatusCheckResponse("00", $qrCodeId,
                    $requestData['content']['BankRRN'],'HDFC12345POS','45678765','4000','ftfdtft@ybl');
            }
        }, 'upi_mozart');

        $this->ba->reminderAppAuth();

        $this->startTest();

        $this->runQrPaymentAssertions(str_after($qrCodeId, 'qr_'), $requestData, 'live');

        $this->assertEquals(1, $reminderDeleteCallCount);

    }
    public function testStatusCheckApiPendingResponseForUpiMindgate()
    {
        $this->config['gateway.mock_upi_mozart'] = true;
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_mindgate_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@hdfcbank',
                'gateway_merchant_id'  => 'HDFC12345POS',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $reminderDeleteCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount, false, $reminderDeleteCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));

        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        $requestData['content']['BankRRN'] = 'NA';
        $requestData['content']['merchantTranId'] = str_after($qrCodeId, 'qr_');

        $this->mockServerContentFunction(function (&$content, $action = null) use ($qrCodeId, $requestData) {
            if ($action === 'verify')
            {
                $content = $this->getMockedUpiMindgateQrStatusCheckResponse("01", $qrCodeId,
                    $requestData['content']['BankRRN'],'HDFC12345POS','NA','00','NA');
            }
        }, 'upi_mozart');

        $this->ba->reminderAppAuth();

        $this->startTest();

        $this->runAssertionsForUpiMindgate();

        $this->assertEquals(0, $reminderDeleteCallCount);

    }

    public function testStatusCheckApiFailedResponseForUpiMindgate()
    {
        $this->config['gateway.mock_upi_mozart'] = true;
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_mindgate_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@hdfcbank',
                'gateway_merchant_id'  => 'HDFC12345POS',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $reminderDeleteCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount, false, $reminderDeleteCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));

        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        $requestData['content']['BankRRN'] = 'NA';
        $requestData['content']['merchantTranId'] = str_after($qrCodeId, 'qr_');

        $this->mockServerContentFunction(function (&$content, $action = null) use ($qrCodeId, $requestData) {
            if ($action === 'verify')
            {
                $content = $this->getMockedUpiMindgateQrStatusCheckResponse("U30", $qrCodeId,
                    $requestData['content']['BankRRN'],'HDFC12345POS','NA','00','NA');
            }
        }, 'upi_mozart');

        $this->ba->reminderAppAuth();

        $this->startTest();

        $this->runAssertionsForUpiMindgate();
        $this->assertEquals(0, $reminderDeleteCallCount);

    }

    public function testStatusCheckApiRecordNotFoundResponseForUpiMindgate()
    {
        $this->config['gateway.mock_upi_mozart'] = true;
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_mindgate_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@hdfcbank',
                'gateway_merchant_id'  => 'HDFC12345POS',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $reminderDeleteCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount, false, $reminderDeleteCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));

        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $this->assertEquals(1, $remindersCallCount);

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        $requestData['content']['BankRRN'] = 'NA';
        $requestData['content']['merchantTranId'] = str_after($qrCodeId, 'qr_');

        $this->mockServerContentFunction(function (&$content, $action = null) use ($qrCodeId, $requestData) {
            if ($action === 'verify')
            {
                $content = $this->getMockedUpiMindgateQrStatusCheckResponse("RNF", $qrCodeId,
                    $requestData['content']['BankRRN'],'HDFC12345POS','NA','00','NA');
            }
        }, 'upi_mozart');

        $this->ba->reminderAppAuth();

        $this->startTest();

        $this->runAssertionsForUpiMindgate();

        $this->assertEquals(0, $reminderDeleteCallCount);

    }
    public function testStatusCheckApiSuccessResponseMultipleAttemptsForUpiMindgate()
    {
        $this->config['gateway.mock_upi_mozart'] = true;
        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_mindgate_terminal',
            [
                'gateway_merchant_id2' => 'rzp.razorpay1234@hdfcbank',
                'gateway_merchant_id'  => 'HDFC12345POS',
                'gateway_terminal_id'  => '5411',
            ]
        );

        $remindersCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $qrCode        = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'multiple_use',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        $requestData['content']['BankRRN'] = '326414338959';
        $requestData['content']['merchantTranId'] = str_after($qrCodeId, 'qr_');

        $this->mockServerContentFunction(function (&$content, $action = null) use ($qrCodeId, $requestData) {
            if ($action === 'verify')
            {
                $content = $this->getMockedUpiMindgateQrStatusCheckResponse("00", $qrCodeId,
                    $requestData['content']['BankRRN'],'HDFC12345POS','87654678898','4000','owiueh@axl');
            }
        }, 'upi_mozart');

        $this->ba->reminderAppAuth();

        $this->startTest();

        $this->runQrPaymentAssertions(str_after($qrCodeId, 'qr_'), $requestData, 'live');

        $this->fixtures->edit('qr_code',str_after($qrCodeId, 'qr_'),['payments_received_count'=>0],'live');

        $qrPaymentCount = count($this->getDbEntities('qr_payment', [],'live'));
        $paymentCount = count($this->getDbEntities('payment', [],'live'));
        $qrPaymentReqCount = count($this->getDbEntities('qr_payment_request', [],'live'));

        $this->startTest();

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');
        $this->assertEquals(0, $qrCodeEntity['payments_received_count']);
        $this->assertEquals($qrPaymentCount, count($this->getDbEntities('qr_payment', [],'live')));
        $this->assertEquals($paymentCount, count($this->getDbEntities('payment', [],'live')));
        $this->assertEquals($qrPaymentReqCount + 1, count($this->getDbEntities('qr_payment_request', [],'live')));

        $qrPaymentReqEntity = $this->getDbLastEntity('qr_payment_request', 'live');
        $this->assertEquals("QR_PAYMENT_DUPLICATE_NOTIFICATION", $qrPaymentReqEntity['failure_reason']);

    }

    public function runAssertionsForUpiMindgate()
    {
        $qrPayment        = $this->getDbLastEntity('qr_payment', 'live');
        $payment          = $this->getDbLastEntity('payment', 'live');
        $qrPaymentRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $upi              = $this->getDbLastEntity('upi', 'live');

        $this->assertNull($qrPayment);
        $this->assertNull($payment);
        $this->assertNull($qrPaymentRequest);
        $this->assertNull($upi);
    }

}
