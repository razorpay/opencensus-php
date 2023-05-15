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

    /* UPI Transfer Traces name*/
    const UPI_TRANSFER_CAPTURE_OR_REFUND                        = 'upi_transfer.processor.processPayment';
    const UPI_TRANSFER_PROCESS_PAYMENT                          = 'upi_transfer.processor.processPayment';
    const UPI_TRANSFER_PRE_PROCESS_CALLBACK                     = 'upi_transfer.service.computeGatewayResponseAndTerminal';
    const UPI_TRANSFER_FETCH_TERMINAL                           = 'upi_transfer.service.computeGatewayResponseAndTerminal';

    /*QR code traces names*/
    const QR_CODE_CREATE                                        = 'qrv2_qr_code.create';
    const QR_CODE_CREATE_FOR_CHECKOUT                           = 'qrv2_qr_code.create_for_checkout';
    const QR_CODE_CREATE_BUILD_QR_CODE                          = 'qrv2_qr_code.create.buildQrCode';
    const QR_CODE_CREATE_FOR_CHECKOUT_SERVICE                   = 'qrv2_qr_code.create_for_checkout.createForCheckout';
    const QR_CODES_CLOSE_QR_CODE                                = 'qrv2_qr_codes.closeQrCode';
    const QR_CODES_FETCH_MULTIPLE_PAYMENT_ID                    = 'qrv2_qr_codes.fetch_multiple.paymentId';
    const QR_CODES_FETCH_MULTIPLE_FETCH_ALL                     = 'qrv2_qr_codes.fetch_multiple.fetch_all';
    const QR_CODES_FETCH                                        = 'qrv2_qr_codes.fetch';
    const QR_CODE_GENERATE_IMAGE                                = 'qrv2_qr_code.generateQrCodeFile.generateImage';
    const QR_CODE_BUILD_GENERATE_QR_STRING                      = 'qrv2_qr_code.build.generateQrString';
    const QR_CODE_BUILD_SET_SHORT_URL                           = 'qrv2_qr_code.build.setShortUrl';
    const QR_CODE_BUILD_SAVE_OR_FAIL                            = 'qrv2_qr_code.build.saveOrFail';
    const QR_CODE_UFH_UPLOAD                                    = 'qrv2_qr_code.generateQrCodeFile.saveQrCodeImageUsingUfh';

    const QR_PAYMENT_FETCH_MULTIPLE_PAYMENTS                    = 'qrv2_qr_payment.fetchMultiplePayments';
    const QR_PAYMENT_FETCH_PAYMENT_BY_QR_CODE_ID                = 'qrv2_qr_payment.fetchCapturedPaymentByQrCodeId';
    const QR_PAYMENT_FETCH_PAYMENT_STATUS_BY_QR_CODE_ID         = 'qrv2_qr_payment.fetchPaymentStatusByQrCodeId';
    const QR_PAYMENT_FETCH_PAYMENT_BY_QR_PAYMENT_ID             = 'qrv2_qr_payment.fetchCapturedPaymentByQrCodeId';

    /*Onboarding APIs traces names*/
    const CREATE_ACCOUNT_V2                                    = 'account_v2.create.service';
    const CREATE_ACCOUNT_V2_CORE                               = 'account_v2.create.core';
    const ACCOUNT_V2_INVALIDATE_CACHE                          = 'account_v2.create.core.invalidate_cache';
    const CREATE_SUBMERCHANT_ENTITIES                          = 'create_submerchant_entities';
    const CREATE_SUBMERCHANT_AND_SET_RELATIONS                 = 'create_submerchant_and_set_relations';
    const CREATE_SUBMERCHANT_AND_SET_RELATIONS_INTERNAL        = 'create_submerchant_and_set_relations_internal';
    const SEND_MAIL_TO_SUBMERCHANT                             = 'send_mail_to_submerchant';
    const VALIDATE_PARTNER_ACCESS                              = 'validate_partner_access';
    const VALIDATE_LINKED_ACCOUNT_ACCESS                       = 'validate_linked_account';
    const FILL_SUBMERCHANT_DETAILS                             = 'fill_submerchant_details';
    const FETCH_ACCOUNT_V2                                     = 'account_v2.fetch.service';
    const FETCH_ACCOUNT_V2_CORE                                = 'account_v2.fetch.core';
    const EDIT_ACCOUNT_V2                                      = 'account_v2.edit.service';
    const EDIT_ACCOUNT_V2_CORE                                 = 'account_v2.edit.core';
    const DELETE_ACCOUNT_V2                                    = 'account_v2.delete.service';
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
    const SAVE_MERCHANT_DETAILS                                 = 'save_merchant_details';
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

    const CREATE_PRODUCT_CONFIG                                 = 'create_product_config.service';
    const CREATE_PRODUCT_CONFIG_CORE                            = 'create_product_config.core';
    const SET_DEFAULT_METHODS                                   = 'set_default_methods';
    const GET_PRODUCT_CONFIG                                    = 'get_product_config.service';
    const GET_PRODUCT_CONFIG_CORE                               = 'get_product_config.core';
    const UPDATE_PRODUCT_CONFIG                                 = 'update_product_config.service';
    const UPDATE_PRODUCT_CONFIG_CORE                            = 'update_product_config.core';
    const TRANSFORM_PRODUCT_CONFIG_REQUEST                      = 'transform_product_config_request';
    const ACCEPT_PRODUCT_TNC                                    = 'accept_product_tnc';
    const ACCEPT_OR_FETCH_PRODUCT_TNC                           = 'accept_or_fetch_product_tnc';
    const CREATE_PAYMENT_GENERAL_CONFIG                         = 'create_payment_general_config';
    const CREATE_ROUTE_CONFIG                                   = 'create_route_config';
    const CREATE_CONFIG                                         = 'payment_general_config.create_config';
    const UPDATE_CONFIG                                         = 'payment_general_config.update_config';
    const GET_CONFIG                                            = 'payment_general_config.get_config';
    const FETCH_ACCEPTED_TNC_DETAILS                            = 'fetch_accepted_tnc_details';
    const FETCH_REQUIREMENTS                                    = 'fetch_requirements';
    const GET_PAYMENT_METHODS                                   = 'get_payment_methods';
    const HANDLE_PRODUCT_CONFIG_RESPONSE                        = 'handle_product_config_response';
    const VALIDATE_AND_FETCH_DEFAULT_CONFIG                     = 'validate_and_fetch_default_config';

    const FETCH_BU_TNC                                          = 'fetch_business_unit_tnc';

    /*Partnerships traces names*/
    const CREATE_SUBMERCHANT_SERVICE                           = 'create_submerchant.service';
    const CREATE_SUBMERCHANT_CORE                              = 'create_submerchant.core';
    const ASSIGN_SUBMERCHANT_PRICING_PLAN                      = 'assign_submerchant_pricing_plan';
    const ADD_MERCHANT_SUPPORTING_ENTITIES                     = 'add_merchant_supporting_entities';
    const ADD_SUBMERCHANT_SUPPORTING_ENTITIES                  = 'add_submerchant_supporting_entities';
    const MAP_SUBMERCHANT_PARTNER_APP_IF_APPLICABLE            = 'map_submerchant_partner_app_if_applicable';
    const ADD_FEATURE_REQUEST                                  = 'add_feature_request.core';
    const ATTACH_SUBMERCHANT_USER_IF_APPLICABLE                = 'attach_submerchant_user_if_applicable';
    const ATTACH_SUBMERCHANT_USER                              = 'attach_submerchant_user';
    const CREATE_ADDITIONAL_USER_OR_FETCH_IF_APPLICABLE        = 'create_additional_user_or_fetch_if_applicable';
    const GET_FUX_DETAILS_FOR_PARTNER_SERVICE                  = 'fetch_partner_first_user_experience.service';

    const CREATE_OAUTH_TOKEN                                   = 'create_oauth_token.service';
    const CREATE_OAUTH_TOKEN_CORE                              = 'create_oauth_token.core';
    const CREATE_OAUTH_MIGRATION_TOKEN                         = 'create_oauth_migration_token';
    const ADD_MAPPING_FOR_OAUTH_APP                            = 'add_mapping_for_oauth_app';
    const ASSIGN_S2S_IF_APPLICABLE                             = 'assign_s2s_if_applicable';
    const FETCH_FEATURE                                        = 'fetch_feature';
    const SAVE_AND_SYNC_FEATURE                                = 'save_and_sync_feature';
    const APPROVE_FEATURE_ONBOARDING_REQUEST                   = 'approve_feature_onboarding_request';
    const SKIP_SUBM_ONBOARDING_COMMUNICATION                   = 'skip_subm_onboarding_communication';
    const CREATE_ACCESS_MAP_CORE                               = 'create_access_map.core';
    const GET_OAUTH_TOKENS                                     = 'get_oauth_tokens';
    const REVOKE_OAUTH_TOKEN                                   = 'revoke_oauth_token';

    /* Partner Type Update  */
    const CREATE_PARTNER_ACTIVATION                            = 'create_partner_activation';
    const CREATE_DEFAULT_FEATURE_FOR_PARTNER                   = 'default_feature_for_partner';
    const UPDATE_PARTNER_TYPE_SERVICE                          = 'update_partner_type.service';
    const UPDATE_PARTNER_TYPE_CORE                             = 'update_partner_type.core';
    const PROCESS_MARK_AS_PARTNER                              = 'process_mark_as_partner';
    const MARK_AS_PARTNER                                      = 'mark_as_partner';

    const CREATE_ACCOUNTS                                      = 'create_accounts.service';
    const CREATE_ACCOUNTS_CORE                                 = 'create_accounts.core';
    const LIST_ACCOUNTS                                        = 'list_accounts.service';
    const LIST_ACCOUNTS_CORE                                   = 'list_accounts.core';
    const FETCH_ACCOUNTS                                       = 'fetch_accounts.service';
    const FETCH_ACCOUNTS_CORE                                  = 'fetch_accounts.core';
    const FETCH_ACCOUNTS_BY_EXTERNAL_ID                        = 'fetch_accounts_by_external_id.service';
    const FETCH_ACCOUNTS_BY_EXTERNAL_ID_CORE                   = 'fetch_accounts_by_external_id.core';
    const EDIT_ACCOUNTS                                        = 'edit_accounts.service';
    const EDIT_ACCOUNTS_CORE                                   = 'edit_accounts.core';
    const CREATE_SUBMERCHANT_AND_ASSOCIATED_ENTITIES           = 'create_submerchant_and_associated_entities';
    const UPDATE_ACTIVATION_FLOWS                              = 'update_activation_flows';
    const SUBMIT_DETAILS_AND_ACTIVATE_IF_APPLICABLE            = 'submit_details_and_activate_if_applicable';
    const EDIT_MERCHANT_DETAIL_FIELDS                          = 'edit_merchant_detail_fields';
    const GET_UPDATED_KYC_CLARIFICATION_REASONS                = 'get_updated_kyc_clarification_reasons';
    const AUTO_UPDATE_MERCHANT_CATEGORY_DETAILS_IF_APPLICABLE  = 'auto_update_merchant_category_details_if_applicable';
    const SYNC_MERCHANT_DETAIL_FIELDS_TO_STAKEHOLDER           = 'sync_merchant_detail_fields_to_stakeholder';
    const SAVE_BUSINESS_DETAILS_FOR_MERCHANT                   = 'save_business_details_for_merchant';
    const PERFORM_KYC_VERIFICATION                             = 'perform_kyc_verification';
    const SUBMIT_ACTIVATION_FORM                               = 'submit_activation_form';

    const LIST_SUBMERCHANTS                                    = 'list_submerchants.service';
    const LIST_SUBMERCHANTS_CORE                               = 'list_submerchants.core';
    const FETCH_SUBMERCHANTS_ON_APP_IDS                        = 'fetch_submerchants_on_app_ids';
    const FILTER_SUBMERCHANTS_ON_PRODUCT                       = 'filter_submerchants_on_product';
    const GET_PARTNER_SUBMERCHANT_DATA                         = 'get_partner_submerchant_data';
    const GET_SUBMERCHANT_OWNER_DATA                           = 'get_submerchant_owner_data';
    const GET_REDUCED_SUBMERCHANT_OWNER_DATA                   = 'get_reduced_submerchant_owner_data';
    const GET_BANKING_ACCOUNT_STATUS                           = 'get_banking_account_status';
    const GET_MERCHANT_BANKING_ACCOUNT_STATUS                  = 'get_merchant_banking_account_status';

    const FETCH_REFERRAL                                       = 'fetch_referral.service';
    const FETCH_MERCHANT_REFERRAL_CORE                         = 'fetch_merchant_referral.core';
    const CREATE_REFERRAL                                      = 'create_referral.service';
    const CREATE_OR_FETCH_REFERRAL_CORE                        = 'create_or_fetch_referral.core';
    const CREATE_REFERRAL_CORE                                 = 'create_referral.core';
    const GENERATE_REFERRAL_CODE                               = 'generate_referral_code';

    const CREATE_SUBMERCHANT_BATCH                             = 'create_submerchant_batch.service';
    const PARTNER_SUBMERCHANT_INVITE                           = 'partner_submerchant_invite';

    const GET_MERCHANT_DETAILS_CORE                            = 'get_merchant_details.core';
    const CREATE_MERCHANT_DETAILS_CORE                         = 'create_merchant_details.core';

    const BULK_CAPTURE_BY_PARTNER                              = 'bulk_capture_by_partner.service';
    const COMMISSIONS_CAPTURE                                  = 'commissions_capture.service';
    const COMMISSIONS_CAPTURE_CORE                             = 'commissions_capture.core';
    const CLEAR_ON_HOLD_FOR_PARTNER                            = 'clear_on_hold_for_partner.service';
    const CLEAR_ON_HOLD_FOR_PARTNER_CORE                       = 'clear_on_hold_for_partner.core';
    const VALIDATE_TDS_DEFINED                                 = 'validate_tds_defined';
    const COMMISSION_TDS_SETTLEMENT                            = 'commission_tds_settlement';
    const COMMISSION_FINANCE_TRIGGERED_ONHOLD_CLEAR            = 'commission_finance_triggered_onhold_clear';
    const CLEAR_ON_HOLD_COMMISSION_INVOICE_BULK                = 'clear_on_hold_commission_invoice_bulk.service';
    const CLEAR_ON_HOLD_COMMISSION_INVOICE_BULK_CORE           = 'clear_on_hold_commission_invoice_bulk.core';
    const COMMISSION_INVOICE_CHANGE_STATUS                     = 'commission_invoice_change_status.service';
    const COMMISSION_INVOICE_CHANGE_STATUS_CORE                = 'commission_invoice_change_status.core';
    const COMMISSION_INVOICE_ACTION                            = 'commission_invoice_action';
    const TRIGGER_COMMISSION_INVOICE_ACTION                    = 'trigger_commission_invoice_action';
    const COMMISSION_INVOICE_FETCH                             = 'commission_invoice_fetch.service';

    const GET_PARTNER_COMMISSION_CONFIG                        = 'get_partner_commission_config';

    const STORE_OTP_VERIFICATION_LOG                           = 'store_otp_verification_log';

    // Recurring traces
    const SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_SERVICE                           = 'subscription_registration_charge_token.service';
    const SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_SERVICE_SET_ORDER_BATCH           = 'subscription_registration_charge_token.service.set_order_batch';
    const SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_CORE                              = 'subscription_registration_charge_token.core';
    const SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_CORE_CHECK_IDEMPOTENCY            = 'subscription_registration_charge_token.core.check_idempotency';
    const SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_CORE_FETCH_TOKEN_BY_GATEWAY_TOKEN = 'subscription_registration_charge_token.core.fetch_token_by_gateway_token';
    const SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_CORE_FETCH_TOKEN                  = 'subscription_registration_charge_token.core.fetch_token';
    const SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_CORE_FETCH_CUSTOMER               = 'subscription_registration_charge_token.core.fetch_customer';
    const SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_CORE_CREATE_ORDER                 = 'subscription_registration_charge_token.core.create_order';
    const SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_CORE_PROCESS_PAYMENT              = 'subscription_registration_charge_token.core.process_payment';
    const SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_CORE_PAYMENT_SET_BATCH            = 'subscription_registration_charge_token.core.payment_set_batch';
    const EMANDATE_DEBIT_PAYMENT_PROCESSING_BATCH_REQUEST                          = 'emandate_debit_payment.process_batch_request';
    const EMANDATE_DEBIT_UPDATE_PAYMENT_ENTITIES                                   = 'emandate_debit_payment.update_payment_entities';
    const EMANDATE_DEBIT_UPDATE_PAYMENT                                            = 'emandate_debit_payment.update_payment';
    const EMANDATE_DEBIT_PROCESS_AUTHORIZED_PAYMENT                                = 'emandate_debit_payment.process_authorized_payment';

    const MAILABLE_SEND                  = 'mailable.send';
    const MAILABLE_QUEUE                 = 'mailable.queue';
    const MAILABLE_HANDLE                = 'mailable.handle';
    const MAILABLE_EVALUATE_MAIL_DRIVER  = 'mailable.evaluate_mail_driver';
    const MAILABLE_SEND_VIA_STORK        = 'mailable.send_via_stork';
    const MAILABLE_MAILER_SEND           = 'mailable.mailer_send';
    const MAILABLE_SHOULD_SEND_VIA_STORK = 'mailable.should_send_via_stork';

    const SUBMERCHANT_STORK_INVALIDATE_CACHE_REQUEST                          = 'submerchant.stork.invalidate_cache_request';
    const PAYMENT_VALIDATE_VPA                                                = 'payment.validate_vpa';

    //payouts alerts traces
    const BAS_PROCESSOR_JOB_FAILURES_TOTAL                         = 'bas.bas_processor_job_failures_total';
    const MISSING_STATEMENTS_FOUND                                 = 'bas.missing_statements_found';
    const MISSING_STATEMENT_REDIS_INSERT_FAILURES                  = 'bas.missing_statement_redis_insert_failures';
    const BAS_PROCESSOR_QUEUE_PUSH_FAILURES_TOTAL                  = 'bas.bas_processor_queue_push_failures_total';
    const FAV_QUEUE_FOR_FTS_JOB_FAILED_OR_RETRY_ATTEMPT_EXHAUSTED  = 'fav.fav_queue_for_fts_job_failed_or_attempt_exhausted';
    const LEDGER_JOURNAL_FETCH_TRANSACTION_ERROR_TOTAL             = 'ledger.ledger_journal_fetch_transaction_error_total';
    const HASH_MISMATCH_FOR_INPUT_AND_DUPLICATE_FUND_ACCOUNT_TOTAL = 'fa.hash_mismatch_for_input_dup_fa';
    const PAYOUT_CREATE_SUBMITTED_PROCESS_JOB_ERROR_TOTAL          = 'payout.payout_create_submitted_process_job_error_total';
    const PAYOUT_TO_CARDS_VAULT_TOKEN_DELETION_RETRIES_EXHAUSTED   = 'payout.payout_to_cards_vault_token_deletion_retries_exhausted';
    const SERVER_ERROR_PRICING_RULE_ABSENT_TOTAL                   = 'payout.server_error_pricing_rule_absent_total';
    const BANK_TRANSFER_SAVE_REQUESTS_TOTAL                        = 'bt.bank_transfer_save_requests_total';
    const INVALID_VAULT_TOKEN_ASSOCIATED                           = 'card.invalid_vault_token_associated';
    const VIRTUAL_ACCOUNT_PAYMENT_SUCCESS_TOTAL                    = 'virtual_account_payment_success_total';

    const AUTHENTICATE_PRE_AUTHENTICATE                                                         = 'authenticate.pre_authenticate';
    const AUTHENTICATE_USING_PASSPORT                                                           = 'authenticate.using_passport';
    const AUTHENTICATE_BASIC_AUTH                                                               = 'authenticate.basic_auth';
    const AUTHENTICATE_BEARER_AUTH                                                              = 'authenticate.bearer_auth';
    const AUTHENTICATE_USING_PASSPORT_OAUTH                                                     = 'authenticate.using_passport_oauth';
    const AUTHENTICATE_PRIVATE_ROUTE_PRIVATE_AUTH                                               = 'authenticate.private_route.private_auth';
    const BASIC_AUTH_VERIFY_SECRET                                                              = 'basic_auth.verify_secret';
    const BASIC_AUTH_IS_KEY_EXISTING                                                            = 'basic_auth.is_key_existing';
    const CLIENT_AUTH_CRED_IS_KEY_EXISTING                                                      = 'client_auth_cred.is_key_existing';
    const KEY_AUTH_CRED_IS_KEY_EXISTING                                                         = 'key_auth_cred.is_key_existing';
    const KEY_AUTH_CRED_GET_DECRYPTED_SECRET                                                    = 'key_auth_cred.get_decrypted_secret';
    const KEY_AUTH_CRED_MERCHANT                                                                = 'key_auth_cred.merchant';
    const CLIENT_AUTH_CRED_MERCHANT                                                             = 'client_auth_cred.merchant';
    const KEY_AUTH_CRED_SET_AND_CHECK_MERCHANT_ACTIVATED_FOR_LIVE                               = 'key_auth_cred.set_and_check_merchant_activated_for_live';
    const AUTH_CRED_CAN_NON_KYC_ACTIVATED_MERCHANT_ACCESS_PRIVATE_X_ROUTES                      = 'auth_cred.can_non_kyc_activated_merchant_access_private_X_routes';
    const AUTH_CRED_IS_CURRENT_ACCOUNT_ACTIVATED                                                = 'auth_cred.is_current_account_activated';
    const AUTH_CRED_IS_X_VA_ACTIVATED                                                           = 'auth_cred.is_X_Va_Activated';
    const AUTH_CRED_GET_TREATMENT                                                               = 'auth_cred.get_treatment';
    const BASIC_AUTH_CHECK_AND_SET_KEY_ID                                                       = 'basic_auth.check_and_set_key_id';
    const BASIC_AUTH_VERIFY_KEY_NOT_EXPIRED                                                     = 'basic_auth.verify_key_not_expired';
    const BASIC_AUTH_CHECK_AND_SET_ACCOUNT_SCOPE                                                = 'basic_auth.check_and_set_account_scope';
    const BASIC_AUTH_SET_ADMIN_AUTH_IF_APPLICABLE                                               = 'basic_auth.set_admin_auth_if_applicable';
    const BASIC_AUTH_VERIFY_INTERNAL_APP_AS_PROXY                                               = 'basic_auth.verify_internal_app_as_proxy';
    const BASIC_AUTH_IS_MERCHANT_MANAGED_BY_PARTNER                                             = 'basic_auth.is_merchant_managed_by_partner';
    const BASIC_AUTH_CHECK_AND_SET_PARTNER_MERCHANT_SCOPE                                       = 'basic_auth.check_and_set_partner_merchant_scope';
    const BASIC_AUTH_SET_AND_CHECK_MERCHANT_ACTIVATED_FOR_LIVE                                  = 'basic_auth.set_and_check_merchant_activated_for_live';
    const BASIC_AUTH_VALIDATE_ACCOUNT_FOR_CURRENT_AUTH_TYPE                                     = 'basic_auth.validate_account_for_current_auth_type';
    const MERCHANT_CORE_FETCH_BANKING_ACCOUNT_BY_MERCHANT_ID_ACCOUNT_TYPE_CHANNEL_AND_STATUS    = 'merchant_core.fetch_banking_account_by_merchant_id_account_type_channel_and_status';
    const MERCHANT_CORE_IS_X_VA_ACTIVATED                                                       = 'merchant_core.is_X_Va_Activated';
    const MERCHANT_CORE_GET_BALANCE_BY_MERCHANT_ID_CHANNELS_AND_ACCOUNT_TYPE                    = 'merchant_core.get_balance_by_merchant_id_channels_and_account_type';

    const BATCH_SUBMITTED_PAYOUTS_CRON_REQUEST                                                  = 'batch_submitted_payouts_cron_request';
    const PAYOUT_PUBLIC_ERROR_CODE_UNMAPPED_BANK_STATUS_CODE                                    = 'payout_public_error_code_unmapped_bank_status_code';
    const BANKING_ACCOUNT_STATEMENT_FETCH_JOB_INIT                                              = 'banking_account_statement_fetch_job_init';
    const BANKING_ACCOUNT_STATEMENT_RATE_LIMITED                                                = 'banking_account_statement_rate_limited';
    const FAV_COMPLETED_WITH_STATUS_ACTIVE_AND_BENE_NAME_NULL                                   = 'fav_completed_with_status_active_and_bene_name_null';
    const FAV_REQUEST_WITH_NO_ACCOUNT_NUMBER                                                    = 'fav_request_with_no_account_number';
    const BANKING_ACCOUNT_FETCH_AND_UPDATE_GATEWAY_BALANCE_REQUEST_FAILED                       = 'banking_account_fetch_and_update_gateway_balance_request_failed';
    const REQUEST_LOG_HANDLER_UNEXPECTED_EXCEPTION                                              = 'request_log_handler_unexpected_exception';
    const BAS_ENTRY_FOR_A_FAILED_PAYOUT                                                         = 'bas_entry_for_a_failed_payout';
    const TRANSACTION_FOUND_DURING_PAYOUT_PROCESSED                                             = 'transaction_found_during_payout_processed';

    //DCS
    const DCS_FETCH_FEATURE                                                                     = 'dcs.fetch.feature';
    const DCS_EDIT_FEATURE                                                                      = 'dcs.edit.feature';
    const DCS_FETCH_FEATURES_AGGREGATE                                                          = 'dcs.fetch.feature.aggregate';
}
