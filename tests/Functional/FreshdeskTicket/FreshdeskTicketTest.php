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
}
