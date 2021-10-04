<?php

namespace RZP\Models\Batch;

use RZP\Exception;
use RZP\Models\Payment\Gateway;
use RZP\Models\Admin\Permission\Name;
use RZP\Models\Payment\Processor\CardlessEmi;

class Type
{
    const REFUND                    = 'refund';
    const PAYMENT_LINK              = 'payment_link';

    // Merchant Onboarding
    const MERCHANT_ONBOARDING       = 'merchant_onboarding';

    // IRCTC Batch Types
    const IRCTC_REFUND              = 'irctc_refund';
    const IRCTC_DELTA_REFUND        = 'irctc_delta_refund';
    const IRCTC_SETTLEMENT          = 'irctc_settlement';

    // Marketplace Batch
    const LINKED_ACCOUNT            = 'linked_account';

    const LINKED_ACCOUNT_CREATE     = 'linked_account_create';

    // Virtual Account Bulk Creation
    const VIRTUAL_BANK_ACCOUNT      = 'virtual_bank_account';

    // Bank Transfer Bulk Insert
    const BANK_TRANSFER             = 'bank_transfer';

    const RECURRING_CHARGE          = 'recurring_charge';

    // Custom Batch recurring payments for AXIS
    const RECURRING_CHARGE_AXIS     = 'recurring_charge_axis';

    // BSE custom batch
    const RECURRING_CHARGE_BSE      = 'recurring_charge_bse';

    const RECONCILIATION            = 'reconciliation';

    const EMANDATE                  = 'emandate';

    const NACH                      = 'nach';

    const NACH_MIGRATION            = 'nach_migration';

    const PAYOUT                    = 'payout';

    const SUB_MERCHANT              = 'sub_merchant';

    const DIRECT_DEBIT              = 'direct_debit';

    const ENTITY_MAPPING            = 'entity_mapping';

    const AUTH_LINK                 = 'auth_link';

    const INSTANT_ACTIVATION        = 'instant_activation';

    const SUBMERCHANT_ASSIGN        = 'submerchant_assign';

    const PRICING_RULE              = 'pricing_rule';

    const BUY_PRICING_RULE          = 'buy_pricing_rule';

    const BUY_PRICING_ASSIGN        = 'buy_pricing_assign';

    const LOC_WITHDRAWAL            = 'loc_withdrawal';

    const MERCHANT_CONFIG_INHERITANCE = 'merchant_config_inheritance';

    const PAYMENT_LINK_V2           = 'payment_link_v2';

    // Batch Terminal Creation
    const TERMINAL                  = 'terminal';

    const LINKED_ACCOUNT_REVERSAL   = 'linked_account_reversal';

    const TERMINAL_CREATION         = 'terminal_creation';

    /**
     * This is for one time migration of OAuth merchants to Pure-Platform
     * type partners. This bypasses oauth authentication by end merchant.
     */
    const OAUTH_MIGRATION_TOKEN = 'oauth_migration_token';

    /**
     * This type is used to create short urls in bulk async using elfin (hence gimli) service via api
     */
    const ELFIN                 = 'elfin';

    const PARTNER_SUBMERCHANTS  = 'partner_submerchants';

    const PARTNER_SUBMERCHANT_INVITE = 'partner_submerchant_invite';

    const CONTACT               = 'contact';

    const FUND_ACCOUNT          = 'fund_account';

    // iin batches
    const IIN_NPCI_RUPAY        = 'iin_npci_rupay';

    const IIN_HITACHI_VISA      = 'iin_hitachi_visa';

    const IIN_MC_MASTERCARD     = 'iin_mc_mastercard';

    const MPAN                  = 'mpan';

    const CAPTURE_SETTING       = 'capture_setting';

    const ADMIN_BATCH           = 'admin_batch';

    const ENTITY_UPDATE_ACTION  = 'entity_update_action';

    const MERCHANT_ACTIVATION   = 'merchant_activation';

    const SUBMERCHANT_LINK      = 'submerchant_link';

    const SUBMERCHANT_DELINK    = 'submerchant_delink';

    const SUBMERCHANT_PARTNER_CONFIG_UPSERT = 'submerchant_partner_config_upsert';

    const SUBMERCHANT_TYPE_UPDATE      = 'submerchant_type_update';

    const MERCHANT_STATUS_ACTION  = 'merchant_status_action';

    const ADJUSTMENT            = 'adjustment';

    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG = 'settlement_ondemand_feature_config';

    const CAPITAL_MERCHANT_ELIGIBILITY_CONFIG = 'capital_merchant_eligibility_config';

    const ECOLLECT_ICICI        = 'ecollect_icici';

    const ECOLLECT_RBL          = 'ecollect_rbl';

    const REPORT                = 'report';

    const BANK_TRANSFER_EDIT    = 'bank_transfer_edit';

    const CREDIT              = 'credit';

    const BANKING_ACCOUNT_ACTIVATION_COMMENTS     = 'banking_account_activation_comments';

    const ICICI_LEAD_ACCOUNT_ACTIVATION_COMMENTS  = 'icici_lead_account_activation_comments';

    //cbk => chargeback
    const HITACHI_CBK_MASTERCARD = 'hitachi_cbk_mastercard';

    const HITACHI_CBK_VISA = 'hitachi_cbk_visa';

    const HITACHI_CBK_RUPAY = 'hitachi_cbk_rupay';

    const INTERNAL_INSTRUMENT_REQUEST = 'internal_instrument_request';

    const PAYOUT_APPROVAL             = 'payout_approval';

    const TALLY_PAYOUT                = 'tally_payout';

    const PAYOUT_LINK_BULK            = 'payout_link_bulk';

    const PAYOUT_LINK_BULK_V2         = 'payout_link_bulk_v2';

    const UPI_TERMINAL_ONBOARDING     = 'upi_terminal_onboarding';

    const EMANDATE_DEBIT_HDFC         = 'emandate_debit_hdfc';

    //
    // Support admin action for bulk retrying refunds via FTA to custom sources
    //
    const RETRY_REFUNDS_TO_BA = 'retry_refunds_to_ba';

    const CANCEL_DEBIT = 'cancel_debit';

    const PAYMENT_TRANSFER  = 'payment_transfer';

    const TRANSFER_REVERSAL = 'transfer_reversal';

    const WEBSITE_CHECKER = 'website_checker';

    public static $disabledTypes = [
        //
        // Removing till auth for this is figured out. Other parts of the code aren't
        // removed, since this may be necessary for the YesBank integration as well.
        //
        //self::BANK_TRANSFER,
        // Not exposed for direct use via api/dashbaord. Its processor is internally used by other batch types.
        self::CONTACT,
    ];

    public static $appTypes = [
        self::INSTANT_ACTIVATION,
        self::RECONCILIATION,
        self::EMANDATE,
        self::NACH,
        self::BANK_TRANSFER,
        self::ENTITY_MAPPING,
        self::TERMINAL,
        self::TERMINAL_CREATION,
        self::MERCHANT_ONBOARDING,
        self::SUB_MERCHANT,
        self::SUBMERCHANT_ASSIGN,
        self::IIN_NPCI_RUPAY,
        self::IIN_HITACHI_VISA,
        self::IIN_MC_MASTERCARD,
        self::MPAN,
        self::CAPTURE_SETTING,
        self::PRICING_RULE,
        self::BUY_PRICING_RULE,
        self::BUY_PRICING_ASSIGN,
        self::LOC_WITHDRAWAL,
        self::ADMIN_BATCH,
        self::MERCHANT_CONFIG_INHERITANCE,
        self::ENTITY_UPDATE_ACTION,
        self::ADJUSTMENT,
        self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG,
        self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG,
        self::ECOLLECT_ICICI,
        self::ECOLLECT_RBL,
        self::BANK_TRANSFER_EDIT,
        self::CREDIT,
        self::MERCHANT_STATUS_ACTION,
        self::BANKING_ACCOUNT_ACTIVATION_COMMENTS,
        self::ICICI_LEAD_ACCOUNT_ACTIVATION_COMMENTS,
        self::NACH_MIGRATION,
        self::MERCHANT_ACTIVATION,
        self::INTERNAL_INSTRUMENT_REQUEST,
        self::SUBMERCHANT_LINK,
        self::SUBMERCHANT_DELINK,
        self::SUBMERCHANT_PARTNER_CONFIG_UPSERT,
        self::SUBMERCHANT_TYPE_UPDATE,
        self::RETRY_REFUNDS_TO_BA,
        self::UPI_TERMINAL_ONBOARDING,
        self::WEBSITE_CHECKER,
        self::HITACHI_CBK_MASTERCARD,
        self::HITACHI_CBK_VISA,
        self::HITACHI_CBK_RUPAY,
    ];

    /**
     * Below batch types can not be retried in case of failures.
     * New batch to be created in case of reprocessing of same file.
     *
     * @var array
     */
    public static $retryDisabledTypes = [
        self::IRCTC_SETTLEMENT,
        self::IRCTC_REFUND,
        self::IRCTC_DELTA_REFUND,
    ];

    /**
     * For following batch types, sometimes batches get stuck during
     * processing due to big file size or infra issue. So we are enabling
     * 'Retry Batch' option for these batches even when they are in created
     * state and having processing = true.
     *
     * Here the value against each type indicates the time gap from updated_at
     * in seconds, after which only we will allow such retries.
     *
     * @var array
     */
    public static $retryInProcessingBatchTypes = [
        // 1 hour gap for Recon batches
        self::RECONCILIATION    => 3600,
    ];

    /**
     * Following batch types get processed via CRON job, CRON currently runs
     * less frequently (now every 6 hrs).
     *
     * @var array
     */
    public static $cronGroup = [
        self::REFUND,
    ];

    /**
     * Following batch types get processed via QUEUE, Queues are instant and
     * batch gets processed immediately.
     *
     * @var array
     */
    public static $queueGroup = [
        self::PAYOUT_LINK_BULK,
        self::PAYOUT_LINK_BULK_V2,
        self::PAYMENT_LINK,
        self::LINKED_ACCOUNT,
        self::VIRTUAL_BANK_ACCOUNT,
        self::BANK_TRANSFER,
        self::RECONCILIATION,
        self::EMANDATE,
        self::NACH,
        self::PAYOUT,
        self::SUB_MERCHANT,
        self::DIRECT_DEBIT,
        self::RECURRING_CHARGE,
        self::ELFIN,
        self::OAUTH_MIGRATION_TOKEN,
        self::PARTNER_SUBMERCHANTS,
        self::ENTITY_MAPPING,
        self::AUTH_LINK,
        self::INSTANT_ACTIVATION,
        self::TERMINAL,
        self::TERMINAL_CREATION,
        self::CONTACT,
        self::FUND_ACCOUNT,
        self::MERCHANT_ONBOARDING,
        self::LINKED_ACCOUNT_REVERSAL,
        self::SUBMERCHANT_ASSIGN,
        self::IIN_NPCI_RUPAY,
        self::IIN_HITACHI_VISA,
        self::IIN_MC_MASTERCARD,
        self::MPAN,
        self::PRICING_RULE,
        self::BUY_PRICING_RULE,
        self::BUY_PRICING_ASSIGN,
        self::LOC_WITHDRAWAL,
        self::ADMIN_BATCH,
        self::ADJUSTMENT,
        self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG,
        self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG,
        self::CREDIT,
        self::UPI_TERMINAL_ONBOARDING,
    ];

    /**
     * Batch sub_types
     *
     * @var array
     */
    public static $subTypes = [
        Gateway::NETBANKING_HDFC,
        Gateway::NETBANKING_ICICI,
        Gateway::NETBANKING_AXIS,
        Gateway::HITACHI,
        Gateway::BILLDESK,
        CardlessEmi::ZESTMONEY,
        CardlessEmi::FLEXMONEY,
        CardlessEmi::EARLYSALARY,
        self::CANCEL_DEBIT,
    ];

    /**
     * Following batch types get processed via Kubernetes Job, this is used for long
     * running batches.
     *
     * @var array
     */
    public static $kubernetesJobGroup = [
        // Do not include PAYOUT, FUND_ACCOUNT & CONTACT because their implementation is not parallel execution ready.
        self::PAYMENT_LINK,
        self::SUB_MERCHANT,
        self::OAUTH_MIGRATION_TOKEN,
        self::PARTNER_SUBMERCHANTS,
        self::RECURRING_CHARGE,
        self::AUTH_LINK,
        self::VIRTUAL_BANK_ACCOUNT,
        self::ENTITY_MAPPING,
        self::LINKED_ACCOUNT,
        self::LINKED_ACCOUNT_REVERSAL,
        self::INSTANT_ACTIVATION,
        self::MERCHANT_ONBOARDING,
        self::IIN_NPCI_RUPAY,
        self::IIN_HITACHI_VISA,
        self::IIN_MC_MASTERCARD,
    ];

    /**
     * Following batch types get processed via Kubernetes Job, this is used for long
     * running batches. These batches first get pushed into SQS queue, then worker picks up
     * from the queue and initiate K8s job.
     *
     * @var array
     */
    public static $kubernetesJobQueueGroup = [
        // Do not include PAYOUT, FUND_ACCOUNT & CONTACT because their implementation is not parallel execution ready.
        self::RECONCILIATION,
    ];

    /**
     * Following batch types are not yet completely migrated to new batch service.
     * @var array
     */
    public static $batchTypeMigrating = [
        self::PAYOUT_LINK_BULK,
        self::PAYOUT_LINK_BULK_V2,
        self::PAYMENT_LINK,
        self::PAYOUT,
        self::TALLY_PAYOUT,
        self::FUND_ACCOUNT,
        self::SUBMERCHANT_ASSIGN,
        self::PRICING_RULE,
        self::BUY_PRICING_RULE,
        self::BUY_PRICING_ASSIGN,
        self::LOC_WITHDRAWAL,
        self::RECURRING_CHARGE,
        self::AUTH_LINK,
        self::VIRTUAL_BANK_ACCOUNT,
        self::PARTNER_SUBMERCHANTS,
        self::OAUTH_MIGRATION_TOKEN,
        self::LINKED_ACCOUNT_REVERSAL,
        self::ADJUSTMENT,
        self::PAYMENT_LINK_V2,
        self::RECURRING_CHARGE_BSE,
        self::ECOLLECT_ICICI,
        self::ECOLLECT_RBL,
        self::REPORT,
        self::ADMIN_BATCH,
        self::RECONCILIATION,
        self::BANK_TRANSFER_EDIT,
        self::CREDIT,
        self::TERMINAL_CREATION,
        self::NACH_MIGRATION,
        self::MPAN,
        self::ADJUSTMENT,
        self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG,
        self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG,
        self::PARTNER_SUBMERCHANT_INVITE,
        self::REFUND,
        self::LINKED_ACCOUNT_CREATE,
        self::RETRY_REFUNDS_TO_BA,
        self::UPI_TERMINAL_ONBOARDING,
        self::PAYMENT_TRANSFER,
        self::TRANSFER_REVERSAL,
        self::RECURRING_CHARGE_AXIS,
        self::EMANDATE_DEBIT_HDFC,
    ];

    /**
     * Following batch types are completely migrated to new batch service.
     * Make sure batch type mentioned here is also present in $batchTypeMigrating array.
     * @var array
     */
    public static $batchTypeMigrationCompleted = [
        self::TALLY_PAYOUT,
        self::PAYOUT_LINK_BULK,
        self::PAYOUT_LINK_BULK_V2,
        self::PAYMENT_LINK,
        self::PAYOUT,
        self::FUND_ACCOUNT,
        self::PRICING_RULE,
        self::BUY_PRICING_RULE,
        self::BUY_PRICING_ASSIGN,
        self::LOC_WITHDRAWAL,
        self::MERCHANT_CONFIG_INHERITANCE,
        self::ENTITY_UPDATE_ACTION,
        self::ADJUSTMENT,
        self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG,
        self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG,
        self::PAYMENT_LINK_V2,
        self::RECURRING_CHARGE_BSE,
        self::ECOLLECT_ICICI,
        self::ECOLLECT_RBL,
        self::REPORT,
        self::ADMIN_BATCH,
        self::BANK_TRANSFER_EDIT,
        self::CREDIT,
        self::TERMINAL_CREATION,
        self::MERCHANT_STATUS_ACTION,
        self::NACH_MIGRATION,
        self::MPAN,
        self::MERCHANT_ACTIVATION,
        self::INTERNAL_INSTRUMENT_REQUEST,
        self::ADJUSTMENT,
        self::CAPTURE_SETTING,
        self::PARTNER_SUBMERCHANT_INVITE,
        self::SUBMERCHANT_LINK,
        self::SUBMERCHANT_DELINK,
        self::SUBMERCHANT_PARTNER_CONFIG_UPSERT,
        self::SUBMERCHANT_TYPE_UPDATE,
        self::RECURRING_CHARGE,
        self::AUTH_LINK,
        self::REFUND,
        self::LINKED_ACCOUNT_CREATE,
        self::RETRY_REFUNDS_TO_BA,
        self::UPI_TERMINAL_ONBOARDING,
        self::PAYMENT_TRANSFER,
        self::TRANSFER_REVERSAL,
        self::SUB_MERCHANT,
        self::RECURRING_CHARGE_AXIS,
        self::WEBSITE_CHECKER,
        self::EMANDATE_DEBIT_HDFC,
    ];

    // For following batches, sensitive data is encrypted in storeInputFileAndSaveBatchWithSettings() so that file with sensitive/PCI data
    // don't get saved on disk. We decrypt the data again at the time of processing in api, so that batch service don't come under PCI scope
    // See SENSITIVE_HEADERS in Header.php
    public static $haveSensitiveData = [
        self::MPAN,
        self::TERMINAL_CREATION,
    ];

    public static $batchToAdminPermissionMapping = [
        self::ADJUSTMENT                    => Name::ADJUSTMENT_BATCH_UPLOAD,
        self::REPORT                        => Name::REPORTING_BATCH_UPLOAD,
        self::CREDIT                        => Name::CREDITS_BATCH_UPLOAD,
        self::LOC_WITHDRAWAL                => Name::LOC_WITHDRAWAL_EDIT,
        self::REFUND                        => Name::EDIT_PAYMENT_REFUND,
        self::RETRY_REFUNDS_TO_BA           => Name::BULK_RETRY_REFUNDS_VIA_FTA,
        self::BUY_PRICING_RULE              => Name::PAYMENTS_CREATE_BUY_PRICING_PLAN,
        self::BUY_PRICING_ASSIGN            => Name::EDIT_TERMINAL,
        self::TERMINAL_CREATION             => Name::PAYMENTS_BATCH_CREATE_TERMINALS_BULK,
        self::TERMINAL                      => Name::PAYMENTS_BATCH_CREATE_TERMINALS_BULK,
        self::UPI_TERMINAL_ONBOARDING       => Name::PAYMENTS_BATCH_CREATE_TERMINALS_BULK,
        self::INTERNAL_INSTRUMENT_REQUEST   => Name::INTERNAL_INSTRUMENT_CREATE_BULK,
        self::PAYOUT_LINK_BULK              => Name::PAYOUT_LINKS_ADMIN_BULK_CREATE,
        self::TALLY_PAYOUT                  => Name::TALLY_PAYOUT_BULK_CREATE,
        self::CAPTURE_SETTING               => Name::CAPTURE_SETTING_BATCH_UPLOAD,
        self::WEBSITE_CHECKER               => Name::WEBSITE_CHECKER,
        self::IIN_HITACHI_VISA              => Name::IIN_BATCH_UPLOAD,
        self::IIN_MC_MASTERCARD             => Name::IIN_BATCH_UPLOAD,
        self::IIN_NPCI_RUPAY                => Name::IIN_BATCH_UPLOAD,
        self::MPAN                          => Name::MPAN_BATCH_UPLOAD,
        self::EMANDATE                      => Name::EMANDATE_BATCH_UPLOAD,
        self::NACH                          => Name::NACH_BATCH_UPLOAD,
        self::HITACHI_CBK_MASTERCARD        => Name::BULK_HITACHI_CHARGEBACK,
        self::HITACHI_CBK_VISA              => Name::BULK_HITACHI_CHARGEBACK,
        self::HITACHI_CBK_RUPAY             => Name::BULK_HITACHI_CHARGEBACK,

        self::SUBMERCHANT_ASSIGN                => Name::ADMIN_MANAGE_PARTNERS,
        self::SUBMERCHANT_LINK                  => Name::ADMIN_MANAGE_PARTNERS,
        self::SUBMERCHANT_DELINK                => Name::ADMIN_MANAGE_PARTNERS,
        self::SUB_MERCHANT                      => Name::ADMIN_MANAGE_PARTNERS,
        self::SUBMERCHANT_PARTNER_CONFIG_UPSERT => Name::ADMIN_MANAGE_PARTNERS,
        self::SUBMERCHANT_TYPE_UPDATE           => Name::ADMIN_MANAGE_PARTNERS,
        self::PARTNER_SUBMERCHANTS              => Name::ADMIN_MANAGE_PARTNERS,
    ];

    public static $workflowApplicableBatchTypes = [
        self::ADJUSTMENT     => Name::CREATE_BULK_ADJUSTMENT,
    ];

    public static function exists(string $type)
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    public static function isDisabled(string $type)
    {
        return (in_array($type, self::$disabledTypes, true) === true);
    }

    public static function isRetryDisabled(string $type)
    {
        return (in_array($type, self::$retryDisabledTypes, true) === true);
    }

    public static function validateType(string $type)
    {
        if ((self::exists($type) === false) or
            (self::isDisabled($type) === true))
        {
            throw new Exception\BadRequestValidationFailureException('Not a valid type: ' . $type);
        }
    }

    public static function validateTypes(array $types)
    {
        foreach ($types as $row)
        {
            self::validateType($row);
        }
    }

    public static function validateSubType(string $subtype)
    {
        if (in_array($subtype, self::$subTypes, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Not a valid sub_type' . $subtype);
        }
    }

    public static function isQueueGroup(string $type): bool
    {
        return in_array($type, self::$queueGroup, true);
    }

    public static function isKubernetesJobGroup(string $type): bool
    {
        return in_array($type, self::$kubernetesJobGroup, true);
    }

    public static function isKubernetesJobQueueGroup(string $type): bool
    {
        return in_array($type, self::$kubernetesJobQueueGroup, true);
    }

    public static function isAppType(string $type): bool
    {
        return in_array($type, self::$appTypes, true);
    }
}
