<?php

namespace RZP\Http\Controllers\P2p;

class Requests
{
    const P2P_CUSTOMER_INITIATE_VERIFICATION                = 'p2p_customer_initiate_verification';
    const P2P_CUSTOMER_VERIFICATION                         = 'p2p_customer_verification';
    const P2P_CUSTOMER_INITIATE_GET_TOKEN                   = 'p2p_customer_initiate_get_token';
    const P2P_CUSTOMER_GET_TOKEN                            = 'p2p_customer_get_token';
    const P2P_CUSTOMER_DEREGISTER                           = 'p2p_customer_deregister';

    const P2P_BANKS_FETCH_ALL                               = 'p2p_banks_fetch_all';
    const P2P_CUSTOMER_BA_INITIATE_RETRIEVE                 = 'p2p_customer_ba_initiate_retrieve';
    const P2P_CUSTOMER_BA_RETRIEVE                          = 'p2p_customer_ba_retrieve';
    const P2P_CUSTOMER_BA_FETCH_ALL                         = 'p2p_customer_ba_fetch_all';
    const P2P_CUSTOMER_BA_FETCH                             = 'p2p_customer_ba_fetch';
    const P2P_CUSTOMER_BA_INITIATE_SET_UPI_PIN              = 'p2p_customer_ba_initiate_set_upi_pin';
    const P2P_CUSTOMER_BA_SET_UPI_PIN                       = 'p2p_customer_ba_set_upi_pin';
    const P2P_CUSTOMER_BA_INITIATE_FETCH_BALANCE            = 'p2p_customer_ba_initiate_fetch_balance';
    const P2P_CUSTOMER_BA_FETCH_BALANCE                     = 'p2p_customer_ba_fetch_balance';

    const P2P_HANDLES_FETCH_ALL                             = 'p2p_handles_fetch_all';
    const P2P_CUSTOMER_VPA_INITIATE_CREATE                  = 'p2p_customer_vpa_initiate_create';
    const P2P_CUSTOMER_VPA_CREATE                           = 'p2p_customer_vpa_create';
    const P2P_CUSTOMER_VPA_FETCH_ALL                        = 'p2p_customer_vpa_fetch_all';
    const P2P_CUSTOMER_VPA_FETCH                            = 'p2p_customer_vpa_fetch';
    const P2P_CUSTOMER_VPA_ASSIGN_BANK_ACCOUNT              = 'p2p_customer_vpa_assign_bank_account';
    const P2P_CUSTOMER_VPA_INITIATE_CHECK_AVAILABILITY      = 'p2p_customer_vpa_check_initiate_availability';
    const P2P_CUSTOMER_VPA_CHECK_AVAILABILITY               = 'p2p_customer_vpa_check_availability';
    const P2P_CUSTOMER_VPA_DELETE                           = 'p2p_customer_vpa_delete';

    const P2P_CUSTOMER_BENEFICIARIES                        = 'p2p_customer_beneficiaries';
    const P2P_CUSTOMER_BENEFICIARIES_VALIDATE               = 'p2p_customer_beneficiaries_validate';
    const P2P_CUSTOMER_BENEFICIARIES_FETCH_ALL              = 'p2p_customer_beneficiaries_fetch_all';
    const P2P_CUSTOMER_BENEFICIARIES_HANDLE                 = 'p2p_customer_beneficiaries_handle';

    const P2P_CUSTOMER_TRANSACTIONS_INITIATE_PAY            = 'p2p_customer_transactions_initiate_pay';
    const P2P_CUSTOMER_TRANSACTIONS_INITIATE_COLLECT        = 'p2p_customer_transactions_initiate_collect';
    const P2P_CUSTOMER_TRANSACTIONS_FETCH_ALL               = 'p2p_customer_transactions_fetch_all';
    const P2P_CUSTOMER_TRANSACTIONS_FETCH                   = 'p2p_customer_transactions_fetch';
    const P2P_CUSTOMER_TRANSACTIONS_INITIATE_AUTHORIZE      = 'p2p_customer_transactions_initiate_authorize';
    const P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE               = 'p2p_customer_transactions_authorize';
    const P2P_CUSTOMER_TRANSACTIONS_INITIATE_REJECT         = 'p2p_customer_transactions_initiate_reject';
    const P2P_CUSTOMER_TRANSACTIONS_REJECT                  = 'p2p_customer_transactions_reject';

    const P2P_CUSTOMER_CONCERNS_TRANSACTION_RAISE          = 'p2p_customer_concerns_transaction_raise';
    const P2P_CUSTOMER_CONCERNS_TRANSACTION_FETCH_ALL      = 'p2p_customer_concerns_transaction_fetch_all';
    const P2P_CUSTOMER_CONCERNS_TRANSACTION_STATUS         = 'p2p_customer_concerns_transaction_status';

    const P2P_GATEWAY_CALLBACK                              = 'p2p_gateway_callback';
}
