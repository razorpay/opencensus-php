<?php

namespace RZP\Gateway\Mpi\Blade\Mock\Response;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Mpi\Blade\Mock\CardNumber;

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
                'time'          => Carbon::createFromTimestamp(time(), Timezone::IST)->format('Ymd H:m:s'),
                'status'        => 'Y',
                'cavv'          => 'AAABBJg0VhI0VniQEjRWAAAAAAA=',
                'eci'           => '02',
                'cavvAlgorithm' => '2',
            ]
        ];
    }

    public function enrolledValidVisaResponse(array $content)
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
                'time'          => Carbon::createFromTimestamp(time(), Timezone::IST)->format('Ymd H:m:s'),
                'status'        => 'Y',
                'cavv'          => 'AAABBJg0VhI0VniQEjRWAAAAAAA=',
                'eci'           => '05',
                'cavvAlgorithm' => '2',
            ]
        ];
    }

    public function internationalVisaResponse(array $content)
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
                'time'          => Carbon::createFromTimestamp(time(), Timezone::IST)->format('Ymd H:m:s'),
                'status'        => 'A',
                'cavv'          => 'AAABBJg0VhI0VniQEjRWAAAAAAA=',
                'eci'           => '06',
                'cavvAlgorithm' => '2',
            ]
        ];
    }

    public function internationalMasterResponse(array $content)
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
                'time'          => Carbon::createFromTimestamp(time(), Timezone::IST)->format('Ymd H:m:s'),
                'status'        => 'A',
                'cavv'          => 'AAABBJg0VhI0VniQEjRWAAAAAAA=',
                'eci'           => '01',
                'cavvAlgorithm' => '2',
            ]
        ];
    }

    public function invalidEci(array $content)
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
                'time'          => Carbon::createFromTimestamp(time(), Timezone::IST)->format('Ymd H:m:s'),
                'status'        => 'A',
                'cavv'          => 'AAABBJg0VhI0VniQEjRWAAAAAAA=',
                'eci'           => '07',
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

    public function paresWithErrorCode()
    {
        return [
            'Error' =>[
                'version'   => null,
                'errorCode' => '98',
                'errorMessage' => 'Transient system failure',
                'errorDetail' => null,
                'vendorCode' => 'TEMPORARY SYSTEM FAILURE HAS OCCURED',
            ],
        ];
    }
}
