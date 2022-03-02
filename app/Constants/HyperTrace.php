<?php


namespace RZP\Constants;


class HyperTrace
{
    /*virtual account traces name*/
    const VIRTUAL_ACCOUNTS_SERVICE_CREATE                           = 'virtual_accounts.service.create';
    const VIRTUAL_ACCOUNTS_SERVICE_FETCH                            = 'virtual_accounts.service.fetch.findByPublicIdAndMerchantWithRelations';
    const VIRTUAL_ACCOUNTS_SERVICE_FETCH_MULTIPLE                   = 'virtual_accounts.service.fetchMultiple';
    const VIRTUAL_ACCOUNTS_SERVICE_FETCH_PAYMENT                    = 'virtual_accounts.service.fetchPayment';

    const VIRTUAL_ACCOUNTS_CORE_SAVE                                = 'virtual_accounts.core.save';
    const VIRTUAL_ACCOUNTS_CORE_VIRTUAL_ACCOUNT_PRODUCTS            = 'virtual_accounts.core.virtualAccountProducts';
    const VIRTUAL_ACCOUNTS_CORE_ADD_ALLOWED_PAYER                   = 'virtual_accounts.core.virtualAccountProducts';
    const VIRTUAL_ACCOUNTS_CORE_CREATE_ORIGIN_ENTITY                = 'virtual_accounts.core.createEntityOrigin';
    const VIRTUAL_ACCOUNTS_FETCH_PAYMENTS                           = 'virtual_accounts.fetchPayment';
    const VIRTUAL_ACCOUNTS_CORE_ACQUIRE_AND_RELEASE                 = 'virtual_accounts.core.acquireAndRelease';
    const VIRTUAL_ACCOUNTS_ADD_RECEIVER                             = 'virtual_accounts.addReceiver';
    const VIRTUAL_ACCOUNTS_CORE_ADD_RECEIVERS                       = 'virtual_accounts.core.addReceiver';
    const VIRTUAL_ACCOUNTS_ADD_ALLOWED_PAYER                        = 'virtual_accounts.addAllowedPayer';
    const VIRTUAL_ACCOUNTS_DELETE_ALLOWED_PAYER                     = 'virtual_accounts.deleteAllowedPayer';

    const VIRTUAL_ACCOUNTS_SERVICE_ADD_ALLOWED_PAYER_FIND_BY_PUBLIC_ID_AND_MERCHANT = 'virtual_accounts.service.addAllowedPayer.findByPublicIdAndMerchant';
    const VIRTUAL_ACCOUNTS_SERVICE_DELETE_ALLOWED_PAYER_FIND_BY_PUBLIC_ID_AND_MERCHANT = 'virtual_accounts.service.deleteAllowedPayer.findByPublicIdAndMerchant';
    const VIRTUAL_ACCOUNTS_SERVICE_ADD_ALLOWED_PAYER_ACQUIRE_AND_RELEASE           = 'virtual_accounts.service.addAllowedPayer.acquireAndRelease';
    const VIRTUAL_ACCOUNTS_SERVICE_DELETE_ALLOWED_PAYER_ACQUIRE_AND_RELEASE           = 'virtual_accounts.service.deleteAllowedPayer.acquireAndRelease';


    const VIRTUAL_ACCOUNTS_SERVICE_ADD_RECEIVERS_FIND_BY_PUBLIC_ID_AND_MERCHANT   = 'virtual_accounts.service.addReceiver.findByPublicIdAndMerchant';
    const VIRTUAL_ACCOUNTS_SERVICE_ADD_RECEIVERS_ACQUIRE_AND_RELEASE              = 'virtual_accounts.service.addReceiver.acquireAndRelease';

    const VIRTUAL_ACCOUNTS_GET_BANK_TRANSFER_FOR_PAYMENT        = 'virtual_accounts.getBankTransferForPayment';
    const BANK_TRANSFER_SERVICE_FIND_BY_PUBLIC_ID_AND_MERCHANT  = 'virtual_accounts.service.findByPublicIdAndMerchant';
    const BANK_TRANSFER_SERVICE_FIND_BY_PAYMENT                 = 'virtual_accounts.service.findByPayment';

    const UPI_FETCH_FOR_PAYMENT                                 = 'upi.fetchForPayment';
    const UPI_SERVICE_FIND_BY_PUBLIC_ID_AND_MERCHANT            = 'upi.service.fetchForPayment.findByPublicIdAndMerchant';
    const UPI_SERVICE_FIND_BY_PAYMENT_ID                        = 'upi.service.findByPaymentId';
    const PAYMENT_REFUND                                        = 'payment.refund';

    /*QR code traces names*/
    const QR_CODE_CREATE                                        = 'qrv2_qr_code.create';
    const QR_CODE_CREATE_FOR_CHECKOUT                           = 'qrv2_qr_code.create_for_checkout';
    const QR_CODE_CREATE_BUILD_QR_CODE                          = 'qrv2_qr_code.create.buildQrCode';
    const QR_CODE_CREATE_FOR_CHECKOUT_SERVICE                   = 'qrv2_qr_code.create_for_checkout.createForCheckout';
    const QR_CODES_CLOSE_QR_CODE                                = 'qrv2_qr_codes.closeQrCode';
    const QR_CODES_FETCH_MULTIPLE_PAYMENT_ID                    = 'qrv2_qr_codes.fetch_multiple.paymentId';
    const QR_CODES_FETCH_MULTIPLE_FETCH_ALL                     = 'qrv2_qr_codes.fetch_multiple.fetch_all';
    const QR_CODES_FETCH                                        = 'qrv2_qr_codes.fetch';

    const QR_PAYMENT_FETCH_MULTIPLE_PAYMENTS                    = 'qrv2_qr_payment.fetchMultiplePayments';
    const QR_PAYMENT_FETCH_PAYMENT_BY_QR_CODE_ID                = 'qrv2_qr_payment.fetchCapturedPaymentByQrCodeId';
    const QR_PAYMENT_FETCH_PAYMENT_BY_QR_PAYMENT_ID             = 'qrv2_qr_payment.fetchCapturedPaymentByQrCodeId';

    /*Onboarding APIs traces names*/
    const CREATE_ACCOUNT_V2                                    = 'account_v2.create.service';
    const CREATE_ACCOUNT_V2_CORE                               = 'account_v2.create.core';
    const ACCOUNT_V2_INVALIDATE_CACHE                          = 'account_v2.create.core.invalidate_cache';
    const CREATE_SUBMERCHANT_ENTITIES                          = 'create_submerchant_entities';
    const CREATE_SUBMERCHANT_AND_SET_RELATIONS                 = 'create_submerchant_and_set_relations';
    const SEND_MAIL_TO_SUBMERCHANT                             = 'send_mail_to_submerchant';
    const VALIDATE_PARTNER_ACCESS                              = 'validate_partner_access';
    const FILL_SUBMERCHANT_DETAILS                             = 'fill_submerchant_details';
    const FETCH_ACCOUNT_V2                                     = 'account_v2.fetch';
    const FETCH_ACCOUNT_V2_CORE                                = 'account_v2.fetch.core';
    const EDIT_ACCOUNT_V2                                      = 'account_v2.edit';
    const EDIT_ACCOUNT_V2_CORE                                 = 'account_v2.edit.core';
    const DELETE_ACCOUNT_V2                                    = 'account_v2.delete';
    const ACCOUNT_V2_DISABLE                                   = 'account_v2.delete.disable';

    const CREATE_STAKEHOLDER_V2                                 = 'stakeholder_v2.create.service';
    const CREATE_STAKEHOLDER_V2_CORE                            = 'stakeholder_v2.create.core';
    const STAKEHOLDER_CREATE_RESPONSE                           = 'stakeholder_v2.create_response';
    const FETCH_STAKEHOLDER_V2                                  = 'stakeholder_v2.fetch.service';
    const FETCH_STAKEHOLDER_V2_CORE                             = 'stakeholder_v2.fetch.core';
    const FETCH_ALL_STAKEHOLDER_V2                              = 'stakeholder_v2.fetch_all.service';
    const FETCH_ALL_STAKEHOLDER_V2_CORE                         = 'stakeholder_v2.fetch_all.core';
    const UPDATE_STAKEHOLDER_V2                                 = 'stakeholder_v2.update.service';
    const UPDATE_STAKEHOLDER_V2_CORE                            = 'stakeholder_v2.update.core';

    const CREATE_OR_FETCH_STAKEHOLDER                           = 'create_or_fetch_stakeholder';
    const SAVE_STAKEHOLDER                                      = 'save_stakeholder';
    const EDIT_STAKEHOLDER                                      = 'save_stakeholder.edit';
    const SAVE_MERCHANT_DETAILS                                 = 'save_stakeholder.save_merchant_details';
    const UPDATE_NC_FIELDS_ACKNOWLEDGED                         = 'update_nc_fields_acknowledged';
    const VALIDATE_NC_RESPONDED_IF_APPLICABLE                   = 'validate_nc_responded_if_applicable';

    const POST_ACCOUNTS_DOCUMENTS                               = 'post_accounts_documents_v2.service';
    const POST_STAKEHOLDER_DOCUMENTS                            = 'post_stakeholder_documents_v2.service';
    const GET_ACCOUNTS_DOCUMENTS                                = 'get_accounts_documents_v2.service';
    const GET_STAKEHOLDER_DOCUMENTS                             = 'get_stakeholder_documents_v2.service';
    const UPLOAD_ACTIVATION_FILE                                = 'upload_activation_file';
    const UPDATE_NC_FIELDS_ACKNOWLEDGED_FOR_NO_DOC              = 'update_nc_fields_acknowledged_for_no_doc';
    const DOCUMENT_V2_GET_RESPONSE                              = 'document_v2_get_response';
    const GET_REQUIRED_DOC_TYPES                                = 'get_required_doc_types';
    const CONSTRUCT_DOCUMENT_V2_RESPONSE                        = 'construct_document_v2_response';

    /*Partnerships traces names*/
    const CREATE_SUBMERCHANT_SERVICE                           = 'create_submerchant.service';
    const CREATE_SUBMERCHANT_CORE                              = 'create_submerchant.core';

}
