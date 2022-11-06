<?php

namespace RZP\Tests\Functional\Helpers;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use Platform\Bvs\Legaldocumentmanager\V1\LegalDocumentsManagerResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient\BvsLegalDocumentManagerClient;


trait CreateLegalDocumentsTrait
{

    public function mockCreateLegalDocument(): \PHPUnit\Framework\MockObject\MockObject
    {
        $response = new LegalDocumentsManagerResponse();

        $response->setId('random-id');

        $response->setStatus(Constant::SUCCESS);

        $response->setCountUnwrapped(0);

        $mock = (new TestCase())->getMockBuilder(BvsLegalDocumentManagerClient::class)
            ->onlyMethods(['createLegalDocument'])
            ->getMock();

        $mock->method('createLegalDocument')
            ->willReturn($response);

        $this->app->instance('bvs_legal_document_manager', $mock);

        return $mock;
    }
}
