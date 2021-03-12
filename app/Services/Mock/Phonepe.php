<?php

namespace RZP\Services\Mock;

use RZP\Services\Phonepe as BasePhonepe;

class Phonepe extends BasePhonepe
{
    public function sendRequest($input)
    {
        $a = $input['a'];

        if($a == 1)
        {
            return [
                'overallHealth' => 'UP',
                'instruments'   => [
                    [
                        'instrument' => 'UPI',
                        'health'     => 'DOWN',
                        'providers'  => []
                    ]
                ]
            ];
        }
        elseif ($a == 2)
        {
            return [
                'overallHealth' => 'UP',
                'instruments'   => [
                    [
                        'instrument' => 'UPI',
                        'health'     => 'UP',
                        'providers'  => []
                    ]
                ]
            ];
        }
        elseif ($a == 3)
        {
            return [
                'overallHealth' => 'UP',
                'instruments'   => [
                    [
                        'instrument' => 'UPI',
                        'health'     => 'UP',
                        'providers'  => [
                            [
                                'providerType'  =>  'BANK',
                                'providerId'    =>  'PMCB',
                                'health'        =>  'DOWN',
                                'reason'        =>  'Not on UPI',
                            ]
                        ]
                    ]
                ]
            ];
        }

    }
}
