<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\kycDetails;

use Requests_Response;
use Requests_Exception;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\Detail\Constants;

class KycDetailProcessorMock extends KycDetailsProcessor
{
    /**
     * @var string
     */
    protected $mockStatus = 'success';

    public function setMockStatus(string $status)
    {
        $this->mockStatus = $status;
    }

    /**
     * @var array
     */
    protected $documentTypes;

    public function setDocumentTypes(array $documentTypes)
    {
        $this->documentTypes = $documentTypes;
    }

    protected function getResponse(array $request)
    {
        $response = new Requests_Response();

        $response->headers = ['Content-Type' => 'application/json'];

        $response->status_code = 200;

        $body = null;

        if (Constants::SUCCESS === $this->mockStatus)
        {
            $body = $this->getSuccessResponse();
        }
        else
        {
            throw new Requests_Exception('Error when fetching kyc data', 'timeout/downtime');
        }

        $response->body = json_encode($body);

        return $response;
    }

    private function getSuccessResponse(): array
    {
        $documents = [];

        foreach ($this->documentTypes as $documentType)
        {
            array_push($documents, $this->getDocumentBlock($documentType));
        }

        return [
            'data'   => [
                'customer_id' => '1213',
                'documents'   => $documents,
                'kyc_id'      => 'DqSqt9iTs0JDXW',
            ],
            'status' => 'success',
        ];
    }

    private function getDocumentBlock($documentType)
    {
        return [
            Constants::DOCUMENT_TYPE => $documentType ?? 'personal_pan',
        ];
    }

    public function process(): Response
    {
        $response = $this->getResponse([]);

        return new KycDetailProcessorResponse($response);
    }
}
