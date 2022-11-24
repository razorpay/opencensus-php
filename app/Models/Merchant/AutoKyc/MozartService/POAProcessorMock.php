<?php

namespace RZP\Models\Merchant\AutoKyc\MozartService;

use \WpOrg\Requests\Response;
use \WpOrg\Requests\Exception as Requests_Exception;
use RZP\Models\Merchant\Detail\Constants;

class POAProcessorMock extends POAProcessor
{
    protected $documentType = Constants::AADHAR_FRONT;

    public function setDocumentType(string $status)
    {
        $this->documentType = $status;
    }

    protected function getResponse(array $request)
    {
        $response = new \WpOrg\Requests\Response();

        $response->headers = ['Content-Type' => 'application/json'];

        $response->status_code = 200;

        switch ($this->documentType)
        {
            case Constants::PASSPORT_FRONT:
                $body = $this->getMozarPayloadForPassportOcr();

                break;

            case Constants::VOTER_ID_FRONT:
                $body = $this->getMozartPayloadForVoterIdOcr();

                break;

            case Constants::AADHAR_FRONT:
                $body = $this->getMozartPayloadForAadharOcr();

                break;

            case Constants::AADHAAR_FRONT_COMPLETE:
                $body = $this->getMozartPayloadForAadhaarCompleteOcr();

                break;
            default:
                throw new Requests_Exception('Error when fetching ocr data', 'timeout/downtime');
        }

        $response->body = json_encode($body);

        return $response;
    }

    protected function getMozartPayloadForAadharOcr()
    {
        $body = [
            'data'              => [
                '_raw'    => '',
                'content' => [
                    'response' => [
                        'requestId'  => 'b00d3d45-22c8-4b0c-9a05-2475e7f92dad',
                        'result'     => [
                            [
                                'details' => [
                                    'aadhaar' => [
                                        'conf'     => 0.9,
                                        'isMasked' => 'no',
                                        'value'    => '823679449784'
                                    ],
                                    'dob'     => [
                                        'conf'  => 0,
                                        'value' => ''
                                    ],
                                    'father'  => [
                                        'conf'  => 0,
                                        'value' => ''
                                    ],
                                    'gender'  => [
                                        'conf'  => 1,
                                        'value' => 'MALE'
                                    ],
                                    'mother'  => [
                                        'conf'  => 0,
                                        'value' => ''
                                    ],
                                    'name'    => [
                                        'conf'  => 1,
                                        'value' => 'ABCDE FGHIJ'
                                    ],
                                    'qr'      => [
                                        'value' => ''
                                    ],
                                    'yob'     => [
                                        'conf'  => 0.9,
                                        'value' => '1992'
                                    ]
                                ],
                                'type'    => 'Aadhaar Front Bottom'
                            ]
                        ],
                        'statusCode' => 101
                    ]
                ],
                'status'  => 'successful'
            ],
            'error'             => null,
            'external_trace_id' => '',
            'mozart_id'         => 'bmercp99qbm0u76u3t9g',
            'next'              => [],
            'success'           => true
        ];

        return $body;
    }

    protected function getMozarPayloadForPassportOcr()
    {
        $body = [
            'data'              => [
                '_raw'    => '',
                'content' => [
                    'response' => [
                        'requestId'  => 'a8e1b113-5b49-45a0-af3a-e0fb81ac979f',
                        'result'     => [
                            [
                                'details' => [
                                    'countryCode'  => [
                                        'conf'  => 0.99,
                                        'value' => 'IND'
                                    ],
                                    'dob'          => [
                                        'conf'  => 0.9,
                                        'value' => '31/01/1995'
                                    ],
                                    'doe'          => [
                                        'conf'  => 0.9,
                                        'value' => '11/02/2023'
                                    ],
                                    'doi'          => [
                                        'conf'  => 0.9,
                                        'value' => '12/02/2013'
                                    ],
                                    'gender'       => [
                                        'conf'  => 0.75,
                                        'value' => 'MALE'
                                    ],
                                    'givenName'    => [
                                        'conf'  => 0.99,
                                        'value' => 'ABCDE FGHIJ'
                                    ],
                                    'mrz'          => [
                                        'conf'  => 0.75,
                                        'line1' => '',
                                        'line2' => ''
                                    ],
                                    'nationality'  => [
                                        'conf'  => 0.99,
                                        'value' => 'INDIAN'
                                    ],
                                    'passportNum'  => [
                                        'conf'  => 0.49,
                                        'value' => 'K9251785'
                                    ],
                                    'placeOfBirth' => [
                                        'conf'  => 0.99,
                                        'value' => 'MUMBAI, MAHARASHTRA'
                                    ],
                                    'placeOfIssue' => [
                                        'conf'  => 0.97,
                                        'value' => 'MUMBAI'
                                    ],
                                    'surname'      => [
                                        'conf'  => 0.99,
                                        'value' => 'GUPTA'
                                    ],
                                    'type'         => [
                                        'conf'  => 0.99,
                                        'value' => 'P'
                                    ]
                                ],
                                'type'    => 'Passport Front'
                            ]
                        ],
                        'statusCode' => 101
                    ]
                ],
                'status'  => 'successful'
            ],
            'error'             => null,
            'external_trace_id' => '',
            'mozart_id'         => 'bmer9119qbm0u76u3t90',
            'next'              => [],
            'success'           => true
        ];

        return $body;
    }

    protected function getMozartPayloadForVoterIdOcr()
    {
        $body = [
            'data'              => [
                '_raw'    => '',
                'content' => [
                    'response' => [
                        'requestId'  => '1fa38991-add6-47fb-9b05-498586bbe80a',
                        'result'     => [
                            [
                                'details' => [
                                    'age'      => [
                                        'conf'  => 1,
                                        'value' => '24'
                                    ],
                                    'dob'      => [
                                        'conf'  => 0,
                                        'value' => ''
                                    ],
                                    'doc'      => [
                                        'conf'  => 0.62,
                                        'value' => '1.1.2005'
                                    ],
                                    'gender'   => [
                                        'conf'  => 1,
                                        'value' => 'MALE'
                                    ],
                                    'name'     => [
                                        'conf'  => 1,
                                        'value' => 'ABCDE FGHIJ'
                                    ],
                                    'relation' => [
                                        'conf'  => 0.98,
                                        'value' => 'BALAICHARAN MANDAL'
                                    ],
                                    'voterid'  => [
                                        'conf'  => 1,
                                        'value' => 'LBT1381581'
                                    ]
                                ],
                                'type'    => 'Voterid Front'
                            ]
                        ],
                        'statusCode' => 101
                    ]
                ],
                'status'  => 'successful'
            ],
            'error'             => null,
            'external_trace_id' => '',
            'mozart_id'         => 'bmdl7lfm5e0jj2to72e0',
            'next'              => [],
            'success'           => true
        ];

        return $body;
    }

    protected function getMozartPayloadForAadhaarCompleteOcr() {
        $body = [
            'data'              => [
                '_raw'    => '',
                'content' => [
                    'response' => [
                        'requestId'  => 'b00d3d45-22c8-4b0c-9a05-2475e7f92dad',
                        'result'     => [
                            [
                                'details' => [
                                    'aadhaar' => [
                                        'conf'     => 0.9,
                                        'isMasked' => 'no',
                                        'value'    => '823679449784'
                                    ],
                                    'dob'     => [
                                        'conf'  => 0,
                                        'value' => ''
                                    ],
                                    'father'  => [
                                        'conf'  => 0,
                                        'value' => ''
                                    ],
                                    'gender'  => [
                                        'conf'  => 1,
                                        'value' => 'MALE'
                                    ],
                                    'address' => [
                                        'conf'  => 0,
                                        'value' => 'D.No:17-274,VijayaNagar, Machilipatnam - 521002'
                                    ],
                                    'name'    => [
                                        'conf'  => 1,
                                        'value' => 'ABCDE FGHIJ'
                                    ],
                                    'qr'      => [
                                        'value' => ''
                                    ],
                                    'yob'     => [
                                        'conf'  => 0.9,
                                        'value' => '1992'
                                    ]
                                ],
                                'type'    => 'Aadhaar Front Top'
                            ],
                            [
                                'details' => [
                                    'aadhaar' => [
                                        'conf'     => 0.9,
                                        'isMasked' => 'no',
                                        'value'    => '823679449784'
                                    ],
                                    'dob'     => [
                                        'conf'  => 0,
                                        'value' => ''
                                    ],
                                    'father'  => [
                                        'conf'  => 0,
                                        'value' => ''
                                    ],
                                    'gender'  => [
                                        'conf'  => 1,
                                        'value' => 'MALE'
                                    ],
                                    'mother'  => [
                                        'conf'  => 0,
                                        'value' => ''
                                    ],
                                    'name'    => [
                                        'conf'  => 1,
                                        'value' => 'ABCDE FGHIJ'
                                    ],
                                    'qr'      => [
                                        'value' => ''
                                    ],
                                    'yob'     => [
                                        'conf'  => 0.9,
                                        'value' => '1992'
                                    ]
                                ],
                                'type'    => 'Aadhaar Front Bottom'
                            ],

                        ],
                        'statusCode' => 101
                    ]
                ],
                'status'  => 'successful'
            ],
            'error'             => null,
            'external_trace_id' => '',
            'mozart_id'         => 'bmercp99qbm0u76u3t9g',
            'next'              => [],
            'success'           => true
        ];

        return $body;
    }
}
