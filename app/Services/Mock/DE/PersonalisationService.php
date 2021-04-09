<?php


namespace RZP\Services\Mock\DE;


class PersonalisationService
{
    public function fetchPersonalisationData(array $input, bool $upiIntent = false, bool $nullResponse = false)
    {
        if ($nullResponse === true)
        {
            return null;
        }

        $response = (new \Requests_Response());

        $upiIntrument = [
            'instrument' => 'abcd@okhdfc',
            'method'     => 'upi',
            'score'      =>  0.34
        ];

        if ($upiIntent === true)
        {
            $upiIntrument['instrument'] = '@ybl';
        }

        $instruments = [
                    [
                        'instrument' => 'phonepay',
                        'method'     => 'wallet',
                        'score'      =>  0.54
                    ],
                    [
                        'instrument' => '100000002lcard',
                        'method'     => 'card',
                        'score'      => 0.20
                    ],
                    [
                        'instrument' => 'icici_bank',
                        'method'     => 'netbanking',
                        'score'      => 0.21,
                    ]
        ];


        $responseArray = [
            "is_customer_identified"    => true,
            "user_aggregates_available" => false,
            'preferences' => $instruments,
        ];

        if ( isset($input['app_token']) === false && isset($input['customer_id']) === false)
        {
            $responseArray['is_customer_identified'] = false;
        }

        $response->body = json_encode($responseArray);

        return $response;
    }

}
