<?php

namespace RZP\Models\FileStore;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Document\Type as MerchantDocumentType;

class Type
{
    const KOTAK_NETBANKING_CLAIM            = 'kotak_netbanking_claim';

    const KOTAK_NETBANKING_REFUND           = 'kotak_netbanking_refund';

    const HDFC_NETBANKING_REFUND            = 'hdfc_netbanking_refund';

    const HDFC_EMANDATE_REFUND              = 'hdfc_emandate_refund';
    const HDFC_EMANDATE_REGISTER            = 'hdfc_emandate_register';
    const HDFC_EMANDATE_DEBIT               = 'hdfc_emandate_debit';

    const RBL_ENACH_DEBIT                   = 'rbl_enach_debit';
    const RBL_ENACH_REGISTER                = 'rbl_enach_register';
    const RBL_ENACH_CANCEL                  = 'rbl_enach_cancel';

    const ENACH_NPCI_NB_DEBIT               = 'enach_npci_nb_debit';
    const ENACH_NPCI_NB_CANCEL              = 'enach_npci_nb_cancel';
    const ENACH_NPCI_NB_DEBIT_ICICI         = 'enach_npci_nb_debit_icici';  // deprecated

    const CORPORATION_NETBANKING_REFUND     = 'corporation_netbanking_refund';

    const ICICI_NACH_REGISTER               = 'icici_nach_register';
    const ICICI_NACH_COMBINED_DEBIT         = 'icici_nach_combined_debit';
    const ICICI_NACH_COMBINED_CANCEL        = 'icici_nach_combined_cancel';

    const CITI_NACH_REGISTER                = 'citi_nach_register';
    const CITI_NACH_DEBIT                   = 'citi_nach_debit';
    const CITI_NACH_DEBIT_SUMMARY           = 'citi_nach_debit_summary';
    const CITI_NACH_EARLY_DEBIT             = 'citi_nach_early_debit';
    const CITI_NACH_EARLY_DEBIT_SUMMARY     = 'citi_nach_early_debit_summary';
    const CITI_NACH_COMBINED_CANCEL         = 'citi_nach_combined_cancel';

    const ALLAHABAD_NETBANKING_REFUND       = 'allahabad_netbanking_refund';
    const CANARA_NETBANKING_REFUND          = 'canara_netbanking_refund';
    const CANARA_NETBANKING_CLAIMS          = 'canara_netbanking_claims';

    const BOB_NETBANKING_REFUND             = 'bob_netbanking_refund';
    const BOB_NETBANKING_CLAIMS             = 'bob_netbanking_claims';

    const IDFC_NETBANKING_REFUND            = 'idfc_netbanking_refund';
    const IDFC_NETBANKING_CLAIMS            = 'idfc_netbanking_claims';
    const IDFC_NETBANKING_SUMMARY           = 'idfc_netbanking_summary';

    const ICICI_NETBANKING_REFUND           = 'icici_netbanking_refund';
    const ICICI_NETBANKING_REFUND_EMI       = 'icici_netbanking_refund_emi';
    const ICICI_PAYLATER_REFUND             = 'icici_paylater_refund';

    const OBC_NETBANKING_REFUND             = 'obc_netbanking_refund';

    const AXIS_NETBANKING_REFUND            = 'axis_netbanking_refund';

    const EQUITAS_NETBANKING_REFUND         = 'equitas_netbanking_refund';

    const ISG_REFUND                        = 'isg_refund';
    const ISG_SUMMARY                       = 'isg_summary';

    const AXIS_NETBANKING_CLAIMS            = 'axis_netbanking_claims';

    const AXIS_EMANDATE_DEBIT               = 'axis_emandate_debit';

    const SBI_EMANDATE_DEBIT                = 'sbi_emandate_debit';

    const FEDERAL_NETBANKING_REFUND         = 'federal_netbanking_refund';

    const CSB_NETBANKING_REFUND             = 'csb_netbanking_refund';

    const RBL_NETBANKING_REFUND             = 'rbl_netbanking_refund';

    const RBL_NETBANKING_CLAIM              = 'rbl_netbanking_claim';

    const RBL_CORP_NETBANKING_REFUND        = 'rbl_corp_netbanking_refund';

    const RBL_CORP_NETBANKING_CLAIM         = 'rbl_corp_netbanking_claim';

    const RBL_MERCHANT_MASTER_FIRS          = 'rbl_merchant_master_firs';

    const SBI_NETBANKING_REFUND             = 'sbi_netbanking_refund';

    const SBI_NETBANKING_CLAIM              = 'sbi_netbanking_claim';

    const CBI_NETBANKING_REFUND             = 'cbi_netbanking_refund';

    const CUB_NETBANKING_REFUND             = 'cub_netbanking_refund';

    const CUB_NETBANKING_CLAIM              = 'cub_netbanking_claim';

    const IDBI_NETBANKING_REFUND            = 'idbi_netbanking_refund';

    const DCB_NETBANKING_REFUND             = 'dcb_netbanking_refund';

    const IBK_NETBANKING_REFUND             = 'ibk_netbanking_refund';
    const IBK_NETBANKING_CLAIM              = 'ibk_netbanking_claim';

    const AUBL_NETBANKING_CLAIM             = 'aubl_netbanking_claim';
    const AUBL_NETBANKING_COMBINED          = 'aubl_netbanking_combined';

    const AUBL_CORP_NETBANKING_CLAIM        = 'aubl_corp_netbanking_claim';

    const AUBL_CORP_NETBANKING_REFUND       = 'aubl_corp_netbanking_refund';

    const AUBL_CORP_NETBANKING_COMBINED     = 'aubl_corp_netbanking_combined';

    const DLB_NETBAKING_REFUND              = 'dlb_netbanking_refund';

    const TMB_NETBANKING_REFUND             = 'tmb_netbanking_refund';

    const KARNATAKA_NETBANKING_REFUND       = 'karnataka_netbanking_refund';

    const INDUSIND_NETBANKING_REFUND        = 'indusind_netbanking_refund';

    const INDUSIND_NETBANKING_CLAIM         = 'indusind_netbanking_claim';

    const SIB_NETBANKING_REFUND             = 'sib_netbanking_refund';

    const VIJAYA_NETBANKING_REFUND          = 'vijaya_netbanking_refund';

    const VIJAYA_NETBANKING_CLAIM           = 'vijaya_netbanking_claim';

    const YESB_NETBANKING_CLAIM             = 'yesb_netbanking_claim';

    const YESB_NETBANKING_REFUND            = 'yesb_netbanking_refund';

    const KVB_NETBANKING_REFUND             = 'kvb_netbanking_refund';

    const KVB_NETBANKING_CLAIM              = 'kvb_netbanking_claim';

    const SVC_NETBANKING_REFUND             = 'svc_netbanking_refund';

    const JKB_NETBANKING_REFUND             = 'jkb_netbanking_refund';

    const SCB_NETBANKING_CLAIM              = 'scb_netbanking_claim';

    const SCB_NETBANKING_REFUND             = 'scb_netbanking_refund';

    const JSB_NETBANKING_REFUND             = 'jsb_netbanking_refund';

    const JSB_NETBANKING_CLAIM              = 'jsb_netbanking_claim';

    const IOB_NETBANKING_REFUND             = 'iob_netbanking_refund';

    const FSB_NETBANKING_REFUND             = 'fsb_netbanking_refund';

    const UBI_NETBANKING_REFUND             = 'ubi_netbanking_refund';

    const KOTAK_CORP_NETBANKING_REFUND      = 'kotak_corp_netbanking_refund';

    const NSDL_NETBANKING_REFUND            = 'nsdl_netbanking_refund';

    const NSDL_NETBANKING_CLAIM             = 'nsdl_netbanking_claim';

    const UJJIVAN_NETBANKING_REFUND         = 'ujjivan_netbanking_refund';

    const UJJIVAN_NETBANKING_CLAIMS         = 'ujjivan_netbanking_claims';

    const AIRTELMONEY_WALLET_REFUND         = 'airtelmoney_wallet_refund';

    const PAYUMONEY_WALLET_REFUND           = 'payumoney_wallet_refund';

    const ICICI_UPI_REFUND                  = 'icici_upi_refund';

    const MINDGATE_UPI_REFUND               = 'mindgate_upi_refund';

    const SBI_UPI_REFUND                    = 'sbi_upi_refund';

    const AIRTEL_UPI_REFUND                 = 'airtel_upi_refund';

    const YESBANK_UPI_REFUND                = 'yesbank_upi_refund';

    const AIRTELMONEY_WALLET_FAILED_REFUND  = 'airtelmoney_wallet_failed_refund';

    const AXIS_MIGS_FAILED_REFUND           = 'axis_migs_failed_refund';

    const ICIC_FIRST_DATA_FAILED_REFUND     = 'icic_first_data_failed_refund';

    const HDFC_CYBERSOURCE_FAILED_REFUND    = 'hdfc_cybersource_failed_refund';

    const AXIS_CYBERSOURCE_FAILED_REFUND    = 'axis_cybersource_failed_refund';

    const HDFC_FSS_FAILED_REFUND            = 'hdfc_fss_failed_refund';

    const PNB_NETBANKING_REFUND             = 'pnb_netbanking_refund';

    const PNB_NETBANKING_CLAIMS             = 'pnb_netbanking_claims';

    const BDBL_NETBANKING_REFUND            = 'bdbl_netbanking_refund';

    const BDBL_NETBANKING_COMBINED            = 'bdbl_netbanking_combined';

    const SARASWAT_NETBANKING_REFUND        = 'saraswat_netbanking_refund';

    const SARASWAT_NETBANKING_CLAIMS        = 'saraswat_netbanking_claims';

    const UCO_NETBANKING_REFUND             = 'uco_netbanking_refund';

    const DBS_NETBANKING_CLAIMS             = 'dbs_netbanking_claims';

    const DBS_NETBANKING_REFUND             = 'dbs_netbanking_refund';

    const DBS_NETBANKING_COMBINED           = 'dbs_netbanking_combined';

    const DBS_NETBANKING_COMBINED_UNENCRYPTED = 'dbs_netbanking_combined_unencrypted';

    const HDFC_CORP_NETBANKING_CLAIMS       = 'hdfc_corp_netbanking_claims';

    const HDFC_CORP_NETBANKING_REFUNDS      = 'hdfc_corp_netbanking_refunds';

    const GATEWAY_FAILED_REFUNDS            = 'gateway_failed_refunds';

    const BULK_DISPUTES_FILE                = 'bulk_disputes_file';
    const BULK_RAW_ADDRESS_FILE             = 'bulk_raw_address_file';

    const BATCH_INPUT                           = 'batch_input';
    const BATCH_OUTPUT                          = 'batch_output';
    const BATCH_VALIDATED                       = 'batch_validated';
    const RECONCILIATION_BATCH_INPUT            = 'reconciliation_batch_input';
    const BATCH_SERVICE                         = 'batch_service';
    const BATCH_SERVICE_PAYMENTS                = 'batch_service_payments';
    const BATCH_SERVICE_PLATFORM                = 'batch_service_platform';
    const BATCH_SERVICE_CAPITAL                 = 'batch_service_capital';
    const BATCH_SERVICE_RAZORPAYX               = 'batch_service_razorpayx';
    const NON_MIGRATED_BATCH                    = 'non_migrated_batch';
    const RECONCILIATION_BATCH_ANALYTICS_OUTPUT = 'reconciliation_batch_analytics_output';
    const RECONCILIATION_BATCH_TXN_FILE         = 'reconciliation_batch_txn_file';

    const BLANK                             = 'blank';

    const INVOICE_PDF                       = 'invoice_pdf';
    const COMMISSION_INVOICE                = 'commission_invoice';
    const MERCHANT_INVOICE                  = 'merchant_invoice';

    const QR_CODE_IMAGE                     = 'qr_code_image';

    const REPORT                            = 'report';

    const FUND_TRANSFER_DEFAULT             = 'fund_transfer_default';
    const FUND_TRANSFER_H2H                 = 'fund_transfer_h2h';

    const BENEFICIARY_FILE                  = 'beneficiary_file';
    const EMI_FILE                          = 'emi_file';
    const AXIS_EMI_FILE                     = 'axis_emi_file';
    const INDUSIND_EMI_FILE                 = 'indusind_emi_file';
    const KOTAK_EMI_FILE                    = 'kotak_emi_file';
    const RBL_EMI_FILE                      = 'rbl_emi_file';
    const SCBL_EMI_FILE                     = 'scbl_emi_file';
    const YES_EMI_FILE_SFTP                 = 'yes_emi_file_sftp';
    const YES_EMI_FILE_MAIL                 = 'yes_emi_file_mail';
    const ICICI_EMI_REFUND_FILE             = 'icici_emi_refund_file';
    const ICICI_EMI_FILE_SFTP               = 'icici_emi_file_sftp';
    const ICICI_EMI_FILE_MAIL               = 'icici_emi_file_mail';
    const SBI_EMI_FILE                      = 'sbi_emi_file';
    const SBI_NC_EMI_FILE                   = 'sbi_nc_emi_file';
    const HSBC_EMI_FILE                     = 'hsbc_emi_file';
    const SBI_EMI_OUTPUT_FILE               = 'sbi_emi_output_file';
    const ONECARD_EMI_FILE                  = 'onecard_emi_file';
    const CITI_EMI_FILE                     = 'citi_emi_file';
    const BOB_EMI_FILE                      = 'bob_emi_file';
    const RECON_AUTOMATIC_FILE_FETCH        = 'recon_automatic_file_fetch';
    const INDUS_IND_DEBIT_EMI_FILE          = 'indusind_debit_emi_file';
    const FEDERAL_EMI_FILE                  = 'federal_emi_file';

    const AXIS_CARD_SETTLEMENT_FILE         = 'axis_cardsettlement_file';
    const AXIS_CARD_SETTLEMENT_OUTPUT_FILE  = 'axis_cardsettlement_output_file';

    const AXIS_PAYSECURE                    = 'axis_paysecure';

    const FIRST_DATA_PARES_FILE             = 'first_data_pares_file';
    const DATA_LAKE_SEGMENT_FILE            = 'data_lake_segment_file';
    const NIUM_SETTLEMENT_FILE              = 'nium_settlement_file';
    const HDFC_COLLECT_NOW_SETTLEMENT_FILE  = 'custom_org_settlement_file';
    const APM_ONBOARD_REQUEST_FILE          = 'apm_onboard_request_file';
    const ICICI_OPGSP_IMPORT_SETTLEMENT_FILE= 'icici_opgsp_import_settlement_file';

    const AP_SOUTH_DEFAULT_SETTLEMENT_BUCKET_CONFIG = 'ap_south_default_settlement_bucket_config';
    const AP_SOUTH_ACTIVATION_BUCKET_CONFIG         = 'ap_south_activation_bucket_config';

    const SETTLEMENT_BUCKET_CONFIG              = 'settlement_bucket_config';
    const TEST_BUCKET_CONFIG                    = 'test_bucket_config';
    const INVOICE_BUCKET_CONFIG                 = 'invoice_bucket_config';
    const INVOICE_BUCKET_AP_SOUTH_MIGRATED      = 'invoice_bucket_ap_south_config';
    const ACTIVATION_BUCKET_CONFIG              = 'activation_bucket_config';
    const H2H_BUCKET_CONFIG                     = 'h2h_bucket_config';
    const RECON_BUCKET_CONFIG                   = 'recon_bucket_config';
    const RECON_ART_BUCKET_CONFIG               = 'recon_art_bucket_config';
    const ANALYTICS_BUCKET_CONFIG               = 'analytics_bucket_config';
    const MOCK_RECONCILIATION_FILE              = 'mock_reconciliation_file';
    const CUSTOMER_BUCKET_CONFIG                = 'customer_bucket_config';
    const H2H_DEFAULT_BUCKET_CONFIG             = 'h2h_default_bucket_config';
    const BEAM_BUCKET_CONFIG                    = 'beam_bucket_config';
    const BATCH_SERVICE_BUCKET_CONFIG           = 'batch_service_bucket_config';
    const BATCH_SERVICE_BUCKET_CONFIG_PAYMENTS  = 'batch_service_bucket_config_payments';
    const BATCH_SERVICE_BUCKET_CONFIG_PLATFORM  = 'batch_service_bucket_config_platform';
    const BATCH_SERVICE_BUCKET_CONFIG_CAPITAL   = 'batch_service_bucket_config_capital';
    const BATCH_SERVICE_BUCKET_CONFIG_RAZORPAYX = 'batch_service_bucket_config_razorpayx';
    const FUND_TRANSFER_SFTP_BUCKET_CONFIG      = 'fund_transfer_sftp_bucket_config';
    const RECON_SFTP_INPUT_BUCKET_CONFIG        = 'recon_sftp_input_bucket';
    const DATA_LAKE_SEGMENTS_BUCKET_CONFIG      = 'data_lake_segments_bucket_config';
    const PAYOUTS_BUCKET_CONFIG                 = 'payouts_bucket_config';
    const NON_MIGRATED_BATCH_BUCKET_CONFIG      = 'non_migrated_batch_bucket_config';
    const CROSS_BORDER_BUCKET_CONFIG            = 'cross_border_bucket_config';

    const COMMISSION_INVOICE_AP_SOUTH_BUCKET_CONFIG    = 'commission_invoice_ap_south_bucket_config';

    const PAYOUT_SAMPLE                         = 'payout_sample';

    const RECON_INPUT                           = 'recon_input';

    const BULK_FRAUD_NOTIFICATION               = 'bulk_fraud_notification';

    const ICICI_NETBANKING_REFUND_DIRECT_SETTLEMENT = 'icici_netbanking_refund_direct_settlement';

    const PAYMENT_LIMIT               = 'payment_limit';

    const SECURITY_ALERT_BUCKET_CONFIG          = 'security_alert_bucket_config';

    const WAF_RULES_FILE                        = 'waf_rules_file';


    // File contants required for merchant feature onboarding
    const FEATURE_ONBOARDING                = FeatureConstants::ONBOARDING;
    const MARKETPLACE_VENDOR_AGREEMENT      = FeatureConstants::MARKETPLACE . '.' . FeatureConstants::VENDOR_AGREEMENT;

    const PAYOUT_ATTACHMENTS = 'payout_attachments';

    /**
     * Map of types allowed for each entity.
     */
    const TYPE_MAP = [
        self::BLANK => [
            self::RBL_MERCHANT_MASTER_FIRS,
            self::HSBC_EMI_FILE,
            self::KOTAK_NETBANKING_CLAIM,
            self::KOTAK_NETBANKING_REFUND,
            self::HDFC_NETBANKING_REFUND,
            self::HDFC_EMANDATE_REFUND,
            self::HDFC_EMANDATE_REGISTER,
            self::HDFC_EMANDATE_DEBIT,
            self::RBL_ENACH_DEBIT,
            self::RBL_ENACH_REGISTER,
            self::ENACH_NPCI_NB_DEBIT,
            self::ENACH_NPCI_NB_CANCEL,
            self::RBL_ENACH_CANCEL,
            self::ENACH_NPCI_NB_DEBIT_ICICI,
            self::ICICI_NACH_COMBINED_DEBIT,
            self::ICICI_NACH_COMBINED_CANCEL,
            self::ICICI_NACH_REGISTER,
            self::CITI_NACH_REGISTER,
            self::CITI_NACH_DEBIT,
            self::CITI_NACH_DEBIT_SUMMARY,
            self::CITI_NACH_EARLY_DEBIT,
            self::CITI_NACH_EARLY_DEBIT_SUMMARY,
            self::CITI_NACH_COMBINED_CANCEL,
            self::SBI_EMANDATE_DEBIT,
            self::ICICI_NETBANKING_REFUND,
            self::ICICI_NETBANKING_REFUND_EMI,
            self::ICICI_PAYLATER_REFUND,
            self::ICICI_NETBANKING_REFUND_DIRECT_SETTLEMENT,
            self::AXIS_NETBANKING_REFUND,
            self::AXIS_EMANDATE_DEBIT,
            self::FEDERAL_NETBANKING_REFUND,
            self::CORPORATION_NETBANKING_REFUND,
            self::ALLAHABAD_NETBANKING_REFUND,
            self::CANARA_NETBANKING_REFUND,
            self::CANARA_NETBANKING_CLAIMS,
            self::BOB_NETBANKING_REFUND,
            self::BOB_NETBANKING_CLAIMS,
            self::RBL_NETBANKING_REFUND,
            self::RBL_CORP_NETBANKING_REFUND,
            self::SBI_NETBANKING_REFUND,
            self::EQUITAS_NETBANKING_REFUND,
            self::CBI_NETBANKING_REFUND,
            self::CUB_NETBANKING_REFUND,
            self::CUB_NETBANKING_CLAIM,
            self::IDBI_NETBANKING_REFUND,
            self::IBK_NETBANKING_REFUND,
            self::IBK_NETBANKING_CLAIM,
            self::INDUSIND_NETBANKING_REFUND,
            self::INDUSIND_NETBANKING_CLAIM,
            self::SIB_NETBANKING_REFUND,
            self::VIJAYA_NETBANKING_REFUND,
            self::VIJAYA_NETBANKING_CLAIM,
            self::YESB_NETBANKING_CLAIM,
            self::YESB_NETBANKING_REFUND,
            self::IDFC_NETBANKING_REFUND,
            self::IDFC_NETBANKING_CLAIMS,
            self::IDFC_NETBANKING_SUMMARY,
            self::AXIS_NETBANKING_CLAIMS,
            self::KVB_NETBANKING_REFUND,
            self::KVB_NETBANKING_CLAIM,
            self::SVC_NETBANKING_REFUND,
            self::JKB_NETBANKING_REFUND,
            self::SCB_NETBANKING_CLAIM,
            self::SCB_NETBANKING_REFUND,
            self::JSB_NETBANKING_REFUND,
            self::JSB_NETBANKING_CLAIM,
            self::IOB_NETBANKING_REFUND,
            self::FSB_NETBANKING_REFUND,
            self::UBI_NETBANKING_REFUND,
            self::NSDL_NETBANKING_REFUND,
            self::NSDL_NETBANKING_CLAIM,
            self::AIRTELMONEY_WALLET_REFUND,
            self::PAYUMONEY_WALLET_REFUND,
            self::RBL_NETBANKING_CLAIM,
            self::RBL_CORP_NETBANKING_CLAIM,
            self::SBI_NETBANKING_CLAIM,
            self::CSB_NETBANKING_REFUND,
            self::DCB_NETBANKING_REFUND,
            self::ICICI_UPI_REFUND,
            self::MINDGATE_UPI_REFUND,
            self::SBI_UPI_REFUND,
            self::AIRTEL_UPI_REFUND,
            self::YESBANK_UPI_REFUND,
            self::REPORT,
            self::BENEFICIARY_FILE,
            self::EMI_FILE,
            self::AXIS_EMI_FILE,
            self::INDUSIND_EMI_FILE,
            self::KOTAK_EMI_FILE,
            self::RBL_EMI_FILE,
            self::SBI_EMI_FILE,
            self::SBI_NC_EMI_FILE,
            self::SBI_EMI_OUTPUT_FILE,
            self::ONECARD_EMI_FILE,
            self::AXIS_PAYSECURE,
            self::CITI_EMI_FILE,
            self::SCBL_EMI_FILE,
            self::BOB_EMI_FILE,
            self::YES_EMI_FILE_MAIL,
            self::YES_EMI_FILE_SFTP,
            self::ICICI_EMI_FILE_MAIL,
            self::ICICI_EMI_FILE_SFTP,
            self::ICICI_EMI_REFUND_FILE,
            self::PNB_NETBANKING_REFUND,
            self::PNB_NETBANKING_CLAIMS,
            self::AIRTELMONEY_WALLET_FAILED_REFUND,
            self::AXIS_MIGS_FAILED_REFUND,
            self::ICIC_FIRST_DATA_FAILED_REFUND,
            self::HDFC_CYBERSOURCE_FAILED_REFUND,
            self::AXIS_CYBERSOURCE_FAILED_REFUND,
            self::HDFC_FSS_FAILED_REFUND,
            self::MOCK_RECONCILIATION_FILE,
            self::GATEWAY_FAILED_REFUNDS,
            self::OBC_NETBANKING_REFUND,
            self::ISG_REFUND,
            self::ISG_SUMMARY,
            self::FIRST_DATA_PARES_FILE,
            self::BULK_DISPUTES_FILE,
            self::BULK_RAW_ADDRESS_FILE,
            self::PAYOUT_SAMPLE,
            self::AUBL_NETBANKING_CLAIM,
            self::AUBL_NETBANKING_COMBINED,
            self::DLB_NETBAKING_REFUND,
            self::TMB_NETBANKING_REFUND,
            self::AUBL_CORP_NETBANKING_CLAIM,
            self::AUBL_CORP_NETBANKING_REFUND,
            self::AUBL_CORP_NETBANKING_COMBINED,
            self::BDBL_NETBANKING_COMBINED,
            self::KOTAK_CORP_NETBANKING_REFUND,
            self::RECON_INPUT,
            self::BDBL_NETBANKING_REFUND,
            self::SARASWAT_NETBANKING_REFUND,
            self::SARASWAT_NETBANKING_CLAIMS,
            self::KARNATAKA_NETBANKING_REFUND,
            self::AXIS_CARD_SETTLEMENT_FILE,
            self::AXIS_CARD_SETTLEMENT_OUTPUT_FILE,
            self::UCO_NETBANKING_REFUND,
            self::DBS_NETBANKING_CLAIMS,
            self::DBS_NETBANKING_REFUND,
            self::DBS_NETBANKING_COMBINED,
            self::DBS_NETBANKING_COMBINED_UNENCRYPTED,
            self::HDFC_CORP_NETBANKING_CLAIMS,
            self::HDFC_CORP_NETBANKING_REFUNDS,
            self::UJJIVAN_NETBANKING_REFUND,
            self::UJJIVAN_NETBANKING_CLAIMS,
            self::HDFC_COLLECT_NOW_SETTLEMENT_FILE,
            self::NIUM_SETTLEMENT_FILE,
            self::APM_ONBOARD_REQUEST_FILE,
            self::RECON_AUTOMATIC_FILE_FETCH,
            self::ICICI_OPGSP_IMPORT_SETTLEMENT_FILE,
            self::INDUS_IND_DEBIT_EMI_FILE,
            self::FEDERAL_EMI_FILE,
        ],

        Constants\Entity::BATCH => [
            self::BATCH_INPUT,
            self::BATCH_OUTPUT,
            self::BATCH_VALIDATED,
            self::RECONCILIATION_BATCH_INPUT,
            self::RECONCILIATION_BATCH_ANALYTICS_OUTPUT,
            self::RECONCILIATION_BATCH_TXN_FILE,
        ],

        Constants\Entity::MERCHANT_DETAIL => MerchantDocumentType::VALID_DOCUMENTS,

        Constants\Entity::INVOICE => [
            self::INVOICE_PDF,
        ],

        Constants\Entity::COMMISSION_INVOICE => [
            self::COMMISSION_INVOICE,
        ],

        Constants\Entity::MERCHANT_INVOICE => [
            self::MERCHANT_INVOICE,
        ],

        Constants\Entity::QR_CODE => [
            self::QR_CODE_IMAGE,
        ],

        Constants\Entity::BATCH_FUND_TRANSFER => [
            self::FUND_TRANSFER_DEFAULT,
            self::FUND_TRANSFER_H2H,
        ],

        Constants\Entity::FEATURE => [
            self::MARKETPLACE_VENDOR_AGREEMENT
        ],

        Constants\Entity::BULK_FRAUD_NOTIFICATION => [
            self::BULK_FRAUD_NOTIFICATION,
        ],

        Constants\Entity::PAYMENT_LIMIT => [
            self::PAYMENT_LIMIT,
        ]
    ];

    /**
     * Types allowed when no entity is associated
     */
    const SHARED_ACCOUNT_ALLOWED_TYPES = [
        self::RBL_MERCHANT_MASTER_FIRS,
        self::RECONCILIATION_BATCH_INPUT,
        self::RECONCILIATION_BATCH_ANALYTICS_OUTPUT,
        self::RECONCILIATION_BATCH_TXN_FILE,
        self::BENEFICIARY_FILE,
        self::EMI_FILE,
        self::AXIS_EMI_FILE,
        self::INDUSIND_EMI_FILE,
        self::HSBC_EMI_FILE,
        self::KOTAK_EMI_FILE,
        self::RBL_EMI_FILE,
        self::SBI_EMI_FILE,
        self::SBI_NC_EMI_FILE,
        self::SBI_EMI_OUTPUT_FILE,
        self::ONECARD_EMI_FILE,
        self::AXIS_PAYSECURE,
        self::BOB_EMI_FILE,
        self::CITI_EMI_FILE,
        self::SCBL_EMI_FILE,
        self::YES_EMI_FILE_MAIL,
        self::YES_EMI_FILE_SFTP,
        self::ICICI_EMI_FILE_MAIL,
        self::ICICI_EMI_FILE_SFTP,
        self::ICICI_EMI_REFUND_FILE,
        self::KOTAK_NETBANKING_CLAIM,
        self::CSB_NETBANKING_REFUND,
        self::KOTAK_NETBANKING_REFUND,
        self::HDFC_NETBANKING_REFUND,
        self::HDFC_EMANDATE_REFUND,
        self::HDFC_EMANDATE_REGISTER,
        self::HDFC_EMANDATE_DEBIT,
        self::ICICI_NACH_REGISTER,
        self::CITI_NACH_DEBIT_SUMMARY,
        self::CITI_NACH_DEBIT,
        self::CITI_NACH_EARLY_DEBIT,
        self::CITI_NACH_EARLY_DEBIT_SUMMARY,
        self::CITI_NACH_REGISTER,
        self::CITI_NACH_COMBINED_CANCEL,
        self::RBL_ENACH_DEBIT,
        self::RBL_ENACH_REGISTER,
        self::ENACH_NPCI_NB_DEBIT,
        self::ENACH_NPCI_NB_CANCEL,
        self::RBL_ENACH_CANCEL,
        self::ENACH_NPCI_NB_DEBIT_ICICI,
        self::ICICI_NACH_COMBINED_DEBIT,
        self::ICICI_NACH_COMBINED_CANCEL,
        self::SBI_EMANDATE_DEBIT,
        self::BOB_NETBANKING_REFUND,
        self::BOB_NETBANKING_CLAIMS,
        self::CANARA_NETBANKING_REFUND,
        self::CANARA_NETBANKING_CLAIMS,
        self::IDFC_NETBANKING_REFUND,
        self::IDFC_NETBANKING_CLAIMS,
        self::IDFC_NETBANKING_SUMMARY,
        self::CORPORATION_NETBANKING_REFUND,
        self::ALLAHABAD_NETBANKING_REFUND,
        self::ICICI_NETBANKING_REFUND,
        self::ICICI_NETBANKING_REFUND_EMI,
        self::ICICI_NETBANKING_REFUND_DIRECT_SETTLEMENT,
        self::ICICI_PAYLATER_REFUND,
        self::AXIS_NETBANKING_REFUND,
        self::AXIS_EMANDATE_DEBIT,
        self::FEDERAL_NETBANKING_REFUND,
        self::RBL_NETBANKING_REFUND,
        self::RBL_CORP_NETBANKING_REFUND,
        self::SBI_NETBANKING_REFUND,
        self::CBI_NETBANKING_REFUND,
        self::KVB_NETBANKING_REFUND,
        self::KVB_NETBANKING_CLAIM,
        self::SVC_NETBANKING_REFUND,
        self::JKB_NETBANKING_REFUND,
        self::SCB_NETBANKING_CLAIM,
        self::SCB_NETBANKING_REFUND,
        self::CUB_NETBANKING_CLAIM,
        self::CUB_NETBANKING_REFUND,
        self::IDBI_NETBANKING_REFUND,
        self::IBK_NETBANKING_REFUND,
        self::IBK_NETBANKING_CLAIM,
        self::JSB_NETBANKING_REFUND,
        self::JSB_NETBANKING_CLAIM,
        self::IOB_NETBANKING_REFUND,
        self::FSB_NETBANKING_REFUND,
        self::UBI_NETBANKING_REFUND,
        self::INDUSIND_NETBANKING_REFUND,
        self::INDUSIND_NETBANKING_CLAIM,
        self::SIB_NETBANKING_REFUND,
        self::VIJAYA_NETBANKING_REFUND,
        self::VIJAYA_NETBANKING_CLAIM,
        self::YESB_NETBANKING_CLAIM,
        self::YESB_NETBANKING_REFUND,
        self::AXIS_NETBANKING_CLAIMS,
        self::RBL_NETBANKING_CLAIM,
        self::RBL_CORP_NETBANKING_CLAIM,
        self::SBI_NETBANKING_CLAIM,
        self::DCB_NETBANKING_REFUND,
        self::NSDL_NETBANKING_REFUND,
        self::NSDL_NETBANKING_CLAIM,
        self::BDBL_NETBANKING_REFUND,
        self::UCO_NETBANKING_REFUND,
        self::AIRTELMONEY_WALLET_REFUND,
        self::PAYUMONEY_WALLET_REFUND,
        self::ICICI_UPI_REFUND,
        self::MINDGATE_UPI_REFUND,
        self::SBI_UPI_REFUND,
        self::AIRTEL_UPI_REFUND,
        self::YESBANK_UPI_REFUND,
        self::FUND_TRANSFER_DEFAULT,
        self::FUND_TRANSFER_H2H,
        self::PNB_NETBANKING_REFUND,
        self::PNB_NETBANKING_CLAIMS,
        self::AIRTELMONEY_WALLET_FAILED_REFUND,
        self::AXIS_MIGS_FAILED_REFUND,
        self::ICIC_FIRST_DATA_FAILED_REFUND,
        self::HDFC_CYBERSOURCE_FAILED_REFUND,
        self::HDFC_FSS_FAILED_REFUND,
        self::AXIS_CYBERSOURCE_FAILED_REFUND,
        self::MOCK_RECONCILIATION_FILE,
        self::GATEWAY_FAILED_REFUNDS,
        self::OBC_NETBANKING_REFUND,
        self::EQUITAS_NETBANKING_REFUND,
        self::ISG_REFUND,
        self::ISG_SUMMARY,
        self::FIRST_DATA_PARES_FILE,
        self::BULK_DISPUTES_FILE,
        self::AUBL_NETBANKING_CLAIM,
        self::AUBL_NETBANKING_COMBINED,
        self::AUBL_CORP_NETBANKING_CLAIM,
        self::AUBL_CORP_NETBANKING_REFUND,
        self::AUBL_CORP_NETBANKING_COMBINED,
        self::BDBL_NETBANKING_COMBINED,
        self::DLB_NETBAKING_REFUND,
        self::UJJIVAN_NETBANKING_REFUND,
        self::UJJIVAN_NETBANKING_CLAIMS,
        self::TMB_NETBANKING_REFUND,
        self::KOTAK_CORP_NETBANKING_REFUND,
        self::DATA_LAKE_SEGMENT_FILE,
        self::RECON_INPUT,
        self::BULK_FRAUD_NOTIFICATION,
        self::SARASWAT_NETBANKING_REFUND,
        self::SARASWAT_NETBANKING_CLAIMS,
        self::KARNATAKA_NETBANKING_REFUND,
        self::AXIS_CARD_SETTLEMENT_OUTPUT_FILE,
        self::AXIS_CARD_SETTLEMENT_FILE,
        self::DBS_NETBANKING_CLAIMS,
        self::DBS_NETBANKING_REFUND,
        self::DBS_NETBANKING_COMBINED,
        self::DBS_NETBANKING_COMBINED_UNENCRYPTED,
        self::HDFC_CORP_NETBANKING_REFUNDS,
        self::HDFC_CORP_NETBANKING_CLAIMS,
        self::HDFC_COLLECT_NOW_SETTLEMENT_FILE,
        self::NIUM_SETTLEMENT_FILE,
        self::APM_ONBOARD_REQUEST_FILE,
        self::PAYMENT_LIMIT,
        self::RECON_AUTOMATIC_FILE_FETCH,
        self::ICICI_OPGSP_IMPORT_SETTLEMENT_FILE,
        self::INDUS_IND_DEBIT_EMI_FILE,
        self::FEDERAL_EMI_FILE,
    ];

    /**
     * Bucket Config Mapping for file types
     */
    const BUCKET_CONFIG_TYPE_MAPPING = [

        self::SETTLEMENT_BUCKET_CONFIG => [
            self::BATCH_INPUT,
            self::BATCH_OUTPUT,
            self::BATCH_VALIDATED,
        ],

        self::AP_SOUTH_DEFAULT_SETTLEMENT_BUCKET_CONFIG => [
            self::REPORT,
        ],

        self::INVOICE_BUCKET_CONFIG => [
            self::INVOICE_PDF,
            self::COMMISSION_INVOICE,
        ],

        self::INVOICE_BUCKET_AP_SOUTH_MIGRATED => [
            self::MERCHANT_INVOICE,
        ],

        self::CUSTOMER_BUCKET_CONFIG => [
            self::QR_CODE_IMAGE,
        ],

        self::AP_SOUTH_ACTIVATION_BUCKET_CONFIG => MerchantDocumentType::VALID_DOCUMENTS,

        self::H2H_BUCKET_CONFIG => [
            self::FIRST_DATA_PARES_FILE,
        ],

        self::RECON_BUCKET_CONFIG => [
            self::RECONCILIATION_BATCH_INPUT,
        ],

        self::ANALYTICS_BUCKET_CONFIG => [
            self::RECONCILIATION_BATCH_ANALYTICS_OUTPUT,
            self::RECONCILIATION_BATCH_TXN_FILE,
        ],

        self::H2H_DEFAULT_BUCKET_CONFIG => [
            self::FUND_TRANSFER_DEFAULT,
        ],

        self::BEAM_BUCKET_CONFIG => [
            self::SBI_EMI_FILE,
            self::ONECARD_EMI_FILE,
            self::AXIS_PAYSECURE,
            self::SCBL_EMI_FILE,
            self::ICICI_EMI_FILE_SFTP,
            self::ICICI_EMI_REFUND_FILE,
            self::KOTAK_EMI_FILE,
            self::YES_EMI_FILE_SFTP,
            self::RBL_EMI_FILE,
            self::INDUSIND_EMI_FILE,
            self::AXIS_EMI_FILE,
            self::BOB_EMI_FILE,
            self::CITI_EMI_FILE,
            self::YES_EMI_FILE_MAIL,
            self::ICICI_EMI_FILE_MAIL,
            self::SBI_EMI_OUTPUT_FILE,
            self::EMI_FILE,
            self::AXIS_CARD_SETTLEMENT_FILE,
            self::HSBC_EMI_FILE,
            self::INDUS_IND_DEBIT_EMI_FILE,
            self::FEDERAL_EMI_FILE,
        ],

        self::BATCH_SERVICE_BUCKET_CONFIG => [
            self::BATCH_SERVICE,
        ],

        self::BATCH_SERVICE_BUCKET_CONFIG_PAYMENTS => [
            self::BATCH_SERVICE_PAYMENTS
        ],

        self::BATCH_SERVICE_BUCKET_CONFIG_PLATFORM => [
            self::BATCH_SERVICE_PLATFORM
        ],

        self::BATCH_SERVICE_BUCKET_CONFIG_CAPITAL => [
            self::BATCH_SERVICE_CAPITAL
        ],

        self::BATCH_SERVICE_BUCKET_CONFIG_RAZORPAYX => [
            self::BATCH_SERVICE_RAZORPAYX
        ],

        self::NON_MIGRATED_BATCH_BUCKET_CONFIG => [
            self::NON_MIGRATED_BATCH,
        ],

        self::FUND_TRANSFER_SFTP_BUCKET_CONFIG => [
            self::FUND_TRANSFER_H2H,
            self::BENEFICIARY_FILE,
        ],

        self::RECON_ART_BUCKET_CONFIG => [
            self::RECON_AUTOMATIC_FILE_FETCH
        ],

        self::RECON_SFTP_INPUT_BUCKET_CONFIG => [
            self::FSB_NETBANKING_REFUND,
            self::ICICI_NACH_REGISTER,
            self::ICICI_NACH_COMBINED_DEBIT,
            self::ICICI_NACH_COMBINED_CANCEL,
            self::CITI_NACH_REGISTER,
            self::CITI_NACH_DEBIT,
            self::CITI_NACH_DEBIT_SUMMARY,
            self::CITI_NACH_EARLY_DEBIT,
            self::CITI_NACH_EARLY_DEBIT_SUMMARY,
            self::CITI_NACH_COMBINED_CANCEL,
            self::RBL_ENACH_REGISTER,
            self::RBL_ENACH_DEBIT,
            self::UBI_NETBANKING_REFUND,
            self::JKB_NETBANKING_REFUND,
            self::AIRTELMONEY_WALLET_FAILED_REFUND,
            self::AXIS_MIGS_FAILED_REFUND,
            self::ICIC_FIRST_DATA_FAILED_REFUND,
            self::HDFC_CYBERSOURCE_FAILED_REFUND,
            self::AXIS_CYBERSOURCE_FAILED_REFUND,
            self::HDFC_FSS_FAILED_REFUND,
            self::PNB_NETBANKING_REFUND,
            self::PNB_NETBANKING_CLAIMS,
            self::GATEWAY_FAILED_REFUNDS,
            self::ICICI_NETBANKING_REFUND_DIRECT_SETTLEMENT,
            self::ICICI_NETBANKING_REFUND,
            self::ICICI_NETBANKING_REFUND_EMI,
            self::HDFC_EMANDATE_REFUND,
            self::HDFC_EMANDATE_REGISTER,
            self::HDFC_EMANDATE_DEBIT,
            self::ICICI_PAYLATER_REFUND,
            self::OBC_NETBANKING_REFUND,
            self::ISG_REFUND,
            self::ISG_SUMMARY,
            self::CSB_NETBANKING_REFUND,
            self::SBI_NETBANKING_REFUND,
            self::SBI_EMANDATE_DEBIT,
            self::SBI_NETBANKING_CLAIM,
            self::CBI_NETBANKING_REFUND,
            self::CUB_NETBANKING_REFUND,
            self::CUB_NETBANKING_CLAIM,
            self::IDBI_NETBANKING_REFUND,
            self::DCB_NETBANKING_REFUND,
            self::IBK_NETBANKING_REFUND,
            self::IBK_NETBANKING_CLAIM,
            self::AUBL_NETBANKING_CLAIM,
            self::AUBL_NETBANKING_COMBINED,
            self::AUBL_CORP_NETBANKING_REFUND,
            self::AUBL_CORP_NETBANKING_CLAIM,
            self::AUBL_CORP_NETBANKING_COMBINED,
            self::BDBL_NETBANKING_COMBINED,
            self::SIB_NETBANKING_REFUND,
            self::VIJAYA_NETBANKING_REFUND,
            self::YESB_NETBANKING_REFUND,
            self::YESB_NETBANKING_CLAIM,
            self::KVB_NETBANKING_REFUND,
            self::SVC_NETBANKING_REFUND,
            self::SCB_NETBANKING_REFUND,
            self::JSB_NETBANKING_REFUND,
            self::MINDGATE_UPI_REFUND,
            self::DLB_NETBAKING_REFUND,
            self::TMB_NETBANKING_REFUND,
            self::NSDL_NETBANKING_REFUND,
            self::NSDL_NETBANKING_CLAIM,
            self::KOTAK_NETBANKING_REFUND,
            self::HDFC_NETBANKING_REFUND,
            self::CORPORATION_NETBANKING_REFUND,
            self::ALLAHABAD_NETBANKING_REFUND,
            self::CANARA_NETBANKING_REFUND,
            self::BOB_NETBANKING_REFUND,
            self::IDFC_NETBANKING_REFUND,
            self::KOTAK_NETBANKING_CLAIM,
            self::KOTAK_CORP_NETBANKING_REFUND,
            self::CANARA_NETBANKING_CLAIMS,
            self::BOB_NETBANKING_CLAIMS,
            self::IDFC_NETBANKING_CLAIMS,
            self::IDFC_NETBANKING_SUMMARY,
            self::AXIS_NETBANKING_REFUND,
            self::EQUITAS_NETBANKING_REFUND,
            self::FEDERAL_NETBANKING_REFUND,
            self::RBL_NETBANKING_REFUND,
            self::RBL_CORP_NETBANKING_REFUND,
            self::INDUSIND_NETBANKING_REFUND,
            self::AIRTELMONEY_WALLET_REFUND,
            self::PAYUMONEY_WALLET_REFUND,
            self::ICICI_UPI_REFUND,
            self::AXIS_NETBANKING_CLAIMS,
            self::RBL_NETBANKING_CLAIM,
            self::RBL_CORP_NETBANKING_CLAIM,
            self::INDUSIND_NETBANKING_CLAIM,
            self::AXIS_EMANDATE_DEBIT,
            self::ENACH_NPCI_NB_DEBIT_ICICI,
            self::ENACH_NPCI_NB_DEBIT,
            self::ENACH_NPCI_NB_CANCEL,
            self::RBL_ENACH_CANCEL,
            self::BDBL_NETBANKING_REFUND,
            self::SARASWAT_NETBANKING_REFUND,
            self::KARNATAKA_NETBANKING_REFUND,
            self::SBI_UPI_REFUND,
            self::AIRTEL_UPI_REFUND,
            self::YESBANK_UPI_REFUND,
            self::UCO_NETBANKING_REFUND,
            self::DBS_NETBANKING_COMBINED,
            self::DBS_NETBANKING_COMBINED_UNENCRYPTED,
            self::HDFC_CORP_NETBANKING_REFUNDS,
            self::HDFC_CORP_NETBANKING_CLAIMS,
            self::UJJIVAN_NETBANKING_REFUND,
            self::IOB_NETBANKING_REFUND,
            self::JSB_NETBANKING_CLAIM,
            self::KVB_NETBANKING_CLAIM,
        ],

        self::DATA_LAKE_SEGMENTS_BUCKET_CONFIG => [
            self::DATA_LAKE_SEGMENT_FILE,
            self::RBL_MERCHANT_MASTER_FIRS,
        ],

        self::PAYOUTS_BUCKET_CONFIG => [
            self::PAYOUT_SAMPLE,
        ],

        self::CROSS_BORDER_BUCKET_CONFIG => [
            self::APM_ONBOARD_REQUEST_FILE,
        ],

        self::SECURITY_ALERT_BUCKET_CONFIG => [
            self::WAF_RULES_FILE,
        ]
    ];

    /**
     * Check if Filestore Type is valid
     *
     * @param string $type Filestore type value
     *
     * @return boolean
     * @throws Exception\LogicException
     */
    public static function validateType(string $type)
    {
        foreach (self::TYPE_MAP as $entity => $typeArray)
        {
            if (in_array($type, $typeArray) === true)
            {
                return true;
            }
        }

        throw new Exception\LogicException('Not a valid Type: '. $type);
    }

    /**
     * Check if Filestore Type is valid for shared account
     *
     * @param string $type Filestore type value
     *
     * @return boolean
     * @throws Exception\LogicException
     */
    public static function isTypeForSharedAccount(string $type)
    {
        if (in_array($type, self::SHARED_ACCOUNT_ALLOWED_TYPES, true) === true)
        {
            return true;
        }

        throw new Exception\LogicException('Not a valid Type For Shared Merchant Account: ' . $type);
    }
}
