<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
   'testIntlPaymentWhenNotAllowed' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED
        ],
   ], 
   
   'testCreatePaymentInEs' => [
       'type' => 'payments',
       'body' => [
           'doc' => [
               'notes' => [
                   'merchant_order_id' => 'random order id'
               ],
           ],
           'upsert' => [
               'merchant_id' => '10000000000000', 
               'notes' => [
                   'merchant_order_id' => 'random order id'
               ]
           ],
       ],
   ],
    
];