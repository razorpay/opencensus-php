<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class InvoiceUserIdAclTest extends TestCase
{
    use InvoiceTestTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/InvoiceUserIdAclTestData.php';

        parent::setUp();

        $this->fixtures->create('user', ['id' => '10000000UserId']);
        $this->fixtures->create('user', ['id' => '10000001UserId']);

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
        $this->createDraftInvoice(['user_id' => '10000000UserId']);

        $this->startTest();
    }

    public function testGetInvoiceWithUserIdHeaderForbidden()
    {
        $this->createDraftInvoice(['user_id' => '10000001UserId']);

        $this->startTest();
    }

    public function testGetInvoiceWithUserIdAndDifferentRoleHeaderSuccess()
    {
        $this->createDraftInvoice(['user_id' => '10000001UserId']);

        $this->startTest();
    }

    public function testListInvoiceWithUserIdHeader()
    {
        $this->createDraftInvoice(['user_id' => '10000000UserId']);
        $this->createDraftInvoice(['user_id' => '10000000UserId', 'id' => '1000001invoice']);
        $this->createDraftInvoice(['user_id' => '10000001UserId', 'id' => '1000002invoice']);

        $expectedSearchParams = $this->testData[__FUNCTION__ . 'EsExpectedSearchParams'];
        $expectedSearchRes    = $this->testData[__FUNCTION__ . 'EsExpectedSearchResponse'];

        $this->startTest();
    }

    public function testListInvoiceWithUserIdHeaderAndEsParams()
    {
        // Need to just assert that it doesn't throw any validation errors.

        $this->startTest();
    }

    public function testListInvoiceWithoutUserIdHeader()
    {
        $this->createDraftInvoice(['user_id' => '10000000UserId']);
        $this->createDraftInvoice(['user_id' => '10000000UserId', 'id' => '1000001invoice']);
        $this->createDraftInvoice(['user_id' => '10000001UserId', 'id' => '1000002invoice']);

        $this->startTest();
    }

    public function testListInvoiceWithUserIdAndDifferentRoleHeader()
    {
        $this->createDraftInvoice(['user_id' => '10000000UserId']);
        $this->createDraftInvoice(['user_id' => '10000000UserId', 'id' => '1000001invoice']);
        $this->createDraftInvoice(['user_id' => '10000001UserId', 'id' => '1000002invoice']);

        $this->startTest();
    }

    public function testUpdateInvoiceWithUserIdHeaderSuccess()
    {
        $this->createDraftInvoice(['user_id' => '10000000UserId']);

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateInvoiceWithUserIdHeaderForbidden()
    {
        $this->createDraftInvoice(['user_id' => '10000001UserId']);

        $this->startTest();
    }

    public function testDeleteInvoiceWithUserIdHeaderSuccess()
    {
        $this->createDraftInvoice(['user_id' => '10000000UserId']);

        $this->startTest();

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertNull($invoice);
    }

    public function testDeleteInvoiceWithUserIdHeaderForbidden()
    {
        $this->createDraftInvoice(['user_id' => '10000001UserId']);

        $this->startTest();
    }

    public function testCancelInvoiceWithUserIdHeaderSuccess()
    {
        $order = $this->fixtures->create('order');

        $this->createIssuedInvoice(['user_id' => '10000000UserId', 'order_id' => $order->getId()]);

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testCancelInvoiceWithUserIdHeaderForbidden()
    {
        $order = $this->fixtures->create('order');

        $this->createIssuedInvoice(['user_id' => '10000001UserId', 'order_id' => $order->getId()]);

        $this->startTest();
    }
}
