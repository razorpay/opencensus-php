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

    public function testFetchAndFindForSoftDeletedForProxyAuth()
    {
        $this->ba->proxyAuth();

        $this->startTest();

        $invoice = $this->createDraftInvoice(['deleted_at' => time()]);

        $this->testData[__FUNCTION__]['request']['url'] .= $invoice['public_id'];

        $this->startTest();
    }

    public function testFetchForSoftDeletedInvoiceForAppAuth()
    {
        $this->ba->appAuth();

        $this->createDraftInvoice(['deleted_at' => time()]);

        $testData = & $this->testData[__FUNCTION__];

        $content = $this->startTest();
        $this->assertSame(0, $content['count']);

        $testData['request']['content']['deleted'] = 0;
        $content = $this->startTest();
        $this->assertSame(0, $content['count']);

        $testData['request']['content']['deleted'] = '1';
        $content = $this->startTest();
        $this->assertSame(1, $content['count']);

        $testData['request']['content']['deleted'] = 'xyz';
        $this->makeRequestAndCatchException(function() use ($testData)
        {
            $this->runRequestResponseFlow($testData);
        },
        \RZP\Exception\BadRequestValidationFailureException::class);
    }

    public function testFindByIdForSoftDeletedInvoiceForAppAuth()
    {
        $this->ba->appAuth();

        $invoice = $this->createDraftInvoice(['deleted_at' => time()]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] .= $invoice['public_id'];
        $this->makeRequestAndCatchException(function() use ($testData)
        {
            $this->runRequestResponseFlow($testData);
        },
        \RZP\Exception\BadRequestException::class);

        $testData['request']['content']['deleted'] = 0;
        $this->makeRequestAndCatchException(function() use ($testData)
        {
            $this->runRequestResponseFlow($testData);
        },
        \RZP\Exception\BadRequestException::class);

        $testData['request']['content']['deleted'] = 1;
        $content = $this->startTest();
        $this->assertSame($invoice['public_id'], $content['id']);

        $testData['request']['content']['deleted'] = 'true';
        $this->makeRequestAndCatchException(function() use ($testData)
        {
            $this->runRequestResponseFlow($testData);
        },
        \RZP\Exception\BadRequestValidationFailureException::class);
    }
}
