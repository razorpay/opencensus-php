<?php

namespace RZP\Http;

use RZP\Http\Controllers\P2p\Requests;

trait P2pRouteTrait
{
    protected static $p2pRoutes = [
        /*************** Customers ****************/
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
        Requests::P2P_CUSTOMER_DELETE =>
            [
                'delete',
                'p2p/customers',
                'P2p\CustomerController@delete'
            ],

        /*************** Devices ******************/
        Requests::P2P_CUSTOMER_DEVICE_CREATE =>
            [
                'post',
                'p2p/customers/{customer_id}/devices',
                'P2p\DeviceController@create'
            ],
        Requests::P2P_CUSTOMER_DEVICE_FETCH =>
            [
                'get',
                'p2p/customers/{customer_id}/devices',
                'P2p\DeviceController@fetch'
            ],
        Requests::P2P_CUSTOMER_DEVICE_REFRESH_TOKEN =>
            [
                'post',
                'p2p/customers/{customer_id}/devices/cl_token_refresh',
                'P2p\DeviceController@refreshClToken'
            ],
        Requests::P2P_CUSTOMER_DEVICE_DELETE =>
            [
                'delete',
                'p2p/customers/{customer_id}/devices',
                'P2p\DeviceController@delete'
            ],

        /*************** Bank Account **************/
        Requests::P2P_BANKS_FETCH_ALL =>
            [
                'get',
                'p2p/banks',
                'P2p\BankAccountController@fetchBanks'
            ],
        Requests::P2P_CUSTOMER_BA_RETRIEVE =>
            [
                'get',
                'p2p/customers/{customer_id}/bank_accounts/bank/{bank_code}',
                'P2p\BankAccountController@retrieve'
            ],
        Requests::P2P_CUSTOMER_BA_FETCH_ALL =>
            [
                'get',
                'p2p/customers/{customer_id}/bank_accounts',
                'P2p\BankAccountController@fetchAll'
            ],
        Requests::P2P_CUSTOMER_BA_FETCH =>
            [
                'get',
                'p2p/customers/{customer_id}/bank_accounts/{ba_id}',
                'P2p\BankAccountController@fetch'
            ],
        Requests::P2P_CUSTOMER_BA_INITIATE_SET_UPI_PIN =>
            [
                'get',
                'p2p/customers/{customer_id}/bank_accounts/{ba_id}/upipin/initiate',
                'P2p\BankAccountController@initiateSetUpiPin'
            ],
        Requests::P2P_CUSTOMER_BA_SET_UPI_PIN =>
            [
                'post',
                'p2p/customers/{customer_id}/bank_accounts/{ba_id}/upi_pin',
                'P2p\BankAccountController@setUpiPin'
            ],
        Requests::P2P_CUSTOMER_BA_INITIATE_FETCH_BALANCE =>
            [
                'get',
                'p2p/customers/{customer_id}/bank_accounts/{ba_id}/balance/initiate',
                'P2p\BankAccountController@initiateFetchBalance'
            ],
        Requests::P2P_CUSTOMER_BA_FETCH_BALANCE =>
            [
                'post',
                'p2p/customers/{customer_id}/bank_accounts/{ba_id}/balance/',
                'P2p\BankAccountController@fetchBalance'
            ],

        /****************** VPA *******************/
        Requests::P2P_HANDLES_FETCH_ALL =>
            [
                'get',
                'p2p/handles',
                'P2p\VpaController@fetchHandles'
            ],
        Requests::P2P_CUSTOMER_VPA_CREATE =>
            [
                'post',
                'p2p/customers/{customer_id}/vpa',
                'P2p\VpaController@create'
            ],
        Requests::P2P_CUSTOMER_VPA_FETCH_ALL =>
            [
                'get',
                'p2p/customers/{customer_id}/vpa',
                'P2p\VpaController@fetchAll'
            ],
        Requests::P2P_CUSTOMER_VPA_FETCH =>
            [
                'post',
                'p2p/customers/{customer_id}/vpa/{vpa_id}',
                'P2p\VpaController@fetch'
            ],
        Requests::P2P_CUSTOMER_VPA_ASSIGN_BANK_ACCOUNT =>
            [
                'post',
                'p2p/customers/{customer_id}/vpa/{vpa_id}/assign/{ba_id}',
                'P2p\VpaController@assignBankAccount'
            ],
        Requests::P2P_CUSTOMER_VPA_CHECK_AVAILABILITY =>
            [
                'post',
                'p2p/customers/{customer_id}/vpa/available',
                'P2p\VpaController@checkAvailability'
            ],
        Requests::P2P_CUSTOMER_VPA_DELETE =>
            [
                'post',
                'p2p/customers/{customer_id}/vpa/{vpa_id}',
                'P2p\VpaController@delete'
            ],

        /************* Beneficiaries **************/
        Requests::P2P_CUSTOMER_BENEFICIARIES =>
            [
                'post',
                'p2p/customers/{customer_id}/beneficiaries',
                'P2p\BeneficiaryController@create'
            ],
        Requests::P2P_CUSTOMER_BENEFICIARIES_VALIDATE =>
            [
                'post',
                'p2p/customers/{customer_id}/beneficiaries',
                'P2p\BeneficiaryController@validate'
            ],
        Requests::P2P_CUSTOMER_BENEFICIARIES_FETCH_ALL =>
            [
                'get',
                'p2p/customers/{customer_id}/beneficiaries',
                'P2p\BeneficiaryController@fetchAll'
            ],

        /************* Transactions **************/
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_PAY =>
            [
                'post',
                'p2p/customers/{customer_id}/transactions/pay/initiate',
                'P2p\TransactionController@initiatePay'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_COLLECT =>
            [
                'post',
                'p2p/customers/{customer_id}/transactions/collect/initiate',
                'P2p\TransactionController@initiateCollect'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_FETCH_ALL =>
            [
                'get',
                'p2p/customers/{customer_id}/transactions/',
                'P2p\TransactionController@fetchAll'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_FETCH =>
            [
                'post',
                'p2p/customers/{customer_id}/transactions/{transaction_id}',
                'P2p\TransactionController@fetch'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_AUTHORIZE =>
            [
                'get',
                'p2p/customers/{customer_id}/transactions/{transaction_id}/authorize/initiate',
                'P2p\TransactionController@initiateAuthorize'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE =>
            [
                'post',
                'p2p/customers/{customer_id}/transactions/{transaction_id}/authorize',
                'P2p\TransactionController@authorize'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_REJECT_COLLECT =>
            [
                'post',
                'p2p/customers/{customer_id}/transactions/{transaction_id}/reject',
                'P2p\TransactionController@reject'
            ],
    ];

    public static $p2p = [
        Requests::P2P_CUSTOMER_START_VERIFICATION,
        Requests::P2P_CUSTOMER_VERIFICATION_STATUS,
        Requests::P2P_CUSTOMER_CREATE,
        Requests::P2P_CUSTOMER_DELETE,

        Requests::P2P_CUSTOMER_DEVICE_CREATE,
        Requests::P2P_CUSTOMER_DEVICE_FETCH,
        Requests::P2P_CUSTOMER_DEVICE_REFRESH_TOKEN,
        Requests::P2P_CUSTOMER_DEVICE_DELETE,

        Requests::P2P_BANKS_FETCH_ALL,
        Requests::P2P_CUSTOMER_BA_RETRIEVE,
        Requests::P2P_CUSTOMER_BA_FETCH_ALL,
        Requests::P2P_CUSTOMER_BA_FETCH,
        Requests::P2P_CUSTOMER_BA_INITIATE_SET_UPI_PIN,
        Requests::P2P_CUSTOMER_BA_SET_UPI_PIN,
        Requests::P2P_CUSTOMER_BA_INITIATE_FETCH_BALANCE,
        Requests::P2P_CUSTOMER_BA_FETCH_BALANCE,

        Requests::P2P_HANDLES_FETCH_ALL,
        Requests::P2P_CUSTOMER_VPA_CREATE,
        Requests::P2P_CUSTOMER_VPA_FETCH_ALL,
        Requests::P2P_CUSTOMER_VPA_FETCH,
        Requests::P2P_CUSTOMER_VPA_ASSIGN_BANK_ACCOUNT,
        Requests::P2P_CUSTOMER_VPA_CHECK_AVAILABILITY,
        Requests::P2P_CUSTOMER_VPA_DELETE,

        Requests::P2P_CUSTOMER_BENEFICIARIES,
        Requests::P2P_CUSTOMER_BENEFICIARIES_VALIDATE,
        Requests::P2P_CUSTOMER_BENEFICIARIES_FETCH_ALL,

        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_PAY,
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_COLLECT,
        Requests::P2P_CUSTOMER_TRANSACTIONS_FETCH_ALL,
        Requests::P2P_CUSTOMER_TRANSACTIONS_FETCH,
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_AUTHORIZE,
        Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE,
        Requests::P2P_CUSTOMER_TRANSACTIONS_REJECT_COLLECT,
    ];

}
