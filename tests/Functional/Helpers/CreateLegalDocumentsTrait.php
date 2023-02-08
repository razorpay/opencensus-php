<?php

namespace RZP\Tests\Functional\Helpers;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use Platform\Bvs\Legaldocumentmanager\V1\LegalDocumentDetails;
use Platform\Bvs\Legaldocumentmanager\V1\LegalDocumentsManagerResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient\BvsLegalDocumentManagerClient;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\FetchLegalDocumentBaseResponse;

trait CreateLegalDocumentsTrait
{

    public function mockCreateLegalDocument(): \PHPUnit\Framework\MockObject\MockObject
    {
        // Create document details
        $documentDetail1 = new LegalDocumentDetails();

        $documentDetail1->setStatus(Constant::SUCCESS);

        $documentDetail1->setType('X_Privacy Policy');

        $documentDetail1->setUfhFileId('random-ufh-file-id');

        $documentDetail2 = new LegalDocumentDetails();

        $documentDetail2->setStatus(Constant::SUCCESS);

        $documentDetail2->setType('X_Terms of Use');

        $documentDetail2->setUfhFileId('random-ufh-file-id');

        // Create legal doc response
        $response = new LegalDocumentsManagerResponse();

        $response->setId(Constants::DUMMY_REQUEST_ID);

        $response->setStatus(Constant::SUCCESS);

        $response->setCountUnwrapped(2);

        $response->setDocumentsDetail([$documentDetail1, $documentDetail2]);

        $mock = $this->getMockBuilder(BvsLegalDocumentManagerClient::class)
            ->onlyMethods(['createLegalDocument', 'getLegalDocumentsByOwnerId', 'getLegalDocumentsByRequestId'])
            ->getMock();

        $mock->method('createLegalDocument')
            ->willReturn($response);

        $mock->method('getLegalDocumentsByOwnerId')
            ->willReturn($response);

        $mock->method('getLegalDocumentsByRequestId')
            ->willReturn(new FetchLegalDocumentBaseResponse($response));

        $this->app->instance('bvs_legal_document_manager', $mock);

        return $mock;
    }
}
