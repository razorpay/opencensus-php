<?php

namespace RZP\Tests\Functional\CardMandate;

use Mockery;

use Queue;
use RZP\Constants\Entity;
use RZP\Constants\Entity as E;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Network;
use RZP\Models\Currency\Currency;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\CardMandate\Status;
use RZP\Exception\BadRequestException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;

class CardMandateTest extends TestCase
{
    use WebhookTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;
    use TerminalTrait;

    /**
     * @var array
     */
    protected $paymentInput;

    protected $mandateHQ;

    protected $mandateConfirm;

    protected $smartRoutingService;

    protected $sharedSharpTerminal;

    protected $mandateHqTerminal;

    protected $rupaySiHubTerminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/CardMandateTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->addFeatures(['recurring_card_mandate']);

        $this->sharedSharpTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');

        $this->fixtures->create('iin', [
            'iin' => '400018',
            'type' => 'credit',
            'recurring' => 1,
            'issuer' => IFSC::RATN,
            'mandate_hubs' => ['mandate_hq'=>'1', 'billdesk_sihub'=>'1', 'rupay_sihub'=>'1'],

        ]);

        $this->paymentInput = $this->getDefaultRecurringPaymentArray();

        $this->paymentInput['bank'] = IFSC::RATN;

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);

        $this->paymentInput['card']['number'] = '4000184186218826';

        $this->paymentInput['order_id'] = $order->getPublicId();

        $this->mandateHQ = Mockery::mock('RZP\Services\MandateHQ', [$this->app]);

        $this->app->instance('mandateHQ', $this->mandateHQ);

        $this->mandateConfirm = 'true';

        $this->mockShouldSkipSummaryPage(false);
    }

    public function testCreateCardMandatePayment()
    {
        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['razorpay_payment_id'] ?? null);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());

        $token = $payment->localToken;
        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());
        $this->assertTrue($token->hasCardMandate());

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertNotEmpty($cardMandate->getMandateSummaryUrl());
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandate->getMandateId());
    }

    public function testCreateCardMandatePaymentWithTokenisationSuccess()
    {
        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $this->mockCardVaultWithMigrateToken();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $this->setMockRazorxTreatment(['recurring_tokenisation_unhappy_flow_handling' => 'on',
                                       'recurring_tokenisation' => 'on']);

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $paymentInp = $this->paymentInput;
        $paymentInp['_']['library'] = 'razorpayjs';
        $paymentInp['save'] = 1;

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $paymentInp,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['razorpay_payment_id'] ?? null);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());

        $token = $payment->localToken;
        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());
        $this->assertTrue($token->hasCardMandate());

        $card = $payment->localToken->card;
        $this->assertEquals('visa', $card->getVault());

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertNotEmpty($cardMandate->getMandateSummaryUrl());
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandate->getMandateId());
    }

    public function testCreateCardMandatePaymentWithSkipSummaryPage()
    {
        $this->mockCheckBin();
        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $this->fixtures->merchant->addFeatures(['card_mandate_skip_page']);

        $input = $this->paymentInput;
        $input['order_id'] = $this->fixtures->create('order', [
            'amount' => 50000,
        ])->getPublicId();

        $this->doAuthAndCapturePayment($input);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());

        $token = $payment->localToken;
        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());

        $this->assertNotEmpty($cardMandate);
        $this->assertNotEmpty($cardMandate->getMandateSummaryUrl());
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandate->getMandateId());
    }

    public function testCreateSplitAuthenticatePayment($frequency = 'as_presented')
    {
        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $this->razorxValue = 'cardps';
        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
                'recurring_3ds' => '1',
                'recurring_non_3ds' => '1',
            ]
        ]);
        $this->fixtures->merchant->addFeatures(['auth_split']);
        $this->mockCps($terminal, 'callback_split');

        $paymentArray = $this->getDefaultPaymentArray();
        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment::CARD_PAYMENT_SERVICE, $payment['cps_route']);
        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authenticated', $payment['status']);
        $this->assertEquals(2, $payment['cps_route']);

        $this->mockCardVault();

        $payment = $this->getLastEntity('payment', true);

        $this->ba->expressAuth();

        $currentTime = Carbon::now();

        $debitType = ($frequency === 'as_presented') ? 'variable_amount' : 'fixed_amount';

        $request = [
            "url" => "/payments/" . $payment['id'] . "/authorize",
            "method" => "post",
            "content" => [
                "meta" => [
                    "action_type"  => "capture",
                    "reference_id" => $payment['id']
                ],
                'recurring_token' => [
                    'expire_by' => $currentTime->addYear()->timestamp,
                    'frequency' => $frequency,
                    'max_amount' => '4000000',
                    'debit_type' => $debitType,
                    'notes'      => [
                        'key1' => 'value1',
                        'key2' => 'value2',
                    ]
                ]
            ],
        ];

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('captured', $payment->getStatus());

        $token = $payment->localToken;
        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertNotEmpty($cardMandate->getMandateSummaryUrl());
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandate->getMandateId());
        $this->assertEquals(4000000, $cardMandate->max_amount);
        $this->assertEquals($debitType, $cardMandate->debit_type);
        $this->assertEquals(50000, $cardMandate->amount);
        $this->assertEquals($request['content']['recurring_token']['expire_by'], $cardMandate->end_at);
        $this->assertEquals($frequency, $cardMandate->frequency);
        $this->assertEquals('mandate_hq', $cardMandate->mandate_hub);
    }

    public function testPreDebitNotify()
    {
        $this->testCreateSplitAuthenticatePayment();

        $token = $this->getDbLastEntity('token');

        $request = [
            "url" => "/tokens/" . $token->getPublicId() . "/pre_debit/notify",
            "method" => "post",
            "content" => [
                'debit_at' => Carbon::now()->addDays(2)->timestamp,
                'amount'   => 50000,
                'currency' => 'INR',
                'purpose'  => 'test debit',
                'notes' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ]
            ],
        ];

        $this->ba->privateAuth();

        $this->mockCreatePreDebitNotification();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('notified', $response['status']);
        $this->assertNotEmpty($response['id']);

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');

        $this->assertNull($cardMandateNotification->reminder_id);
    }

    public function testCreateCardMandateAutoPaymentWithNotificationId()
    {
        $this->markTestSkipped('until fixed');

        $this->testPreDebitNotify();

        $payment = $this->getDbLastEntity('payment');

        $tokenId = $payment->localToken->getPublicId();

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');

        $request = [
            "url" => "/payments/tokens/charge",
            "method" => "post",
            "content" => [
                'amount'   => 50000,
                'currency' => 'INR',
                'description'  => 'test debit',
                'token' => $tokenId,
                'notes' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ],
                'recurring_token' => [
                    'notification_id' => $cardMandateNotification->getPublicId(),
                ],
            ],
        ];

        $this->ba->privateAuth();

        $this->mockValidatePayment();

        $this->makeRequestAndGetContent($request);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment::CARD_PAYMENT_SERVICE, $payment['cps_route']);
        $this->assertEquals('authenticated', $payment['status']);
        $this->assertEquals(2, $payment['cps_route']);

        $this->mockCardVault();

        $payment = $this->getLastEntity('payment', true);

        $this->ba->expressAuth();

        $request = [
            "url" => "/payments/" . $payment['id'] . "/authorize",
            "method" => "post",
            "content" => [
                "meta" => [
                    "action_type"  => "capture",
                    "reference_id" => $payment['id']
                ],
            ],
        ];

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals($cardMandateNotification->getId(), $payment->cardMandateNotification->getId());

        $token = $payment->localToken;
        $this->assertEquals($token->getPublicId(), $tokenId);
    }

    public function testCreateCardMandateAutoPaymentWithNotificationIdCustomerNotApproved()
    {
        $this->testPreDebitNotify();

        $payment = $this->getDbLastEntity('payment');

        $tokenId = $payment->localToken->getPublicId();

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);
        unset($paymentInput[Payment::CUSTOMER_ID]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $paymentInput[Payment::AMOUNT] = 50000;

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');

        $cardMandateNotification->afa_required = 1;
        $cardMandateNotification->afa_status = 'rejected';

        $cardMandateNotification->saveOrFail();

        $paymentInput[Payment::RECURRING_TOKEN][Payment::NOTIFICATION_ID] = $cardMandateNotification->getPublicId();

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();

        $this->ba->privateAuth();

        $this->makeRequestAndCatchException(function () use ($paymentInput) {
            $this->doS2SRecurringPayment($paymentInput);
        }, \RZP\Exception\BadRequestException::class, 'customer not approved the debit');
    }

    public function testCreateCardMandateAutoPaymentWithNotificationIdNotificationNotDelivered()
    {
        $this->testPreDebitNotify();

        $payment = $this->getDbLastEntity('payment');

        $tokenId = $payment->localToken->getPublicId();

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);
        unset($paymentInput[Payment::CUSTOMER_ID]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $paymentInput[Payment::AMOUNT] = 50000;

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');

        $cardMandateNotification->afa_required = 0;
        $cardMandateNotification->status = 'failed';

        $cardMandateNotification->saveOrFail();

        $paymentInput[Payment::RECURRING_TOKEN][Payment::NOTIFICATION_ID] = $cardMandateNotification->getPublicId();

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();

        $this->ba->privateAuth();

        $this->makeRequestAndCatchException(function () use ($paymentInput) {
            $this->doS2SRecurringPayment($paymentInput);
        }, \RZP\Exception\BadRequestException::class, 'customer not notified');
    }

    public function testCreateCardMandateAutoPaymentWithNotificationIdAmountMismatch()
    {
        $this->testPreDebitNotify();

        $payment = $this->getDbLastEntity('payment');

        $tokenId = $payment->localToken->getPublicId();

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);
        unset($paymentInput[Payment::CUSTOMER_ID]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $paymentInput[Payment::AMOUNT] = 50001;

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');

        $cardMandateNotification->afa_required = 0;
        $cardMandateNotification->status = 'notified';

        $cardMandateNotification->saveOrFail();

        $paymentInput[Payment::RECURRING_TOKEN][Payment::NOTIFICATION_ID] = $cardMandateNotification->getPublicId();

        $order = $this->fixtures->create('order', [
            'amount' => 50001,
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();

        $this->ba->privateAuth();

        $this->mockValidatePayment();

        $this->makeRequestAndCatchException(function () use ($paymentInput) {
            $this->doS2SRecurringPayment($paymentInput);
        }, \RZP\Exception\BadRequestException::class, 'notification amount does not match with payment amount');
    }

    public function testPreDebitNotifyAmountMoreThanMaxAmount()
    {
        $this->testCreateSplitAuthenticatePayment();

        $token = $this->getDbLastEntity('token');

        $request = [
            "url" => "/tokens/" . $token->getPublicId() . "/pre_debit/notify",
            "method" => "post",
            "content" => [
                'debit_at' => Carbon::now()->addDays(2)->timestamp,
                'amount'   => 11500000,
                'purpose'  => 'test debit',
            ],
        ];

        $this->ba->privateAuth();

        $obj = $this;

        $this->makeRequestAndCatchException(function () use ($request, $obj) {
            $obj->makeRequestAndGetContent($request);
        }, \RZP\Exception\BadRequestValidationFailureException::class, 'amount can\'t greater than max amount');
    }

    public function testPreDebitNotifyAmountNotMatchingForFixedTypeDebit()
    {
        $this->testCreateSplitAuthenticatePayment('monthly');

        $token = $this->getDbLastEntity('token');

        $request = [
            "url" => "/tokens/" . $token->getPublicId() . "/pre_debit/notify",
            "method" => "post",
            "content" => [
                'debit_at' => Carbon::now()->addDays(2)->timestamp,
                'amount'   => 1500000,
                'purpose'  => 'test debit',
            ],
        ];

        $this->ba->privateAuth();

        $obj = $this;

        $this->makeRequestAndCatchException(function () use ($request, $obj) {
            $obj->makeRequestAndGetContent($request);
        }, \RZP\Exception\BadRequestValidationFailureException::class, 'amount has to be same as mandate\'s max amount for fixed amount debit type');
    }

    public function testCreateCardMandatePaymentWithMandateRegisterNotSupportingCard()
    {
        $this->mockCheckBin();

        $callable = function ($input)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CARD_MANDATE_CARD_NOT_SUPPORTED);
        };

        $this->mockRegisterMandate($callable);

        $this->mockReportPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];

        $exception = false;
        try
        {
            $this->makeRequestAndGetContent($request);
        }
        catch (\Exception $e)
        {
            $this->assertEquals(ErrorCode::BAD_REQUEST_CARD_MANDATE_CARD_NOT_SUPPORTED, $e->getCode());
            $exception = true;
        }

        $this->assertTrue($exception);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('failed', $payment->getStatus());
        $this->assertEquals('BAD_REQUEST_CARD_MANDATE_CARD_NOT_SUPPORTED', $payment->internal_error_code);
    }

    public function testUseRecurringTokenisedSavedCardForPayment()
    {
        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $this->mockCardVaultWithMigrateToken();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $this->setMockRazorxTreatment(['recurring_tokenisation' => 'on']);

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $paymentInp = $this->paymentInput;
        $paymentInp['_']['library'] = 'razorpayjs';
        $paymentInp['save'] = 1;
        $paymentInp['user_consent_for_tokenisation'] = 1;
        $paymentInp['recurring'] = 1;
        $paymentInp['customer_id'] = 'cust_100000customer';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $paymentInp,
        ];

        $this->makeRequestAndGetContent($request);

        $freshCardPayment = $this->getDbLastEntity(E::PAYMENT);

        $actualCard = $freshCardPayment->card;

        $freshCardtoken = $freshCardPayment->localToken;

        $tokenisedCard = $freshCardPayment->localToken->card;

        $this->assertTrue($tokenisedCard->isRzpSavedCard() === false);
        $this->assertTrue($actualCard->isRzpSavedCard() === true);
        $this->assertTrue($freshCardtoken->isRecurring() === true);

        $paymentInp[Payment::CARD] = [];
        $paymentInp[Payment::TOKEN] = $freshCardtoken->getPublicId();
        $paymentInp[Payment::CARD] = array('cvv'  => 111);

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);

        unset($paymentInp['order_id']);
        unset($paymentInp['save']);
        $paymentInp['order_id'] = $order->getPublicId();

        $this->mockCardVaultWithCryptogram();

        $this->doAuthPayment($paymentInp);

        $savedCardPayment = $this->getDbLastEntity(E::PAYMENT);

        $tokenisedSavedCardForPayment = $savedCardPayment->card;
        $tokenisedSavedCard = $savedCardPayment->localToken->card;
        $savedCardToken = $savedCardPayment->localToken;

        $this->assertTrue($tokenisedSavedCardForPayment->isRzpSavedCard() === true);
        $this->assertTrue($tokenisedSavedCard->isRzpSavedCard() === false);
        $this->assertTrue($savedCardToken->isRecurring() === true);

        $this->assertEquals($tokenisedCard->getId(), $tokenisedSavedCard->getId());
        $this->assertNotEquals($freshCardtoken->getId(), $savedCardToken->getId());
    }

    public function testCreateCardMandatePaymentWithAuthLink()
    {
        $this->ba->proxyAuth();
        $this->startTest();

        $order = $this->getDbLastEntity('order');

        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];

        $request['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['razorpay_payment_id'] ?? null);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());

        $token = $payment->localToken;
        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());
        $this->assertEquals(123400, $token->getMaxAmount());

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertNotEmpty($cardMandate->getMandateSummaryUrl());
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandate->getMandateId());
        $this->assertEquals(123400, $cardMandate->getMaxAmount());
    }

    public function testMandateHQCallbackMandatePaused()
    {
        $this->testCreateCardMandatePayment();

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->testData[__FUNCTION__]['request']['content']['payload']['mandate']['entity']['id'] = $cardMandate->getMandateId();

        $this->mockStorkService();

        $this->mockServiceStorkRequest(
            function ($path, $payload) use (&$webhookPayload)
            {
                $webhookPayload = $payload;

                return new \WpOrg\Requests\Response();
            })->times(1);

        $this->startTest();

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->assertEquals('paused', $cardMandate->getStatus());

        $token = $this->getDbLastEntity(E::TOKEN);

        $this->assertEquals('paused', $token->getRecurringStatus());

        $this->assertEquals('token.paused', $webhookPayload['event']['name']);

        $payload = json_decode($webhookPayload['event']['payload'], true);

        $this->assertEquals($token->getPublicId(), $payload['payload']['token']['entity']['id']);
    }

    public function testMandateHQCallbackMandateResumed()
    {
        $this->testMandateHQCallbackMandatePaused();

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->testData[__FUNCTION__]['request']['content']['payload']['mandate']['entity']['id'] = $cardMandate->getMandateId();

        $this->mockStorkService();

        $this->mockServiceStorkRequest(
            function ($path, $payload) use (&$webhookPayload)
            {
                $webhookPayload = $payload;

                return new \WpOrg\Requests\Response();
            })->times(1);

        $this->startTest();

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->assertEquals('active', $cardMandate->getStatus());

        $token = $this->getDbLastEntity(E::TOKEN);

        $this->assertEquals('confirmed', $token->getRecurringStatus());

        $this->assertEquals('token.confirmed', $webhookPayload['event']['name']);

        $payload = json_decode($webhookPayload['event']['payload'], true);

        $this->assertEquals($token->getPublicId(), $payload['payload']['token']['entity']['id']);
    }

    public function testMandateHQCallbackMandateCancelled()
    {
        $this->testCreateCardMandatePayment();

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->testData[__FUNCTION__]['request']['content']['payload']['mandate']['entity']['id'] = $cardMandate->getMandateId();

        $webhookPayload = [];

        $this->mockStorkService();

        $this->mockServiceStorkRequest(
            function ($path, $payload) use (&$webhookPayload)
            {
                $webhookPayload = $payload;

                return new \WpOrg\Requests\Response();
            })->times(1);

        $this->startTest();

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->assertEquals('cancelled', $cardMandate->getStatus());

        $token = $this->getDbLastEntity(E::TOKEN);

        $this->assertEquals('cancelled', $token->getRecurringStatus());

        $this->assertEquals('token.cancelled', $webhookPayload['event']['name']);

        $payload = json_decode($webhookPayload['event']['payload'], true);

        $this->assertEquals($token->getPublicId(), $payload['payload']['token']['entity']['id']);
    }

    public function testMandateHQCallbackMandateCompleted()
    {
        $this->testCreateCardMandatePayment();

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->testData[__FUNCTION__]['request']['content']['payload']['mandate']['entity']['id'] = $cardMandate->getMandateId();

        $this->mockStorkService();

        $webhookPayload = [];

        $this->mockServiceStorkRequest(
            function ($path, $payload) use (&$webhookPayload)
            {
                $webhookPayload = $payload;

                return new \WpOrg\Requests\Response();
            })->times(1);

        $this->startTest();

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->assertEquals('completed', $cardMandate->getStatus());

        $token = $this->getDbLastEntity(E::TOKEN);

        $this->assertEquals('cancelled', $token->getRecurringStatus());

        $this->assertNotEmpty($token->getExpiredAt());

        $this->assertEquals('token.cancelled', $webhookPayload['event']['name']);

        $payload = json_decode($webhookPayload['event']['payload'], true);

        $this->assertEquals($token->getPublicId(), $payload['payload']['token']['entity']['id']);
    }

    public function testCreateCardMandatePaymentViaPaymentCheckoutApi()
    {
        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/checkout',
            'content' => $this->paymentInput,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['razorpay_payment_id'] ?? null);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());

        $token = $payment->localToken;
        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertNotEmpty($cardMandate->getMandateSummaryUrl());
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandate->getMandateId());
    }

    public function testSubscriptionRegistrationInitialCardMandatePaymentAmountGreaterThanMaxAmount()
    {
        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $paymentInp = $this->paymentInput;

        $paymentInp['amount'] = 800000;

        $subr = $this->fixtures->create('subscription_registration',
            ['method' => 'card', 'max_amount' => 400000, 'expire_at' => 4091958776, 'notes' => []]);

        $order = $this->fixtures->create('order',
            ['amount' => 800000, 'payment_capture' => 1]);

        $this->fixtures->create('invoice',
            ['entity_type' => 'subscription_registration', 'entity_id' => $subr->id, 'order_id' => $order->id]);

        $paymentInp['order_id'] = $order->getPublicId();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $paymentInp,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['razorpay_payment_id'] ?? null);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());

        $token = $payment->localToken;
        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());
        $this->assertEquals($subr['max_amount'], $token->getMaxAmount());
        $this->assertEquals($subr['expire_at'], $token->getExpiredAt());

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertNotEmpty($cardMandate->getMandateSummaryUrl());
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandate->getMandateId());
    }

    public function testSubscriptionRegistrationInitialCardMandatePaymentWithMonthlyFrequency()
    {

        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $paymentInp = $this->paymentInput;

        $paymentInp['amount'] = 100;

        $subr = $this->fixtures->create('subscription_registration',
            ['method' => 'card', 'max_amount' => 500, 'expire_at' => 4091958776, 'frequency' => 'monthly', 'notes' => []]);

        $order = $this->fixtures->create('order',
            ['amount' => 100, 'payment_capture' => 1]);

        $this->fixtures->create('invoice',
            ['entity_type' => 'subscription_registration', 'entity_id' => $subr->id, 'order_id' => $order->id]);

        $paymentInp['order_id'] = $order->getPublicId();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $paymentInp,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['razorpay_payment_id'] ?? null);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());

        $token = $payment->localToken;
        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());
        $this->assertEquals($subr['max_amount'], $token->getMaxAmount());
        $this->assertEquals($subr['expire_at'], $token->getExpiredAt());
        $this->assertEquals('monthly', $token->getFrequency());

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertNotEmpty($cardMandate->getMandateSummaryUrl());
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandate->getMandateId());
        $this->assertEquals('monthly', $cardMandate->getFrequency());
    }

    public function testSiHubSubscriptionRegistrationInitialCardMandatePaymentWithMonthlyFrequency()
    {
        $this->fixtures->create('terminal:shared_billdesk_sihub_terminal');

        $this->fixtures->terminal->disableTerminal($this->mandateHqTerminal['id']);

        $this->fixtures->edit('iin', '411111',[
            'mandate_hubs' => ['billdesk_sihub'=>'1'],
        ]);

        $this->fixtures->merchant->addFeatures(['allow_billdesk_sihub']);

        $this->paymentInput['amount'] = 100;

        $subr = $this->fixtures->create('subscription_registration',
            ['method' => 'card', 'max_amount' => 500, 'expire_at' => 4091958776, 'frequency' => 'monthly', 'notes' => []]);

        $order = $this->fixtures->create('order',
            ['amount' => 100, 'payment_capture' => 1]);

        $this->fixtures->create('invoice',
            ['entity_type' => 'subscription_registration', 'entity_id' => $subr->id, 'order_id' => $order->id]);

        $this->paymentInput['order_id'] = $order->getPublicId();

        $this->paymentInput['card']['number'] = '4111111111111111';

        $this->mockCps(null, 'entity_fetch');

        $this->mockCheckBin();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['razorpay_payment_id'] ?? null);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());

        $token = $payment->localToken;
        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());
        $this->assertEquals($subr['max_amount'], $token->getMaxAmount());
        $this->assertEquals($subr['expire_at'], $token->getExpiredAt());
        $this->assertEquals('monthly', $token->getFrequency());

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('VkHYuA3NH3', $cardMandate->getMandateId());
        $this->assertEquals('monthly', $cardMandate->getFrequency());
    }

    public function testSubscriptionRegistrationTokenizedInitialCardMandatePaymentAmountGreaterThanMaxAmount()
    {
        $this->mockCheckBin();

        $this->mockCardVaultWithMigrateToken();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $paymentInp = $this->paymentInput;

        $paymentInp['_']['library'] = 'razorpayjs';

        $paymentInp['recurring'] = 1;

        $paymentInp['amount'] = 800000;

        $subr = $this->fixtures->create('subscription_registration',
            ['method' => 'card', 'max_amount' => 400000, 'expire_at' => 4091958776, 'notes' => []]);

        $order = $this->fixtures->create('order',
            ['amount' => 800000, 'payment_capture' => 1]);

        $this->fixtures->create('invoice',
            ['entity_type' => 'subscription_registration', 'entity_id' => $subr->id, 'order_id' => $order->id]);

        $paymentInp['order_id'] = $order->getPublicId();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $paymentInp,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['razorpay_payment_id'] ?? null);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());

        $token = $payment->localToken;
        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());
        $this->assertEquals($subr['max_amount'], $token->getMaxAmount());
        $this->assertEquals($subr['expire_at'], $token->getExpiredAt());

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertNotEmpty($cardMandate->getMandateSummaryUrl());
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandate->getMandateId());

        $card = $this->getLastEntity('card', true);
        $this->assertEquals($card['vault'], 'rzpvault');
    }

    public function testSubscriptionRegistrationTokenizedInitialCardMandatePaymentAmountGreaterThanMaxAmountRupay()
    {
        $this->fixtures->edit('iin', 400018 ,[
            'network' => "RuPay",
        ]);

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::RUPAY]);

        $this->mockCheckBin();

        $this->mockCardVaultWithMigrateToken();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $this->setMockRazorxTreatment(['recurring_through_rupay_card_mid' => 'on',
            'recurring_tokenisation' => 'on',
            'recurring_through_rupay_card_iin' => 'on'
            ]);

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $this->mockCps(null, 'entity_fetch');

        $paymentInp = $this->paymentInput;

        $paymentInp['_']['library'] = 'razorpayjs';

        $paymentInp['recurring'] = 1;

        $paymentInp['save'] = 1;

        $paymentInp['amount'] = 800000;

        $subr = $this->fixtures->create('subscription_registration',
            ['method' => 'card', 'max_amount' => 400000, 'expire_at' => 1735669799, 'notes' => []]);

        $order = $this->fixtures->create('order',
            ['amount' => 800000, 'payment_capture' => 1]);

        $this->fixtures->create('invoice',
            ['entity_type' => 'subscription_registration', 'entity_id' => $subr->id, 'order_id' => $order->id]);

        $paymentInp['order_id'] = $order->getPublicId();

        $this->setMockRazorxTreatment(['recurring_through_rupay_card_mid' => 'on',
            'recurring_through_rupay_card_iin' => 'on',
            'recurring_tokenisation' => 'on'
        ]);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $paymentInp,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['razorpay_payment_id'] ?? null);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());

        $token = $payment->localToken;
        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());
        $this->assertEquals($subr['max_amount'], $token->getMaxAmount());
        $this->assertEquals($subr['expire_at'], $token->getExpiredAt());

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertNotEmpty($cardMandate->getMandateSummaryUrl());
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandate->getMandateId());

        $card = $this->getLastEntity('card', true);
        $this->assertEquals($card['vault'], 'rupay');
    }

    public function testCreateCardMandatePaymentWithFailedToken()
    {
        $this->mandateConfirm = 'false';

        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];

        try {
            $this->makeRequestAndGetContent($request);
        } catch (\Exception $e)
        {}
        $payment = $this->getDbLastEntity(E::PAYMENT);

        $token = $payment->localToken;

        unset($request['content']['card']);
        $request['content']['token'] = $token->getPublicId();

        $this->mandateConfirm = 'true';

        $this->makeRequestAndGetContent($request);
        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());
        $this->assertNotEquals($payment->getTokenId(), $token->getId());
    }

    public function testCreateCardMandatePaymentWithFailedTokenWithPreferredRecurring()
    {
        $this->mandateConfirm = 'false';

        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];

        try {
            $this->makeRequestAndGetContent($request);
        } catch (\Exception $e)
        {}
        $payment = $this->getDbLastEntity(E::PAYMENT);

        $token = $payment->localToken;

        $request['content']['recurring'] = 'preferred';

        $this->mandateConfirm = 'true';

        $this->makeRequestAndGetContent($request);
        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());
        $this->assertNotEquals($payment->getTokenId(), $token->getId());
    }

    public function testCreateCardMandateForUSDCurrencyPayment()
    {
        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];

        $request['content']['currency'] = Currency::USD;

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
            'currency' => Currency::USD,
        ]);
        $request['content'][Payment::ORDER_ID] = $order->getPublicId();

        try
        {
            $this->makeRequestAndGetContent($request);
        }
        catch (BadRequestException $e)
        {
            $exception = true;
            $this->assertEquals('BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED', $e->getCode());
            $this->assertEquals('Currency is not supported', $e->getMessage());
        }

        $this->assertTrue($exception);
    }

    public function testCreateCardMandatePaymentForMandateCancelledByCustomer()
    {
        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mandateConfirm = 'false';

        $this->mockReportPayment();

        $exception = false;
        try
        {
            $request = [
                'method'  => 'POST',
                'url'     => '/payments/create/ajax',
                'content' => $this->paymentInput,
            ];

            $this->makeRequestAndGetContent($request);
        }
        catch (BadRequestException $e)
        {
            $exception = true;
            $this->assertEquals('BAD_REQUEST_CARD_MANDATE_CANCELLED_BY_USER', $e->getCode());
            $this->assertEquals('Card mandate created for payment has been cancelled by user', $e->getMessage());
        }

        $this->assertTrue($exception);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('failed', $payment->getStatus());
        $this->assertEquals('BAD_REQUEST_CARD_MANDATE_CANCELLED_BY_USER', $payment->internal_error_code);
        $this->assertEquals('Card mandate created for payment has been cancelled by user', $payment->error_description);

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertEquals('mandate_cancelled', $cardMandate->getStatus());
    }

    public function testCardMandateTokenDelete()
    {
        $this->testCreateCardMandatePayment();

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, $token->getPublicId());

        $this->ba->proxyAuth();

        $this->mockCancelMandate();

        $this->startTest();

        $cardMandate = $this->getDbLastEntity(Entity::CARD_MANDATE);

        $this->assertEquals('cancelled', $cardMandate->getStatus());
    }

    public function testCreateCardMandateAutoPayment()
    {
        $this->testCreateCardMandatePayment();

        $this->mockCreatePreDebitNotification();

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($paymentInput);
        $this->assertNotEmpty($content['razorpay_payment_id']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('auto', $payment->getRecurringType());
        $this->assertEquals('created', $payment->getStatus());

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandateNotification->notification_id);
        $this->assertNotNull($cardMandateNotification->reminder_id);
        $this->assertNotEmpty($cardMandateNotification->notified_at);

        $this->mockPostDebitNotification();
        $this->mockValidatePayment();

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, $payment->getId());
        $this->ba->reminderAppAuth();

        $this->startTest();

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('captured', $payment->getStatus());
    }

    public function testCreateCardMandateAutoPaymentDuplicateNotificationDeliveryCallback()
    {
        $this->testCreateCardMandatePayment();

        $this->mockCreatePreDebitNotification(true, false);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($paymentInput);
        $this->assertNotEmpty($content['razorpay_payment_id']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('auto', $payment->getRecurringType());
        $this->assertEquals('created', $payment->getStatus());

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandateNotification->notification_id);

        $this->testData[__FUNCTION__]['request']['content']['payload']['mandate.notification']['entity']['id'] = $cardMandateNotification->getNotificationId();

        $this->startTest();

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandateNotification->notification_id);
        $this->assertNotNull($cardMandateNotification->reminder_id);
        $this->assertNotEmpty($cardMandateNotification->notified_at);

        $this->mockReminder();

        $this->startTest();

        $cardMandateNotification2 = $this->getDbLastEntity('card_mandate_notification');

        $this->assertEquals($cardMandateNotification->reminder_id, $cardMandateNotification2->reminder_id);
    }

    public function testCreateCardMandateAutoPaymentWithAfa()
    {
        $this->testCreateCardMandatePayment();

        $this->mockCreatePreDebitNotification(false, true);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($paymentInput);
        $this->assertNotEmpty($content['razorpay_payment_id']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('auto', $payment->getRecurringType());
        $this->assertEquals('created', $payment->getStatus());

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('failed', $cardMandateNotification->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandateNotification->notification_id);

        $this->mockPostDebitNotification();
        $this->mockValidatePayment();

        $this->testData[__FUNCTION__]['request']['content']['payload']['mandate.notification']['entity']['id'] = $cardMandateNotification->notification_id;

        $this->startTest();

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('failed', $cardMandateNotification->getStatus());
        $this->assertEquals('approved', $cardMandateNotification->getAfaStatus());

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('captured', $payment->getStatus());
    }

    public function testCreateCardMandateAutoPaymentWithAfaUsingTokenisedCard()
    {
        $this->testCreateCardMandatePaymentWithTokenisationSuccess();

        $this->mockCreatePreDebitNotification(false, true);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();

        $this->ba->privateAuth();

        $this->setMockRazorxTreatment(['recurring_subsequent_through_tokenised_card' => 'on',
                                       'recurring_tokenisation_not_using_actual_card_iin' => 'on']);

        $this->mockCardVaultWithCryptogram();

        // Explicitly setting the tokens.status as active to pass the validation in subsequent payment for tokenised card
        $token = $this->getDbLastEntity(E::TOKEN);
        $token->setStatus('active');
        $token->saveOrFail();

        $this->fixtures->create('tokenised_iin', [
                            'iin' => '400018',
                            'high_range' => '111111111',
                            'low_range' => '111111111',
                            'token_iin_length' => 9,
        ]);

        $card = $token->card;
        $card->setTokenIin(111111111);
        $card->saveOrFail();

        $content = $this->doS2SRecurringPayment($paymentInput);
        $this->assertNotEmpty($content['razorpay_payment_id']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('auto', $payment->getRecurringType());
        $this->assertEquals('created', $payment->getStatus());

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('failed', $cardMandateNotification->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandateNotification->notification_id);

        $this->mockPostDebitNotification();
        $this->mockValidatePayment();

        $this->testData[__FUNCTION__]['request']['content']['payload']['mandate.notification']['entity']['id'] = $cardMandateNotification->notification_id;

        $this->startTest();

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('failed', $cardMandateNotification->getStatus());
        $this->assertEquals('approved', $cardMandateNotification->getAfaStatus());

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('captured', $payment->getStatus());
    }

    public function testCreateCardMandateOptOutOfPayment()
    {
        $this->markTestSkipped('until fixed');

        $this->testCreateCardMandatePayment();

        $this->mockCreatePreDebitNotification(false);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();

        $this->ba->privateAuth();

        $exception = false;
        try
        {
            $this->doS2SRecurringPayment($paymentInput);
        }
        catch (BadRequestException $e)
        {
            $exception = true;
            $this->assertEquals('Payment debit notification failed to deliver to customer', $e->getMessage());
            $this->assertEquals('BAD_REQUEST_PAYMENT_CARD_MANDATE_NOTIFICATION_NOT_SENT', $e->getCode());
        }
        finally
        {
            $this->assertTrue($exception);
        }

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('auto', $payment->getRecurringType());
        $this->assertEquals('failed', $payment->getStatus());
        $this->assertEquals('BAD_REQUEST_PAYMENT_CARD_MANDATE_NOTIFICATION_NOT_SENT', $payment->internal_error_code);
        $this->assertEquals('Payment debit notification failed to deliver to customer', $payment->error_description);


        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('failed', $cardMandateNotification->getStatus());
        $this->assertEquals('rejected', $cardMandateNotification->getAfaStatus());
    }

    public function testSubscriptionRegistrationAutoCardMandatePaymentAmountGreaterThanMaxAmountWithAFA()
    {
        $this->testSubscriptionRegistrationInitialCardMandatePaymentAmountGreaterThanMaxAmount();

        $this->mockCreatePreDebitNotification(true, true);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $order = $this->fixtures->create('order', [
            'amount' => 900000, // Amount greater than Token Max Amount
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();
        $paymentInput[Payment::AMOUNT] = $order['amount'];

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($paymentInput);
        $this->assertNotEmpty($content['razorpay_payment_id']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('auto', $payment->getRecurringType());
        $this->assertEquals('created', $payment->getStatus());

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandateNotification->notification_id);
        $this->assertNotEmpty($cardMandateNotification->notified_at);

        $this->mockPostDebitNotification();

        $this->testData[__FUNCTION__]['request']['content']['payload']['mandate.notification']['entity']['id'] = $cardMandateNotification->notification_id;

        $this->mockValidatePayment();

        $this->startTest();

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('captured', $payment->getStatus());
    }

    public function testSubscriptionRegistrationAutoTokenizedCardMandatePaymentAmountGreaterThanMaxAmountWithAFA()
    {
        $this->testSubscriptionRegistrationInitialCardMandatePaymentAmountGreaterThanMaxAmount();

        $this->mockCreatePreDebitNotification(true, true);

        $this->allowAllTerminalRazorx();

        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'recurring_non_3ds' => '1',
            ]
        ]);

        $this->mockCardVaultWithCryptogram();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function(string $method, string $url, array $input) use ($terminal)
            {
                $input = $input['input'];

                switch ($url)
                {
                    case 'action/authorize':

                        $payment = $input['payment'];

                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);

                        $content = [
                            'Message' => [
                                'PAReq' => [
                                    'Merchant' => [
                                        'acqBIN' => '11111111111',
                                        'merID'  => '12AB,cd/34-EF  -g,5/H-67'
                                    ],
                                    'CH' => [
                                        'acctID' => 'NTU2NzYzMDAwMDAwMjAwNA==',
                                    ],
                                    'Purchase' => [
                                        'xid'    => base64_encode(str_pad($payment['id'], 20, '0', STR_PAD_LEFT)),
                                        'date'    => \Carbon\Carbon::createFromTimestamp($payment['created_at'], 'Asia/Kolkata')->format('Ymd H:m:s'),
                                        'amount' => '500.00',
                                        'purchAmount' => '50000',
                                        'currency' => '356',
                                        'exponent' => 2,
                                    ]
                                ]
                            ],
                        ];

                        $content['Message']['@attributes']['id'] = $payment['id'];

                        $xml = \Lib\Formatters\Xml::create('ThreeDSecure', $content);

                        $xml = zlib_encode($xml, 15);
                        $xml = base64_encode($xml);

                        return [
                            'data' => [
                                'content' => [
                                    'TermUrl' => $input['callbackUrl'],
                                    'PaReq' => $xml,
                                    'MD' => $payment['id'],
                                ],
                                'method' => 'post',
                                'url' =>  'https://api.razorpay.com/v1/gateway/acs/mpi_blade',
                            ],
                            'payment' => [
                                'terminal_id' => $terminal->getId(),
                                'auth_type' => null,
                                'authentication_gateway' => 'mpi_blade'
                            ],
                        ];
                    case 'action/callback':
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);
                        return [
                            'data' => [
                                'acquirer' => [
                                    'reference2' => 'test'
                                ],
                                'two_factor_auth' => 'Y'
                            ],
                            'payment' => [
                                'auth_type' => "3ds",
                            ],
                        ];

                    case 'action/capture':
                        return [
                            'data' => [
                                'status' => 'captured',
                            ],
                        ];

                    case 'action/pay':
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);
                        return [
                            'data' => [
                                'acquirer' => [
                                    'reference2' => 'test'
                                ],
                                'two_factor_auth' => 'Y'
                            ],
                            'payment' => [
                                'auth_type' => "3ds",
                            ],
                        ];

                    default:
                        return null;
                }
            });

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $token = $this->getDbLastEntity(E::TOKEN);

        $card = $token->card;
        $card->setVault('visa');

        $token->setStatus('active');

        $token->saveOrFail();
        $card->saveOrFail();

        $paymentInput = $this->getDefaultTokenPanPaymentArray();
        $paymentInput['recurring'] = true;
        $paymentInput['customer_id'] = 'cust_100000customer';

        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $order = $this->fixtures->create('order', [
            'amount' => 900000, // Amount greater than Token Max Amount
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();
        $paymentInput[Payment::AMOUNT] = $order['amount'];

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($paymentInput);
        $this->assertNotEmpty($content['razorpay_payment_id']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('auto', $payment->getRecurringType());
        $this->assertEquals('created', $payment->getStatus());

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandateNotification->notification_id);
        $this->assertNotEmpty($cardMandateNotification->notified_at);

        $this->mockPostDebitNotification();

        $this->testData[__FUNCTION__]['request']['content']['payload']['mandate.notification']['entity']['id'] = $cardMandateNotification->notification_id;

        $this->mockValidatePayment();

        $this->startTest();

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('captured', $payment->getStatus());
    }

    public function testCreateCardMandateAutoPaymentVerificationFailed()
    {
        $this->testCreateCardMandatePayment();

        $this->mockCreatePreDebitNotification();

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;
        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($paymentInput);
        $this->assertNotEmpty($content['razorpay_payment_id']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('auto', $payment->getRecurringType());
        $this->assertEquals('created', $payment->getStatus());

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandateNotification->notification_id);
        $this->assertNotNull($cardMandateNotification->reminder_id);
        $this->assertNotEmpty($cardMandateNotification->notified_at);

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, $payment->getId());
        $this->ba->reminderAppAuth();

        $this->mockValidatePayment('mandate not active');

        $this->startTest();

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('failed', $payment->getStatus());
        $this->assertEquals('BAD_REQUEST_VALIDATION_FAILURE', $payment->internal_error_code);
        $this->assertEquals('Something went wrong, please try again after sometime.', $payment->error_description);
    }

    public function testCreateCardMandateAutoPaymentNotificationFailed()
    {
        $this->testCreateCardMandatePayment();

        $this->mockCreatePreDebitNotification(false);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $this->ba->privateAuth();

        $exception = false;
        try
        {
            $this->doS2SRecurringPayment($paymentInput);
        }
        catch (BadRequestException $e)
        {
            $exception = true;
            $this->assertEquals('Payment debit notification failed to deliver to customer', $e->getMessage());
            $this->assertEquals('BAD_REQUEST_PAYMENT_CARD_MANDATE_NOTIFICATION_NOT_SENT', $e->getCode());
        }
        finally
        {
            $this->assertTrue($exception);
        }

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('auto', $payment->getRecurringType());
        $this->assertEquals('failed', $payment->getStatus());
        $this->assertEquals('BAD_REQUEST_PAYMENT_CARD_MANDATE_NOTIFICATION_NOT_SENT', $payment->internal_error_code);
        $this->assertEquals('Payment debit notification failed to deliver to customer', $payment->error_description);

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandateNotification->notification_id);
        $this->assertEmpty($cardMandateNotification->reminder_id);
        $this->assertEquals('failed', $cardMandateNotification->getStatus());
    }

    public function testCreateCardMandateAutoPaymentMandateNotActive()
    {
        $this->testCreateCardMandatePayment();

        $this->mockCreatePreDebitNotification();

        $cardMandate = $this->getDbLastEntity('card_mandate');
        $cardMandate->setStatus(Status::PAUSED);
        $cardMandate->saveOrFail();

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $this->ba->privateAuth();

        $exception = false;
        try
        {
            $this->doS2SRecurringPayment($paymentInput);
        }
        catch (BadRequestException $e)
        {
            $exception = true;
            $this->assertEquals('Card mandate is not active, it is paused by user', $e->getMessage());
            $this->assertEquals('BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE_PAUSED', $e->getCode());
        }
        finally
        {
            $this->assertTrue($exception);
        }

        $this->fixtures->edit('card_mandate', $cardMandate->getId(), [
            'status' => Status::CANCELLED,
        ]);

        $exception = false;
        try
        {
            $this->doS2SRecurringPayment($paymentInput);
        }
        catch (BadRequestException $e)
        {
            $exception = true;
            $this->assertEquals('Card mandate is not active, it is cancelled by user', $e->getMessage());
            $this->assertEquals('BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE_CANCELLED', $e->getCode());
        }
        finally
        {
            $this->assertTrue($exception);
        }

        $this->fixtures->edit('card_mandate', $cardMandate->getId(), [
            'status' => Status::COMPLETED,
        ]);

        $exception = false;
        try
        {
            $this->doS2SRecurringPayment($paymentInput);
        }
        catch (BadRequestException $e)
        {
            $exception = true;
            $this->assertEquals('Card mandate is not active, it is expired', $e->getMessage());
            $this->assertEquals('BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE_EXPIRED', $e->getCode());
        }
        finally
        {
            $this->assertTrue($exception);
        }
    }

    public function testCardMandateMaxAmountFetchForSubscriptions()
    {
        $this->testCreateCardMandatePayment();

        $token = $this->getDbLastEntity(E::TOKEN);

        $request = [
            'method'  => 'GET',
            'url'     => '/tokens/' . $token->getPublicId() . '/card_mandate_detail',
            'content' => []
        ];

        $this->ba->subscriptionsAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response);
        $this->assertEquals(1500000, $response['maxAmount']);
    }


    public function runBDSIHubCreateCardMandatePayment()
    {
        $this->fixtures->create('terminal:shared_billdesk_sihub_terminal');

        $this->fixtures->terminal->disableTerminal($this->mandateHqTerminal['id']);

        $this->fixtures->edit('iin', '411111',[
            'mandate_hubs' => ['mandate_hq'=>'1', 'billdesk_sihub'=>'1'],
        ]);

        $this->fixtures->merchant->addFeatures(['allow_billdesk_sihub']);

        $this->paymentInput['card']['number'] = '4111111111111111';

        $this->mockCps(null, 'entity_fetch');

        $this->mockCheckBin();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];

        $response = $this->makeRequestAndGetContent($request);


        $this->assertNotNull($response['razorpay_payment_id'] ?? null);
    }

    public function runSIHubCreateCardMandateAutoPayment()
    {
        $this->runBDSIHubCreateCardMandatePayment();

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($paymentInput);
        $this->assertNotEmpty($content['razorpay_payment_id']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('auto', $payment->getRecurringType());
        $this->assertEquals('created', $payment->getStatus());

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());
        $this->assertNotNull($cardMandateNotification->reminder_id);
        $this->assertNotEmpty($cardMandateNotification->notified_at);

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, $payment->getId());
        $this->ba->reminderAppAuth();

        $this->startTest();

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('captured', $payment->getStatus());
    }

    protected function mockValidatePayment($errorCode = '')
    {
        $callable = function () use ($errorCode)
        {
            if (empty($errorCode) === false)
            {
                throw new BadRequestValidationFailureException($errorCode);
            }

            return [
                'validation_id' => 'ttttttttttttt',
                'afa_required'  => false
            ];
        };

        return $this->mockMandateHQ($callable, 'validatePayment');
    }

    protected function mockPostDebitNotification($success = true)
    {
        $callable = function () use ($success)
        {
            return [
                'success' => $success
            ];
        };

        return $this->mockMandateHQ($callable, 'postDebitNotify');
    }

    protected function mockRegisterMandate($callable = null)
    {
        if ($callable === null)
        {
            $callable = function ($input)
            {
                return [
                    'redirect_url' => "https://mandate-manager.stage.razorpay.in/issuer/hdfc_GX3VC146gmBVNe/hostedpage",
                    'id' => "ratn_PP3VC146gmBVGG",
                    'status' => "created",
                    'amount'     => $input['amount'],
                    'debit_type' => $input['debit_type'],
                    'start_at'   => $input['start_at'] ?? Carbon::now()->timestamp,
                    'max_amount' => $input['max_amount'],
                    'frequency'  => $input['frequency'],
                    'end_time'   => $input['end_time'],
                ];
            };
        }

        return $this->mockMandateHQ($callable);
    }

    protected function mockCancelMandate()
    {
        $callable = function ()
        {
            return [
                'id' => "ratn_PP3VC146gmBVGG",
                "status" => "cancelled",
            ];
        };

        return $this->mockMandateHQ($callable, 'cancelMandate');
    }

    protected function mockReportPayment()
    {
        $callable = function ()
        {
            return [];
        };

        return $this->mockMandateHQ($callable, 'reportPayment');
    }

    protected function mockCreatePreDebitNotification($success = true, $afaRequired = false, $isPending = false)
    {
        $callable = function ($mandateId, $input) use ($success, $afaRequired, $isPending)
        {
            $status = $success ? 'delivered' : 'failed';

            if ($isPending) {
                $status = 'created';
            }

            return [
                'id' => 'ratn_PP3VC146gmBVGG',
                'status' => $status,
                'delivered_at' => Carbon::now()->timestamp,
                'afa_status' => 'created',
                'afa_required' => $afaRequired,
                'afa_completed_at' => 0,
                'currency' => empty($input['pre_debit_details']['currency']) ? 'INR' : $input['pre_debit_details']['currency'],
                'amount' => $input['pre_debit_details']['amount'],
                'purpose' => empty($input['pre_debit_details']['purpose']) ? null : $input['pre_debit_details']['purpose'],
                'notes' => empty($input['notes']) ? null : $input['notes'],
            ];
        };

        return $this->mockMandateHQ($callable, 'createPreDebitNotification');
    }

    public function testCallbackUrlRedirection()
    {

        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mandateConfirm = 'false';

        $this->mockReportPayment();

        $this->paymentInput[Payment::CALLBACK_URL]='https://www.facebook.com';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];

        $this->makeRequestAndGetRawContent($request);
    }

    public function testMerchantIdHeaderAddition()
    {

        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mandateConfirm = 'true';

        $this->mockReportPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];
        $this->makeRequestAndGetContent($request);
    }

    public function testBinNotSupported()
    {
        $this->mockCheckBin();

        $callable = function ($input)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CARD_MANDATE_CARD_NOT_SUPPORTED);
        };

        $this->mockRegisterMandate($callable);

        $this->mockReportPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];

        $exception = false;
        try
        {
            $this->makeRequestAndGetContent($request);
        }
        catch (\Exception $e)
        {
            $this->assertEquals(ErrorCode::BAD_REQUEST_CARD_MANDATE_CARD_NOT_SUPPORTED, $e->getCode());
            $exception = true;
        }

        $this->assertTrue($exception);
    }
    protected function mockCps($terminal, $responder)
    {
        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function(string $method, string $url, array $input) use ($terminal, $responder)
            {
                switch($responder)
                {
                    case 'callback_split':
                        return $this->mockCpsCallbackSplit($method, $url, $input, $terminal);
                }
            });

        $cardService->shouldReceive('sendRequest')
            ->with('GET', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input) use ($terminal, $responder)
            {
                switch ($responder)
                {
                    case 'entity_fetch':
                        return $this->mockCpsEntityFetch($url);
                }
            });
    }

    protected function mockCpsCallbackSplit($method, $url, $input, $terminal)
    {
        $input = $input['input'];
        switch ($url) {
            case 'action/authorize':
            case 'action/callback' :
                return [
                    'data' => [
                        'status' => 'authenticated',
                    ],
                ];
            case 'action/pay':
                if ((isset($input['iin']['iin']) === true) and ($input['iin']['iin'] === '556763'))
                {
                    return [
                        'data'  => null,
                        'error' => [
                            'internal_error_code'       => 'BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE',
                            'gateway_error_code'        => '',
                            'gateway_error_description' => '',
                            'description'               => 'Not sufficient funds'
                        ],
                        'payment' => [],
                        'success' => false,
                    ];
                }
                return [
                    'data' => [
                        'acquirer' => [
                            'reference2' => 'test'
                        ],
                        'two_factor_auth' => 'Y'
                    ],
                    'payment' => [
                        'auth_type' => "3ds",
                    ],
                ];
            default:
                return [
                    'success' => true,
                    'data' => [],
                ];
        }
    }

    protected function mockCpsEntityFetch($url)
    {
        $payment = $this->getDbLastPayment();

        $case1 = 'entity/authentication/'.$payment->getId();
        $case2 = 'entity/authorization/'.$payment->getId();
        switch ($url)
        {
            case $case1:
                return [
                    "id" => "IWwKsyEBb05e21",
                    "created_at" => $payment->getCreatedAt(),
                    "updated_at" => $payment->getUpdatedAt(),
                    "payment_id" => $payment->getId(),
                    "merchant_id" => "8XGbgY6OnlIm6z",
                    "attempt_id" => "IWwKsxawtcsIBm",
                    "status" => "success",
                    "gateway" => "mpi_blade",
                    "terminal_id" => "CXj5Zj9zr8uC7X",
                    "acq_bin" => "",
                    "gateway_merchant_id" => "38RR00000000823",
                    "protocol" => "Mpi_bladeResponse",
                    "protocol_version" => "1.0.2",
                    "enrollment_status" => "Y",
                    "pares_status" => "Y",
                    "acs_url" => "https:\/\/mock-go.func.razorpay.in\/Acs\/mpi",
                    "eci" => "5",
                    "commerce_indicator" => "",
                    "xid" => "MDAwMDAwSVd3S3FISkJKT2psV0o=",
                    "cavv" => "jF1fS0KopYfvCBEDAhxwAmkAAAA=",
                    "cavv_algorithm" => "3",
                    "notes" => "",
                    "error_code" => "",
                    "gateway_error_code" => "",
                    "gateway_error_description" => "",
                    "gateway_transaction_id1" => "062154366958693210908217",
                    "gateway_transaction_id2" => "",
                    "gateway_reference_id1" => "",
                    "gateway_reference_id2" => "",
                    "success" => true,
                    "status_code" => 200
                ];
            case $case2:
                return [
                    "id" => "IWwKwBHY8FXCEH",
                    "created_at" => 1639394284,
                    "updated_at" => 1639394287,
                    "merchant_id" => "8XGbgY6OnlIm6z",
                    "payment_id" => "IWwKqHJBJOjlWJ",
                    "verify_id" => "",
                    "recon_id" => "",
                    "acquirer" => "ratn",
                    "gateway" => "hitachi",
                    "gateway_merchant_id" => "38RR00000000823",
                    "action" => "pay",
                    "amount" => 600,
                    "currency" => "INR",
                    "narration" => "",
                    "gateway_transaction_id" => "IWwKwBFnz9HQy9",
                    "gateway_reference_id1" => "",
                    "gateway_reference_id2" => "",
                    "cavv_algorithm" => "",
                    "status" => "authorized",
                    "notes" => "",
                    "auth_code" => "000235",
                    "rrn" => "000011841449",
                    "arn" => "",
                    "reason_code" => "00",
                    "avs_response_code" => "",
                    "cvc_response_code" => "",
                    "risk_result" => "",
                    "switch_response_code" => "",
                    "error_code" => "",
                    "gateway_error_code" => "",
                    "gateway_error_description" => "",
                    "acs_transaction_id" => "",
                    "gateway_payment_id" => "",
                    "avs_result" => "",
                    "network_transaction_id" => "",
                    "success" => true,
                    "status_code" => 200,
                    "data" => "{\"si_registration_id\":\"ratn_PP3VC146gmBVGG\"}"
                ];
            default:
                return [
                    'error' => 'CORE_FAILED_TO_FIND_MODEL',
                    'success' => false,
                ];
        }
    }

    public function testUserMandateCancel()
    {
        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mandateConfirm = 'false';

        $this->mockReportPayment();

        $request = [
            'method' => 'POST',
            'url' => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];

        $exception = false;
        try {
            $this->makeRequestAndGetContent($request);
        } catch (Exception\BadRequestException $e) {
            $this->assertEquals(ErrorCode::BAD_REQUEST_CARD_MANDATE_CANCELLED_BY_USER, $e->getCode());
            $this->assertArrayKeysExist($e->getData(), ['payment_id', 'order_id', 'method']);
            $this->assertEquals('Card mandate created for payment has been cancelled by user', $e->getMessage());
            $exception = true;
        }
        assertTrue($exception);
        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertEquals('mandate_cancelled', $cardMandate->getStatus());
    }

    public function testPreDebitNotifyWithPaymentId()
    {
        $this->testCreateSplitAuthenticatePayment();

        $token = $this->getDbLastEntity('token');

        $request = [
            "url" => "/tokens/" . $token->getPublicId() . "/pre_debit/notify",
            "method" => "post",
            "content" => [
                'debit_at' => Carbon::now()->addDays(2)->timestamp,
                'amount'   => 50000,
                'currency' => 'INR',
                'purpose'  => 'test debit',
                'payment_id' => 'pay12345',
                'notes' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ]
            ],
        ];

        $this->ba->privateAuth();

        $this->mockCreatePreDebitNotification();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('notified', $response['status']);
        $this->assertNotEmpty($response['id']);

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');

        $this->assertNull($cardMandateNotification->reminder_id);
    }

    public function testSIHUBCardMandateFlow()
    {
        $this->runSIHubCreateCardMandateAutoPayment();

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, $token->getPublicId());

        $this->ba->proxyAuth();

        $this->startTest();

        $cardMandate = $this->getDbLastEntity(Entity::CARD_MANDATE);

        $this->assertEquals('cancelled', $cardMandate->getStatus());
    }

    protected function mockMandateHQ($callable = null, $method = 'registerMandate')
    {
        $this->mandateHQ->shouldReceive($method)
            ->andReturnUsing($callable);
    }

    protected function mockReminder($success = false)
    {
        $reminders = Mockery::mock('RZP\Services\Reminders')->makePartial();

        $this->app->instance('reminders', $reminders);

        $reminders->shouldReceive('createReminder')
            ->with(Mockery::type('array'), Mockery::type('string'))
            ->andReturnUsing(function ($request, $merchantId) use ($success) {
                if (!$success) {
                    throw new BadRequestValidationFailureException('reminder create failed');
                }

                $this->mockedRemindersRequest = [$request, $merchantId];

                $response = [
                    'success'   => true
                ];

                return $response;
            });

        $this->app->instance('reminders', $reminders);
    }

    protected function setMockRazorxTreatment(array $razorxTreatment, string $defaultBehaviour = 'off')
    {
        // Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                        function ($mid, $feature, $mode) use ($razorxTreatment, $defaultBehaviour)
                        {
                            if (array_key_exists($feature, $razorxTreatment) === true)
                            {
                                return $razorxTreatment[$feature];
                            }

                            return strtolower($defaultBehaviour);
                        }));
    }

    public function testUseRecurringTokenisedSavedCardForSIHubPayment()
    {
        $this->mockCardVaultWithMigrateToken();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $this->setMockRazorxTreatment(['recurring_tokenisation' => 'on']);

        $this->fixtures->create('terminal:shared_billdesk_sihub_terminal');

        $this->fixtures->terminal->disableTerminal($this->mandateHqTerminal['id']);

        $this->fixtures->edit('iin', '411111',[
            'mandate_hubs' => ['mandate_hq'=>'1', 'billdesk_sihub'=>'1'],
        ]);

        $this->fixtures->merchant->addFeatures(['allow_billdesk_sihub']);

        $this->paymentInput['card']['number'] = '4111111111111111';

        $paymentInput = $this->paymentInput;
        $paymentInput['_']['library'] = 'razorpayjs';
        $paymentInput['save'] = 1;
        $paymentInput['user_consent_for_tokenisation'] = 1;
        $paymentInput['recurring'] = 1;
        $paymentInput['customer_id'] = 'cust_100000customer';

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->mockCps(null, 'entity_fetch');

        $this->mockCheckBin();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $paymentInput,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['razorpay_payment_id'] ?? null);

        $freshCardPayment = $this->getDbLastEntity(E::PAYMENT);

        $actualCard = $freshCardPayment->card;

        $freshCardtoken = $freshCardPayment->localToken;

        $tokenisedCard = $freshCardPayment->localToken->card;

        $this->assertTrue($tokenisedCard->isRzpSavedCard() === false);
        $this->assertTrue($actualCard->isRzpSavedCard() === true);
        $this->assertTrue($freshCardtoken->isRecurring() === true);

        $paymentInput[Payment::CARD] = [];
        $paymentInput[Payment::TOKEN] = $freshCardtoken->getPublicId();
        $paymentInput[Payment::CARD] = array('cvv'  => 111);

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);

        unset($paymentInput['order_id']);
        unset($paymentInput['save']);
        $paymentInput['order_id'] = $order->getPublicId();

        $this->mockCardVaultWithCryptogram();

        $this->doAuthPayment($paymentInput);

        $savedCardPayment = $this->getDbLastEntity(E::PAYMENT);

        $tokenisedSavedCardForPayment = $savedCardPayment->card;
        $tokenisedSavedCard = $savedCardPayment->localToken->card;
        $savedCardToken = $savedCardPayment->localToken;

        $this->assertTrue($tokenisedSavedCardForPayment->isRzpSavedCard() === true);
        $this->assertTrue($tokenisedSavedCard->isRzpSavedCard() === false);
        $this->assertTrue($savedCardToken->isRecurring() === true);

        $this->assertEquals($tokenisedCard->getId(), $tokenisedSavedCard->getId());
        $this->assertNotEquals($freshCardtoken->getId(), $savedCardToken->getId());
    }

    public function testCreateRupaySICardMandatePaymentWithAuthLink()
    {
        $this->ba->proxyAuth();
        $this->startTest();

        $order = $this->getDbLastEntity('order');

        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $this->fixtures->edit('iin', 400018 ,[
            'network' => "RuPay",
            'mandate_hubs' => ['rupay_sihub'=>'1'],
        ]);

        $this->fixtures->create('terminal:shared_rupay_sihub_terminal');

        $this->fixtures->terminal->disableTerminal($this->mandateHqTerminal['id']);

        $this->setMockRazorxTreatment(['recurring_through_rupay_card_mid' => 'on',
            'recurring_through_rupay_card_iin' => 'on']);

        $this->mockCps(null, 'entity_fetch');

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];

        $request['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['razorpay_payment_id'] ?? null);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());

        $token = $payment->localToken;
        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());
        $this->assertEquals(123400, $token->getMaxAmount());

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertNotEmpty($cardMandate->getMandateSummaryUrl());
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandate->getMandateId());
        $this->assertEquals(123400, $cardMandate->getMaxAmount());
    }

    public function testRupayHubCreateCardMandateAutoPayment()
    {
        $this->testCreateRupaySICardMandatePaymentWithAuthLink();

        $this->mockCreatePreDebitNotification();

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($paymentInput);
        $this->assertNotEmpty($content['razorpay_payment_id']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('auto', $payment->getRecurringType());
        $this->assertEquals('created', $payment->getStatus());

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandateNotification->notification_id);
        $this->assertNotNull($cardMandateNotification->reminder_id);
        $this->assertNotEmpty($cardMandateNotification->notified_at);

        $this->mockPostDebitNotification();
        $this->mockValidatePayment();

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, $payment->getId());
        $this->ba->reminderAppAuth();

        $this->startTest();

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('captured', $payment->getStatus());
    }
}

