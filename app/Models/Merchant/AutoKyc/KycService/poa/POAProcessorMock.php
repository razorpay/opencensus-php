<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\poa;

use Requests_Response;
use Requests_Exception;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\AutoKyc\Response;

class POAProcessorMock extends POAProcessor
{
    /**
     * @var string
     */
    protected $documentType;

    /**
     * @param string $documentType
     */
    public function setDocumentType(string $documentType): void
    {
        $this->documentType = $documentType;
    }

    public function getResponse(array $request)
    {
        $response = new Requests_Response();

        $response->headers = ['Content-Type' => 'application/json'];

        $response->status_code = 201;

        $body = null;

        switch ($this->documentType)
        {
            case Type::PASSPORT:

                $body = $this->getKycPayloadForPassportOcr();

                break;
            case Type::VOTERS_ID:

                $body = $this->getKycPayloadForVoterIdOcr();

                break;
            case Type::AADHAAR:

                $body = $this->getKycPayloadForAadharOcr();

                break;
            default:

                throw new Requests_Exception('Error when fetching ocr data', 'timeout/downtime');
        }

        $response->body = json_encode($body);

        return $response;
    }

    protected function getKycPayloadForPassportOcr(): array
    {
        $response = [
            'data'   => [
                'customer_id' => '1213',
                'documents'   => [
                    0 => [
                        'detail'        => [
                            'address'               => '',
                            'address_split'         => '',
                            'country'               => 'IND',
                            'date_of_birth'         => '1991-12-06T00:00:00Z',
                            'date_of_expiry'        => '2030-12-02T00:00:00Z',
                            'father_name'           => '',
                            'file_number'           => '',
                            'gender'                => 'MALE',
                            'id'                    => 'DtETwZJdeqzEk1',
                            'kyc_id'                => 'DtESL9dtPMY2Io',
                            'machine_readable_zone' => ' ',
                            'mother_name'           => '',
                            'name'                  => 'ABCDE FGHIJ',
                            'old_place_of_issue'    => '',
                            'online_validated'      => false,
                            'passport_type'         => '',
                            'pin'                   => '',
                            'place_of_issue'        => '',
                            'provider_id'           => '',
                            'spouse_name'           => '',
                            'ufh_file_id'           => '',
                        ],
                        'document_type' => 'passport',
                    ],
                ],
                'kyc_id'      => 'DtESL9dtPMY2Io',
            ],
            'status' => 'success',
        ];

        return $response;
    }

    protected function getKycPayloadForAadharOcr(): array
    {
        $response = [
            'data'   => [
                'customer_id' => '1213',
                'documents'   => [
                    0 => [
                        'detail'        => [
                            'YearOfBirth'       => '',
                            'address'           => '',
                            'date_of_birth'     => '',
                            'father_name'       => '',
                            'gender'            => '',
                            'husband_name'      => '',
                            'id'                => '',
                            'kyc_id'            => 'DsoYgHxZwe5jKO',
                            'masked_image_url'  => '',
                            'mother_name'       => '',
                            'name'              => 'ABCDE FGHIJ',
                            'offline_validated' => false,
                            'online_validated'  => false,
                            'pincode'           => '',
                            'provider_id'       => '',
                            'ufh_file_id'       => '',
                        ],
                        'document_type' => 'aadhaar',
                    ],
                ],
                'kyc_id'      => 'DsoYgHxZwe5jKO',
            ],
            'status' => 'success',
        ];

        return $response;
    }

    protected function getKycPayloadForVoterIdOcr(): array
    {
        $response = [
            'data'   => [
                'customer_id' => '1213',
                'documents'   => [
                    0 => [
                        'detail'        => [
                            'IModel'              => null,
                            'address'             => '',
                            'address_split'       => '',
                            'age'                 => '',
                            'date_of_birth'       => null,
                            'date_of_calculation' => null,
                            'date_of_issue'       => null,
                            'gender'              => '',
                            'id'                  => 'DtEHsytB4ijzaY',
                            'kyc_id'              => 'DtEHl9Q8b2VoE0',
                            'name'                => 'ABCDE FGHIJ',
                            'offline_validated'   => false,
                            'online_validated'    => false,
                            'pin'                 => '',
                            'relative_type'       => '',
                            'type'                => '',
                        ],
                        'document_type' => 'voters_id',
                    ],
                ],
                'kyc_id'      => 'DtEHl9Q8b2VoE0',
            ],
            'status' => 'success',
        ];

        return $response;
    }

    public function process(): Response
    {
        $response = $this->getResponse([]);

        return new POAProcessorResponse($response);
    }
}
