<?php

namespace RZP\Tests\Functional\Merchant;

use Mail;

use RZP\Mail\Merchant\CreditsAdditionSuccess;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Traits\TestsWebhookEvents;

class SelfServeCreditsTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/SelfServeCreditsTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    //Tests for fund Addition via Orders

    public function testCreateOrderForRefundCreditAddition()
    {
        $this->fixtures->merchant->enableTPV();

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.refund_credit.merchant_id', '10000000000001');

        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $bankAccount = $this->getDbLastEntity('bank_account');

        $this->fixtures->edit('bank_account', $bankAccount->getId(), ['ifsc' => 'ORBC0101685', 'beneficiary_name' => 'testBankAccount']);

        $this->ba->proxyAuth();

        $this->startTest();

    }

    public function testCreateOrderForFeeCreditAddition()
    {
        $this->fixtures->merchant->enableTPV();

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.fee_credit.merchant_id', '10000000000001');

        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $bankAccount = $this->getDbLastEntity('bank_account');

        $this->fixtures->edit('bank_account', $bankAccount->getId(), ['ifsc' => 'ORBC0101685', 'beneficiary_name' => 'testBankAccount']);

        $this->ba->proxyAuth();

        $this->startTest();

    }

    public function testCreateOrderForReserveBalanceAddition()
    {
        $this->fixtures->merchant->enableTPV();

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.reserve_balance.merchant_id', '10000000000001');

        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $bankAccount = $this->getDbLastEntity('bank_account');

        $this->fixtures->edit('bank_account', $bankAccount->getId(), ['ifsc' => 'ORBC0101685', 'beneficiary_name' => 'testBankAccount']);

        $this->ba->proxyAuth();

        $this->startTest();

    }

    public function testCreateOrderAndMakePaymentForFeeCreditAddition()
    {

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->gateway = 'upi_icici';

        $terminal = $this->fixtures->create('terminal:shared_upi_icici_tpv_terminal', ['tpv' => 3]);

        $this->fixtures->merchant->enableTPV();

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.refund_credit.merchant_id', '10000000000000');

        $this->fixtures->edit('methods', '10000000000000', ['upi' => true]);

        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $this->fixtures->create('key', ['merchant_id' => '10000000000001']);

        $key = $this->getDbLastEntity('key');

        $bankAccount = $this->getDbLastEntity('bank_account');

        $this->fixtures->edit('bank_account', $bankAccount->getId(), ['ifsc' => 'IDFB0080181', 'beneficiary_name' => 'randomAccount']);

        $this->ba->proxyAuth();

        $this->startTest();

        $order = $this->getDbLastEntity('order');

        $this->payment['amount'] = $order->getAmount();

        $this->payment['order_id'] = $order->getPublicId();

        $payment = $this->doAuthPayment($this->payment);

        $payment = $this->getDbEntityById('payment', $payment['payment_id']);

        $upiEntity = $this->getLastEntity('upi', true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment->toArrayPublic());

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $this->assertEquals($payment['terminal_id'], $terminal->getId());

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('upi', true);
    }

    public function testCreateOrderAndMakePaymentForRefundCreditAdditionWithWebhook()
    {

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->gateway = 'upi_icici';

        $terminal = $this->fixtures->create('terminal:shared_upi_icici_tpv_terminal', ['tpv' => 3]);

        $this->fixtures->merchant->enableTPV();

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.refund_credit.merchant_id', '10000000000000');

        $this->fixtures->edit('methods', '10000000000000', ['upi' => true]);

        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $this->fixtures->create('key', ['merchant_id' => '10000000000001']);

        $key = $this->getDbLastEntity('key');

        $bankAccount = $this->getDbLastEntity('bank_account');

        $this->fixtures->edit('bank_account', $bankAccount->getId(), ['ifsc' => 'IDFB0080181', 'beneficiary_name' => 'randomAccount']);

        $this->ba->proxyAuth();

        $this->startTest();

        $order = $this->getDbLastEntity('order');

        $this->testData['orderPaidWebhookEventData']['event']['payload']['order']['entity']['id'] = $order->getPublicId();

        $expectedEvent = $this->testData['orderPaidWebhookEventData']['event'];

        $this->expectWebhookEvent(
            'order.paid',
            function (array $event) use ($expectedEvent)
            {
                $this->assertArrayHasKey('payload', $event);
                $this->assertEquals($expectedEvent['payload']['order']['entity']['notes']['merchant_id'], $event['payload']['order']['entity']['notes']['merchant_id']);
                $this->assertEquals($expectedEvent['payload']['order']['entity']['notes']['type'], $event['payload']['order']['entity']['notes']['type']);
                $this->assertEquals($expectedEvent['payload']['order']['entity']['id'], $event['payload']['order']['entity']['id']);

            }
        );

        $this->testData['orderPaidWebhookEventData']['event']['payload']['order']['id'] = $order->getPublicId();

        $this->payment['amount'] = $order->getAmount();

        $this->payment['order_id'] = $order->getPublicId();

        $payment = $this->doAuthPayment($this->payment);

        $payment = $this->getDbEntityById('payment', $payment['payment_id']);

        $upiEntity = $this->getLastEntity('upi', true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment->toArrayPublic());

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $this->assertEquals($payment['terminal_id'], $terminal->getId());

        $order = $this->getDbLastEntity('order');

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('upi', true);

    }

    public function testCreateOrder($key= null, $merchant_id='10000000000000')
    {
        $this->testData[__FUNCTION__]['request']['content']['notes']['merchant_id'] = $merchant_id;

        $this->testData[__FUNCTION__]['response']['content']['notes']['merchant_id'] = $merchant_id;

        $this->ba->privateAuth($key);

        $order = $this->startTest();

        return $order;
    }

    public function testFundAdditionWebhookWithInvalidOrderStatusForRefundcredits()
    {
        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.refund_credit.merchant_id', '10000000000001');

        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $key = $this->fixtures->create('key', ['merchant_id' => '10000000000001']);

        $order = $this->testCreateOrder('rzp_test_'.$key->getKey());

        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['payload']['order']['entity']['id'] = $order['id'];

        $this->startTest();
    }

    public function testFundAdditionWebhookWithInvalidOrderStatusForFeeCredits()
    {
        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.fee_credit.merchant_id', '10000000000001');

        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $key = $this->fixtures->create('key', ['merchant_id' => '10000000000001']);

        $order = $this->testCreateOrder('rzp_test_'.$key->getKey());

        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['payload']['order']['entity']['id'] = $order['id'];

        $this->startTest();
    }

    public function testFundAdditionWebhookWithTamperedAmountForRefundCredits()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_billdesk_terminal');

        $this->gateway = 'billdesk';

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.refund_credit.merchant_id', '10000000000000');

        $order = $this->testCreateOrder();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['order_id'] = $order['id'];

        $payment['amount'] = $order['amount'];

        $this->doAuthPayment($payment);

        $payment = $this->getDbLastEntity('payment');

        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['payload']['order']['entity']['id'] = $order['id'];

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['id'] = $payment->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['order_id'] = $order['id'];

        $this->startTest();
    }

    public function createAndMakePayment($order)
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_billdesk_terminal');

        $this->gateway = 'billdesk';

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['order_id'] = $order->getPublicId();

        $payment['amount'] = $order->getAmount();

        $this->doAuthPayment($payment);
    }

    public function testFundAdditionWebhookWithFundAlreadyAddedForOrder()
    {

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.refund_credit.merchant_id', '10000000000000');

        $this->testCreateOrder();

        $order = $this->getDbLastEntity('order');

        $this->createAndMakePayment($order);

        $payment = $this->getDbLastEntity('payment');

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => $order->getPublicId(), 'type' => 'fee']);

        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['payload']['order']['entity']['id'] = $order->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['id'] = $payment->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testFundAdditionWebhookWithFundAlreadyAddedForReserveBalance()
    {

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.reserve_balance.merchant_id', '10000000000000');

        $this->testCreateOrder();

        $order = $this->getDbLastEntity('order');

        $this->createAndMakePayment($order);

        $payment = $this->getDbLastEntity('payment');

        $this->fixtures->create('adjustment', ['merchant_id' => '10000000000000', 'amount' => 100 , 'description' => 'Reserve Balance|'.$order->getPublicId(), 'transaction_id' => $payment->getTransactionId()]);

        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['payload']['order']['entity']['id'] = $order->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['id'] = $payment->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testFundAdditionWebhookWithFundAdditionInCredits()
    {
        Mail::fake();

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.refund_credit.merchant_id', '10000000000000');

        $this->testCreateOrder(null, '10000000000001');

        $order = $this->getDbLastEntity('order');

        $this->createAndMakePayment($order);

        $payment = $this->getDbLastEntity('payment');

        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $key = $this->fixtures->create('key', ['merchant_id' => '10000000000001']);

        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 1000,
                'type'          => 'primary',
                'merchant_id'   => '10000000000001'
            ]
        );

        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['payload']['order']['entity']['id'] = $order->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['id'] = $payment->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['order_id'] = $order->getPublicId();

        $this->startTest();

        $credit = $this->getDbLastEntity('credits');

        $this->assertEquals($order->getPublicId(), $credit->getCampaign());

        $this->assertEquals('10000000000001', $credit->getMerchantId());

        $this->assertEquals($payment->getAmount() - $payment->getFee(), $credit->getValue());

        $balance = $this->getDbEntityById('balance', '100def000def00');

        $this->assertEquals($payment->getAmount() - $payment->getFee(), $balance->getRefundCredits());

        Mail::assertQueued(CreditsAdditionSuccess::class, function ($mail)
        {
            $this->assertEquals("10000000000001", $mail->viewData['merchant_id']);
            $this->assertEquals("Refund Credit",  $mail->viewData['account_type']);
            return true;
        });
    }

    public function testCreateOrderForRefundCreditAdditionIfBankAccountDoesNotExist()
    {
        $this->fixtures->merchant->enableTPV();

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.refund_credit.merchant_id', '10000000000002');

        $this->fixtures->create('merchant', ['id' => '10000000000002']);

        $user = $this->fixtures->user->createUserForMerchant('10000000000002');

        $this->ba->proxyAuth('rzp_test_10000000000002', $user->getId());

        $this->startTest();

    }

    public function testFundAdditionWebhookWithFundAdditionInCreditsWithEmptyNotes()
    {
        Mail::fake();

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.refund_credit.merchant_id', '10000000000000');

        $this->testCreateOrder(null, '10000000000001');

        $order = $this->getDbLastEntity('order');

        $this->createAndMakePayment($order);

        $payment = $this->getDbLastEntity('payment');

        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $key = $this->fixtures->create('key', ['merchant_id' => '10000000000001']);

        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 1000,
                'type'          => 'primary',
                'merchant_id'   => '10000000000001'
            ]
        );

        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['payload']['order']['entity']['id'] = $order->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['id'] = $payment->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testFundAdditionWebhookWithFundAdditionInCreditsWithFeeMoreThanAmount()
    {
        Mail::fake();

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.refund_credit.merchant_id', '10000000000000');

        $order = $this->fixtures->create('order',['amount' => 100, 'status' => 'paid', 'notes' => ['merchant_id' => '10000000000001', 'type' => 'refund_credit']]);

        $payment = $this->fixtures->create('payment',['amount' => 100, 'status' => 'captured','order_id' => $order->getId(), 'fee' => 110 ]);

        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['payload']['order']['entity']['id'] = $order->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['id'] = $payment->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testFundAdditionWebhookWithFundAdditionInReserveBalanceWithFeeMoreThanAmount()
    {
        Mail::fake();

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.reserve_balance.merchant_id', '10000000000000');

        $order = $this->fixtures->create('order',['amount' => 100, 'status' => 'paid', 'notes' => ['merchant_id' => '10000000000001', 'type' => 'reserve_balance']]);

        $payment = $this->fixtures->create('payment',['amount' => 100, 'status' => 'captured','order_id' => $order->getId(), 'fee' => 110 ]);

        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['payload']['order']['entity']['id'] = $order->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['id'] = $payment->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['order_id'] = $order->getPublicId();

        $this->startTest();
    }
}
