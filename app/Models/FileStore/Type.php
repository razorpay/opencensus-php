<?php

namespace RZP\Models\FileStore;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Feature\Constants as FeatureConstants;

class Type
{
    const KOTAK_NETBANKING_CLAIM            = 'kotak_netbanking_claim';

    const KOTAK_NETBANKING_REFUND           = 'kotak_netbanking_refund';

    const HDFC_NETBANKING_REFUND            = 'hdfc_netbanking_refund';
    const HDFC_EMANDATE_REGISTER            = 'hdfc_emandate_register';
    const HDFC_EMANDATE_DEBIT               = 'hdfc_emandate_debit';

    const CORPORATION_NETBANKING_REFUND     = 'corporation_netbanking_refund';

    const ICICI_NETBANKING_REFUND           = 'icici_netbanking_refund';

    const AXIS_NETBANKING_REFUND            = 'axis_netbanking_refund';

    const AXIS_NETBANKING_CLAIMS            = 'axis_netbanking_claims';

    const AXIS_EMANDATE_DEBIT               = 'axis_emandate_debit';

    const FEDERAL_NETBANKING_REFUND         = 'federal_netbanking_refund';

    const RBL_NETBANKING_REFUND             = 'rbl_netbanking_refund';

    const RBL_NETBANKING_CLAIM              = 'rbl_netbanking_claim';

    const INDUSIND_NETBANKING_REFUND        = 'indusind_netbanking_refund';

    const INDUSIND_NETBANKING_CLAIM         = 'indusind_netbanking_claim';

    const AIRTELMONEY_WALLET_REFUND         = 'airtelmoney_wallet_refund';

    const PAYUMONEY_WALLET_REFUND           = 'payumoney_wallet_refund';

    const ICICI_UPI_REFUND                  = 'icici_upi_refund';

    const SBI_UPI_REFUND                    = 'sbi_upi_refund';

    const PNB_NETBANKING_REFUND             = 'pnb_netbanking_refund';

    const PNB_NETBANKING_CLAIMS             = 'pnb_netbanking_claims';

    const BATCH_INPUT                       = 'batch_input';
    const BATCH_OUTPUT                      = 'batch_output';
    const RECONCILIATION_BATCH_INPUT        = 'reconciliation_batch_input';

    const BLANK                             = 'blank';

    const INVOICE_PDF                       = 'invoice_pdf';

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
    const ICICI_EMI_FILE_SFTP               = 'icici_emi_file_sftp';
    const ICICI_EMI_FILE_MAIL               = 'icici_emi_file_mail';

    const MERCHANT_BUSINESS_PROOF_URL           = 'business_proof_url';
    const MERCHANT_BUSINESS_OPERATION_PROOF_URL = 'business_operation_proof_url';
    const MERCHANT_BUSINESS_PAN_URL             = 'business_pan_url';
    const MERCHANT_ADDRESS_PROOF_URL            = 'address_proof_url';
    const MERCHANT_PROMOTER_PROOF_URL           = 'promoter_proof_url';
    const MERCHANT_PROMOTER_PAN_URL             = 'promoter_pan_url';
    const MERCHANT_PROMOTER_ADDRESS_URL         = 'promoter_address_url';

    const SETTLEMENT_BUCKET_CONFIG              = 'settlement_bucket_config';
    const TEST_BUCKET_CONFIG                    = 'test_bucket_config';
    const INVOICE_BUCKET_CONFIG                 = 'invoice_bucket_config';
    const ACTIVATION_BUCKET_CONFIG              = 'activation_bucket_config';
    const H2H_BUCKET_CONFIG                     = 'h2h_bucket_config';
    const RECON_BUCKET_CONFIG                   = 'recon_bucket_config';

    // File contants required for merchant feature onboarding
    const FEATURE_ONBOARDING                = FeatureConstants::ONBOARDING;
    const MARKETPLACE_VENDOR_AGREEMENT      = FeatureConstants::MARKETPLACE . "." . FeatureConstants::VENDOR_AGREEMENT;

    /**
     * Map of types allowed for each entity.
     */
    const TYPE_MAP = [

        self::BLANK => [
            self::KOTAK_NETBANKING_CLAIM,
            self::KOTAK_NETBANKING_REFUND,
            self::HDFC_NETBANKING_REFUND,
            self::HDFC_EMANDATE_REGISTER,
            self::HDFC_EMANDATE_DEBIT,
            self::ICICI_NETBANKING_REFUND,
            self::AXIS_NETBANKING_REFUND,
            self::AXIS_EMANDATE_DEBIT,
            self::FEDERAL_NETBANKING_REFUND,
            self::CORPORATION_NETBANKING_REFUND,
            self::RBL_NETBANKING_REFUND,
            self::INDUSIND_NETBANKING_REFUND,
            self::INDUSIND_NETBANKING_CLAIM,
            self::AXIS_NETBANKING_CLAIMS,
            self::AIRTELMONEY_WALLET_REFUND,
            self::PAYUMONEY_WALLET_REFUND,
            self::RBL_NETBANKING_CLAIM,
            self::ICICI_UPI_REFUND,
            self::SBI_UPI_REFUND,
            self::REPORT,
            self::BENEFICIARY_FILE,
            self::EMI_FILE,
            self::AXIS_EMI_FILE,
            self::INDUSIND_EMI_FILE,
            self::KOTAK_EMI_FILE,
            self::RBL_EMI_FILE,
            self::SCBL_EMI_FILE,
            self::YES_EMI_FILE_MAIL,
            self::YES_EMI_FILE_SFTP,
            self::ICICI_EMI_FILE_MAIL,
            self::ICICI_EMI_FILE_SFTP,
            self::PNB_NETBANKING_REFUND,
            self::PNB_NETBANKING_CLAIMS,
        ],

        Constants\Entity::BATCH => [
            self::BATCH_INPUT,
            self::BATCH_OUTPUT,
            self::RECONCILIATION_BATCH_INPUT,
        ],

        Constants\Entity::MERCHANT_DETAIL => [
            self::MERCHANT_BUSINESS_PROOF_URL,
            self::MERCHANT_BUSINESS_OPERATION_PROOF_URL,
            self::MERCHANT_BUSINESS_PAN_URL,
            self::MERCHANT_ADDRESS_PROOF_URL,
            self::MERCHANT_PROMOTER_PROOF_URL,
            self::MERCHANT_PROMOTER_PAN_URL,
            self::MERCHANT_PROMOTER_ADDRESS_URL,
        ],

        Constants\Entity::INVOICE => [
            self::INVOICE_PDF,
        ],

        Constants\Entity::BATCH_FUND_TRANSFER => [
            self::FUND_TRANSFER_DEFAULT,
            self::FUND_TRANSFER_H2H,
        ],

        Constants\Entity::FEATURE => [
            self::MARKETPLACE_VENDOR_AGREEMENT
        ],
    ];

    /**
     * Types allowed when no entity is associated
     */
    const SHARED_ACCOUNT_ALLOWED_TYPES = [
        self::RECONCILIATION_BATCH_INPUT,
        self::BENEFICIARY_FILE,
        self::EMI_FILE,
        self::AXIS_EMI_FILE,
        self::INDUSIND_EMI_FILE,
        self::KOTAK_EMI_FILE,
        self::RBL_EMI_FILE,
        self::SCBL_EMI_FILE,
        self::YES_EMI_FILE_MAIL,
        self::YES_EMI_FILE_SFTP,
        self::ICICI_EMI_FILE_MAIL,
        self::ICICI_EMI_FILE_SFTP,
        self::KOTAK_NETBANKING_CLAIM,
        self::KOTAK_NETBANKING_REFUND,
        self::HDFC_NETBANKING_REFUND,
        self::HDFC_EMANDATE_REGISTER,
        self::HDFC_EMANDATE_DEBIT,
        self::CORPORATION_NETBANKING_REFUND,
        self::ICICI_NETBANKING_REFUND,
        self::AXIS_NETBANKING_REFUND,
        self::AXIS_EMANDATE_DEBIT,
        self::FEDERAL_NETBANKING_REFUND,
        self::RBL_NETBANKING_REFUND,
        self::INDUSIND_NETBANKING_REFUND,
        self::INDUSIND_NETBANKING_CLAIM,
        self::AXIS_NETBANKING_CLAIMS,
        self::RBL_NETBANKING_CLAIM,
        self::AIRTELMONEY_WALLET_REFUND,
        self::PAYUMONEY_WALLET_REFUND,
        self::ICICI_UPI_REFUND,
        self::SBI_UPI_REFUND,
        self::FUND_TRANSFER_DEFAULT,
        self::FUND_TRANSFER_H2H,
        self::PNB_NETBANKING_REFUND,
        self::PNB_NETBANKING_CLAIMS,
    ];

    /**
     * Bucket Config Mapping for file types
     */
    const BUCKET_CONFIG_TYPE_MAPPING = [
        self::SETTLEMENT_BUCKET_CONFIG => [
            self::KOTAK_NETBANKING_REFUND,
            self::HDFC_NETBANKING_REFUND,
            self::HDFC_EMANDATE_REGISTER,
            self::HDFC_EMANDATE_DEBIT,
            self::AXIS_NETBANKING_REFUND,
            self::AXIS_NETBANKING_CLAIMS,
            self::AIRTELMONEY_WALLET_REFUND,
            self::PAYUMONEY_WALLET_REFUND,
            self::ICICI_UPI_REFUND,
            self::SBI_UPI_REFUND,
            self::FUND_TRANSFER_DEFAULT,
            self::REPORT,
            self::BENEFICIARY_FILE,
            self::EMI_FILE,
            self::AXIS_EMI_FILE,
            self::INDUSIND_EMI_FILE,
            self::KOTAK_EMI_FILE,
            self::RBL_EMI_FILE,
            self::SCBL_EMI_FILE,
            self::YES_EMI_FILE_MAIL,
            self::ICICI_EMI_FILE_MAIL,
            self::BATCH_INPUT,
            self::BATCH_OUTPUT,
            self::PNB_NETBANKING_REFUND,
            self::PNB_NETBANKING_CLAIMS,
        ],

        self::INVOICE_BUCKET_CONFIG => [
            self::INVOICE_PDF
        ],

        self::ACTIVATION_BUCKET_CONFIG => [
            self::MERCHANT_BUSINESS_PROOF_URL,
            self::MERCHANT_BUSINESS_OPERATION_PROOF_URL,
            self::MERCHANT_BUSINESS_PAN_URL,
            self::MERCHANT_ADDRESS_PROOF_URL,
            self::MERCHANT_PROMOTER_PROOF_URL,
            self::MERCHANT_PROMOTER_PAN_URL,
            self::MERCHANT_PROMOTER_ADDRESS_URL,
        ],

        self::H2H_BUCKET_CONFIG => [
            self::FUND_TRANSFER_H2H,
            self::ICICI_EMI_FILE_SFTP,
            self::YES_EMI_FILE_SFTP,
        ],
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
