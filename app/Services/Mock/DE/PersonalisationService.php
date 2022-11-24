<?php


namespace RZP\Services\Mock\DE;


class PersonalisationService
{
    public function fetchPersonalisationData(array $input, array $data, bool $upiIntent = false, bool $nullResponse = false)
    {
        $contact = null;
        if(isset($data['customer'])){
            if(isset($data['customer']['contact'])){
                $contact = $data['customer']['contact'];
            }
        }

        if ($nullResponse === true)
        {
            return null;
        }

        $response = (new \WpOrg\Requests\Response());

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
                        'instrument' => 'paytm',
                        'method'     => 'wallet',
                        'score'      =>  0.54
                    ],
                    [
                        'instrument' => 'SBIN',
                        'method'     => 'netbanking',
                        'score'      => 0.21,
                    ],
                    [
                        'instrument' => '100000002lcard',
                        'method'     => 'card',
                        'score'      => 0.20
                    ]
        ];

        if($contact === "+919999999909"){
            $instruments = [
                [
                    'instrument' => 'freecharge',
                    'method'     => 'wallet',
                    'score'      =>  0.54
                ],
                [
                    'instrument' => 'SBIN',
                    'method'     => 'netbanking',
                    'score'      => 0.21,
                ],
                [
                    'instrument' => 'HEyHQ6jw2vd6aE',
                    'method'     => 'card',
                    'issuer'     => 'KKBK',
                    'type'       => 'debit',
                    'network'    => 'Visa',
                    'score'      =>  0.15
                ]
            ];
        } else if($contact === "+919999999908"){
            $instruments = [
                [
                    'instrument' => 'freecharge',
                    'method'     => 'wallet',
                    'score'      =>  0.54
                ],
                [
                    'instrument' => 'SBIN',
                    'method'     => 'netbanking',
                    'score'      => 0.55,
                ],
                [
                    'instrument' => 'Scbaala@okhdfcbank',
                    'method'     => 'upi',
                    'score'      =>  0.60
                ]
            ];
        }


        $responseArray = [
            "is_customer_identified"    => true,
            "user_aggregates_available" => false,
            'preferences' => $instruments,
            'versionID'   => 'v2',
        ];

        if ( isset($input['app_token']) === false && isset($input['customer_id']) === false)
        {
            $responseArray['is_customer_identified'] = false;
        }

        $response->body = json_encode($responseArray);

        return $response;
    }

}
