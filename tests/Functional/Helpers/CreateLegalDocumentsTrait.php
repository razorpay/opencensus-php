<?php

namespace RZP\Tests\Functional\Helpers;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use Platform\Bvs\Legaldocumentmanager\V1\LegalDocumentDetails;
use Platform\Bvs\Legaldocumentmanager\V1\LegalDocumentsManagerResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient\BvsLegalDocumentManagerClient;


trait CreateLegalDocumentsTrait
{

    public function mockCreateLegalDocument(): \PHPUnit\Framework\MockObject\MockObject
    {
        $documentDetail = new LegalDocumentDetails();

        $documentDetail->setStatus(Constant::SUCCESS);

        $response = new LegalDocumentsManagerResponse();

        $response->setId(Constants::DUMMY_REQUEST_ID);

        $response->setStatus(Constant::SUCCESS);

        $response->setCountUnwrapped(2);

        $response->setDocumentsDetail([$documentDetail, $documentDetail]);

        $mock = $this->getMockBuilder(BvsLegalDocumentManagerClient::class)
            ->onlyMethods(['createLegalDocument', 'getLegalDocumentsByOwnerId'])
            ->getMock();

        $mock->method('createLegalDocument')
            ->willReturn($response);

        $mock->method('getLegalDocumentsByOwnerId')
            ->willReturn($response);

        $this->app->instance('bvs_legal_document_manager', $mock);

        return $mock;
    }
}
