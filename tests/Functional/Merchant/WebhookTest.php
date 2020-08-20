<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Models\Feature;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\FundTransfer\Attempt;
use RZP\Tests\Traits\TestsWebhookEvents;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;

class WebhookTest extends TestCase
{
    use AttemptTrait;
    use AttemptReconcileTrait;
    use WebhookTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use PartnerTrait;
    use TestsBusinessBanking;

    protected $sharedTerminal;

    // Used in webhook trait
    protected $storkMock;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/WebhookData.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);

        $this->ba->proxyAuth();

        $this->mockStorkService();
    }

    /*
     * Partner type reseller, cannot create webhook
     */
    public function testCreateAppWebhookInvalidPartnerType()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'reseller']);

        $this->startTest();
    }

    /*
     * Partner type pure platform, can create webhook
     */
    public function testCreateAppWebhookPurePlatform()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'pure_platform']);
        $this->addOAuthTag();
        $this->createOAuthApplication(['id' => '10000000000App', 'merchant_id' => '10000000000000']);

        $this->startTest();
    }

    /*
     * Not partner yet but tagged OAuth, can create webhook
     */
    public function testCreateAppWebhookOAuthTag()
    {
        $this->addOAuthTag();
        $this->createOAuthApplication(['id' => '10000000000App', 'merchant_id' => '10000000000000']);

        $this->startTest();
    }

    /*
     * Partner type bank, also tagged OAuth, cannot create webhook
     * as this should ideally not happen and we should prevent by default
     */
    public function testCreateAppWebhookBankWithOAuthTag()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'bank']);
        $this->addOAuthTag();
        $this->createOAuthApplication(['id' => '10000000000App', 'merchant_id' => '10000000000000']);

        $this->startTest();
    }

    /*
     * Partner type fully managed, can create webhook irrespective
     * of the oauth tag
     */
    public function testCreateAppWebhookFullyManagedWithOAuthTag()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);
        $this->addOAuthTag();
        $this->createOAuthApplication(['id' => '10000000000App', 'merchant_id' => '10000000000000']);

        $this->startTest();
    }

    public function testCreateWebhookForProductBankingWithInvalidEventsWithStork()
    {
        $this->fixtures->merchant->addFeatures(['payout']);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->mockServiceStorkRequest(
            function ($path, $payload)
            {
                return $this->getStorkListResponseEmpty();
            })->times(1);

        $this->startTest();
    }

    public function testEditWebhookByNonOwnerUser()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user->id,
            'merchant_id' => '10000000000000',
            'role'        => 'support',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->toArrayPublic(), 'support');

        $this->startTest();
    }

    public function testEditWebhookForProductBankingWithInvalidEvents()
    {
        $this->startTest();
    }

    public function testGetWebhookEvents()
    {
        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $response = $this->startTest();

        $this->assertContains('order.paid', $response);
        $this->assertContains('virtual_account.credited', $response);
        $this->assertNotContains('subscription.charged', $response);

        // Events of other products (e.g. banking) should not come in response.
        $this->assertNotContains('transaction.created', $response);
        $this->assertNotContains('payout.created', $response);
        $this->assertNotContains('payout.processed', $response);
        $this->assertNotContains('payout.reversed', $response);
    }

    public function testGetWebhookEventsForProductBanking()
    {
        // This is required, because this is going to on board the merchant on X on the test mode
        // which requires the terminal entity to be present
        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
            ['merchant_id' => '100000Razorpay']);

        $this->fixtures->merchant->addFeatures(['payout']);

        $this->startTest();
    }

    public function testWebhookEventData()
    {
        $this->expectWebhookEventWithContents('payment.authorized', __FUNCTION__);

        $this->doAuthPayment();
    }

    public function testInvoicePaidWebhookEventData()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];

        $this->expectWebhookEvent(
            'invoice.paid',
            function (array $event) use ($expectedEvent)
            {
                $this->assertArraySelectiveEquals($expectedEvent, $event);
                $this->assertArrayNotHasKey('terminal_id', $event['payload']['payment']['entity']);
            }
        );

        $order = $this->fixtures->create('order',
                    [
                        'id'              => '100000000order',
                        'receipt'         => 'random',
                        'payment_capture' => true,
                    ]);

        $this->fixtures->create('invoice', ['amount' => 1000000]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();

        $this->doAuthPayment($payment);
    }

     /**
     * If partner parent has feature "terminal_onboarding" enabled, then only payment entity should have terminal_id key
     */
    public function testPaymentWebhookShouldHaveTerminalIdForFeaturedPartner()
    {
        $partner = $this->fixtures->create('merchant');

        $partnerId = $partner->getId();

        $this->fixtures->edit('merchant', $partnerId, ['partner_type' => 'aggregator']);

        // Assign submerchant to partner
        $accessMapData = [
            'entity_type'     => 'application',
            'merchant_id'     => '10000000000000',
            'entity_owner_id' => $partnerId,
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        $this->fixtures->merchant->addFeatures(
            [Feature\Constants::TERMINAL_ONBOARDING],
            $partnerId
        );

        $expectedEvent = $this->testData['testInvoicePaidWebhookEventData']['event'];

        $expectedEvent['payload']['payment']['entity']['terminal_id'] = 'term_1n25f6uN5S1Z5a';

        $this->expectWebhookEventWithContents('invoice.paid', $expectedEvent);

        $order = $this->fixtures->create('order',
                    [
                        'id'              => '100000000order',
                        'receipt'         => 'random',
                        'payment_capture' => true,
                    ]);

        $this->fixtures->create('invoice', ['amount' => 1000000]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();

        $this->doAuthPayment($payment);
    }

    /**
     * Invoice created without customer details, once paid should contain those
     * information in invoices entity and same should be sent as hook payload.
     *
     */
    public function testInvoiceWithoutCustomerDetailsPaidWebhookEventData()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];

        $this->expectWebhookEventWithContents('invoice.paid', $expectedEvent);

        $order = $this->fixtures->create('order',
                    [
                        'id'              => '100000000order',
                        'receipt'         => 'random',
                        'payment_capture' => true,
                    ]);

        $this->fixtures->create('invoice',
            [
                'amount'           => 1000000,
                'customer_id'      => null,
                'customer_name'    => null,
                'customer_email'   => null,
                'customer_contact' => null,
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();

        $this->doAuthPayment($payment);
    }

    public function testInvoicePaidWebhookEventDataWithOrderAndWithoutInvoice()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];

        // This webhook will be called for order.paid event.
        $this->expectWebhookEvent(
            'order.paid',
            function (array $event) use ($expectedEvent)
            {
                $this->assertArraySelectiveEquals($expectedEvent, $event);
                $this->assertArrayNotHasKey('invoice', $event['payload']);
            }
        );

        $order = $this->fixtures->create('order', ['amount' => 50000, 'receipt' => 'random']);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();

        $this->doAuthAndCapturePayment($payment);
    }

    public function testCreateWebhookWithEventWhenFeatureNotEnabled()
    {
        $this->startTest();
    }

    public function testWebhooksFeatureBasedEvents()
    {
        // Adds feature and creates webhook with subscriptions.charged even and asserts the same in next get call.
        $this->fixtures->merchant->addFeatures(['subscriptions']);

        $this->startTest();
    }

    public function testWebhookEventWithExpressTranslationEnabled()
    {
        $translatedWebhookBody = 'sample translated webhook body';

        // mark as partner
        $partnerId     = '100000Razorpay';
        $client        = $this->setUpPartnerMerchantAppAndGetClient('dev', [], $partnerId);
        $submerchantId = '10000000000000';

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $submerchantId,
                'entity_owner_id' => $partnerId,
            ]
        );

        $app = DB::Connection('auth')
                 ->table('applications')
                 ->orderBy('created_at', 'desc')
                 ->first();

        // create setting for translation url
        $this->ba->adminAuth();
        $this->fixtures->edit('admin', 'RzrpySprAdmnId', ['allow_all_merchants' => 1]);
        $this->ba->addAccountAuth($partnerId);

        $testData = $this->testData['createSettingsForWebhookTranslateUrl'];

        $this->runRequestResponseFlow($testData);

        $this->ba->deleteAccountAuth();

        $payment  = $this->getDefaultPaymentArray();

        // mock mozart webhook translate requests
        $this->mockMozartWebhookTranslateRequest(function ($path, $content) use ($translatedWebhookBody) {

            return [
                'content'   => $translatedWebhookBody,
                'headers'   => ['request-id' => ['12345678']],
            ];
        }, 2);

        $this->expectWebhookEvent(
            'payment.authorized',
            function ($body) use ($translatedWebhookBody)
            {
                $this->assertEquals($translatedWebhookBody, $body);
            }
        );

        // make payment on submerchant
        $this->doPartnerAuthPayment($payment, $client->getId(), $submerchantId);
    }

    public function testWebhookEventWithExpressTranslationNotEnabled()
    {
        $this->ba->privateAuth();

        $this->mockMozartWebhookTranslateRequest(null, 0);

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);
    }

    public function testWebhookPaymentCreatedForJsonp()
    {
        $this->expectWebhookEvent('payment.created');

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);
    }

    public function testWebhookPaymentCreatedForAuth()
    {
        $this->expectWebhookEvent('payment.created');

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthPayment($payment);
    }

    public function testWebhookPaymentCreatedForAjax()
    {
        $this->expectWebhookEvent('payment.created');

        $this->gateway = 'upi_hulk';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_hulk_terminal');

        $this->gateway = 'upi_hulk';

        $payment = $this->getDefaultUpiPaymentArray();

        $this->doAuthPaymentViaAjaxRoute($payment);
    }

    public function testWebhookPaymentCreatedForCheckout()
    {
        $this->expectWebhookEvent('payment.created');

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthPaymentViaCheckoutRoute($payment);
    }

    public function testWebhookPaymentCreatedForS2SPrivateAuth()
    {
        $this->fixtures->merchant->addFeatures(['s2s']);

        $this->ba->privateAuth();

        $this->mockCardVault();

        $this->expectWebhookEvent('payment.created');

        $this->doS2SPrivateAuthPayment();
    }

    public function testWebhookPaymentCreatedForCustomerFee()
    {
        $this->fixtures->merchant->addFeatures(['s2s']);

        $this->fixtures->merchant->enableConvenienceFeeModel();

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $this->ba->privateAuth();

        $this->mockCardVault();

        $this->dontExpectAnyWebhookEvent();

        $this->createAndGetFeesForPayment();
    }

    public function testOrderPaidWebhookEventData()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];

        $this->expectWebhookEventWithContents('order.paid', $expectedEvent);

        $order = $this->fixtures->create('order', ['amount' => 50000, 'receipt' => 'random']);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $this->doAuthAndCapturePayment($payment);
    }

    public function testOrderPaidWebhookEventDataWithoutOrder()
    {
        $this->dontExpectWebhookEvent('order.paid');

        $this->doAuthAndCapturePayment();
    }

    /**
     * Tests if a webhook is triggered to the merchant when a settlement is processed.
     */
    public function testTransferSettlementWebhook()
    {
        $this->ba->privateAuth();

        $channel = Settlement\Channel::ICICI;

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $payment = $this->createPaymentEntities(1);

        $account2 = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);
        $this->fixtures->merchant->edit('10000000000002', ['channel' => $channel]);

        $this->createTransferEntity($payment, $account2);

        $account3 = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000003']);
        $this->fixtures->merchant->edit('10000000000003', ['channel' => $channel]);

        $this->createTransferEntity($payment, $account3);

        $expectedEvent = $this->testData[__FUNCTION__]['event'];

        // Expects settlement.processed on account with id acc_10000000000002.
        $this->expectWebhookEvent(
            'settlement.processed',
            function (array $event) use ($expectedEvent)
            {
                $this->assertArrayHasKey('account_id', $event);
                $this->assertEquals('acc_10000000000002', $event['account_id']);
                $this->assertEquals('settlement.processed', $event['event']);
                $this->assertArraySelectiveEquals($expectedEvent, $event);
            }
        );
        // Expects settlement.processed on account with id acc_10000000000003.
        $this->expectWebhookEvent(
            'settlement.processed',
            function (array $event) use ($expectedEvent)
            {
                $this->assertArrayHasKey('account_id', $event);
                $this->assertEquals('acc_10000000000003', $event['account_id']);
                $this->assertEquals('settlement.processed', $event['event']);
                $this->assertArraySelectiveEquals($expectedEvent, $event);
            }
        );

        $this->initiateSettlements($channel);

        $content = $this->initiateTransfer($channel,
            Attempt\Purpose::SETTLEMENT,
            Attempt\Type::SETTLEMENT);

        $setlFile = $content[$channel]['file']['local_file_path'];

        $this->reconcileSettlementsForChannel($setlFile, $channel, false);

        $this->reconcileEntitiesForChannel($channel);

        $this->reconcileEntitiesForChannel($channel);
    }

    public function testWebhookOnSettlementFailure()
    {
        $channel = Settlement\Channel::ICICI;

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $this->createPaymentAndRefundEntities(2);

        $this->initiateSettlements($channel);

        $content = $this->initiateTransfer($channel,
            Attempt\Purpose::SETTLEMENT,
            Attempt\Type::SETTLEMENT);

        $setlFile = $content[$channel]['file']['local_file_path'];

        $this->reconcileSettlementsForChannel($setlFile, $channel, true);

        $this->dontExpectAnyWebhookEvent();

        $this->reconcileEntitiesForChannel($channel);
    }

    public function testRefundSpeedChangedWebhookEventData()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            if ($action === 'refund') {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEvent(
            'refund.speed_changed',
            function (array $event) use ($expectedEvent)
            {
                $this->assertArraySelectiveEquals($expectedEvent, $event);
                $this->assertArrayHasKey('created_at', $event);
            }
        );

        // Adding specific amount to refund - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        $this->refundPayment($payment['id'], 3470, ['speed' => 'optimum', 'is_fta' => true]);
    }

    public function testRefundFailedWebhookEventData()
    {
        $this->fixtures->merchant->addFeatures(['show_refund_public_status']);

        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEventWithContents('refund.failed', $expectedEvent);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            if($action === 'refund')
            {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        // Adding specific amount to refund - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        $refund = $this->refundPayment($payment['id'], 3459);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals('reversed', $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals($reversal['entity_type'], 'refund');
        $this->assertEquals('rfnd_'.$reversal['entity_id'], $refund['id']);
        $this->assertNotNull($reversal['balance_id']);
    }

    public function testRefundProcessedInstantWebhookEventData()
    {
        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);
        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEventWithContents('refund.processed', $expectedEvent);

        // Adding specific amount to refund - this is meant to test processed instant refunds on scrooge -
        $this->refundPayment($payment['id'], 3471, ['speed' => 'optimum', 'is_fta' => true]);
    }

    public function testRefundProcessedNormalWebhookEventData()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEventWithContents('refund.processed', $expectedEvent);

        $this->refundPayment($payment['id']);
    }

    public function testRefundCreatedWebhookEventData()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEventWithContents('refund.created', $expectedEvent);

        $this->refundPayment($payment['id']);
    }

    // We fire webhook terminal.created to aggregators 45 min after terminal is created
    // We are using reminders service for this
    public function testTerminalCreatedReminderWebhook()
    {
        $subMerchant = $this->fixtures->create('merchant');

        $terminal = $this->fixtures->create('terminal',
        [
            'merchant_id' => $subMerchant->getId(),
            'enabled'     => true,
            'gateway'     => 'worldline',
            'status'      => 'pending',
            'mc_mpan'     => base64_encode('1234567890123456'),
            'visa_mpan'   => base64_encode('9876543210123456'),
            'rupay_mpan'  => base64_encode('1234123412341234'),
        ]);

        $expectedEvent = $this->testData[__FUNCTION__.'Data']['event'];
        $this->expectWebhookEventWithContents('terminal.created', $expectedEvent);

        $this->testData[__FUNCTION__]['request']['url'] = '/reminders/send/test/terminal/terminal_created_webhook/' . $terminal->getId();
        $this->startTest();
    }

    public function testTerminalOnboardingStatusActivatedWebhook()
    {
        $subMerchant = $this->fixtures->create('merchant');

        $subMerchantId = $subMerchant->getId();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        // Assign submerchant to partner
        $accessMapData = [
            'entity_type'     => 'application',
            'merchant_id'     => $subMerchantId,
            'entity_owner_id' => '10000000000000',
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        $subMerchant->setCategory("742");

        $subMerchant->save();

        $this->fixtures->merchant->addFeatures(
            [Feature\Constants::TERMINAL_ONBOARDING],
            '10000000000000'
        );

        $terminal = $this->fixtures->create('terminal',
            [
                'merchant_id' => $subMerchantId,
                'enabled'     => false,
                'gateway'     => 'worldline',
                'status'      => 'pending'
            ]);

        $this->ba->adminAuth();

        $expectedEvent = $this->testData[__FUNCTION__.'Data']['event'];
        $this->expectWebhookEventWithContents('terminal.activated', $expectedEvent);

        $this->testData[__FUNCTION__]['request']['content'] = [
            'terminal_ids' => [$terminal->getId()] ,
            'attributes'   => ['status' => 'activated', 'enabled' => true]
        ];

        $this->startTest();
    }

    protected function createTransferEntity($payment, $account)
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(20)->timestamp + 5;

        $this->fixtures->create('transfer:to_account',
            [
                'account'       => $account,
                'source_id'     => $payment->getId(),
                'source_type'   => 'payment',
                'amount'        => 2500,
                'currency'      => 'INR',
                'on_hold'       => '0',
                'on_hold_until' => Carbon::today(Timezone::IST)->timestamp - 600,
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt + 10
            ]);
    }

    protected function addOAuthTag(string $merchantId = '10000000000000')
    {
        $merchant = Merchant\Entity::find($merchantId);
        $merchant->reTag(["oauth"]);
        $merchant->saveOrFail();
    }
}
