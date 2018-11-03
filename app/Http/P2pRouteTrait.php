<?php

namespace RZP\Http;

trait P2pRouteTrait
{
    protected static $p2pRoutes = [
        'p2p_cust_start_verification' =>
            [
                'post',
                'customers/verification/start',
                'P2p\CustomerController@getP2p',
            ],
        'p2p_cust_initiate_fetch_balance' =>
            [
                'post',
                'customers/{customer_id}/bank_accounts/{ba_id}/balance/initiate',
                'P2p\BankAccountController@intiateFetchBalance'
            ],
    ];

    public static $p2p = [
        'p2p_cust_verification_start',
        'p2p_cust_initiate_fetch_balance',
    ];
}
