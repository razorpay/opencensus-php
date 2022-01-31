<?php


namespace RZP\Constants;


class HyperTrace
{
    /*virtual account traces name*/
    const VIRTUAL_ACCOUNTS_SERVICE_CREATE                       = 'virtual_accounts.service.create';
    const VIRTUAL_ACCOUNTS_SERVICE_FETCH                        = 'virtual_accounts.service.fetch.findByPublicIdAndMerchantWithRelations';
    const VIRTUAL_ACCOUNTS_SERVICE_FETCH_MULTIPLE               = 'virtual_accounts.service.fetchMultiple';
    const VIRTUAL_ACCOUNTS_SERVICE_FETCH_PAYMENT                = 'virtual_accounts.service.fetchPayment';

    const VIRTUAL_ACCOUNTS_CORE_SAVE                            = 'virtual_accounts.core.save';
    const VIRTUAL_ACCOUNTS_CORE_VIRTUAL_ACCOUNT_PRODUCTS        = 'virtual_accounts.core.virtualAccountProducts';
    const VIRTUAL_ACCOUNTS_CORE_ADD_ALLOWED_PAYER               = 'virtual_accounts.core.virtualAccountProducts';
    const VIRTUAL_ACCOUNTS_CORE_CREATE_ORIGIN_ENTITY            = 'virtual_accounts.core.createEntityOrigin';
    const VIRTUAL_ACCOUNTS_FETCH_PAYMENTS                       = 'virtual_accounts.fetchPayment';
    const VIRTUAL_ACCOUNTS_CORE_ACQUIRE_AND_RELEASE             = 'virtual_accounts.core.acquireAndRelease';

    const VIRTUAL_ACCOUNTS_GET_BANK_TRANSFER_FOR_PAYMENT        = 'virtual_accounts.getBankTransferForPayment';
    const BANK_TRANSFER_SERVICE_FIND_BY_PUBLIC_ID_AND_MERCHANT  = 'virtual_accounts.service.findByPublicIdAndMerchant';
    const BANK_TRANSFER_SERVICE_FIND_BY_PAYMENT                 = 'virtual_accounts.service.findByPayment';

    const UPI_FETCH_FOR_PAYMENT                                 = 'upi.fetchForPayment';
    const UPI_SERVICE_FIND_BY_PUBLIC_ID_AND_MERCHANT            = 'upi.service.fetchForPayment.findByPublicIdAndMerchant';
    const UPI_SERVICE_FIND_BY_PAYMENT_ID                        = 'upi.service.findByPaymentId';




}
