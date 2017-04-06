<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class InvoiceUserIdAclTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/InvoiceUserIdAclTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreateInvoiceWithUserIdHeader()
    {
        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    /**
     * Even if userRole is something other than sellerapp we would want to record
     * userId in invoices.user_id column.
     *
     * @return void
     */
    public function testCreateInvoiceWithUserIdAndDiffRoleHeader()
    {
        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testGetInvoiceWithUserIdHeaderSuccess()
    {
        $this->createInvoice(['user_id' => '10000000UserId']);

        $this->startTest();
    }

    public function testGetInvoiceWithUserIdHeaderForbidden()
    {
        $this->createInvoice(['user_id' => '10000001UserId']);

        $this->startTest();
    }

    public function testGetInvoiceWithUserIdAndDifferentRoleHeaderSuccess()
    {
        $this->createInvoice(['user_id' => '10000001UserId']);

        $this->startTest();
    }

    public function testListInvoiceWithUserIdHeader()
    {
        $this->createInvoice(['user_id' => '10000000UserId']);
        $this->createInvoice(['user_id' => '10000000UserId', 'id' => '1000001invoice']);
        $this->createInvoice(['user_id' => '10000001UserId', 'id' => '1000002invoice']);

        $this->startTest();
    }

    public function testListInvoiceWithoutUserIdHeader()
    {
        $this->createInvoice(['user_id' => '10000000UserId']);
        $this->createInvoice(['user_id' => '10000000UserId', 'id' => '1000001invoice']);
        $this->createInvoice(['user_id' => '10000001UserId', 'id' => '1000002invoice']);

        $this->startTest();
    }

    public function testListInvoiceWithUserIdAndDifferentRoleHeader()
    {
        $this->createInvoice(['user_id' => '10000000UserId']);
        $this->createInvoice(['user_id' => '10000000UserId', 'id' => '1000001invoice']);
        $this->createInvoice(['user_id' => '10000001UserId', 'id' => '1000002invoice']);

        $this->startTest();
    }

    public function testUpdateInvoiceWithUserIdHeaderSuccess()
    {
        $this->createInvoice(['user_id' => '10000000UserId']);

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateInvoiceWithUserIdHeaderForbidden()
    {
        $this->createInvoice(['user_id' => '10000001UserId']);

        $this->startTest();
    }

    public function testDeleteInvoiceWithUserIdHeaderSuccess()
    {
        $this->createInvoice(['user_id' => '10000000UserId']);

        $this->startTest();

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertNull($invoice);
    }

    public function testDeleteInvoiceWithUserIdHeaderForbidden()
    {
        $this->createInvoice(['user_id' => '10000001UserId']);

        $this->startTest();
    }

    public function testCancelInvoiceWithUserIdHeaderSuccess()
    {
        $this->createInvoice(['user_id' => '10000000UserId', 'status' => 'issued']);

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testCancelInvoiceWithUserIdHeaderForbidden()
    {
        $this->createInvoice(['user_id' => '10000001UserId', 'status' => 'issued']);

        $this->startTest();
    }

    private function createInvoice(array $with = [])
    {
        return $this->fixtures->create('invoice', array_merge(['order_id' => null, 'status' => 'draft'], $with));
    }
}
