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
        'payu',
        'cashfree',
        'zaakpay',
        'ccavenue',
        'pinelabs',
        'ingenico',
        'billdesk_optimizer',
        'optimizer_razorpay',
        'cred',
        'mozart',
        'axis_genius',
        'axis_migs',
        'axis_tokenhq',
        'billdesk',
        'mpgs',
        'mpi_blade',
        'mpi_enstage',
        'card_fss',
        'cardless_emi',
        'cybersource',
        'esigner_digio',
        'esigner_legaldesk',
        'enach_rbl',
        'enach_npci_netbanking',
        'nach_citi',
        'nach_icici',
        'ebs',
        'first_data',
        'hdfc',
        'hitachi',
        'isg',
        'kotak',
        'mobikwik',
        'mozart',
        'netbanking_sib',
        'netbanking_cbi',
        'netbanking_hdfc',
        'netbanking_idfc',
        'netbanking_bob',
        'netbanking_bob_v2',
        'netbanking_vijaya',
        'netbanking_corporation',
        'netbanking_ubi',
        'netbanking_scb',
        'netbanking_jkb',
        'netbanking_kotak',
        'netbanking_icici',
        'netbanking_airtel',
        'netbanking_axis',
        'netbanking_federal',
        'netbanking_rbl',
        'netbanking_indusind',
        'netbanking_allahabad',
        'netbanking_pnb',
        'netbanking_obc',
        'netbanking_csb',
        'netbanking_canara',
        'netbanking_equitas',
        'netbanking_sbi',
        'netbanking_cub',
        'netbanking_ibk',
        'netbanking_idbi',
        'netbanking_yesb',
        'netbanking_kvb',
        'netbanking_svc',
        'netbanking_jsb',
        'netbanking_iob',
        'netbanking_fsb',
        'netbanking_saraswat',
        'netbanking_dcb',
        'netbanking_ausf',
        'netbanking_nsdl',
        'netbanking_bdbl',
        'netbanking_saraswat',
        'netbanking_uco',
        'netbanking_tmb',
        'netbanking_karnataka',
        'netbanking_dbs',
        'paytm',
        'sharp',
        'twid',
        'upi_airtel',
        'upi_citi',
        'upi_axis',
        'upi_icici',
        'upi_mindgate',
        'upi_hulk',
        'upi_sbi',
        'upi_juspay',
        'upi_npci',
        'upi_rbl',
        'upi_yesbank',
        'upi_mozart',
        'upi_axisolive',
        'aeps_icici',
        'wallet_olamoney',
        'wallet_payzapp',
        'wallet_payumoney',
        'wallet_airtelmoney',
        'wallet_amazonpay',
        'wallet_freecharge',
        'wallet_jiomoney',
        'wallet_sbibuddy',
        'wallet_openwallet',
        'wallet_razorpaywallet',
        'wallet_mpesa',
        'wallet_phonepe',
        'wallet_phonepeswitch',
        'wallet_paypal',
        'bt_yesbank',
        'bt_kotak',
        'bt_icici',
        'bt_rbl',
        'bt_hdfc_ecms',
        'bajajfinserv',
        'p2p_upi_sharp',
        'p2p_upi_axis',
        'p2m_upi_axis_olive',
        'paysecure',
        'paylater',
        'google_pay',
        'getsimpl',
        'worldline',
        'paylater_icici',
        'hdfc_debit_emi',
        'kotak_debit_emi',
        'indusind_debit_emi',
        'netbanking_dlb',
        'fulcrum',
        'checkout_dot_com',
        'billdesk_sihub',
        'mandate_hq',
        'rupay_sihub',
        'emerchantpay',
        'netbanking_ujjivan',
        'hdfc_ezetap',
        'offline_hdfc',
        'currency_cloud',
        'icici',
        'upi_kotak',
        "upi_rzprbl",
    ],

    'mock_amex'                   => env('AMEX_MOCK'),
    'mock_hdfc'                   => env('HDFC_MOCK'),
    'mock_cred'                   => env('CRED_MOCK'),
    'mock_cybersource'            => env('CYBERSOURCE_MOCK'),
    'mock_first_data'             => env('FIRST_DATA_MOCK'),
    'mock_atom'                   => env('ATOM_MOCK'),
    'mock_payu'                   => env('PAYU_MOCK'),
    'mock_ccavenue'               => env('CCAVENUE_MOCK'),
    'mocK_cashfree'               => env('CASHFREE_MOCK'),
    'mocK_ccavenue'               => env('CCAVENUE_MOCK'),
    'mock_zaakpay'                => env('ZAAKPAY_MOCK'),
    'mock_pinelabs'               => env('PINELABS_MOCK'),
    'mock_ingenico'               => env('INGENICO_MOCK'),
    'mock_billdesk_optimizer'     => env('BILLDESK_OPTIMIZER'),
    'mock_optimizer_razorpay'     => env('OPTIMIZER_RAZORPAY'),
    'mock_hitachi'                => env('HITACHI_MOCK'),
    'mock_esigner_digio'          => env('ESIGNER_DIGIO_MOCK'),
    'mock_esigner_legaldesk'      => env('ESIGNER_LEGALDESK_MOCK'),
    'mock_enach_rbl'              => env('ENACH_RBL_MOCK'),
    'mock_enach_npci_netbanking'  => env('ENACH_NPCI_NETBANKING_MOCK'),
    'mock_nach_icici'             => env('NACH_ICICI_MOCK'),
    'mock_axis_migs'              => env('AXIS_MIGS_MOCK'),
    'mock_axis_tokenhq'           => env('AXIS_TOKENHQ_MOCK'),
    'mock_axis_genius'            => env('AXIS_GENIUS_MOCK'),
    'mock_kotak'                  => env('KOTAK_MOCK'),
    'mock_mobikwik'               => env('MOBIKWIK_MOCK'),
    'mock_paytm'                  => env('PAYTM_MOCK'),
    'mock_checkout_dot_com'       => env('CHECKOUT_DOT_COM_MOCK'),
    'mock_netbanking_sib'         => env('NETBANKING_SIB_MOCK'),
    'mock_netbanking_cbi'         => env('NETBANKING_CBI_MOCK'),
    'mock_netbanking_hdfc'        => env('NETBANKING_HDFC_MOCK'),
    'mock_netbanking_bob'         => env('NETBANKING_BOB_MOCK'),
    'mock_netbanking_bob_v2'      => env('NETBANKING_BOB_MOCK'),
    'mock_netbanking_vijaya'      => env('NETBANKING_VIJAYA_MOCK'),
    'mock_netbanking_corporation' => env('NETBANKING_CORPORATION_MOCK'),
    'mock_netbanking_kotak'       => env('NETBANKING_KOTAK_MOCK'),
    'mock_netbanking_icici'       => env('NETBANKING_ICICI_MOCK'),
    'mock_netbanking_airtel'      => env('NETBANKING_AIRTEL_MOCK'),
    'mock_netbanking_axis'        => env('NETBANKING_AXIS_MOCK'),
    'mock_netbanking_federal'     => env('NETBANKING_FEDERAL_MOCK'),
    'mock_netbanking_idfc'        => env('NETBANKING_IDFC_MOCK'),
    'mock_netbanking_rbl'         => env('NETBANKING_RBL_MOCK'),
    'mock_netbanking_equitas'     => env('NETBANKING_EQUITAS_MOCK'),
    'mock_netbanking_sbi'         => env('NETBANKING_SBI_MOCK'),
    'mock_netbanking_indusind'    => env('NETBANKING_INDUSIND_MOCK'),
    'mock_netbanking_pnb'         => env('NETBANKING_PNB_MOCK'),
    'mock_netbanking_ubi'         => env('NETBANKING_UBI_MOCK'),
    'mock_netbanking_scb'         => env('NETBANKING_SCB_MOCK'),
    'mock_netbanking_jkb'         => env('NETBANKING_JKB_MOCK'),
    'mock_netbanking_obc'         => env('NETBANKING_OBC_MOCK'),
    'mock_netbanking_csb'         => env('NETBANKING_CSB_MOCK'),
    'mock_netbanking_allahabad'   => env('NETBANKING_ALLAHABAD_MOCK'),
    'mock_netbanking_cub'         => env('NETBANKING_CUB_MOCK'),
    'mock_netbanking_ibk'         => env('NETBANKING_IBK_MOCK'),
    'mock_netbanking_idbi'        => env('NETBANKING_IDBI_MOCK'),
    'mock_netbanking_svc'         => env('NETBANKING_SVC_MOCK'),
    'mock_netbanking_dcb'         => env('NETBANKING_DCB_MOCK'),
    'mock_billdesk'               => env('BILLDESK_MOCK'),
    'mock_netbanking_canara'      => env('NETBANKING_CANARA_MOCK'),
    'mock_netbanking_yesb'        => env('NETBANKING_YESB_MOCK'),
    'mock_netbanking_kvb'         => env('NETBANKING_KVB_MOCK'),
    'mock_netbanking_jsb'         => env('NETBANKING_JSB_MOCK'),
    'mock_netbanking_iob'         => env('NEBANKING_IOB_MOCK'),
    'mock_netbanking_fsb'         => env('NETBANKING_FSB_MOCK'),
    'mock_netbanking_saraswat'    => env('NETBANKING_saraswat_MOCK'),
    'mock_netbanking_ausf'        => env('NETBANKING_AUSF_MOCK'),
    'mock_netbanking_dlb'         => env('NETBANKING_DLB_MOCK'),
    'mock_netbanking_tmb'         => env('NETBANKING_TMB_MOCK'),
    'mock_netbanking_karnataka'   => env('NETBANKING_KARNATAKA_MOCK'),
    'mock_netbanking_nsdl'        => env('NETBANKING_NSDL_MOCK'),
    'mock_netbanking_uco'         => env('NETBANKING_UCO_MOCK'),
    'mock_netbanking_ujjivan'     => env('NETBANKING_UJJIVAN_MOCK'),
    'mock_netbanking_dbs'         => env('NETBANKING_DBS_MOCK'),
    'mock_mpi_blade'              => env('BLADE_MOCK'),
    'mock_ebs'                    => env('EBS_MOCK'),
    'mock_twid'                   => env('TWID_MOCK'),
    'mock_wallet_olamoney'        => env('OLAMONEY_MOCK'),
    'mock_wallet_payzapp'         => env('PAYZAPP_MOCK'),
    'mock_wallet_payumoney'       => env('PAYUMONEY_MOCK'),
    'mock_wallet_airtelmoney'     => env('AIRTELMONEY_MOCK'),
    'mock_wallet_amazonpay'       => env('AMAZONPAY_MOCK'),
    'mock_wallet_jiomoney'        => env('JIOMONEY_MOCK'),
    'mock_wallet_sbibuddy'        => env('SBIBUDDY_MOCK'),
    'mock_upi_mindgate'           => env('UPI_MINDGATE_MOCK'),
    'mock_upi_sbi'                => env('UPI_SBI_MOCK'),
    'mock_upi_axis'               => env('UPI_AXIS_MOCK'),
    'mock_upi_icici'              => env('UPI_ICICI_MOCK'),
    'mock_upi_hulk'               => env('UPI_HULK_MOCK'),
    'mock_upi_npci'               => env('UPI_NPCI_MOCK'),
    'mock_upi_rbl'                => env('UPI_RBL_MOCK'),
    'mock_upi_axisolive'          => env('UPI_AXISOLIVE_MOCK'),
    'mock_upi_yesbank'            => env('UPI_YESBANK_MOCK'),
    'mock_aeps_icici'             => env('AEPS_ICICI_MOCK'),
    'mock_wallet_freecharge'      => env('FREECHARGE_MOCK'),
    'mock_wallet_mpesa'           => env('MPESA_MOCK'),
    'mock_card_fss'               => env('FSS_MOCK'),
    'mock_mpi_enstage'            => env('ENSTAGE_MOCK'),
    'mock_isg'                    => env('ISG_MOCK'),
    'mock_paysecure'              => env('PAYSECURE_MOCK'),
    'mock_cardless_emi'           => env('CARDLESS_EMI_MOCK'),
    'mock_paylater'               => env('PAYLATER_MOCK'),
    'mock_bajajfinserv'           => env('BAJAJFINSERV_MOCK'),
    'mock_google_pay'             => env('GOOGLE_PAY_MOCK'),
    'mock_p2p_upi_sharp'          => env('P2P_UPI_SHARP_MOCK'),
    'mock_p2p_upi_axis'           => env('P2P_UPI_AXIS_MOCK'),
    'mock_p2m_upi_axis_olive'     => env('P2M_UPI_AXIS_OLIVE_MOCK'),
    'mock_wallet_phonepe'         => env('PHONEPE_MOCK'),
    'mock_wallet_phonepeswitch'   => env('PHONEPE_SWITCH_MOCK'),
    'mock_getsimpl'               => env('GETSIMPL_MOCK'),
    'mock_paylater_icici'         => env('PAYLATER_ICICI_MOCK'),
    'mock_wallet_paypal'          => env('PAYPAL_MOCK'),
    'mock_upi_airtel'             => env('UPI_AIRTEL_MOCK'),
    'mock_upi_juspay'             => env('UPI_JUSPAY_MOCK'),
    'mock_worldline'              => env('WORLDLINE_MOCK'),
    'mock_mozart'                 => env('MOZART_MOCK'),
    'mock_upi_citi'               => env('UPI_CITI_MOCK'),
    'mock_hdfc_debit_emi'         => env('HDFC_DEBIT_EMI_MOCK'),
    'mock_billdesk_sihub'         => env('BILLDESK_SIHUB_MOCK'),
    'mock_emerchantpay'           => env('EMERCHANTPAY_MOCK'),
    'mock_rupay_sihub'            => env('RUPAY_SIHUB_MOCK'),
    'mock_upi_kotak'              => env('UPI_KOTAK_MOCK'),
    'mock_upi_rzprbl'             => env('UPI_RZPRBL_MOCK'),

    'certificate_path'            => env('CERTIFICATE_DIR_PATH'),

    'p2p_upi_axis' => [
        'bank_public_key'           => env('P2P_UPI_AXIS_BANK_PUBLIC_KEY'),
        'merchant_private_key'      => env('P2P_UPI_AXIS_MERCHANT_PRIVATE_KEY'),
        'merchant_id'               => env('P2P_UPI_AXIS_MERCHANT_ID'),
        'merchant_channel_id'       => env('P2P_UPI_AXIS_MERCHANT_CHANNEL_ID'),
        'merchant_category_code'    => env('P2P_UPI_AXIS_MERCHANT_CATEGORY_CODE'),
        'bank_count_threshold'      => env('P2P_UPI_AXIS_BANK_COUNT_THREASHOLD'),
    ],

    'p2p_upi_sharp' => [
        'bank_count_threshold'      => env('P2P_UPI_SHARP_BANK_COUNT_THREASHOLD'),
    ],

    'hdfc' => [
        'test_terminal_id'                 => env('HDFC_GATEWAY_TEST_TERMINAL_ID'),
        'test_terminal_pwd'                => env('HDFC_GATEWAY_TEST_TERMINAL_PASSWORD'),
        'test_debit_pin_terminal_id'       => env('HDFC_GATEWAY_TEST_DEBIT_PIN_TERMINAL_ID'),
        'test_debit_pin_terminal_password' => env('HDFC_GATEWAY_TEST_DEBIT_PIN_TERMINAL_PASSWORD'),
        'mock_server'                      => false,
    ],

    'cybersource' => [
        'test_username'         => env('CYBERSOURCE_GATEWAY_TEST_MERCHANT_ID'),
        'test_password'         => env('CYBERSOURCE_GATEWAY_TEST_ACCESS_CODE'),
        'test_merchant_id'      => env('CYBERSOURCE_GATEWAY_TEST_USERNAME', 'cybersource_id'),
        'test_merchant_secret'  => env('CYBERSOURCE_GATEWAY_TEST_SECRET', 'cybersource_secret'),
    ],

    'hitachi' => [
        'gateway_salt'         => env('HITACHI_GATEWAY_LIVE_HASH_SECRET'),
        'gateway_salt2'        => env('HITACHI_GATEWAY_LIVE_HASH_SECRET2'),
        'test_merchant_id'     => env('HITACHI_GATEWAY_TEST_MERCHANT_ID'),
        'test_terminal_id'     => env('HITACHI_GATEWAY_TEST_TERMINAL_ID'),
        'test_hash_secret'     => env('HITACHI_GATEWAY_TEST_HASH_SECRET'),
        'test_hash_secret2'    => env('HITACHI_GATEWAY_TEST_HASH_SECRET2'),
        'bharatqr_salt'        => env('HITACHI_GATEWAY_BHARAT_QR_SALT'),
    ],

    'isg' => [
        'bharat_qr_secret' => env('ISG_GATEWAY_BHARAT_QR_SECRET'),
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

    'currency_cloud' => [
        'usd_beneficiary_id'        => env('CURRENCY_CLOUD_USD_BENEFICIARY_ID'),
        'gbp_beneficiary_id'        => env('CURRENCY_CLOUD_GBP_BENEFICIARY_ID'),
        'eur_beneficiary_id'        => env('CURRENCY_CLOUD_EUR_BENEFICIARY_ID'),
        'aud_beneficiary_id'        => env('CURRENCY_CLOUD_AUD_BENEFICIARY_ID'),
        'cad_beneficiary_id'        => env('CURRENCY_CLOUD_CAD_BENEFICIARY_ID'),
        'rzp_parent_account_id'     => env('CURRENCY_CLOUD_RZP_PARENT_ACCOUNT_ID'),
        'rzp_commission_fee_account_id' => env('CURRENCY_CLOUD_RZP_COMMISSION_FEE_ACCOUNT_ID'),
    ],

    'amex' => [
        'test_hash_secret'  => env('AMEX_GATEWAY_TEST_HASH_SECRET'),
        'test_merchant_id'  => env('AMEX_GATEWAY_TEST_MERCHANT_ID'),
        'test_access_code'  => env('AMEX_GATEWAY_TEST_ACCESS_CODE'),
        'test_ama_user'     => env('AMEX_GATEWAY_TEST_AMA_USER'),
        'test_ama_password' => env('AMEX_GATEWAY_TEST_AMA_PASSWORD'),
    ],

    'visa' => [
        'identifier_id'   => env('VISA_IDENTIFIER_ID','10075249'),
    ],

    'mastercard' => [
         'identifier_id'            => env('MASTERCARD_IDENTIFIER_ID','RAZ39520'),
         'razorpay_requester_id'    => env('MASTERCARD_RZP_REQUESTER_ID','RAZ39520_100000Razorpay'),
         'razorpay_requester_name'  => env('MASTERCARD_RZP_REQUESTER_NAME','Razorpay_Software Pvt Ltd'),
         'identifier_name'          => env('MASTERCARD_IDENTIFIER_NAME','Razorpay')
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

    'atom' => [
        'test_authorize_hash_secret'   => env('ATOM_GATEWAY_TEST_AUTHORIZE_HASH_SECRET'),
        'test_callback_hash_secret'    => env('ATOM_GATEWAY_TEST_CALLBACK_HASH_SECRET'),
        'test_merchant_id'             => env('ATOM_TEST_MERCHANT_ID'),
        'test_secure_password'         => env('ATOM_TEST_SECURE_PASSWORD'),
        'test_access_code'             => env('ATOM_TEST_ACCESS_CODE'),
        'test_request_encryption_key'  => env('ATOM_TEST_REQUEST_ENCRYPTION_KEY'),
        'test_response_encryption_key' => env('ATOM_TEST_RESPONSE_ENCRYPTION_KEY'),
    ],

    'mpi_blade' => [
        'cert_dir_name'                         => env('BLADE_CERT_DIR_NAME'),

        'live_visa_certificate'                 => env('BLADE_GATEWAY_LIVE_VISA_CERTIFICATE'),
        'live_visa_key'                         => env('BLADE_GATEWAY_LIVE_VISA_PEM'),

        'live_mastercard_certificate'           => env('BLADE_GATEWAY_LIVE_MASTERCARD_CERTIFICATE'),
        'live_mastercard_key'                   => env('BLADE_GATEWAY_LIVE_MASTERCARD_PEM'),

        'live_mastercard_acq_bin'               => env('BLADE_LIVE_MASTERCARD_ACQ_BIN'),
        'live_visa_acq_bin'                     => env('BLADE_LIVE_VISA_ACQ_BIN'),

        'first_data' => [
            'live_mastercard_acq_bin'           => env('BLADE_LIVE_FIRSTDATA_MASTERCARD_ACQ_BIN'),
            'live_visa_acq_bin'                 => env('BLADE_LIVE_FIRSTDATA_VISA_ACQ_BIN'),
            'live_merchant_id'                  => env('BLADE_LIVE_FIRSTDATA_MERCHANT_ID'),
        ],

        'hdfc'  => [
            'live_mastercard_acq_bin'           => env('BLADE_LIVE_HDFC_MASTERCARD_ACQ_BIN'),
            'live_visa_acq_bin'                 => env('BLADE_LIVE_HDFC_VISA_ACQ_BIN'),
        ],

        'live_mastercard_merchant_id'           => env('BLADE_LIVE_MASTERCARD_MERCHANT_ID'),
        'live_visa_merchant_id'                 => env('BLADE_LIVE_VISA_MERCHANT_ID'),

        'test_acq_bin'                          => env('BLADE_TEST_ACQ_BIN'),
        'test_merchant_id'                      => env('BLADE_TEST_MERCHANT_ID'),
        'gateway_access_code'                   => env('BLADE_TEST_ACCESS_CODE'),
        'gateway_merchant_id2'                  => env('BLADE_TEST_MERCHANT_ID2'),
        'gateway_terminal_password'             => env('BLADE_TEST_TERMINAL_PASSWORD'),
    ],

    'ebs' => [
        'test_merchant_id' => env('EBS_GATEWAY_TEST_MERCHANT_ID', 'random'),
        'test_hash_secret' => env('EBS_GATEWAY_TEST_HASH_SECRET', 'secret'),
    ],

    'esigner_digio' => [
        'client_id'         => env('DIGIO_ESIGNER_GATEWAY_CLIENT_ID'),
        'client_password'   => env('DIGIO_ESIGNER_GATEWAY_CLIENT_PASSWORD'),
        'test_access_code'  => env('DIGIO_ESIGNER_GATEWAY_TEST_ACCESS_CODE'),
        'test_merchant_id'  => env('DIGIO_ESIGNER_GATEWAY_TEST_MERCHANT_ID'),
        'test_merchant_id2' => env('DIGIO_ESIGNER_GATEWAY_TEST_MERCHANT_ID2'),
        'test_terminal_id'  => env('DIGIO_ESIGNER_GATEWAY_TEST_TERMINAL_ID')
    ],

    'esigner_legaldesk' => [
        'live_api_key'        => env('LEGALDESK_ESIGNER_GATEWAY_LIVE_API_KEY'),
        'live_application_id' => env('LEGALDESK_ESIGNER_GATEWAY_LIVE_APPLICATION_ID'),
        'test_api_key'        => env('LEGALDESK_ESIGNER_GATEWAY_TEST_API_KEY'),
        'test_application_id' => env('LEGALDESK_ESIGNER_GATEWAY_TEST_APPLICATION_ID'),
        'test_access_code'    => env('LEGALDESK_ESIGNER_GATEWAY_TEST_ACCESS_CODE'),
        'test_merchant_id'    => env('LEGALDESK_ESIGNER_GATEWAY_TEST_MERCHANT_ID'),
        'test_merchant_id2'   => env('LEGALDESK_ESIGNER_GATEWAY_TEST_MERCHANT_ID2'),
    ],

    'card_fss' => [
        'barb' => [
            'test_hash_secret'  => env('FSS_BOB_GATEWAY_TEST_HASH_SECRET', 'secret'),
            'merchant_id'       => env('FSS_BOB_GATEWAY_MERCHANT_ID', '123'),
            'terminal_password' => env('FSS_BOB_GATEWAY_TERMINAL_PASSWORD', 'password'),
        ],
        'fss'  => [
            'merchant_id'       => env('FSS_GATEWAY_MERCHANT_ID', '144'),
            'test_hash_secret'  => env('FSS_GATEWAY_TEST_HASH_SECRET', 'secret'),
            'bank_code'         => env('FSS_GATEWAY_BANK_CODE', '12345678'),
            'terminal_password' => env('FSS_GATEWAY_TERMINAL_PASSWORD', 'password'),
        ],
        'sbin' => [
            'terminal_password' => env('FSS_SBI_TEST_GATEWAY_TERMINAL_PASSWORD', 'password'),
            'test_hash_secret'  => env('FSS_SBI_TEST_HASH_SECRET', 'secret'),
            'merchant_id'       => env('FSS_SBI_TEST_GATEWAY_MERCHANT_ID', '123')
        ]
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
        'test_merchant_id'                  => env('UPI_ICICI_GATEWAY_TEST_MERCHANT_ID'),
        'test_public_key'                   => env('UPI_ICICI_TEST_PUBLIC_KEY'),
        'test_private_key'                  => env('UPI_ICICI_TEST_PRIVATE_KEY'),
        'live_merchant_id'                  => env('UPI_ICICI_GATEWAY_LIVE_MERCHANT_ID'),
        'live_public_key'                   => env('UPI_ICICI_LIVE_PUBLIC_KEY'),
        'live_private_key'                  => env('UPI_ICICI_LIVE_PRIVATE_KEY'),
        'ut_test_private_key'               => env('UPI_ICICI_UPI_TRANSFER_TEST_PRIVATE_KEY'),
        'ut_live_private_key'               => env('UPI_ICICI_UPI_TRANSFER_LIVE_PRIVATE_KEY'),
        'live_recurring_onboarding_api_key' => env('UPI_ICICI_RECURRING_ONBOARDING_API_KEY'),
        'recurring_oksbi_test_merchants'    => [
            'EOQRaXICwJIuoy',   // Srikant's Test Merchant
            '2aTeFCKTYWwfrF',   // RZP Demo Merchant
            'FBYspBmKlWefX9',    // Akshata's Merchant (aka Furlenco Test Merchant)
            'HkC7ZWTZ5N5IGd',   // Prathmesh Bijjargi internal mid
            'HVIrWRENC4AkcJ'    // Test Merchant Acme
        ],
        'recurring_okicici_test_merchants'    => [
            'EOQRaXICwJIuoy',   // Srikant's Test Merchant
            '2aTeFCKTYWwfrF',   // RZP Demo Merchant
            'FBYspBmKlWefX9',    // Akshata's Merchant (aka Furlenco Test Merchant)
            'HkC7ZWTZ5N5IGd',   // Prathmesh Bijjargi internal mid
            'HVIrWRENC4AkcJ'    // Test Merchant Acme
        ],
    ],

    'upi_axis' => [
        'public_key'                        => env('UPI_AXIS_GATEWAY_PUBLIC_KEY'),
        'mobile_no'                         => env('UPI_AXIS_GATEWAY_MOBILE_NUMBER'),
        'aes_encryption_key'                => env('UPI_AXIS_GATEWAY_AES_KEY'),
        'test_merchant_id'                  => env('UPI_AXIS_GATEWAY_TEST_MERCHANT_ID'),
        'test_merchant_id2'                 => env('UPI_AXIS_GATEWAY_TEST_MERCHANT_CHANNEL_ID'),
        'test_vpa'                          => env('UPI_AXIS_GATEWAY_TEST_PAYEE_VPA'),
        'live_razorpay_merchant_id'         => env('UPI_LIVE_RAZORPAY_MERCHANT_ID'),
        'live_razorpay_merchant_channel_id' => env('UPI_LIVE_RAZORPAY_MERCHANT_CHANNEL_ID')
    ],

    'upi_rbl' => [
        'channel_partner_username'          => env('UPI_RBL_CHANNEL_PARTNER_TEST_USERNAME'),
        'channel_partner_password'          => env('UPI_RBL_CHANNEL_PARTNER_TEST_PASSWORD'),
        'channel_partner_bc_agent'          => env('UPI_RBL_CHANNEL_PARTNER_TEST_BC_AGENT'),
        'aggregator_id'                     => env('UPI_RBL_TEST_AGGREGATOR_ID'),
        'customer_mobile_number'            => env('UPI_RBL_MOBILE_NUMBER'),
        'customer_geo_code'                 => env('UPI_RBL_CUSTOMER_GEO_CODE'),
        'customer_location'                 => env('UPI_RBL_CUSTOMER_LOCATION'),
        'customer_app'                      => env('UPI_RBL_CUSTOMER_APP'),
        'customer_os'                       => env('UPI_RBL_CUSTOMER_OS'),
        'customer_ip'                       => env('UPI_RBL_CUSTOMER_IP'),
        'aes_encryption_key'                => env('UPI_RBL_AES_ENCRYPTION_KEY'),
        'client_cert'                       => env('UPI_RBL_CLIENT_CERTIFICATE'),
        'client_key'                        => env('UPI_RBL_CLIENT_CERTIFICATE_KEY'),
        'test_client_id'                    => env('UPI_RBL_TEST_CLIENT_ID'),
        'test_client_secret'                => env('UPI_RBL_TEST_CLIENT_SECRET'),
        'live_client_id'                    => env('UPI_RBL_LIVE_CLIENT_ID'),
        'live_client_secret'                => env('UPI_RBL_LIVE_CLIENT_SECRET'),
        'cert_dir_name'                     => env('UPI_RBL_GATEWAY_CERT_DIR'),
    ],

    'upi_yesbank' => [
        'test_merchant_id'            => env('UPI_YESBANK_TEST_MERCHANT_ID'),
        'live_merchant_id'            => env('UPI_YESBANK_LIVE_MERCHANT_ID'),
        'test_mcc'                    => env('UPI_YESBANK_TEST_MCC'),
        'test_merchant_key'           => env('UPI_YESBANK_TEST_MERCHANT_KEY'),
        'live_merchant_key'           => env('UPI_YESBANK_LIVE_MERCHANT_KEY'),
        'test_client_id'              => env('UPI_YESBANK_TEST_CLIENT_ID'),
        'live_client_id'              => env('UPI_YESBANK_LIVE_CLIENT_ID'),
        'test_client_secret'          => env('UPI_YESBANK_TEST_CLIENT_SECRET'),
        'live_client_secret'          => env('UPI_YESBANK_LIVE_CLIENT_SECRET'),
        'cert_dir_name'               => env('UPI_YESBANK_GATEWAY_CERT_DIR'),
        'client_cert'                 => env('UPI_YESBANK_GATEWAY_CLIENT_CERT'),
        'client_cert_key'             => env('UPI_YESBANK_GATEWAY_CLIENT_KEY')
    ],

    'aeps_icici' => [
        'terminal_id'                  => env('AEPS_ICICI_TEST_TERMINAL_ID'),
        'channel_code'                 => env('AEPS_ICICI_CHANNEL_CODE'),
        'refund_mcc_test'              => env('AEPS_ICICI_REFUND_MCC_TEST'),
        'refund_mcc_live'              => env('AEPS_ICICI_REFUND_MCC_LIVE'),
        'refund_payer_mobile_live'     => env('AEPS_ICICI_REFUND_PAYER_MOBILE_LIVE'),
        'refund_payer_mobile_test'     => env('AEPS_ICICI_REFUND_PAYER_MOBILE_TEST'),
        'refund_device_id_live'        => env('AEPS_ICICI_REFUND_DEVICE_ID_LIVE'),
        'refund_device_id_test'        => env('AEPS_ICICI_REFUND_DEVICE_ID_TEST'),
        'refund_profile_id_live'       => env('AEPS_ICICI_REFUND_PROFILE_ID_LIVE'),
        'refund_profile_id_test'       => env('AEPS_ICICI_REFUND_PROFILE_ID_TEST'),
        'refund_payer_vpa_live'        => env('AEPS_ICICI_REFUND_PAYER_VPA_LIVE'),
        'refund_payer_vpa_test'        => env('AEPS_ICICI_REFUND_PAYER_VPA_TEST'),
        'refund_account_provider_live' => env('AEPS_ICICI_REFUND_ACCOUNT_PROVIDER_LIVE'),
        'refund_account_provider_test' => env('AEPS_ICICI_REFUND_ACCOUNT_PROVIDER_TEST'),
        'refund_api_key_test'          => env('AEPS_ICICI_REFUND_API_KEY_TEST'),
        'refund_api_key_live'          => env('AEPS_ICICI_REFUND_API_KEY_LIVE'),
        'refund_test_private_key'      => env('AEPS_ICICI_REFUND_TEST_PRIVATE_KEY'),
        'refund_live_private_key'      => env('AEPS_ICICI_REFUND_LIVE_PRIVATE_KEY'),
        'refund_test_public_key'       => env('AEPS_ICICI_REFUND_TEST_PUBLIC_KEY'),
        'refund_live_public_key'       => env('AEPS_ICICI_REFUND_LIVE_PUBLIC_KEY'),
    ],

    'upi_npci' => [
        'test_decryption_key'       => env('UPI_NPCI_TEST_DECRYPTION_KEY'),
        'test_signing_key'          => env('UPI_NPCI_TEST_SIGNING_KEY'),
        'test_signing_public_key'   => env('UPI_NPCI_TEST_SIGNING_PUBLIC_KEY'),
    ],

    'upi_mindgate' => [
        'test_merchant_id'       => env('UPI_MINDGATE_TEST_MERCHANT_ID'),
        'gateway_encryption_key' => env('UPI_MINDGATE_GATEWAY_SECURE_SECRET'),
    ],

    'upi_hulk' => [
        'test_terminal_password'    => env('UPI_HULK_GATEWAY_TEST_SECURE_SECRET'),
        'gateway_terminal_password' => env('UPI_HULK_GATEWAY_SECURE_SECRET'),
        'cert_dir_name'             => env('UPI_HULK_GATEWAY_CERT_DIR'),
        'mindgate'                  => [
            'mid'              => env('UPI_MINDGATE_CASHBACK_LIVE_MID'),
            'key_id'           => env('UPI_MINDGATE_CASHBACK_LIVE_KEY_ID'),
            'public_key'       => env('UPI_MINDGATE_CASHBACK_LIVE_HDFC_PUBLIC_KEY'),
            'private_key'      => env('UPI_MINDGATE_CASHBACK_LIVE_PGP_PRIVATE_KEY'),
            'passphrase'       => env('UPI_MINDGATE_CASHBACK_LIVE_PGP_PASSPHRASE'),
            'client_id'        => env('UPI_MINDGATE_CASHBACK_LIVE_CLIENT_ID'),
            'client_secret'    => env('UPI_MINDGATE_CASHBACK_LIVE_CLIENT_SECRET'),
            'username'         => env('UPI_MINDGATE_CASHBACK_LIVE_MID'),
            'password'         => env('UPI_MINDGATE_CASHBACK_LIVE_PASSWORD'),
            'account_id'       => env('UPI_MINDGATE_CASHBACK_LIVE_HDFC_ACC_ID'),
            'vpa'              => env('UPI_MINDGATE_CASHBACK_LIVE_VPA'),
            'mobile'           => env('UPI_MINDGATE_CASHBACK_LIVE_MOBILE'),
            'live_client_cert' => env('UPI_MINDGATE_CASHBACK_LIVE_CLIENT_CERT'),
            'live_cert_key'    => env('UPI_MINDGATE_CASHBACK_LIVE_CLIENT_CERT_KEY'),
        ]
    ],

    'upi_sbi' => [
        'test_merchant_id' => env('UPI_MINDGATE_SBI_MERCHANT_ID'),
        'hash_secret'      => env('UPI_MINDGATE_SBI_HASH_SECRET'),
        'public_key'       => env('UPI_MINDGATE_SBI_PUBLIC_KEY'),
        'private_key'      => env('UPI_MINDGATE_SBI_PRIVATE_KEY'),
        'passphrase'       => env('UPI_MINDGATE_SBI_PASSPHRASE'),
        'client_id'        => env('UPI_MINDGATE_SBI_CLIENT_ID'),
        'client_secret'    => env('UPI_MINDGATE_SBI_CLIENT_SECRET'),
        'username'         => env('UPI_MINDGATE_SBI_OAUTH_USERNAME'),
        'password'         => env('UPI_MINDGATE_SBI_OAUTH_PASSWORD'),
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
        'test_public_key'       => env('OLAMONEY_WALLET_TEST_PUBLIC_KEY'),
        'test_private_key'      => env('OLAMONEY_WALLET_TEST_PRIVATE_KEY'),
        'test_iv'               => env('OLAMONEY_WALLET_TEST_IV'),
        'test_ola_public_key'   => env('OLAMONEY_WALLET_TEST_OLA_PUBLIC_KEY'),
        'live_public_key'       => env('OLAMONEY_WALLET_LIVE_PUBLIC_KEY'),
        'live_private_key'      => env('OLAMONEY_WALLET_LIVE_PRIVATE_KEY'),
        'live_iv'               => env('OLAMONEY_WALLET_LIVE_IV'),
        'live_ola_public_key'   => env('OLAMONEY_WALLET_LIVE_OLA_PUBLIC_KEY'),
        'test_merchant_id_v2'   => env('OLAMONEY_WALLET_TEST_MERCHANT_ID_V2'),
        'test_hash_secret_v2'   => env('OLAMONEY_WALLET_TEST_HASH_SECRET_V2'),
        'test_access_code_v2'   => env('OLAMONEY_WALLET_TEST_CLIENT_ID_V2'),
    ],

    'wallet_payumoney' => [
        'test_hash_secret'      => env('PAYUMONEY_WALLET_TEST_HASH_SECRET'),
        'test_merchant_id'      => env('PAYUMONEY_WALLET_TEST_MERCHANT_ID'),
        'test_access_code'      => env('PAYUMONEY_WALLET_TEST_CLIENT_ID'),
        'test_auth_header'      => env('PAYUMONEY_WALLET_TEST_AUTH_HEADER'),
    ],

    'wallet_airtelmoney' => [
        'test_hash_secret'       => env('AIRTELMONEY_WALLET_TEST_HASH_SECRET'),
        'test_merchant_id2'      => env('AIRTELMONEY_WALLET_TEST_MERCHANT_ID'),
        'test_end_merchant_id'   => env('AIRTELMONEY_WALLET_TEST_END_MERCHANT_ID'),
        'live_merchant_id'       => env('AIRTELMONEY_WALLET_LIVE_MERCHANT_ID'),
        'live_hash_secret'       => env('AIRTELMONEY_WALLET_LIVE_HASH_SECRET'),
    ],

    'wallet_amazonpay' => [
        // Test config
        'test_hash_secret' => env('AMAZONPAY_WALLET_TEST_HASH_SECRET'),
        'test_merchant_id' => env('AMAZONPAY_WALLET_TEST_MERCHANT_ID'),
        'test_access_code' => env('AMAZONPAY_WALLET_TEST_ACCESS_CODE'),
    ],

    'wallet_freecharge' => [
        'test_hash_secret'      => env('FREECHARGE_WALLET_TEST_HASH_SECRET'),
        // Freecharge is little different hence exchanging values
        'test_merchant_id'      => env('FREECHARGE_WALLET_TEST_DEALER_ID'),
        'test_dealer_id'        => env('FREECHARGE_WALLET_TEST_MERCHANT_ID'),
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
        // TODO: Move constants to env file

        'live_hash_secret'  => env('NETBANKING_HDFC_GATEWAY_LIVE_HASH_SECRET'),
        'test_hash_secret'  => '123456',

        // tpv
        'live_hash_secret_tpv' => env('NETBANKING_HDFC_GATEWAY_CUG_LIVE_HASH_SECRET'),
        'test_hash_secret_tpv' => '123456',

        'test_merchant_id' => 'RAZORPAY2',
    ],

    'netbanking_corporation' => [
        'test_merchant_id'       => env('NETBANKING_CORPORATION_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'       => env('NETBANKING_CORPORATION_GATEWAY_TEST_HASH_SECRET'),
        'live_hash_secret'       => env('NETBANKING_CORPORATION_GATEWAY_LIVE_HASH_SECRET'),
        'pooling_account_number' => env('KOTAK_NODAL_ACCOUNT_NUMBER'),
    ],

    'enach_rbl' => [],

    'enach_npci_netbanking' => [
        'test_merchant_id'                          => env('NPCI_EMANDATE_TEST_MERCHANT_ID'),
        'test_merchant_id2'                         => env('NPCI_EMANDATE_TEST_MERCHANT_ID2'),
        'test_emandate_private_key'                 => env('NPCI_EMANDATE_TEST_PRIVATE_KEY'),
        'test_emandate_npci_creditor_account'       => env('NPCI_EMANDATE_TEST_CREDITOR_ACCOUNT'),
        'test_emandate_npci_sponser_ifsc'           => env('NPCI_EMANDATE_TEST_SPONSER_IFSC'),
        'test_emandate_npci_sponser_bank'           => env('NPCI_EMANDATE_TEST_SPONSER_BANK'),
        'live_npci_emandate_private_key'            => env('NPCI_EMANDATE_LIVE_PRIVATE_KEY'),
        'live_npci_emandate_encryption_certificate' => env('NPCI_EMANDATE_LIVE_ENCRYPTION_CERTIFICATE'),
        'live_npci_emandate_signing_certificate'    => env('NPCI_EMANDATE_LIVE_SIGNING_CERTIFICATE'),
    ],

    'netbanking_allahabad' => [
        'test_merchant_id'       => env('NETBANKING_ALLAHABAD_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'       => env('NETBANKING_ALLAHABAD_GATEWAY_TEST_HASH_SECRET'),
        'test_merchant_id2'      => env('NETBANKING_ALLAHABAD_GATEWAY_TEST_MERCHANT_ID2'),
    ],

    'netbanking_canara' => [
        'test_merchant_id'       => env('NETBANKING_CANARA_GATEWAY_TEST_MERCHANT_ID'),
        'key'                    => env('NETBANKING_CANARA_GATEWAY_KEY'),
        'IV'                     => env('NETBANKING_CANARA_GATEWAY_IV'),
    ],

    'netbanking_svc' => [
        'encryption_key'         => env('NETBANKING_SVC_ENCRYPTION_KEY'),
        'encryption_iv'          => env('NETBANKING_SVC_ENCRYPTION_IV'),
    ],

    'netbanking_obc' => [
        'test_merchant_id'       => env('NETBANKING_OBC_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'       => env('NETBANKING_OBC_GATEWAY_TEST_HASH_SECRET'),
    ],

    'netbanking_kotak' => [
        'live_hash_secret'          => env('NETBANKING_KOTAK_GATEWAY_LIVE_HASH_SECRET'),
        'test_hash_secret'          => env('NETBANKING_KOTAK_GATEWAY_TEST_HASH_SECRET'),
        'live_hash_secret_tpv'      => env('NETBANKING_KOTAK_GATEWAY_SEC_LIVE_HASH_SECRET'),
        'live_hmac_hash_secret'     => env('NETBANKING_KOTAK_GATEWAY_LIVE_HMAC_HASH_SECRET'),

        'live_encrypt_hash_secret'  => env('NETBANKING_KOTAK_GATEWAY_LIVE_ENCRYPT_HASH_SECRET'),
        'test_encrypt_hash_secret'  => env('NETBANKING_KOTAK_GATEWAY_TEST_ENCRYPT_HASH_SECRET'),

        'kotak_decrypt_secret'      => env('NETBANKING_KOTAK_GATEWAY_DECRYPT_SECRET'),
        'kotak_encrypt_secret'      => env('NETBANKING_KOTAK_GATEWAY_ENCRYPT_SECRET'),

        'test_verify_hash_secret'   => env('NETBANKING_KOTAK_GATEWAY_TEST_VERIFY_HASH_SECRET'),

        'test_token_client_id'      => env('NETBANKING_KOTAK_GATEWAY_TEST_TOKEN_CLIENT_ID'),
        'test_token_client_secret'  => env('NETBANKING_KOTAK_GATEWAY_TEST_TOKEN_SECRET'),
        'live_token_client_id'      => env('NETBANKING_KOTAK_GATEWAY_LIVE_TOKEN_CLIENT_ID'),
        'live_token_client_secret'  => env('NETBANKING_KOTAK_GATEWAY_LIVE_TOKEN_SECRET'),
    ],

    'netbanking_bob' => [
        'test_merchant_id'       => env('NETBANKING_BOB_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'       => env('NETBANKING_BOB_GATEWAY_TEST_HASH_SECRET'),
        'pooling_account_number' => env('BOB_POOLING_ACCOUNT_NUMBER'),
    ],

    'netbanking_vijaya' => [
        'test_merchant_id'       => env('NETBANKING_VIJAYA_GATEWAY_TEST_MERCHANT_ID'),
        'merchant_constant'      => env('NETBANKING_VIJAYA_GATEWAY_MERCHANT_CONSTANT')
    ],

    'netbanking_idfc' => [
        'test_merchant_id'       => env('NETBANKING_IDFC_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'       => env('NETBANKING_IDFC_GATEWAY_TEST_HASH_SECRET'),
        'live_merchant_id'       => env('NETBANKING_IDFC_GATEWAY_LIVE_MERCHANT_ID'),
        'live_hash_secret'       => env('NETBANKING_IDFC_GATEWAY_LIVE_HASH_SECRET'),
        'client_certificate'     => env('NETBANKING_IDFC_CLIENT_CERTIFICATE'),
        'test_client_certificate' => env('NETBANKING_IDFC_TEST_CLIENT_CERTIFICATE'),
        'live_client_certificate' => env('NETBANKING_IDFC_LIVE_CLIENT_CERTIFICATE'),
        'cert_dir_name'           => env('NETBANKING_IDFC_CERT_DIR_NAME'),
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

        'live_hash_secret_corp'        => env('NETBANKING_ICICI_GATEWAY_LIVE_HASH_SECRET_CORP'),
        'live_merchant_id2_corp'       => env('NETBANKING_ICICI_GATEWAY_LIVE_MERCHANT_ID2_CORP'),

        'live_merchant_id2_corp_karvy' => env('NETBANKING_ICICI_GATEWAY_LIVE_MERCHANT_ID2_CORP_KARVY'),

        // Aditiya birla direct settlement TID
        'live_merchant_id2_aditiya_birla_direct' => env('NETBANKING_ICICI_GATEWAY_LIVE_MERCHANT_ID2_AB_DIRECT'),

        //Cred direct Settlement TID
        'live_hash_secret_cred'   => env('NETBANKING_ICICI_LIVE_HASH_SECRET_CRED'),
        'live_merchant_id2_cred'  => env('NETBANKING_ICICI_GATEWAY_LIVE_MERCHANT_ID_CRED'),
    ],

    'netbanking_axis' => [
        // retail netbanking
        'live_hash_secret_new'              => env('NETBANKING_AXIS_GATEWAY_LIVE_HASH_SECRET_NEW'),
        'test_hash_secret_new'              => env('NETBANKING_AXIS_GATEWAY_TEST_HASH_SECRET_NEW'),
        'test_merchant_id'                  => env('NETBANKING_AXIS_GATEWAY_TEST_MERCHANT_ID'),
        'verify_live_hash_secret'           => env('NETBANKING_AXIS_GATEWAY_VERIFY_LIVE_HASH_SECRET'),
        'verify_test_hash_secret'           => env('NETBANKING_AXIS_GATEWAY_VERIFY_TEST_HASH_SECRET'),

        // corporate netbanking
        'live_hash_secret_corporate'        => env('NETBANKING_AXIS_GATEWAY_LIVE_HASH_SECRET_CORPORATE'),
        'live_hash_secret_corporate_verify' => env('NETBANKING_AXIS_GATEWAY_LIVE_HASH_SECRET_CORPORATE_VERIFY'),
        'test_hash_secret_corporate_verify' => env('NETBANKING_AXIS_GATEWAY_TEST_HASH_SECRET_CORPORATE_VERIFY'),
        'test_hash_secret_corporate'        => env('NETBANKING_AXIS_GATEWAY_TEST_HASH_SECRET_CORPORATE'),
        'test_merchant_id_corporate'        => env('NETBANKING_AXIS_GATEWAY_TEST_MERCHANT_ID_CORPORATE'),

        // recurring
        'test_hash_secret_rec'              => env('NETBANKING_AXIS_GATEWAY_TEST_HASH_SECRET_REC'),
        'test_hash_secret_encrec'           => env('NETBANKING_AXIS_GATEWAY_TEST_HASH_SECRET_ENCREC'),
        'test_merchant_id_rec'              => env('NETBANKING_AXIS_GATEWAY_TEST_MERCHANT_ID_REC'),
    ],

    'netbanking_airtel' => [
        'test_merchant_id2'     => env('NETBANKING_AIRTEL_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'      => env('NETBANKING_AIRTEL_GATEWAY_TEST_HASH_SECRET'),
        'test_end_merchant_id'  => env('NETBANKING_AIRTEL_GATEWAY_TEST_END_MERCHANT_ID'),
    ],

    'netbanking_federal' => [
        'test_merchant_id'        => env('NETBANKING_FEDERAL_GATEWAY_TEST_MERCHANT_ID'),
        'test_terminal_password'  => env('NETBANKING_FEDERAL_GATEWAY_TEST_TERMINAL_PASSWORD'),
        'live_merchant_id'        => env('NETBANKING_FEDERAL_GATEWAY_LIVE_MERCHANT_ID'),
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
        'test_merchant_id'       => env('NETBANKING_PNB_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'       => env('NETBANKING_PNB_GATEWAY_TEST_HASH_SECRET'),
        'live_hash_secret'       => env('NETBANKING_PNB_GATEWAY_LIVE_HASH_SECRET'),
        'test_terminal_password' => env('NETBANKING_PNB_GATEWAY_TEST_TERMINAL_PASSWORD'),
        'live_terminal_password' => env('NETBANKING_PNB_GATEWAY_LIVE_TERMINAL_PASSWORD'),
        'recon_key'              => env('NETBANKING_PNB_RECON_DECRYPTION_KEY'),
        'recon_passphrase'       => env('NETBANKING_PNB_RECON_DECRYPTION_PASSPHRASE'),
    ],

    'netbanking_dbs' => [
        'recon_key'              => env('NETBANKING_DBS_RECON_DECRYPTION_KEY'),
        'files_encryption_key'   => env('NETBANKING_DBS_RECON_ENCRYPTION_KEY'),
    ],

    'netbanking_csb' => [
        'test_merchant_id_2'            => env('NETBANKING_CSB_GATEWAY_TEST_MERCHANT_ID2'),
        'test_merchant_id'              => env('NETBANKING_CSB_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'              => env('NETBANKING_CSB_GATEWAY_TEST_HASH_SECRET'),
        'test_terminal_password'        => env('NETBANKING_CSB_GATEWAY_TEST_TERMINAL_PASSWORD'),
        'test_terminal_password2'       => env('NETBANKING_CSB_GATEWAY_TEST_TERMINAL_PASSWORD2'),
        'test_gateway_secure_secret2'   => env('NETBANKING_CSB_GATEWAY_TEST_SECURE_SECRET2'),
    ],

    'netbanking_equitas' => [
        'test_hash_secret'   => env('NETBANKING_EQUITAS_TEST_HASH_SECRET'),
        'test_merchant_id'   => env('NETBANKING_EQUITAS_TEST_MERCHANT_ID'),
        'live_hash_secret'   => env('NETBANKING_EQUITAS_LIVE_HASH_SECRET'),
        'live_merchant_id'   => env('NETBANKING_EQUITAS_LIVE_MERCHANT_ID'),
    ],

    'netbanking_ubi' => [
        'recon_key'         => env('NETBANKING_UBI_RECON_DECRYPTION_KEY'),
    ],

    'paysecure' => [
        'caller_id'         => env('PAYSECURE_CALLER_ID'),
        'token'             => env('PAYSECURE_TOKEN'),
        'userid'            => env('PAYSECURE_USERID'),
        'password'          => env('PAYSECURE_PASSWORD'),
        'partner_id'        => env('PAYSECURE_PARTNER_ID'),
        'merchant_password' => env('PAYSECURE_MERCHANT_PASSWORD'),
        'terminal_id'       => env('PAYSECURE_TEST_TERMINAL_ID'),
        'merchant_id'       => env('PAYSECURE_TEST_MERCHANT_ID'),
    ],
    'netbanking_sbi' => [
        'test_merchant_id'            => env('NETBANKING_SBI_TEST_MERCHANT_ID'),
        'test_merchant_id_recurring'  => env('NETBANKING_SBI_TEST_MERCHANT_ID_RECURRING'),
        'test_hash_secret'            => env('NETBANKING_SBI_TEST_HASH_SECRET'),
        'test_hash_secret_recurring'  => env('NETBANKING_SBI_TEST_HASH_SECRET_RECURRING'),
        'live_hash_secret'            => env('NETBANKING_SBI_LIVE_HASH_SECRET'),
        'iv'                          => env('NETBANKING_SBI_IV'),
        'emandate_corporate_id'       => env('EMANDATE_SBI_CORPORATE_ID'),
    ],

    'mozart' => [
        'upi_airtel'     => [
            'test_hash_secret' => env('UPI_AIRTEL_TEST_HASH_SECRET')
        ],
        'netbanking_yesb' => [
            'gateway_secure_secret' => env('NETBANKING_YESB_GATEWAY_SECURE_SECRET')
        ],
        'netbanking_cub' => [
            'gateway_secure_secret'      => env('NETBANKING_CUB_GATEWAY_SECURE_SECRET'),
            'gateway_secure_secret2'     => env('NETBANKING_CUB_GATEWAY_SECURE_SECRET2'),
            'gateway_terminal_password'  => env('NETBANKING_CUB_GATEWAY_TERMINAL_PASSWORD'),
            'gateway_terminal_password2' => env('NETBANKING_CUB_GATEWAY_TERMINAL_PASSWORD2'),
        ],
        'netbanking_cbi' => [
            'account_number'         => env('CBI_NODAL_ACCOUNT_NUMBER'),
            'account_number_tpv'     => env('CBI_TPV_POOL_ACCOUNT_NUMBER'),
        ],
        'razorpayx' => [
            'direct' => [
                'rbl' => [
                    'mozart_identifier' => env('BANKING_ACCOUNT_RBL_MOZART_IDENTIFIER'),
                ],
            ]
        ],
        'upi_citi' => [
            'allowed_s2p_client_ips'      => env('UPI_CITI_ALLOWED_S2S_CLIENT_IPS'),
        ],
    ],

    'sharp' => [
    ],

    'cardless_emi' => [
        'flexmoney' => [
            'test_hash_secret' => env('CARDLESS_EMI_TEST_HASH_SECRET'),
            'live_hash_secret' => env('CARDLESS_EMI_LIVE_HASH_SECRET')
        ],
        'earlysalary' => [
            'test_hash_secret' => env('CARDLESS_EMI_EARLYSALARY_TEST_HASH_SECRET'),
            'live_hash_secret' => env('CARDLESS_EMI_EARLYSALARY_LIVE_HASH_SECRET')
        ],

        'live_earlysalary_terminal_password' => env('GATEWAY_TERMINAL_PASSWORD_CARDLESSEMI_EARLYSALARY'),
        'live_zestmoney_terminal_password'   => env('GATEWAY_TERMINAL_PASSWORD_CARDLESSEMI_ZESTMONEY'),
        'live_flexmoney_terminal_password'   => env('GATEWAY_TERMINAL_PASSWORD_CARDLESSEMI_FLEXMONEY'),
    ],

    'netbanking_cub' => [
        'live_terminal_password' => env('GATEWAY_TERMINAL_PASSWORD_NETBANKING_CUB'),
    ],

    'worldline'    => [
        'aes_encryption_key'    => env('WORLDLINE_AES_KEY'),
    ],

    'mpi_enstage' => [
        'test' => [
            'gateway_merchant_id'           => env('ENSTAGE_TEST_MERCHANT_ID'),
            'gateway_merchant_name'         => 'Test Merchant',
        ],
        // Hardcoding the values here.
        'live' => [
            'gateway_merchant_id'           => 'Wibmo_Razorpay_Axis_Expay',
            'gateway_merchant_name'         => 'Razorpay_Axis_Expay',
        ],

        // yatra config
        '87qTXzFTBLFN7i' => [
            'gateway_merchant_id'           => 'Wibmo_RYatra_Axis_Expay',
            'gateway_merchant_name'         => 'RYatra_Axis_Expay',
        ],

        // goomo config
        '7kBHljwok8Fsom' => [
            'gateway_merchant_id'           => 'Wibmo_Goomo_Axis_Expay',
            'gateway_merchant_name'         => 'Goomo_Axis_Expay',
        ],

        // goomo config
        '8STmhcK1Gd1JVo' => [
            'gateway_merchant_id'           => 'Wibmo_Goomo_Axis_Expay',
            'gateway_merchant_name'         => 'Goomo_Axis_Expay',
        ],

        'C1fjEduvEkBUEK' => [
            'gateway_merchant_id'           => 'Wibmo_Razor_Flipkart_Axis_Expay',
            'gateway_merchant_name'         => 'Flipkart'
        ],

        'C1fmOZYiZiezoD' => [
            'gateway_merchant_id'           => 'Wibmo_Razor_Flipkart_Axis_Expay',
            'gateway_merchant_name'         => 'Flipkart'
        ],

        'C1fnUMHBmitlPB' => [
            'gateway_merchant_id'           => 'Wibmo_Razor_Flipkart_Axis_Expay',
            'gateway_merchant_name'         => 'Flipkart'
        ],

        'C1fo6ARXco94tP' => [
            'gateway_merchant_id'           => 'Wibmo_Razor_Flipkart_Axis_Expay',
            'gateway_merchant_name'         => 'Flipkart'
        ],

        'C1fp6DAnDH4YUz' => [
            'gateway_merchant_id'           => 'Wibmo_Razor_Flipkart_Axis_Expay',
            'gateway_merchant_name'         => 'Flipkart'
        ],

        'C1fq8jgl8NRKnh' => [
            'gateway_merchant_id'           => 'Wibmo_Razor_Flipkart_Axis_Expay',
            'gateway_merchant_name'         => 'Flipkart'
        ],

        'test_acq_bin'                      => env('ENSTAGE_TEST_GATEWAY_ACQUIRER_BIN'),
        'test_secret_key'                   => env('ENSTAGE_TEST_SECRET_KEY_ID'),
        'test_encryption_key'               => env('ENSTAGE_TEST_ENCRYPTION_KEY'),
        'live_encryption_key'               => env('ENSTAGE_GATEWAY_LIVE_ENCRYPTION_KEY'),
        'live_secret'                       => env('ENSTAGE_GATEWAY_LIVE_SECRET'),
        'live_mastercard_acq_bin'           => env('ENSTAGE_LIVE_MASTERCARD_ACQ_BIN'),
        'live_visa_acq_bin'                 => env('ENSTAGE_LIVE_VISA_ACQ_BIN'),
    ],

    'proxy_enabled' => env('PROXY_ENABLED'),

    'proxy_address' => env('PROXY_ADDRESS'),

    'validate_vpa_terminal_ids' => [
        'test'  => env('VALIDATE_VPA_TEST_TERMINAL_IDS'),
        'live'  => env('VALIDATE_VPA_LIVE_TERMINAL_IDS'),
    ],
];
