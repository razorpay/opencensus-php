<?php

namespace RZP\Tests\Functional\SubscriptionRegistration;

use Mail;
use Mockery;
use Queue;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Constants\Entity as E;
use RZP\Services\BatchMicroService;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\TokenRegistrationAutoCharge;
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

    public function testCreateAuthLinkInternationalCard()
    {
        $this->fixtures->merchant->enableInternational();

        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => true]);

        $this->startTest();

        $subr = $this->getDbLastEntity('subscription_registration');

        $this->assertEquals('USD', $subr['currency']);
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

    public function testCreateAuthLinkWithBankAndNonZeroAmountAllowed()
    {
        /*
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');
        */

        $this->startTest();
    }

    public function testCreateAuthLinkWithUPIAndMaxAllowedAmount()
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

    public function testUMRNInsteadOfTokenForDebitPayment()
    {
        $this->fixtures->merchant->addFeatures(['recurring_debit_umrn']);

        $payment = $this->setupEmandateAndGetPaymentRequest('UTIB', 0);

        $this->doAuthPayment($payment);

        $token = $this->getDbLastEntity('token');

        $order = $this->fixtures->create('order', [
            'amount' => 3000,
            'payment_capture' => true,
        ]);

        $payment = [
            'contact'     => '9876543210',
            'email'       => 'r@g.c',
            'customer_id' => $token->customer->getPublicId(),
            'currency'    => 'INR',
            'amount'      => 3000,
            'recurring'   => true,
            'token'       => $token->gateway_token,
            'order_id'    => $order->getPublicId(),
        ];

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/recurring',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($token->getId(), $payment->getTokenId());
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

        $this->assertArrayNotHasKey('order_id', $content);
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

    public function testAutoChargeEmandateToken()
    {
        $beforeMidDay = Carbon::now(Timezone::IST)->midDay()->subHour(1)->getTimestamp();
        $afterMidDay = Carbon::now(Timezone::IST)->midDay()->addHour(1)->getTimestamp();

        $tokenAttributes = [
            'id'            => 'FwDj8vdozfJouZ',
            'method'        => 'emandate',
            'token'         => 'aamVRCBCAC122F',
            'created_at'    => $beforeMidDay,
            'confirmed_at'  => $beforeMidDay,
        ];

        $this->fixtures->create('token', $tokenAttributes);     // to be picked

        $tokenAttributes['id'] = 'FwDj8oK33K5Xvl';
        $tokenAttributes['token'] = 'namVRCBCAC122F';
        $tokenAttributes['confirmed_at'] = $afterMidDay;
        $this->fixtures->create('token', $tokenAttributes);    // not to be picked

        $subrAttributes = [
            'token_id'             => 'FwDj8vdozfJouZ',
            'first_payment_amount' => 1100,
            'max_amount'           => 10000,
            'notes'                => [ 'address' => 'sometext' ],
            'method'               => 'emandate',
            'auth_type'            => 'netbanking',
            'status'               => 'authenticated'
        ];

        $this->fixtures->create('subscription_registration', $subrAttributes);   // to be picked

        $subrAttributes['token_id'] = 'FwDj8oK33K5Xvl';
        $this->fixtures->create('subscription_registration', $subrAttributes);   // not to be picked

        Queue::fake();

        // simulate test run at 4:00 PM
        Carbon::setTestNow(Carbon::now(Timezone::IST)->setTime(16, 0));

        $result = $this->startTest();

        Queue::assertPushed(TokenRegistrationAutoCharge::class);

        $this->assertEquals(1, count($result));
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

        $invoice = $this->getDbLastEntity('invoice');

        $this->assertEquals('pending', $invoice->toArrayPublic()['auth_link_status'] ?? null);
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
            'ifsc'              => 'UTIB0002766',
            'account_type'      => 'savings'
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
            'ifsc'              => 'HDFC0001233',
            'account_type'      => 'savings',
        ];

        $expireBy = Carbon::now(Timezone::IST)->addDays(10)->getTimestamp();

        $payment['recurring_token'] = [
            'max_amount' => 3000,
            'expire_by' => $expireBy,
        ];

        return $payment;
    }

    public function testCancelAuthLinkWithCardMandate()
    {
        $subrAttributes = ['method' => 'card', 'notes' => []];

        $subr = $this->fixtures->create('subscription_registration', $subrAttributes);

        $order = $this->fixtures->create('order');

        $invoiceAtrributes = [
            'entity_id'   => $subr->getId(),
            'entity_type' => 'subscription_registration',
            'order_id'    => $order->getId()
        ];

        $invoice = $this->fixtures->create('invoice', $invoiceAtrributes);

        $this->startTest();

        $subr = $this->getDbLastEntity('subscription_registration');

        $this->assertEquals($subr['method'], 'card');
    }

    public function testCancelAuthLinkWithBankMandate()
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

        $this->startTest();

        $subr = $this->getDbLastEntity('subscription_registration');

        $this->assertEquals($subr['method'], 'emandate');
    }

    public function testCancelAuthLinksViaBatch()
    {
        $batchAttributes = [
            'id' => '100000000batch',
            'type' => 'auth_link',
            'status' => 'processed',
        ];

        $batch = $this->fixtures->create('batch', $batchAttributes);

        $this->ba->adminAuth();

        $this->mockBatchService();

        $this->startTest();
    }

    protected function mockBatchService()
    {
        $mock = Mockery::mock(BatchMicroService::class)->makePartial();

        $this->app->instance('batchService', $mock);

        $mock->shouldAllowMockingMethod('getBatchesFromBatchService')
             ->shouldReceive('getBatchesFromBatchService')
             ->andReturnNull();
    }

    public function testResendAuthLinkViaSms()
    {
        $subrAttributes = ['method' => 'card', 'notes' => []];

        $subr = $this->fixtures->create('subscription_registration', $subrAttributes);

        $order = $this->fixtures->create('order');

        $invoiceAtrributes = [
            'entity_id'   => $subr->getId(),
            'entity_type' => 'subscription_registration',
            'order_id'    => $order->getId()
        ];

        $invoice = $this->fixtures->create('invoice', $invoiceAtrributes);

        $this->startTest();
    }

    public function testResendAuthLinkViaEmail()
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

        $this->startTest();
    }

    public function testChargeTokenFromBatch()
    {
        $paymentRequest = $this->setupPaymentRequest();

        $this->doAuthPayment($paymentRequest);

        $token = $this->getDbLastEntity('token');

        $this->ba->batchAuth();

        $chargeContent = ['amount' => 2000, 'receipt' => '1234', 'description' => 'abc'];

        $request = [
            'method'  => 'POST',
            'url'     => '/subscription_registration/tokens/'.$token->getPublicId().'/charge',
            'content' => $chargeContent
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($content['order_id']);
    }

    public function testFirstChargeAmountInAuthLinkCreate()
    {
        $this->startTest();

        $subr = $this->getDbLastEntity('subscription_registration');

        $this->assertEquals(10000, $subr->max_amount);
        $this->assertEquals(1100, $subr->amount);
    }

    public function testMinFirstChargeAmountInAuthLinkCreate()
    {
        $this->startTest();
    }

    public function testFirstChargeAmountGreaterThanMaxAmount()
    {
        $this->startTest();
    }

    public function testDefaultMaxAmountAuthLinkCreate()
    {
        $this->startTest();

        $subr = $this->getDbLastEntity('subscription_registration');

        $this->assertEquals(9999900, $subr->max_amount);
        $this->assertEquals(1100, $subr->amount);
    }

    public function testListTokens()
    {
        $paymentRequest = $this->setupPaymentRequest();

        $this->doAuthPayment($paymentRequest);

        $token = $this->getDbLastEntity('token');

        $this->ba->proxyAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);
        $this->assertEquals(1, sizeof($response['items']));
        $this->assertEquals($token->getPublicId(), $response['items'][0]['id']);
    }

    public function testListTokensWithFilters()
    {
        $paymentRequest = $this->setupPaymentRequest();

        $this->doAuthPayment($paymentRequest);

        $token = $this->getDbLastEntity('token');

        $this->testData[__FUNCTION__]['request']['content']['customer_contact'] = $token->customer->getContact();

        $this->ba->proxyAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);
        $this->assertEquals(1, sizeof($response['items']));
        $this->assertEquals($token->getPublicId(), $response['items'][0]['id']);

        unset($this->testData[__FUNCTION__]['request']['content']['customer_contact']);

        $this->testData[__FUNCTION__]['request']['content']['customer_email'] = $token->customer->getEmail();

        $this->assertEquals(1, $response['count']);
        $this->assertEquals(1, sizeof($response['items']));
        $this->assertEquals($token->getPublicId(), $response['items'][0]['id']);
    }

    public function testListTokensWithPaymentIdFilter()
    {
        $paymentRequest = $this->setupPaymentRequest();

        $this->doAuthPayment($paymentRequest);

        $token = $this->getDbLastEntity('token');

        $payment = $this->getDbLastEntity('payment');

        $this->testData[__FUNCTION__]['request']['content']['payment_id'] = $payment->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['customer_contact'] = $token->customer->getContact();

        $this->ba->proxyAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);
        $this->assertEquals(1, sizeof($response['items']));
        $this->assertEquals($token->getPublicId(), $response['items'][0]['id']);
    }

    public function testCreateAuthLinkBlankContact()
    {
        $this->startTest();
    }

    public function testCreateAuthLinkBlankEmail()
    {
        $this->startTest();
    }
}
