<?php

namespace RZP\Models\FileStore;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;

class Type
{
    const KOTAK_NETBANKING_CLAIM            = 'kotak_netbanking_claim';

    const KOTAK_NETBANKING_REFUND           = 'kotak_netbanking_refund';

    const HDFC_NETBANKING_REFUND            = 'hdfc_netbanking_refund';

    const AXIS_NETBANKING_REFUND            = 'axis_netbanking_refund';

    const AXIS_NETBANKING_CLAIMS            = 'axis_netbanking_claims';

    const AIRTELMONEY_WALLET_REFUND         = 'airtelmoney_wallet_refund';

    const PAYUMONEY_WALLET_REFUND           = 'payumoney_wallet_refund';

    const ICICI_UPI_REFUND                  = 'icici_upi_refund';

    const ICICI_NODAL_TRANSFER              = 'icici_nodal_transfer';

    const BATCH_INPUT                       = 'batch_input';

    const BATCH_OUTPUT                      = 'batch_output';

    const BLANK                             = 'blank';

    const INVOICE_PDF                       = 'invoice_pdf';

    const MERCHANT_BUSINESS_PROOF_URL           = 'business_proof_url';
    const MERCHANT_BUSINESS_OPERATION_PROOF_URL = 'business_operation_proof_url';
    const MERCHANT_BUSINESS_PAN_URL             = 'business_pan_url';
    const MERCHANT_ADDRESS_PROOF_URL            = 'address_proof_url';
    const MERCHANT_PROMOTER_PROOF_URL           = 'promoter_proof_url';
    const MERCHANT_PROMOTER_PAN_URL             = 'promoter_pan_url';
    const MERCHANT_PROMOTER_ADDRESS_URL         = 'promoter_address_url';

    const SETTLEMENT_BUCKET_CONFIG              = 'settlement_bucket_config';
    const TEST_BUCKET_CONFIG                    = 'test_bucket_config';
    const BATCH_BUCKET_CONFIG                   = 'batch_bucket_config';
    const INVOICE_BUCKET_CONFIG                 = 'invoice_bucket_config';
    const ACTIVATION_BUCKET_CONFIG              = 'activation_bucket_config';

    /**
     * Map of types allowed for each entity.
     */
    const TYPE_MAP = [

        self::BLANK => [
            self::KOTAK_NETBANKING_CLAIM,
            self::KOTAK_NETBANKING_REFUND,
            self::HDFC_NETBANKING_REFUND,
            self::AXIS_NETBANKING_REFUND,
            self::AXIS_NETBANKING_CLAIMS,
            self::AIRTELMONEY_WALLET_REFUND,
            self::PAYUMONEY_WALLET_REFUND,
            self::ICICI_UPI_REFUND,
            self::ICICI_NODAL_TRANSFER,
        ],

        Constants\Entity::BATCH => [
            self::BATCH_INPUT,
            self::BATCH_OUTPUT,
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
    ];

    /**
     * Types allowed when no entity is associated
     */
    const SHARED_ACCOUNT_ALLOWED_TYPES = [
        self::KOTAK_NETBANKING_CLAIM,
        self::KOTAK_NETBANKING_REFUND,
        self::HDFC_NETBANKING_REFUND,
        self::AXIS_NETBANKING_REFUND,
        self::AXIS_NETBANKING_CLAIMS,
        self::AIRTELMONEY_WALLET_REFUND,
        self::PAYUMONEY_WALLET_REFUND,
        self::ICICI_UPI_REFUND,
        self::ICICI_NODAL_TRANSFER,
    ];

    /**
     * Bucket Config Mapping for file types
     */
    const BUCKET_CONFIG_TYPE_MAPPING = [
        self::SETTLEMENT_BUCKET_CONFIG => [
            self::KOTAK_NETBANKING_REFUND,
            self::HDFC_NETBANKING_REFUND,
            self::AXIS_NETBANKING_REFUND,
            self::AXIS_NETBANKING_CLAIMS,
            self::AIRTELMONEY_WALLET_REFUND,
            self::PAYUMONEY_WALLET_REFUND,
            self::ICICI_UPI_REFUND,
        ],

        self::BATCH_BUCKET_CONFIG => [
            self::BATCH_INPUT,
            self::BATCH_OUTPUT,
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
        if (in_array($type, self::SHARED_ACCOUNT_ALLOWED_TYPES) == true)
        {
            return true;
        }

        throw new Exception\LogicException('Not a valid Type For Shared Merchant Account: ' . $type);
    }
}
