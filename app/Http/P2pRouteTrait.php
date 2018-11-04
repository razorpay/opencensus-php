<?php

namespace RZP\Http;

use RZP\Http\Controllers\P2p\Requests;

trait P2pRouteTrait
{
    protected static $p2pRoutes = [
        Requests::P2P_CUSTOMER_START_VERIFICATION =>
            [
                'post',
                'p2p/customers/verification/start',
                'P2p\CustomerController@startVerification',
            ],
        Requests::P2P_CUSTOMER_VERIFICATION_STATUS =>
            [
                'get',
                'p2p/customers/verification/{token}',
                'P2p\CustomerController@verificationStatus'
            ],
        Requests::P2P_CUSTOMER_CREATE =>
            [
                'post',
                'p2p/customers',
                'P2p\CustomerController@create'
            ],
        Requests::P2P_CUSTOMER_DEVICE_CREATE =>
            [
                'post',
                'p2p/customers/{customer_id}/devices',
                'P2p\DeviceController@create'
            ],
        Requests::P2P_CUSTOMER_INITIATE_FETCH_BALANCE =>
            [
                'post',
                'p2p/customers/{customer_id}/bank_accounts/{ba_id}/balance/initiate',
                'P2p\BankAccountController@initiateFetchBalance'
            ],
    ];

    public static $p2p = [
        Requests::P2P_CUSTOMER_START_VERIFICATION,
        Requests::P2P_CUSTOMER_VERIFICATION_STATUS,
        Requests::P2P_CUSTOMER_CREATE,

        Requests::P2P_CUSTOMER_DEVICE_CREATE,

        Requests::P2P_CUSTOMER_INITIATE_FETCH_BALANCE,
    ];

}
