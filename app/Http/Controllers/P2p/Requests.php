<?php

namespace RZP\Http\Controllers\P2p;

class Requests
{
    const P2P_CUSTOMER_INITIATE_VERIFICATION                = 'p2p_customer_initiate_verification';
    const P2P_CUSTOMER_VERIFICATION                         = 'p2p_customer_verification';
    const P2P_CUSTOMER_INITIATE_GET_TOKEN                   = 'p2p_customer_initiate_get_token';
    const P2P_CUSTOMER_GET_TOKEN                            = 'p2p_customer_get_token';
    const P2P_CUSTOMER_DEREGISTER                           = 'p2p_customer_deregister';
    const P2P_TURBO_PREFERENCES                             = 'p2p_turbo_preferences';
    const P2P_TURBO_GATEWAY_CONFIG                          = 'p2p_turbo_gateway_config';

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
    const P2P_CUSTOMER_VPA_UPDATE                           = 'p2p_customer_vpa_update';
    const P2P_CUSTOMER_VPA_ASSIGN_BANK_ACCOUNT              = 'p2p_customer_vpa_assign_bank_account';
    const P2P_CUSTOMER_VPA_INITIATE_CHECK_AVAILABILITY      = 'p2p_customer_vpa_check_initiate_availability';
    const P2P_CUSTOMER_VPA_CHECK_AVAILABILITY               = 'p2p_customer_vpa_check_availability';
    const P2P_CUSTOMER_VPA_SET_DEFAULT                      = 'p2p_customer_vpa_set_default';
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

    const P2P_GATEWAY_CALLBACK                             = 'p2p_gateway_callback';
    const P2P_TURBO_GATEWAY_CALLBACK                       = 'p2p_turbo_gateway_callback';

    const P2P_MERCHANT_BENEFICIARY_VALIDATE                = 'p2p_merchant_beneficiary_validate';
    const P2P_MERCHANT_DEVICE_UPDATE_WITH_ACTION           = 'p2p_merchant_device_update_with_action';
    const P2P_MERCHANT_DEVICES_FETCH_ALL                   = 'p2p_merchant_devices_fetch_all';
    const P2P_MERCHANT_VPA_FETCH_ALL                       = 'p2p_merchant_vpa_fetch_all';


    const P2P_CUSTOMER_MANDATE_FETCH_ALL                   = 'p2p_customer_mandate_fetch_all';
    const P2P_CUSTOMER_MANDATE_FETCH                       = 'p2p_customer_mandate_fetch';
    const P2P_CUSTOMER_MANDATE_INITIATE_AUTHORIZE          = 'p2p_customer_mandate_initiate_authorize';
    const P2P_CUSTOMER_MANDATE_INITIATE_REJECT             = 'p2p_customer_mandate_initiate_reject';
    const P2P_CUSTOMER_MANDATE_INITIATE_PAUSE              = 'p2p_customer_mandate_initiate_pause';
    const P2P_CUSTOMER_MANDATE_INITIATE_UNPAUSE            = 'p2p_customer_mandate_initiate_unpause';
    const P2P_CUSTOMER_MANDATE_INITIATE_REVOKE             = 'p2p_customer_mandate_initiate_revoke';
    const P2P_CUSTOMER_MANDATE_AUTHORIZE                   = 'p2p_customer_mandate_authorize';
    const P2P_CUSTOMER_MANDATE_REJECT                      = 'p2p_customer_mandate_reject';
    const P2P_CUSTOMER_MANDATE_PAUSE                       = 'p2p_customer_mandate_pause';
    const P2P_CUSTOMER_MANDATE_UNPAUSE                     = 'p2p_customer_mandate_unpause';
    const P2P_CUSTOMER_MANDATE_REVOKE                      = 'p2p_customer_mandate_revoke';

    const P2P_MERCHANT_BLACKLIST_ADD_BATCH                = 'p2p_merchant_blacklist_add_batch';
    const P2P_MERCHANT_BLACKLIST_FETCH_ALL                = 'p2p_merchant_blacklist_fetch_all';
    const P2P_MERCHANT_BLACKLIST_REMOVE_BATCH             = 'p2p_merchant_blacklist_remove_batch';
}
