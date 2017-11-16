<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
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

    public function testFetchAndFindForSoftDeletedForPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $this->testData[__FUNCTION__]['request']['url'] .= 'inv_00000000000001';

        $this->startTest();
    }

    public function testFetchAndFindForSoftDeletedForProxyAuth()
    {
        $this->ba->proxyAuth();

        $testData = $this->testData['testFetchAndFindForSoftDeletedForPrivateAuth'];

        $this->startTest($testData);

        $testData['request']['url'] .= 'inv_00000000000001';

        $this->startTest($testData);
    }

    public function testFetchForSoftDeletedInvoiceForAppAuth()
    {
        $this->ba->appAuth();

        $this->createDraftInvoice(['deleted_at' => time()]);

        $testData = & $this->testData[__FUNCTION__];

        // Case 1: When no deleted parameter sent, should return 0 entity
        $content = $this->startTest();
        $this->assertSame(0, $content['count']);

        // Case 2: When deleted=0 is sent, should return 0 entity
        $testData['request']['content']['deleted'] = 0;
        $content = $this->startTest();
        $this->assertSame(0, $content['count']);

        // Case 3: When deleted=1 is sent, should return 1 entity
        $testData['request']['content']['deleted'] = 1;
        $content = $this->startTest();
        $this->assertSame(1, $content['count']);

        // Case 4: When invalid deleted sent, should throw exception
        $testData['request']['content']['deleted'] = 'xyz';
        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->runRequestResponseFlow($testData);
            },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'The selected deleted is invalid.');
    }

    public function testFindByIdForSoftDeletedInvoiceForAppAuth()
    {
        $this->ba->appAuth();

        $invoice = $this->createDraftInvoice(['deleted_at' => time()]);

        $testData = & $this->testData[__FUNCTION__];

        // Case 1: When no deleted parameter sent, should throw exception
        $testData['request']['url'] .= $invoice['public_id'];
        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->runRequestResponseFlow($testData);
            },
            \RZP\Exception\BadRequestException::class,
            PublicErrorDescription::BAD_REQUEST_INVALID_ID);

        // Case 2: When deleted=0 is sent, should return 0 entity
        $testData['request']['content']['deleted'] = 0;
        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->runRequestResponseFlow($testData);
            },
            \RZP\Exception\BadRequestException::class,
            PublicErrorDescription::BAD_REQUEST_INVALID_ID);

        // Case 3: When deleted=1 is sent, should return entity
        $testData['request']['content']['deleted'] = 1;
        $content = $this->startTest();
        $this->assertSame($invoice['public_id'], $content['id']);

        // Case 4: When invalid deleted sent, should throw exception
        $testData['request']['content']['deleted'] = 123;
        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->runRequestResponseFlow($testData);
            },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'The selected deleted is invalid.');
    }
}
