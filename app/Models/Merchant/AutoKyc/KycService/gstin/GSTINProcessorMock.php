<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\gstin;

use Requests_Response;
use Requests_Exception;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\Response;

class GSTINProcessorMock extends GSTINProcessor
{
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
                                    'central_jurisdiction'      => '',
                                    'central_jurisdiction_code' => '',
                                    'constitution'              => '',
                                    'gstin'                     => $this->input[Constants::GSTIN] ?? '',
                                    'kyc_id'                    => 'ETZV0Tg3k0e7Ro',
                                    'signatory_names'           => 'Shashank Kumar ,Harshil Mathur ',
                                    'legal_name'                => 'RELIANCE INDUSTRIES LIMITED',
                                    'address'                   => 'SJR Cyber Laskar, Hosur Rd, Bengaluru, Karnataka 560030',
                                    'nature_of_business'        => '',
                                    'offline_validated'         => false,
                                    'online_validated'          => true,
                                    'provider_id'               => '',
                                    'state_jurisdiction'        => '',
                                    'state_jurisdiction_code'   => '',
                                    'status'                    => 'Active',
                                    'tax_payer_type'            => 'Regular',
                                    'trade_name'                => 'RELIANCE INDUSTRIES LIMITED'
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
