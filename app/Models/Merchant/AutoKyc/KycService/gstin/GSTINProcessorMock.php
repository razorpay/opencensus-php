<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\gstin;

use Requests_Response;
use Requests_Exception;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\Response;

class GSTINProcessorMock extends GSTINProcessor
{
    const LEGAL_NAME_AS_SIGNATORY = 'legal_name_as_signatory';

    protected $mockStatus = 'success';

    public function setMockStatus(string $status)
    {
        $this->mockStatus = $status;
    }

    public function process(): Response
    {
        $response = $this->getResponse([]);

        return new GSTINProcessorResponse($response);
    }

    protected function getResponse(array $request)
    {
        $response = new Requests_Response();

        $response->headers = ['Content-Type' => 'application/json'];

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
                                    'gstin'           => $this->input[Constants::GSTIN] ?? '',
                                    'kyc_id'          => 'ETZV0Tg3k0e7Ro',
                                    'signatory_names' => 'Shashank Kumar ,Harshil Mathur ',
                                    'legal_name'      => 'RELIANCE INDUSTRIES LIMITED',
                                    'address'         => 'SJR Cyber Laskar, Hosur Rd, Bengaluru, Karnataka 560030',
                                    'trade_name'      => 'RELIANCE INDUSTRIES LIMITED'
                                ],
                                Constants::DOCUMENT_TYPE => Constants::DOCUMENT_TYPES[Constants::GSTIN]
                            ]
                        ],
                        'request_id'  => 'deff5ed8-0460-11e9-a082-4742912ca12a',
                        'kyc_id'      => 'DqSqt9iTs0JDXW',
                        'status-code' => 101,
                    ]
                ];

                break;

            case self::LEGAL_NAME_AS_SIGNATORY:
                $body = [
                    'data' => [
                        'documents'   => [
                            [
                                'detail'                 => [
                                    'gstin'           => $this->input[Constants::GSTIN] ?? '',
                                    'kyc_id'          => 'ETZV0Tg3k0e7Ro',
                                    'signatory_names' => 'Shashank Kumar ,Harshil Mathur ',
                                    'legal_name'      => 'Shashank Kumar',
                                    'address'         => 'SJR Cyber Laskar, Hosur Rd, Bengaluru, Karnataka 560030',
                                    'trade_name'      => 'N.A.'
                                ],
                                Constants::DOCUMENT_TYPE => Constants::DOCUMENT_TYPES[Constants::GSTIN]
                            ]
                        ],
                        'request_id'  => 'deff5ed8-0460-11e9-a082-4742912ca12a',
                        'kyc_id'      => 'DqSqt9iTs0JDXW',
                        'status-code' => 101,
                    ]
                ];

                break;

            case Constants::FAILURE:
                throw new Requests_Exception('Error when fetching gstin data', 'timeout/downtime');

                break;
        }

        $response->body = json_encode($body);

        return $response;
    }
}
