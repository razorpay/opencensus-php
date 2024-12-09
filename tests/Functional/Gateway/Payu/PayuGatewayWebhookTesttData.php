<?php


use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayuCardsStaticCallback' => [
        'url'     => '/callback/payu',
        'method'  => 'post',
        'content' => [
            'field7' =>  'AUTHPOSITIVE',
            'field8' =>  'AUTHORIZED',
            'field9' =>  'Transaction is Successful',
            'payment_source' =>  'payu',
            'PG_TYPE' =>  'CC-PG',
            'threeDSVersion' =>  '2.2.0',
            'card_hash' =>  '69e58536f06b09e4c44f8df9fcca84ad58f98d03c551de4e4a52f9337d9f66ca',
            'mihpayid' =>  '21681120125',
            'furl' =>  'https => //api.razorpay.com/pg_router/v1/payments/pay_PQGVdrl5rb4KOo/callback/1452e63efc42dca1d64733a912d209fc924919b7',
            'cardToken' =>  '',
            'net_amount_debit' =>  '93',
            'key' =>  '9FFG4r',
            'discount' =>  '0.00',
            'txnid' =>  '',
            'offer_key' =>  '',
            'amount' =>  '93.00',
            'unmappedstatus' =>  'captured',
            'addedon' =>  '2024-11-27 14 => 05 => 51',
            'hash' =>  '7e0c967e638a240160b87a347cbfb92b4bc2594a2551d18f24f9ef14d74dc10c13c8b37cca01d1e5cf1901186850bcbf26e7031eca27531c9c6ba876d93bb4b4',
            'productinfo' =>  'XXXX',
            'bank_ref_no' =>  '7326965773656964805964',
            'firstname' =>  '****************************',
            'bank_ref_num' =>  '7326965773656964805964',
            'lastname' =>  '',
            'mode' =>  'CC',
            'address2' =>  '',
            'city' =>  '',
            'state' =>  '',
            'country' =>  '',
            'zipcode' =>  '',
            'email' =>  '****************************',
            'phone' =>  '**********',
            'curl' =>  'https => //api.razorpay.com/pg_router/v1/payments/callback/1452e63efc42dca1d64733a912d209fc924919b7',
            'udf1' =>  '146465626',
            'surl' =>  'https => //api.razorpay.com/pg_router/v1/payments/callback/1452e63efc42dca1d64733a912d209fc924919b7',
            'udf2' =>  '',
            'bankcode' =>  'CC',
            'udf3' =>  '',
            'udf4' =>  '',
            'udf5' =>  '',
            'udf6' =>  '',
            'udf7' =>  '',
            'udf8' =>  '',
            'status' =>  'success',
            'udf10' =>  '',
            'card_token' =>  '',
            'offer_availed' =>  '',
            'field0' =>  '',
            'error_Message' =>  'No Error',
            'field2' =>  '184370',
            'error' =>  'E000',
            'address1' =>  '',
            'field4' =>  '',
            'field5' =>  '00',
            'udf9' =>  '',
            'field6' =>  '05'
        ]
    ],
];
