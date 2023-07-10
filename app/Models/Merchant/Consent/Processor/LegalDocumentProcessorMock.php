<?php

namespace RZP\Models\Merchant\Consent\Processor;


use RZP\Models\Merchant\Detail\Constants as DEConstants;
use Platform\Bvs\Legaldocumentmanager\V1\LegalDocumentsManagerResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\LegalDocumentBaseResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\ConsentDocumentBaseResponse;

class LegalDocumentProcessorMock extends LegalDocumentProcessor
{
    /**
     * @param array|null $input
     * @param string $platform
     * @param bool $isExpEnabled
     * @return LegalDocumentBaseResponse|ConsentDocumentBaseResponse
     */
    public function processLegalDocuments(array $input = null, string $platform = 'pg', bool $isExpEnabled = false): LegalDocumentBaseResponse|ConsentDocumentBaseResponse
    {
        $response = new LegalDocumentsManagerResponse();

        $response->setId(DEConstants::DUMMY_REQUEST_ID);

        $response->setStatus(DEConstants::INITIATED);

        return new LegalDocumentBaseResponse($response);
    }
}
