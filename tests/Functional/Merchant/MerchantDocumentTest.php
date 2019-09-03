<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class MerchantDocumentTest Extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantDocumentTestData.php';

        parent::setUp();
    }

    public function testDeleteDocument()
    {
        $merchantDocument = $this->fixtures->create('merchant_document');
        //request edited
        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'],'doc_'.$merchantDocument['id']);

        $this->testData[__FUNCTION__]['request'] = $request;

        //response edited
        $response = $this->testData[__FUNCTION__]['response'];

        $response['content']['id'] = sprintf($response['content']['id'],'doc_'.$merchantDocument['id']);

        $this->testData[__FUNCTION__]['response'] = $response;

        $this->ba->proxyAuth('rzp_test_' .$merchantDocument['merchant_id']);

        $this->startTest();
    }

    public function testDeleteDocumentIdNotValid()
    {
        $merchantDocument = $this->fixtures->create('merchant_document');

        $this->ba->proxyAuth('rzp_test_' .$merchantDocument['merchant_id']);

        $this->startTest();
    }

    public function testDeleteDocumentIdNOtExist()
    {
        $merchantDocument = $this->fixtures->create('merchant_document');

        $this->ba->proxyAuth('rzp_test_' .$merchantDocument['merchant_id']);

        $this->startTest();
    }
}
