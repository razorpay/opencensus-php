<?php

namespace RZP\Gateway\Blade\Mock\Response;

use Carbon\Carbon;
use RZP\Gateway\Blade\Mock\CardNumber;

class Pareq
{
    public function __construct($route)
    {
        $this->route = $route;
    }

    public function enrolledValidResponse(array $content)
    {
        $accId = $content['Message']['PAReq']['CH']['acctID'];

        $reqMerchant = $content['Message']['PAReq']['Merchant'];

        return [
            '@attributes' => [
                'id'   => '122345',
            ],
            'version'           => '1.0.2',
            'Merchant' => [
                'acqBIN'        => $reqMerchant['acqBIN'],
                'merID'         => $reqMerchant['merID'],
            ],
            'Purchase'          => $content['Message']['PAReq']['Purchase'],
            'pan'               => CardNumber::getCardNumberFromAccId($accId),
            'TX' => [
                'time'          => Carbon::createFromTimestamp(time(), 'Asia/Kolkata')->format('Ymd H:m:s'),
                'status'        => 'Y',
                'cavv'          => 'AAABBJg0VhI0VniQEjRWAAAAAAA=',
                'eci'           => '05',
                'cavvAlgorithm' => '2',
            ]
        ];
    }

    public function notEnrolledValidResponse(string $paymentId, string $cardNo)
    {
        return [
            'version'  => '1.0.2',
            'CH'       => [
                'enrolled' => 'N',
            ],
        ];
    }

    public function differentMessageResponse(string $paymentId, string $cardNo)
    {
        return [
            'version'  => '1.0.2',
            'CH'       => [
                'enrolled' => 'Y',
                'acctID'   => CardNumber::getAccId($cardNo),
            ],
            'url'      => $this->route->getUrl('mock_acs', ['gateway'=> 'blade']),
            'protocol' => 'ThreeDSecure'
        ];
    }

    public function blankMessageResponse(string $paymentId, string $cardNo)
    {
        return [];
    }

    public function invalidVersionFormat(string $paymentId, string $cardNo)
    {
        return [
            'version'  => '2',
            'CH'       => [
                'enrolled' => 'Y',
                'acctID'   => CardNumber::getAccId($cardNo),
            ],
            'url'      => $this->route->getUrl('mock_acs', ['gateway'=> 'blade']),
            'protocol' => 'ThreeDSecure'
        ];
    }
}
