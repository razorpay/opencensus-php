<?php

return [
    'testFetchMethodsAndOffers'                 => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/1cc/merchant/methods_offers?amount=10000',
        ],
        'response' => [
            'content' => [
                'enabled' => true,
                'methods'       => [
                    'upi'          => false,
                    'card'         => true,
                    'netbanking'   => true,
                    'wallet'       => [],
                    'paylater'     => [],
                    'cod'          => true,
                    'cardless_emi' => true,
                ],
                'offer_methods' => [
                    'card' => true,
                ],
            ],
        ],
    ],
    'testFetchMethodAndOffersForNon1ccMerchant' => [
        'request'   => [
            'method' => 'GET',
            'url'    => '/1cc/merchant/methods_offers?amount=10000',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => \RZP\Error\PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => \RZP\Error\ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],
];
