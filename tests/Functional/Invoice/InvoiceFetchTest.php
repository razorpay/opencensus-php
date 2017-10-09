<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

/**
 * Tests that retreieving of invoive is working fine.
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

        $this->startTest();
    }

    public function testFindForPrivateAuth()
    {
        $this->ba->privateAuth();

        $invoice = $this->createDraftInvoice();

        $this->testData[__FUNCTION__]['request']['url'] .= $invoice->getPublicId();

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

        $invoice = $this->createDraftInvoice();

        $this->fixtures->create('payment', ['invoice_id' => $invoice->getId()]);

        $this->startTest();
    }

    public function testFindWithExpandsForProxyAuth()
    {
        $this->ba->proxyAuth();

        $invoice = $this->createDraftInvoice();

        $this->fixtures->create('payment', ['invoice_id' => $invoice->getId()]);

        $this->testData[__FUNCTION__]['request']['url'] .= $invoice->getPublicId();

        $this->startTest();
    }

    public function testFindForProxyAuthWithInvalidExpands()
    {
        $this->ba->proxyAuth();

        $invoice = $this->createDraftInvoice();

        $this->testData[__FUNCTION__]['request']['url'] .= $invoice->getPublicId();

        $this->startTest();
    }
}
