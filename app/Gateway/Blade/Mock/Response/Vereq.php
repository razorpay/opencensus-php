<?php

namespace RZP\Gateway\Blade\Mock\Response;

use RZP\Gateway\Blade\Mock\CardNumber;

class Vereq
{
    public function __construct($route)
    {
        $this->route = $route;
    }

    public function enrolledValidResponse(string $paymentId, string $cardNo)
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
