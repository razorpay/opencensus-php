<?php

namespace RZP\Tests\Functional\FreshdeskTicket;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class FreshdeskTicketTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $ticketService;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/FreshdeskTicketTestData.php';

        parent::setUp();
    }

    public function testStoreReserveBalanceTicketDetails()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetReserveBalanceTicketStatusForNonExistingTicket()
    {
        $this->fixtures->on('test')->create('merchant', ['id' => '100cq000cq00cq']);

        $user = $this->fixtures->user->createUserForMerchant('100cq000cq00cq', [], 'owner', 'test');

        $this->ba->proxyAuth('rzp_test_100cq000cq00cq', $user->getId());

        $this->startTest();
    }

    public function testGetReserveBalanceTicketStatusForExistingTicket()
    {
        $this->fixtures->create('merchant_freshdesk_tickets');

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'owner', 'test');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->startTest();
    }

    public function testPostTicketMissingField()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testPostTicketPaymentId()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $payment = $this->fixtures->create('payment:captured');

        $this->ba->publicAuth();

        $testData = &$this->testData['testPostTicketPaymentId'];

        $testData['request']['content']['custom_fields']['cf_transaction_id'] = 'pay_' . $payment->toArray()['id'];

        $this->startTest();
    }

    public function testPostTicketInvalidId()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $this->ba->publicAuth();

        $testData = &$this->testData['testPostTicketInvalidId'];

        $testData['request']['content']['custom_fields']['cf_transaction_id'] = 'pay_' . 'ABcYZ';

        $this->startTest();
    }

    public function testPostTicketCustomerNoTransactionId()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testPostTicketPartnerSuccess()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $this->ba->publicAuth();

        $this->startTest();
    }
}
