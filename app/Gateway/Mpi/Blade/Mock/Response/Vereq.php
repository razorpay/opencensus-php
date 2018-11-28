<?php

namespace RZP\Gateway\Mpi\Blade\Mock\Response;

use RZP\Gateway\Mpi\Blade\Mock\CardNumber;

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
            'url'      => $this->route->getUrl('mock_acs', ['gateway'=> 'mpi_blade']),
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
            'IReq' => [
                'iReqCode'   => '56',
                'vendorCode' => '1000',
                'iReqDetail' => 'VEReq.pan',
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
            'url'      => $this->route->getUrl('mock_acs', ['gateway'=> 'mpi_blade']),
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
            'url'      => $this->route->getUrl('mock_acs', ['gateway'=> 'mpi_blade']),
            'protocol' => 'ThreeDSecure'
        ];
    }

    public function unknownEnrolledResponse(string $paymentId)
    {
        return [
            'version'  => '1.0.2',
            'CH'       => [
                'enrolled' => 'U',
            ],
        ];
    }
}
