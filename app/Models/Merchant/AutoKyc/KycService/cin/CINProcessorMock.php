<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\cin;

use Requests_Response;
use Requests_Exception;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\Response;

class CINProcessorMock extends CINProcessor
{
    protected $mockStatus = 'success';

    public function setMockStatus(string $status)
    {
        $this->mockStatus = $status;
    }


    /**
     * @param array $request
     *
     * @return Requests_Response
     * @throws Requests_Exception
     */
    protected function getResponse(array $request)
    {
        $response = new Requests_Response();

        $response->headers = ['Content-Type' => 'application/json'];

        $response->status_code = 201;

        $body = [];

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
                                    'company_name'       => 'company_name',
                                    'kyc_id'             => 'ETZV0Tg3k0e7Ro',
                                    'registered_address' => '1401, RAHEJA CENTRE, FREE PRESS JOURNAL MARG,NARIMAN POINT MUMBAI MH 400021 IN ',
                                    'signatory_details'  => '[{\"din\":\"01243493\",\"full_name\":\"pankaj kumar\",\"designation\":\"Director\",\"dob\":\"19-11-1969\",\"tenureEndDate\":\"11-10-2021\",\"fatherName\":\"JITENDRA JAGANNATH MEHTA\",\"address\":\"D3/D4 AMALFI  L  D RUPAREL MARG  MALABAR HILL MUMBAI 400006 MH IN\",\"pan\":\"\",\"tenureBeginDate\":\"22-06-2005\"},{\"din\":\"01654135\",\"name\":\"JITENDRA MEHTA JAGANNATH\",\"designation\":\"Director\",\"dob\":\"12-11-1945\",\"tenureEndDate\":\"12-09-2019\",\"fatherName\":\"JAGANNATH ANANTRAI MEHTA\",\"address\":\"D-3/D-4  AMALFI 15   L.D. RUPAREL MARG MALABAR HILL MUMBAI 400006 MH IN\",\"pan\":\"AAPPM2381K\",\"tenureBeginDate\":\"22-06-2005\"},{\"din\":\"06456743\",\"name\":\"KAMLESH NANDANIYA VAJUBHAI\",\"designation\":\"Director\",\"dob\":\"01-10-1980\",\"tenureEndDate\":\"23-08-2020\",\"fatherName\":\"VAJUBHAI MALDEV NANDANIYA\",\"address\":\"G-11  SHANTI APARTMENT-2  JAY AMBE RAOD  BHAYANDER (W) THANE 401101 MH IN\",\"pan\":\"\",\"tenureBeginDate\":\"25-02-2013\"},{\"din\":\"07324557\",\"name\":\"DAKSHA PIYUSH KAKADIYA\",\"designation\":\"Additional Director\",\"dob\":\"19-08-1981\",\"tenureEndDate\":\"16-08-2021\",\"fatherName\":\"BABUBHAI MOHANBHAI BHALALA\",\"address\":\"A/102  RAMESHWAR DARSHAN CHSL  SHIMPOLI ROAD KASTUR PARK  BORIVALI WEST MUMBAI 400092 MH IN\",\"pan\":\"\",\"tenureBeginDate\":\"26-10-2015\"}]',
                                ],
                                Constants::DOCUMENT_TYPE => Constants::DOCUMENT_TYPES[Constants::CIN]
                            ]
                        ],
                        'request_id'  => 'deff5ed8-0460-11e9-a082-4742912ca12a',
                        'kyc_id'      => 'DqSqt9iTs0JDXW',
                        'status-code' => 101,
                    ]
                ];

                break;

            case Constants::FAILURE:
                throw new Requests_Exception('Error when fetching business cin data', 'timeout/downtime');

                break;
        }

        $response->body = json_encode($body);

        return $response;
    }

    public function process(): Response
    {
        $response = $this->getResponse([]);

        return new CINProcessorResponse($response);
    }
}
