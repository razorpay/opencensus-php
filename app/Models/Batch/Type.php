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

    // Virtual Account Bulk Creation
    const VIRTUAL_BANK_ACCOUNT      = 'virtual_bank_account';

    // Bank Transfer Bulk Insert
    const BANK_TRANSFER             = 'bank_transfer';

    const RECURRING_CHARGE          = 'recurring_charge';

    const RECONCILIATION            = 'reconciliation';

    const EMANDATE                  = 'emandate';

    const NACH                      = 'nach';

    const PAYOUT                    = 'payout';

    const SUB_MERCHANT              = 'sub_merchant';

    const DIRECT_DEBIT              = 'direct_debit';

    const ENTITY_MAPPING            = 'entity_mapping';

    const AUTH_LINK                 = 'auth_link';

    const INSTANT_ACTIVATION        = 'instant_activation';

    const SUBMERCHANT_ASSIGN        = 'submerchant_assign';

    const PRICING_RULE              = 'pricing_rule';

    const MERCHANT_CONFIG_INHERITANCE = 'merchant_config_inheritance';

    const MDR_ADJUSTMENT            = 'mdr_adjustment';

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

    const CONTACT               = 'contact';

    const FUND_ACCOUNT          = 'fund_account';

    // iin batches
    const IIN_NPCI_RUPAY        = 'iin_npci_rupay';

    const IIN_HITACHI_VISA      = 'iin_hitachi_visa';

    const IIN_MC_MASTERCARD     = 'iin_mc_mastercard';

    const MPAN                  = 'mpan';

    const ADMIN_BATCH           = 'admin_batch';

    const ENTITY_UPDATE_ACTION = 'entity_update_action';

    const ADJUSTMENT            = 'adjustment';

    const ECOLLECT_ICICI        = 'ecollect_icici';

    const ECOLLECT_RBL          = 'ecollect_rbl';

    const REPORT                = 'report';

    const BANK_TRANSFER_EDIT    = 'bank_transfer_edit';

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
        self::PRICING_RULE,
        self::ADMIN_BATCH,
        self::MERCHANT_CONFIG_INHERITANCE,
        self::ENTITY_UPDATE_ACTION,
        self::MDR_ADJUSTMENT,
        self::ADJUSTMENT,
        self::ECOLLECT_ICICI,
        self::ECOLLECT_RBL,
        self::BANK_TRANSFER_EDIT,
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
        self::ADMIN_BATCH,
        self::ADJUSTMENT,
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
        self::PAYMENT_LINK,
        self::PAYOUT,
        self::FUND_ACCOUNT,
        self::SUBMERCHANT_ASSIGN,
        self::PRICING_RULE,
        self::RECURRING_CHARGE,
        self::AUTH_LINK,
        self::VIRTUAL_BANK_ACCOUNT,
        self::PARTNER_SUBMERCHANTS,
        self::OAUTH_MIGRATION_TOKEN,
        self::LINKED_ACCOUNT_REVERSAL,
        self::ADJUSTMENT,
        self::PAYMENT_LINK_V2,
        self::ECOLLECT_ICICI,
        self::ECOLLECT_RBL,
        self::REPORT,
        self::ADMIN_BATCH,
        self::BANK_TRANSFER_EDIT,
    ];

    /**
     * Following batch types are completely migrated to new batch service.
     * Make sure batch type mentioned here is also present in $batchTypeMigrating array.
     * @var array
     */
    public static $batchTypeMigrationCompleted = [
        self::PAYMENT_LINK,
        self::PAYOUT,
        self::FUND_ACCOUNT,
        self::PRICING_RULE,
        self::MERCHANT_CONFIG_INHERITANCE,
        self::MDR_ADJUSTMENT,
        self::ENTITY_UPDATE_ACTION,
        self::ADJUSTMENT,
        self::PAYMENT_LINK_V2,
        self::ECOLLECT_ICICI,
        self::ECOLLECT_RBL,
        self::REPORT,
        self::ADMIN_BATCH,
        self::BANK_TRANSFER_EDIT,
    ];

    public static $batchToAdminPermissionMapping = [
        self::ADJUSTMENT    => Name::ADJUSTMENT_BATCH_UPLOAD,
        self::REPORT        => Name::REPORTING_BATCH_UPLOAD,
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
