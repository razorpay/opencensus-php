<?php

namespace RZP\Tests\Functional\Helpers;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use Platform\Bvs\Legaldocumentmanager\V1\LegalDocumentDetails;
use Platform\Bvs\Consentdocumentmanager\V2\ConsentDocumentDetails;
use Platform\Bvs\Legaldocumentmanager\V1\LegalDocumentsManagerResponse;
use Platform\Bvs\Consentdocumentmanager\V2\ConsentDocumentsManagerResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient\BvsLegalDocumentManagerClient;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\FetchLegalDocumentBaseResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\FetchConsentDocumentBaseResponse;

trait CreateLegalDocumentsTrait
{

    public function createLegalDocumentForResponse($type): LegalDocumentDetails
    {
        $documentDetail = new LegalDocumentDetails();

        $documentDetail->setStatus(Constant::SUCCESS);

        $documentDetail->setType($type);

        $documentDetail->setUfhFileId('random-ufh-file-id');

        return $documentDetail;
    }

    public function createConsentDocumentForResponse($type): ConsentDocumentDetails
    {
        $documentDetail = new ConsentDocumentDetails();

        $documentDetail->setStatus(Constant::SUCCESS);

        $documentDetail->setType($type);

        $documentDetail->setUfhFileId('random-ufh-file-id');

        return $documentDetail;
    }

    public function mockCreateLegalDocument(): \PHPUnit\Framework\MockObject\MockObject
    {
        // Create document details
        $documentDetail1 = $this->createLegalDocumentForResponse('X_Privacy Policy');

        $documentDetail2 = $this->createLegalDocumentForResponse('X_Terms of Use');

        // Create legal doc response
        $response = new LegalDocumentsManagerResponse();

        $response->setId(Constants::DUMMY_REQUEST_ID);

        $response->setStatus(Constant::SUCCESS);

        $response->setCountUnwrapped(2);

        $response->setDocumentsDetail([$documentDetail1, $documentDetail2]);

        $mock = $this->getMockBuilder(BvsLegalDocumentManagerClient::class)
            ->onlyMethods(['createLegalDocument', 'getLegalDocumentsByOwnerId', 'getLegalDocumentsByRequestId', 'getLegalDocumentsByRequestIdV2', 'createLegalDocumentV2'])
            ->getMock();

        $mock->method('createLegalDocument')
            ->willReturn($response);

        $mock->method('getLegalDocumentsByOwnerId')
            ->willReturn($response);

        $mock->method('getLegalDocumentsByRequestId')
            ->willReturn(new FetchLegalDocumentBaseResponse($response));

        $documentDetail1 = $this->createConsentDocumentForResponse('X_Privacy Policy');

        $documentDetail2 = $this->createConsentDocumentForResponse('X_Terms of Use');

        $response = new ConsentDocumentsManagerResponse();

        $response->setId(Constants::DUMMY_REQUEST_ID);

        $response->setStatus(Constant::SUCCESS);

        $response->setCountUnwrapped(2);

        $response->setDocumentsDetail([$documentDetail1, $documentDetail2]);

        $mock->method('getLegalDocumentsByRequestIdV2')
            ->willReturn(new FetchConsentDocumentBaseResponse($response));

        $this->app->instance('bvs_legal_document_manager', $mock);

        return $mock;
    }
}
