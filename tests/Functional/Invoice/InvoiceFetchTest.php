<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

/**
 * Covers Base/Fetch implementation
 */
class InvoiceFetchTest extends TestCase
{
    use InvoiceTestTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/InvoiceFetchTestData.php';

        parent::setUp();
    }

    public function testFetchForPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->createDraftInvoice();
        $this->createDraftInvoice(['id' => '1000001invoice', 'type' => 'link']);

        $this->startTest();
    }

    public function testFindForPrivateAuth()
    {
        $this->ba->privateAuth();

        $invoice = $this->createDraftInvoice();

        $this->startTest();
    }

    public function testFetchRuleCascadingForAdminAuth()
    {
        $this->ba->adminAuth();

        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testFetchRulesForProxyAuthWithExtraFields()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchRulesForProxyAuthWithInvalidField()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchWithExpandsForProxyAuth()
    {
        $this->ba->proxyAuth();

        $this->createDraftInvoice();

        $this->fixtures->create('payment', ['invoice_id' => '1000000invoice']);

        $this->startTest();
    }

    public function testFindWithExpandsForProxyAuth()
    {
        $this->ba->proxyAuth();

        $this->createDraftInvoice();

        $this->fixtures->create('payment', ['invoice_id' => '1000000invoice']);

        $this->startTest();
    }

    public function testFindForProxyAuthWithInvalidExpands()
    {
        $this->ba->proxyAuth();

        $this->createDraftInvoice();

        $this->startTest();
    }
}
