<?php

namespace RZP\Tests\Functional\SubscriptionRegistration;

use Mail;
use Queue;

use RZP\Constants\Entity as E;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class SubscriptionRegistrationTest extends TestCase
{
    use PaymentTrait;
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

        $this->assertEquals($subr['method'], "card");

        $order = $this->getDbLastEntity('order');

        $this->assertEquals($order['method'], null);
    }

    public function testCreateAuthLinkWithBankMandate()
    {
        $this->startTest();

        $subr = $this->getDbLastEntity('subscription_registration');

        $this->assertEquals($subr['method'], "emandate");

        $order = $this->getDbLastEntity('order');

        $this->assertEquals($order['method'], "emandate");
    }

    public function testCreateAuthLinkWithBankAccount()
    {
        $this->startTest();

        $order = $this->getDbLastEntity('order');

        $bankAccount = $this->getDbLastEntity('bank_account');

        $subr = $this->getDbLastEntity('subscription_registration');

        $this->assertEquals($order['bank'], "HDFC");

        $this->assertEquals($order['method'], "emandate");

        $this->assertEquals($bankAccount['ifsc_code'], "HDFC0001233");

        $this->assertEquals($subr['method'], "emandate");
    }

    public function testCreateAuthLinkWithIncompleteBankData()
    {
        $this->startTest();
    }

    public function testFetchAuthLinks()
    {
        $subrAttributes = ['method' => 'emandate'];

        $subr = $this->fixtures->create('subscription_registration', $subrAttributes);

        $order = $this->fixtures->create("order");

        $invoiceAtrributes = [
            'entity_id'   => $subr->getId(),
            'entity_type' => 'subscription_registration',
            'order_id'    => $order->getId()
        ];

        $invoice = $this->fixtures->create("invoice", $invoiceAtrributes);

        $response = $this->startTest();

        $this->assertArrayHasKey(E::SUBSCRIPTION_REGISTRATION, $response);

        $this->assertEquals($response[E::SUBSCRIPTION_REGISTRATION]['method'], "emandate");
    }

    public function testFetchAuthLinksWithMandateAndBankAttributes()
    {
        $bank = $this->fixtures->create("bank_account");

        $subrAttributes = [
            'method'      => 'emandate',
            'entity_id'   => $bank->getId(),
            'entity_type' => E::BANK_ACCOUNT
        ];

        $subr = $this->fixtures->create('subscription_registration', $subrAttributes);

        $orderAtributes = ['bank' => 'HDFC'];

        $order = $this->fixtures->create("order", $orderAtributes);

        $invoiceAtrributes = [
            'entity_id'   => $subr->getId(),
            'entity_type' => 'subscription_registration',
            'order_id'    => $order->getId()
        ];

        $invoice = $this->fixtures->create("invoice", $invoiceAtrributes);

        $response = $this->startTest();

        $this->assertArrayHasKey(E::SUBSCRIPTION_REGISTRATION, $response);

        $this->assertArrayHasKey(E::BANK_ACCOUNT, $response[E::SUBSCRIPTION_REGISTRATION]);

        $this->assertEquals($response[E::SUBSCRIPTION_REGISTRATION]['method'], "emandate");

        $this->assertEquals($response[E::SUBSCRIPTION_REGISTRATION][E::BANK_ACCOUNT]['bank_name'], "HDFC");

        $this->assertEquals($response[E::SUBSCRIPTION_REGISTRATION][E::BANK_ACCOUNT]['ifsc'], "RZPB0000000");
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
        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->mockTokenex();

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $paymentRequest = $this->getDefaultRecurringPaymentArray();

        $this->doAuthPayment($paymentRequest);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testDeleteTokenByMerchant()
    {
        $this->fixtures->create('token',["id" => '10000000000000']);

        $this->startTest();
    }

    public function testFetchDeletedTokenByMerchant()
    {
        $this->fixtures->create('token',["id" => '10000000000000' ,'deleted_at' => '1000000000']);

        $this->startTest();

    }
}