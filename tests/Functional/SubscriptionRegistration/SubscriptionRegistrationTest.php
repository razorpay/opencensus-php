<?php

namespace RZP\Tests\Functional\SubscriptionRegistration;

use Mail;
use Queue;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Constants\Entity as E;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Invoice\InvoiceTestTrait;

class SubscriptionRegistrationTest extends TestCase
{
    use PaymentTrait;
    use InvoiceTestTrait;
    use DbEntityFetchTrait;

    const TEST_INV_ID = 'inv_1000000invoice';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/SubscriptionRegistrationTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreateAuthLinkWithoutMandate()
    {
        $this->startTest();
    }

    public function testCreateAuthLinkWithCardMandate()
    {
        $this->startTest();

        $subr = $this->getDbLastEntity('subscription_registration');

        $this->assertEquals($subr['method'], 'card');

        $order = $this->getDbLastEntity('order');

        $this->assertEquals($order['method'], null);

        $this->assertStatusesWithLastEntity(['sms_status' => 'sent', 'email_status' => 'sent']);
    }

    public function testCreateAuthLinkWithBankMandate()
    {
        $this->startTest();

        $subr = $this->getDbLastEntity('subscription_registration');

        $this->assertEquals($subr['method'], 'emandate');

        $order = $this->getDbLastEntity('order');

        $this->assertEquals($order['method'], 'emandate');

        $this->assertStatusesWithLastEntity(['sms_status' => 'sent', 'email_status' => 'sent']);
    }

    public function testCreateAuthLinkWithBankAccount()
    {
        $this->startTest();

        $order = $this->getDbLastEntity('order');

        $bankAccount = $this->getDbLastEntity('bank_account');

        $subr = $this->getDbLastEntity('subscription_registration');

        $this->assertEquals($order['bank'], 'HDFC');

        $this->assertEquals($order['method'], 'emandate');

        $this->assertEquals($bankAccount['ifsc_code'], 'HDFC0001233');

        $this->assertEquals($subr['method'], 'emandate');

        $this->assertStatusesWithLastEntity(['sms_status' => 'sent', 'email_status' => 'sent']);
    }

    public function testAuthLinkHostedPage()
    {
        $this->testCreateAuthLinkWithBankAccount();

        $invoice = $this->getDbLastEntity('invoice');

        $invoiceId = $invoice->getPublicId();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/t/$invoiceId", ['key_id' => $this->ba->getKey()]);

        $response->assertStatus(200);

        $testData = '"order":{"status":"created"}}';

        $this->assertContains($testData, $response->getContent());
    }

    public function testCreateAuthLinkWithIncompleteBankData()
    {
        $this->startTest();
    }

    public function testFetchAuthLinks()
    {
        $subrAttributes = ['method' => 'emandate', 'notes' => []];

        $subr = $this->fixtures->create('subscription_registration', $subrAttributes);

        $order = $this->fixtures->create('order');

        $invoiceAtrributes = [
            'entity_id'   => $subr->getId(),
            'entity_type' => 'subscription_registration',
            'order_id'    => $order->getId()
        ];

        $invoice = $this->fixtures->create('invoice', $invoiceAtrributes);

        $response = $this->startTest();

        $this->assertArrayHasKey(E::SUBSCRIPTION_REGISTRATION, $response);

        $this->assertEquals($response[E::SUBSCRIPTION_REGISTRATION]['method'], 'emandate');
    }

    public function testFetchAuthLinksWithMandateAndBankAttributes()
    {
        $bank = $this->fixtures->create('bank_account');

        $subrAttributes = [
            'method'      => 'emandate',
            'entity_id'   => $bank->getId(),
            'entity_type' => E::BANK_ACCOUNT,
            'notes'       => [],
        ];

        $subr = $this->fixtures->create('subscription_registration', $subrAttributes);

        $orderAtributes = ['bank' => 'HDFC'];

        $order = $this->fixtures->create('order', $orderAtributes);

        $invoiceAtrributes = [
            'entity_id'   => $subr->getId(),
            'entity_type' => 'subscription_registration',
            'order_id'    => $order->getId()
        ];

        $invoice = $this->fixtures->create('invoice', $invoiceAtrributes);

        $response = $this->startTest();

        $this->assertArrayHasKey(E::SUBSCRIPTION_REGISTRATION, $response);

        $this->assertArrayHasKey(E::BANK_ACCOUNT, $response[E::SUBSCRIPTION_REGISTRATION]);

        $this->assertEquals($response[E::SUBSCRIPTION_REGISTRATION]['method'], 'emandate');

        $this->assertEquals($response[E::SUBSCRIPTION_REGISTRATION][E::BANK_ACCOUNT]['bank_name'], 'HDFC');

        $this->assertEquals($response[E::SUBSCRIPTION_REGISTRATION][E::BANK_ACCOUNT]['ifsc'], 'RZPB0000000');
    }

    public function testCreateAuthLinkWithCardAndZeroAmount()
    {
        $this->startTest();
    }

    public function testCreateAuthLinkWithBankAndNonZeroAmount()
    {
        $this->startTest();
    }

    public function testFetchTokenByMerchant()
    {
        $paymentRequest = $this->setupPaymentRequest();

        $this->doAuthPayment($paymentRequest);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testDeleteTokenByMerchant()
    {
        $this->fixtures->create('token',['id' => '10000000000000']);

        $this->startTest();
    }

    public function testFetchDeletedTokenByMerchant()
    {
        $this->fixtures->create('token',['id' => '10000000000000' ,'deleted_at' => '1000000000']);

        $this->startTest();

    }

    public function testChargeToken()
    {
        $paymentRequest = $this->setupPaymentRequest();

        $this->doAuthPayment($paymentRequest);

        $this->ba->proxyAuth();

        $token = $this->getDbLastEntity('token');

        $chargeContent = ['amount' => 2000, 'receipt' => '1234', 'description' => 'abc'];

        $request = [
            'method'  => 'POST',
            'url'     => '/subscription_registration/tokens/'.$token->getPublicId().'/charge',
            'content' => $chargeContent
        ];

        $content = $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($payment->getPublicId(), $content['razorpay_payment_id']);

        $this->assertEquals($payment->getAmount(), 2000);
    }

    public function testChargeEmandateToken()
    {
        $payment = $this->setupEmandateAndGetPaymentRequest('UTIB', 0);

        $this->doAuthPayment($payment);

        $this->ba->proxyAuth();

        $token = $this->getDbLastEntity('token');

        $chargeContent = ['amount' => 3000, 'receipt' => '1234', 'description' => 'abc'];

        $request = [
            'method'  => 'POST',
            'url'     => '/subscription_registration/tokens/'.$token->getPublicId().'/charge',
            'content' => $chargeContent
        ];

        $content = $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($payment->getPublicId(), $content['razorpay_payment_id']);

        $this->assertEquals($payment->getAmount(), 3000);
    }

    public function testPayAuthLinkAndCopyNotes()
    {
        $this->startTest();

        $order = $this->getDbLastEntity('order');

        $payment = $this->setupEmandateAndGetPaymentRequest('UTIB', 0);

        unset($payment['notes']);

        $payment['order_id'] = $order->getPublicId();

        $payment['amount'] = $order->getAmount();

        $this->doAuthPayment($payment);

        $payment = $this->getDbLastEntity("payment");

        $invoice = $this->getDbLastEntity("invoice");

        $this->assertEquals($payment->getNotesJson(), $invoice->getNotesJson());
    }

    public function testFutureTokenConfirmedEmandateLinks()
    {
        $this->startTest();

        $order = $this->getDbLastEntity('order');

        $payment = $this->setupHdfcEmandateAndGetPaymentRequest();

        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $payment = $this->getDbLastEntity("payment");

        $order = $this->getDbEntityById('order', $order->getPublicId());

        $this->assertEquals($payment->getStatus(), "authorized");

        $this->assertEquals($order->getStatus(), "attempted");
    }

    public function testPayAuthLink()
    {
        $this->startTest();

        $order = $this->getDbLastEntity('order');

        $payment = $this->setupPaymentRequest();

        unset($payment['notes']);

        $payment['order_id'] = $order->getPublicId();

        $payment['amount'] = $order->getAmount();

        $this->doAuthPayment($payment);

        $token = $this->getDbLastEntity('token');

        $subr = $this->getDbLastEntity('subscription_registration');

        $invoice = $this->getDbLastEntity('invoice');

        $this->assertEquals($subr->token->getPublicId(), $token->getPublicId());

        $this->assertEquals($invoice->getNotesJson(), $subr->getNotesJson());
    }

    public function testFetchSingleToken()
    {
       $this->testPayAuthLink();

        $this->ba->proxyAuth();

        $token = $this->getDbLastEntity('token');

        $invoice = $this->getDbLastEntity('invoice');

        $request = [
            'method'  => 'GET',
            'url'     => '/subscription_registration/tokens/'.$token->getPublicId(),
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('subscription_registration', $content);

        $this->assertEquals($content['subscription_registration']['notes'], $invoice->getNotes()->toArray());
    }

    protected function setupPaymentRequest()
    {
        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->mockCardVault();

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $payment = $this->getDefaultRecurringPaymentArray();

        $order = $this->fixtures->create('order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        return $payment;
    }

    protected function setupEmandateAndGetPaymentRequest($bank = 'HDFC', $amount = 2000)
    {
        $this->mockCardVault();
        $this->fixtures->create('terminal:shared_emandate_icici_terminal');

        $this->fixtures->create('terminal:shared_emandate_axis_terminal');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableEmandate();

        $payment = $this->getEmandateNetbankingRecurringPaymentArray($bank, $amount);

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $payment['bank_account'] = [
            'account_number'    => '123123123',
            'name'              => 'test name',
            'ifsc'              => 'UTIB0002766'
        ];

        $expireBy = Carbon::now(Timezone::IST)->addDays(10)->getTimestamp();

        $payment['recurring_token'] = [
            'max_amount' => 3000,
            'expire_by' => $expireBy,
        ];

        return $payment;
    }

    protected function assertStatusesWithLastEntity(array $expected)
    {
        $invoice = $this->getDbLastEntity('invoice');

        $this->assertArraySelectiveEquals($expected, $invoice->toArrayPublic());
    }

    protected function setupHdfcEmandateAndGetPaymentRequest()
    {
        $this->mockCardVault();

        $this->fixtures->create('terminal:shared_emandate_hdfc_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableEmandate();

        $payment = $this->getEmandateNetbankingRecurringPaymentArray("HDFC", "0");

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $payment['bank_account'] = [
            'account_number'    => '123123123',
            'name'              => 'test name',
            'ifsc'              => 'HDFC0001233'
        ];

        $expireBy = Carbon::now(Timezone::IST)->addDays(10)->getTimestamp();

        $payment['recurring_token'] = [
            'max_amount' => 3000,
            'expire_by' => $expireBy,
        ];

        return $payment;
    }
}
