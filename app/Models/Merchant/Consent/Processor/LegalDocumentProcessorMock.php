<?php

namespace RZP\Models\Merchant\Consent\Processor;

use RZP\Models\Merchant\Detail\Constants as DEConstants;
use Platform\Bvs\Legaldocumentmanager\V1\LegalDocumentsManagerResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\LegalDocumentBaseResponse;

class LegalDocumentProcessorMock extends LegalDocumentProcessor
{
    /**
     * @param array|null $input
     * @param string     $platform
     *
     * @return LegalDocumentBaseResponse
     */
    public function processLegalDocuments(array $input = null, string $platform = 'pg') {

        $response = new LegalDocumentsManagerResponse();

        $response->setId(DEConstants::DUMMY_REQUEST_ID);

        $response->setStatus(DEConstants::INITIATED);

        return new LegalDocumentBaseResponse($response);
    }
}
