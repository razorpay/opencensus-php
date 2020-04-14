<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\companyPan;

use Requests_Response;
use Requests_Exception;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\Response;

class CompanyPanProcessorMock extends CompanyPanProcessor
{
    protected $mockStatus = 'success';

    public function setMockStatus(string $status)
    {
        $this->mockStatus = $status;
    }

    protected function getResponse(array $request)
    {
        $response = new Requests_Response();

        $response->headers     = ['Content-Type' => 'application/json'];

        $response->status_code = 201;

        $body = null;

        switch ($this->mockStatus)
        {
            case Constants::INCORRECT_DETAILS:
                $response->status_code = 400;
                $body                  = [
                    'internal_error' => [
                        'code' => 'VALIDATION_ERROR'
                    ]
                ];

                break;

            case Constants::SUCCESS:
                $body = [
                    'data' => [
                        'documents'   => [
                            [
                                'detail'                 => [
                                    'name' => 'Test123',
                                ],
                                Constants::DOCUMENT_TYPE => Constants::DOCUMENT_TYPES[Constants::BUSINESS_PAN]
                            ]
                        ],
                        'request_id'  => 'deff5ed8-0460-11e9-a082-4742912ca12a',
                        'kyc_id'      => 'DqSqt9iTs0JDXW',
                        'status-code' => 101,
                    ]
                ];

                break;

            case Constants::FAILURE:
                throw new Requests_Exception('Error when fetching business pan data', 'timeout/downtime');

                break;
        }

        $response->body = json_encode($body);

        return $response;
    }

    public function process(): Response
    {
        $response = $this->getResponse([]);

        return new CompanyPanProcessorResponse($response);
    }
}
