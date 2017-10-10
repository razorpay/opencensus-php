<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Gateway
    |--------------------------------------------------------------------------
    |
    */

    'available' => [
        'amex',
        'atom',
        'axis_genius',
        'axis_migs',
        'billdesk',
        'blade',
        'cybersource',
        'hitachi',
        'first_data',
        'ebs',
        'hdfc',
        'kotak',
        'mobikwik',
        'paytm',
        'netbanking_hdfc',
        'netbanking_corporation',
        'netbanking_kotak',
        'netbanking_icici',
        'netbanking_airtel',
        'netbanking_axis',
        'netbanking_federal',
        'netbanking_rbl',
        'netbanking_indusind',
        'netbanking_pnb',
        'sharp',
        'wallet_olamoney',
        'upi_icici',
        'upi_mindgate',
        'upi_npci',
        'aeps_icici',
        'wallet_payzapp',
        'wallet_payumoney',
        'wallet_airtelmoney',
        'wallet_freecharge',
        'wallet_jiomoney',
        'wallet_sbibuddy',
        'wallet_openwallet',
        'wallet_mpesa',
    ],

    'mock_amex'                   => env('AMEX_MOCK'),
    'mock_hdfc'                   => env('HDFC_MOCK'),
    'mock_cybersource'            => env('CYBERSOURCE_MOCK'),
    'mock_first_data'             => env('FIRST_DATA_MOCK'),
    'mock_atom'                   => env('ATOM_MOCK'),
    'mock_hitachi'                => env('HITACHI_MOCK'),
    'mock_axis_migs'              => env('AXIS_MIGS_MOCK'),
    'mock_axis_genius'            => env('AXIS_GENIUS_MOCK'),
    'mock_kotak'                  => env('KOTAK_MOCK'),
    'mock_mobikwik'               => env('MOBIKWIK_MOCK'),
    'mock_paytm'                  => env('PAYTM_MOCK'),
    'mock_netbanking_hdfc'        => env('NETBANKING_HDFC_MOCK'),
    'mock_netbanking_corporation' => env('NETBANKING_CORPORATION_MOCK'),
    'mock_netbanking_kotak'       => env('NETBANKING_KOTAK_MOCK'),
    'mock_netbanking_icici'       => env('NETBANKING_ICICI_MOCK'),
    'mock_netbanking_airtel'      => env('NETBANKING_AIRTEL_MOCK'),
    'mock_netbanking_axis'        => env('NETBANKING_AXIS_MOCK'),
    'mock_netbanking_federal'     => env('NETBANKING_FEDERAL_MOCK'),
    'mock_netbanking_rbl'         => env('NETBANKING_RBL_MOCK'),
    'mock_netbanking_indusind'    => env('NETBANKING_INDUSIND_MOCK'),
    'mock_netbanking_pnb'         => env('NETBANKING_PNB_MOCK'),
    'mock_billdesk'               => env('BILLDESK_MOCK'),
    'mock_blade'                  => env('BLADE_MOCK'),
    'mock_ebs'                    => env('EBS_MOCK'),
    'mock_wallet_olamoney'        => env('OLAMONEY_MOCK'),
    'mock_wallet_payzapp'         => env('PAYZAPP_MOCK'),
    'mock_wallet_payumoney'       => env('PAYUMONEY_MOCK'),
    'mock_wallet_airtelmoney'     => env('AIRTELMONEY_MOCK'),
    'mock_wallet_jiomoney'        => env('JIOMONEY_MOCK'),
    'mock_wallet_sbibuddy'        => env('SBIBUDDY_MOCK'),
    'mock_upi_mindgate'           => env('UPI_MINDGATE_MOCK'),
    'mock_upi_icici'              => env('UPI_ICICI_MOCK'),
    'mock_upi_npci'               => env('UPI_NPCI_MOCK'),
    'mock_aeps_icici'             => env('AEPS_ICICI_MOCK'),
    'mock_wallet_freecharge'      => env('FREECHARGE_MOCK'),
    'mock_wallet_mpesa'           => env('MPESA_MOCK'),

    'certificate_path'            => env('CERTIFICATE_DIR_PATH'),

    'hdfc' => [
        'test_terminal_id'  => env('HDFC_GATEWAY_TEST_TERMINAL_ID'),
        'test_terminal_pwd' => env('HDFC_GATEWAY_TEST_TERMINAL_PASSWORD'),
        'mock_server'       => false,
    ],

    'cybersource' => [
        'test_username'         => env('CYBERSOURCE_GATEWAY_TEST_MERCHANT_ID'),
        'test_password'         => env('CYBERSOURCE_GATEWAY_TEST_ACCESS_CODE'),
        'test_merchant_id'      => env('CYBERSOURCE_GATEWAY_TEST_USERNAME', 'cybersource_id'),
        'test_merchant_secret'  => env('CYBERSOURCE_GATEWAY_TEST_SECRET', 'cybersource_secret'),
    ],

    'hitachi' => [
        'test_merchant_id'  => env('HITACHI_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'  => env('HITACHI_GATEWAY_TEST_HASH_SECRET'),
        'test_hash_secret2' => env('HITACHI_GATEWAY_TEST_HASH_SECRET2'),
    ],

    'first_data' => [
        // Test credentials
        'test_store_id'                     => env('FIRST_DATA_TEST_STORE_ID'),
        'test_hash_secret'                  => env('FIRST_DATA_TEST_HASH_SECRET'),
        'test_user_id'                      => env('FIRST_DATA_TEST_USER_ID'),
        'test_password'                     => env('FIRST_DATA_TEST_PASSWORD'),
        'test_client_certificate'           => env('FIRST_DATA_TEST_CLIENT_CERTIFICATE'),
        'test_client_certificate_password'  => env('FIRST_DATA_TEST_CLIENT_CERTIFICATE_PASSWORD'),
        // Live credentials
        'live_hash_secret'                  => env('FIRST_DATA_LIVE_HASH_SECRET'),
        'live_user_id'                      => env('FIRST_DATA_LIVE_USER_ID'),
        'live_password'                     => env('FIRST_DATA_LIVE_PASSWORD'),
        'live_client_certificate'           => env('FIRST_DATA_LIVE_CLIENT_CERTIFICATE'),
        'live_client_certificate_password'  => env('FIRST_DATA_LIVE_CLIENT_CERTIFICATE_PASSWORD'),
        // Default values
        'cert_dir_name'                     => env('FIRST_DATA_CERT_DIR_NAME'),
        'server_certificate'                => env('FIRST_DATA_SERVER_CERTIFICATE'),
        'client_certificate'                => env('FIRST_DATA_CLIENT_CERTIFICATE'),
    ],

    'amex' => [
        'test_hash_secret'  => env('AMEX_GATEWAY_TEST_HASH_SECRET'),
        'test_merchant_id'  => env('AMEX_GATEWAY_TEST_MERCHANT_ID'),
        'test_access_code'  => env('AMEX_GATEWAY_TEST_ACCESS_CODE'),
        'test_ama_user'     => env('AMEX_GATEWAY_TEST_AMA_USER'),
        'test_ama_password' => env('AMEX_GATEWAY_TEST_AMA_PASSWORD'),
    ],

    'axis_migs' => [
        'test_hash_secret'  => env('AXIS_MIGS_GATEWAY_TEST_HASH_SECRET'),
        'test_merchant_id'  => env('AXIS_MIGS_GATEWAY_TEST_MERCHANT_ID'),
        'test_access_code'  => env('AXIS_MIGS_GATEWAY_TEST_ACCESS_CODE'),
        'test_ama_user'     => env('AXIS_MIGS_GATEWAY_TEST_AMA_USER'),
        'test_ama_password' => env('AXIS_MIGS_GATEWAY_TEST_AMA_PASSWORD'),
    ],

    'axis_genius' => [
        'test_hash_secret'  => env('AXIS_GENIUS_GATEWAY_TEST_HASH_SECRET'),
        'test_merchant_id'  => env('AXIS_GENIUS_GATEWAY_TEST_MERCHANT_ID'),
        'test_access_code'  => env('AXIS_GENIUS_GATEWAY_TEST_ACCESS_CODE'),
    ],

    'billdesk' => [
        'test_merchant_id'      => env('BILLDESK_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'      => env('BILLDESK_GATEWAY_TEST_HASH_SECRET'),
        'test_access_code'      => env('BILLDESK_GATEWAY_TEST_ACCESS_CODE'),
        'live_hash_secret'      => env('BILLDESK_GATEWAY_TEST_HASH_SECRET'),
        'live_access_code'      => env('BILLDESK_GATEWAY_TEST_ACCESS_CODE'),
        //SECRET FOR SECURITIES MERCHANTS
        'live_hash_secret_sec'  => env('BILLDESK_GATEWAY_SECURITIES_LIVE_HASH_SECRET'),
        'live_access_code_sec'  => env('BILLDESK_GATEWAY_SECURITIES_LIVE_ACCESS_CODE'),
    ],

    'blade' => [
        //TODO add all env variables
        'cert_dir_name'                  => env('BLADE_CERT_DIR_NAME'),
        'live_visa_certificate'          => env('BLADE_LIVE_VISA_CERTIFICATE'),
        'live_visa_pem'                  => env('BLADE_LIVE_VISA_PEM'),
        'live_mastercard_certificate'    => env('BLADE_LIVE_MASTERCARD_CERTIFICATE'),
        'live_mastercard_pem'            => env('BLADE_LIVE_MASTERCARD_PEM'),
        'gateway_access_code'            => env('BLADE_TEST_ACCESS_CODE'),
        'gateway_merchant_id2'           => env('BLADE_TEST_MERCHANT_ID2'),
        'gateway_terminal_password'      => env('BLADE_TEST_TERMINAL_PASSWORD'),
        'live_mastercard_acq_bin'        => env('BLADE_LIVE_MASTERCARD_ACQ_BIN'),
        'live_visa_acq_bin'              => env('BLADE_LIVE_VISA_ACQ_BIN'),
        'test_acq_bin'                   => env('BLADE_TEST_ACQ_BIN'),
        'live_mastercard_merchant_id'    => env('BLADE_LIVE_MASTERCARD_MERCHANT_ID2'),
        'live_visa_merchant_id'          => env('BLADE_LIVE_VISA_MERCHANT_ID'),
        'test_merchant_id'               => env('BLADE_TEST_MERCHANT_ID'),
    ],

    'ebs' => [
        'test_merchant_id' => env('EBS_GATEWAY_TEST_MERCHANT_ID', 'random'),
        'test_hash_secret' => env('EBS_GATEWAY_TEST_HASH_SECRET', 'secret'),
    ],

    'mobikwik' => [
        'test_hash_secret'  => env('MOBIKWIK_GATEWAY_TEST_HASH_SECRET'),
        'test_merchant_id'  => 'MBK9002',
        // 'test_merchant_id'  => 'MBK7518',
    ],

    'paytm' => [
        'test_merchant_id'  => env('PAYTM_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'  => env('PAYTM_GATEWAY_TEST_HASH_SECRET'),
    ],

    'upi_icici' => [
        'test_merchant_id'       => env('UPI_ICICI_GATEWAY_TEST_MERCHANT_ID'),
        'test_public_key'        => env('UPI_ICICI_TEST_PUBLIC_KEY'),
        'test_private_key'       => env('UPI_ICICI_TEST_PRIVATE_KEY'),
        'live_merchant_id'       => env('UPI_ICICI_GATEWAY_LIVE_MERCHANT_ID'),
        'live_public_key'        => env('UPI_ICICI_LIVE_PUBLIC_KEY'),
        'live_private_key'       => env('UPI_ICICI_LIVE_PRIVATE_KEY'),
    ],

    'aeps_icici' => [
        'terminal_id'           => env('AEPS_TERMINAL_ID'),
    ],

    'upi_npci' => [
        'test_decryption_key'       => env('UPI_NPCI_TEST_DECRYPTION_KEY'),
        'test_signing_key'          => env('UPI_NPCI_TEST_SIGNING_KEY'),
        'test_signing_public_key'   => env('UPI_NPCI_TEST_SIGNING_PUBLIC_KEY'),
    ],

    'upi_mindgate' => [
        'test_merchant_id'       => env('UPI_MINDGATE_TEST_MERCHANT_ID'),
        'test_merchant_key'      => env('UPI_MINDGATE_TEST_MERCHANT_KEY'),
    ],

    'wallet_payzapp' => [
        'pg_merchant_login_id'      => env('PAYZAPP_WALLET_PG_MERCHANT_LOGIN_ID'),
        'test_merchant_id'          => env('PAYZAPP_WALLET_TEST_MERCHANT_ID'),
        'test_merchant_app_id'      => env('PAYZAPP_WALLET_TEST_MERCHANT_APP_ID'),
        'test_hash_secret'          => env('PAYZAPP_WALLET_TEST_HASH_SECRET'),
        'test_pg_instance_id'       => env('PAYZAPP_WALLET_TEST_PG_INSTANCE_ID'),
        'test_pg_merchant_id'       => env('PAYZAPP_WALLET_TEST_PG_MERCHANT_ID'),
        'test_pg_hash_key'          => env('PAYZAPP_WALLET_TEST_PG_HASH_KEY'),
        'live_pg_instance_id'       => env('PAYZAPP_WALLET_LIVE_PG_INSTANCE_ID'),
    ],

    'wallet_olamoney'  => [
        'test_merchant_id'      => env('OLAMONEY_WALLET_TEST_MERCHANT_ID'),
        'test_hash_secret'      => env('OLAMONEY_WALLET_TEST_HASH_SECRET'),
        'test_access_code'      => env('OLAMONEY_WALLET_TEST_CLIENT_ID'),
    ],

    'wallet_payumoney' => [
        'test_hash_secret'      => env('PAYUMONEY_WALLET_TEST_HASH_SECRET'),
        'test_merchant_id'      => env('PAYUMONEY_WALLET_TEST_MERCHANT_ID'),
        'test_access_code'      => env('PAYUMONEY_WALLET_TEST_CLIENT_ID'),
        'test_auth_header'      => env('PAYUMONEY_WALLET_TEST_AUTH_HEADER'),
    ],

    'wallet_airtelmoney' => [
        'test_hash_secret' => env('AIRTELMONEY_WALLET_TEST_HASH_SECRET'),
        'test_merchant_id' => env('AIRTELMONEY_WALLET_TEST_MERCHANT_ID'),
        'test_end_mid'     => env('AIRTELMONEY_WALLET_TEST_END_MID'),
        'live_merchant_id' => env('AIRTELMONEY_WALLET_LIVE_MERCHANT_ID'),
        'live_hash_secret' => env('AIRTELMONEY_WALLET_LIVE_HASH_SECRET'),
    ],

    'wallet_freecharge' => [
        'test_hash_secret'      => env('FREECHARGE_WALLET_TEST_HASH_SECRET'),
        'test_merchant_id'      => env('FREECHARGE_WALLET_TEST_MERCHANT_ID'),
        'test_dealer_id'        => env('FREECHARGE_WALLET_TEST_DEALER_ID'),
    ],

    'wallet_jiomoney' => [
        'test_merchant_id'      => env('JIOMONEY_WALLET_TEST_MERCHANT_ID'),
        'test_client_id'        => env('JIOMONEY_WALLET_TEST_CLIENT_ID'),
        'test_hash_secret'      => env('JIOMONEY_WALLET_TEST_HASH_SECRET')
    ],

    'wallet_sbibuddy' => [
        'test_merchant_id'      => env('SBIBUDDY_WALLET_TEST_MERCHANT_ID'),
        'test_hash_secret'      => env('SBIBUDDY_WALLET_TEST_HASH_SECRET')
    ],

    'wallet_mpesa' => [
        'test_merchant_id'  => env('MPESA_WALLET_TEST_MERCHANT_ID'),
        'test_merchant_id2' => env('MPESA_WALLET_TEST_MERCHANT_ID2'),
        'test_hash_secret'  => env('MPESA_WALLET_TEST_HASH_SECRET'),
        'test_user_id'      => env('MPESA_WALLET_TEST_USER_ID'),
        'test_password'     => env('MPESA_WALLET_TEST_PASSWORD'),
        'live_user_id'      => env('MPESA_WALLET_LIVE_USER_ID'),
        'live_password'     => env('MPESA_WALLET_LIVE_PASSWORD'),
        'live_hash_secret'  => env('MPESA_WALLET_LIVE_HASH_SECRET'),
    ],

    'netbanking_hdfc' => [
        'live_hash_secret'  => env('NETBANKING_HDFC_GATEWAY_LIVE_HASH_SECRET'),
        'test_hash_secret'  => '123456',
        // tpv
        'live_hash_secret_tpv' => env('NETBANKING_HDFC_GATEWAY_CUG_LIVE_HASH_SECRET'),
        'test_hash_secret_tpv' => '12345',
    ],

    'netbanking_corporation' => [
        'test_merchant_id'  => env('NETBANKING_CORPORATION_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'  => env('NETBANKING_CORPORATION_GATEWAY_TEST_HASH_SECRET'),
    ],

    'netbanking_kotak' => [
        'live_hash_secret'     => env('NETBANKING_KOTAK_GATEWAY_LIVE_HASH_SECRET'),
        'test_hash_secret'     => env('NETBANKING_KOTAK_GATEWAY_TEST_HASH_SECRET'),
        'live_hash_secret_tpv' => env('NETBANKING_KOTAK_GATEWAY_SEC_LIVE_HASH_SECRET'),
    ],

    'netbanking_icici' => [
        //retail netbanking
        'test_hash_secret'       => env('NETBANKING_ICICI_GATEWAY_TEST_HASH_SECRET'),
        'test_hash_secret_rec'   => env('NETBANKING_ICICI_GATEWAY_TEST_HASH_SECRET_REC'),
        'test_merchant_id'       => env('NETBANKING_ICICI_GATEWAY_TEST_MERCHANT_ID'),
        'test_merchant_id2'      => env('NETBANKING_ICICI_GATEWAY_TEST_MERCHANT_ID2'),
        'test_merchant_id2_rec'  => env('NETBANKING_ICICI_GATEWAY_TEST_MERCHANT_ID2_REC'),

        'live_hash_secret'       => env('NETBANKING_ICICI_LIVE_HASH_SECRET'),
        'live_merchant_id2'      => env('NETBANKING_ICICI_GATEWAY_LIVE_MERCHANT_ID'),

        //retail tpv
        'live_hash_secret_tpv'   => env('NETBANKING_ICICI_LIVE_HASH_SECRET_BROKER'),
        'live_merchant_id2_tpv'  => env('NETBANKING_ICICI_GATEWAY_LIVE_MERCHANT_ID_BROKER'),

        //corporate netbanking
        'test_hash_secret_corp'  => env('NETBANKING_ICICI_GATEWAY_TEST_HASH_SECRET_CORP'),
        'test_merchant_id2_corp' => env('NETBANKING_ICICI_GATEWAY_TEST_MERCHANT_ID2_CORP'),

        'live_hash_secret_corp'  => env('NETBANKING_ICICI_GATEWAY_LIVE_HASH_SECRET_CORP'),
        'live_merchant_id2_corp' => env('NETBANKING_ICICI_GATEWAY_LIVE_MERCHANT_ID2_CORP'),
    ],

    'netbanking_axis' => [
        'live_hash_secret'           => env('NETBANKING_AXIS_GATEWAY_LIVE_HASH_SECRET'),
        'live_hash_secret_corporate' => env('NETBANKING_AXIS_GATEWAY_LIVE_HASH_SECRET_CORPORATE'),
        'test_hash_secret'           => env('NETBANKING_AXIS_GATEWAY_TEST_HASH_SECRET'),
        'test_hash_secret_corporate' => env('NETBANKING_AXIS_GATEWAY_TEST_HASH_SECRET_CORPORATE'),
        'test_merchant_id'           => env('NETBANKING_AXIS_GATEWAY_TEST_MERCHANT_ID'),
        'test_merchant_id_corporate' => env('NETBANKING_AXIS_GATEWAY_TEST_MERCHANT_ID_CORPORATE'),
    ],

    'netbanking_airtel' => [
        'test_merchant_id'  => env('NETBANKING_AIRTEL_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'  => env('NETBANKING_AIRTEL_GATEWAY_TEST_HASH_SECRET'),
        'live_hash_secret'  => env('NETBANKING_AIRTEL_GATEWAY_LIVE_HASH_SECRET'),
    ],

    'netbanking_federal' => [
        'test_merchant_id'  => env('NETBANKING_FEDERAL_GATEWAY_TEST_MERCHANT_ID'),
        'live_merchant_id'  => env('NETBANKING_FEDERAL_GATEWAY_LIVE_MERCHANT_ID'),
    ],

    'netbanking_rbl' => [
        'test_merchant_id'     => env('NETBANKING_RBL_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'     => env('NETBANKING_RBL_GATEWAY_TEST_HASH_SECRET'),
        'test_access_code'     => env('NETBANKING_RBL_GATEWAY_TEST_ACCESS_CODE'),
        'test_merchant_id2'    => env('NETBANKING_RBL_GATEWAY_TEST_MERCHANT_ID2'),
        'test_merchant_id_tpv' => env('NETBANKING_RBL_GATEWAY_TEST_MERCHANT_ID_TPV')
    ],

    'netbanking_indusind' => [
        'test_merchant_id'  => env('NETBANKING_INDUSIND_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'  => env('NETBANKING_INDUSIND_GATEWAY_TEST_HASH_SECRET'),
        'live_hash_secret'  => env('NETBANKING_INDUSIND_GATEWAY_LIVE_HASH_SECRET'),
    ],

    'netbanking_pnb' => [
        'test_hash_secret'  => env('NETBANKING_PNB_GATEWAY_TEST_HASH_SECRET'),
        'live_hash_secret'  => env('NETBANKING_PNB_GATEWAY_LIVE_HASH_SECRET'),
    ],

    'sharp' => [
    ],

    'proxy_enabled' => env('PROXY_ENABLED'),

    'proxy_address' => env('PROXY_ADDRESS'),
];
