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
    const RAW_ADDRESS               = 'raw_address';
    const FULFILLMENT_ORDER_UPDATE  = 'fulfillment_order_update';


    // Wallet batch types
    const CREATE_WALLET_ACCOUNTS        = 'create_wallet_accounts';
    const CREATE_WALLET_LOADS           = 'create_wallet_loads';
    const CREATE_WALLET_CONTAINER_LOADS = 'create_wallet_container_loads';
    const CREATE_WALLET_USER_CONTAINERS = 'create_wallet_user_containers';

    //Cod eligibility attribute batch
    const ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST = 'one_cc_cod_eligibility_attribute_whitelist_upsert';
    const ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST = 'one_cc_cod_eligibility_attribute_blacklist_upsert';

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

    const RECURRING_CHARGE_BULK     = 'recurring_charge_bulk';

    // Custom Batch recurring payments for AXIS
    const RECURRING_CHARGE_AXIS     = 'recurring_charge_axis';

    // BSE custom batch
    const RECURRING_CHARGE_BSE      = 'recurring_charge_bse';

    const RECONCILIATION            = 'reconciliation';

    const EMANDATE                  = 'emandate';

    const NACH                      = 'nach';

    const NACH_MIGRATION            = 'nach_migration';

    const UPDATE                    = 'update';

    const PAYOUT                    = 'payout';

    const SUB_MERCHANT              = 'sub_merchant';

    /**
     * This type is used to automate merchant and pricing plan in bulk for vas merchants.
     */
    const MERCHANT_UPLOAD_MIQ       = 'merchant_upload_miq';

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

    const PARTNER_SUBMERCHANT_INVITE_CAPITAL = 'partner_submerchant_invite_capital';

    const PGOS_RMDETAILS_BULK = 'pgos_rmdetails_bulk';

    const PARTNER_REFERRAL_FETCH = 'partner_referral_fetch';

    const CONTACT               = 'contact';

    const FUND_ACCOUNT          = 'fund_account';

    const FUND_ACCOUNT_V2       = 'fund_account_v2';

    // iin batches
    const IIN_NPCI_RUPAY        = 'iin_npci_rupay';

    const IIN_HITACHI_VISA      = 'iin_hitachi_visa';

    const IIN_MC_MASTERCARD     = 'iin_mc_mastercard';

    const MPAN                  = 'mpan';

    const VAULT_TOKEN_MIGRATE   = 'vault_token_migrate';

    const TOKEN_HQ_CHARGE       = 'token_hq_charge';

    const VAULT_MIGRATE_TOKEN_NS = 'vault_migrate_token_ns';


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

    const MERCHANT_CAPITAL_TAGS = 'merchant_capital_tags';

    const EARLY_SETTLEMENT_TRIAL = 'early_settlement_trial';

    const ECOLLECT_ICICI        = 'ecollect_icici';

    const MDR_ADJUSTMENTS       = 'mdr_adjustments';

    const MERCHANT_STATUS_ACTIVATION       = 'merchant_status_activation';

    const ECOLLECT_RBL          = 'ecollect_rbl';

    const ECOLLECT_YESBANK      = 'ecollect_yesbank';

    const REPORT                = 'report';

    const BANK_TRANSFER_EDIT    = 'bank_transfer_edit';

    const CREDIT              = 'credit';

    const BANKING_ACCOUNT_ACTIVATION_COMMENTS     = 'banking_account_activation_comments';

    const ICICI_LEAD_ACCOUNT_ACTIVATION_COMMENTS  = 'icici_lead_account_activation_comments';

    const RBL_BULK_UPLOAD_COMMENTS                = 'rbl_bulk_upload_comments';

    const ICICI_BULK_UPLOAD_COMMENTS              = 'icici_bulk_upload_comments';

    const ICICI_VIDEO_KYC_BULK_UPLOAD             = 'icici_video_kyc_bulk_upload';

    const ICICI_STP_MIS                            = 'icici_stp_mis';

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

    const UPI_ONBOARDED_TERMINAL_EDIT = 'upi_onboarded_terminal_edit';

    const HITACHI_FULCRUM_ONBOARD     = 'hitachi_fulcrum_onboard';

    const EMANDATE_DEBIT_HDFC         = 'emandate_debit_hdfc';

    const ENACH_NPCI_NETBANKING       = 'enach_npci_netbanking';

    const EMANDATE_DEBIT_ENACH_RBL    = 'emandate_debit_enach_rbl';

    const EMANDATE_DEBIT_SBI    = 'emandate_debit_sbi';


    //
    // Support admin action for bulk retrying refunds via FTA to custom sources
    //
    const RETRY_REFUNDS_TO_BA = 'retry_refunds_to_ba';

    const CANCEL_DEBIT = 'cancel_debit';

    const PAYMENT_TRANSFER  = 'payment_transfer';

    const TRANSFER_REVERSAL = 'transfer_reversal';

    const PAYMENT_TRANSFER_RETRY = 'payment_transfer_retry';

    const WEBSITE_CHECKER = 'website_checker';

    const CREATE_EXEC_RISK_ACTION = 'create_exec_risk_action';

    const LEDGER_ONBOARD_OLD_ACCOUNT = 'ledger_onboard_old_account';

    const LEDGER_BULK_JOURNAL_CREATE = 'ledger_bulk_journal_create';

    const CHARGEBACK_POC      =  'chargeback_poc';

    const WHITELISTED_DOMAIN  =  'whitelisted_domain';

    const ED_MERCHANT_SEARCH  =  'ed_merchant_search';

    const REWARDS = 'rewards';

    const VIRTUAL_ACCOUNT_EDIT = "virtual_account_edit";

    const EZETAP_SETTLEMENT    = "ezetap_settlement";

    const DEBIT_NOTE          = 'debit_note';

    const CREATE_PAYMENT_FRAUD  =  'create_payment_fraud';

    const COLLECT_LOCAL_CONSENTS_TO_CREATE_TOKENS = 'collect_local_consents_to_create_tokens';

    const PAYMENT_PAGE = 'payment_page';

    const PARTNER_SUBMERCHANT_REFERRAL_INVITE = 'partner_submerchant_referral_invite';

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
        self::VAULT_MIGRATE_TOKEN_NS,
        self::TOKEN_HQ_CHARGE,
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
        self::EARLY_SETTLEMENT_TRIAL,
        self::MERCHANT_CAPITAL_TAGS,
        self::ECOLLECT_ICICI,
        self::ECOLLECT_RBL,
        self::ECOLLECT_YESBANK,
        self::BANK_TRANSFER_EDIT,
        self::CREDIT,
        self::MERCHANT_STATUS_ACTION,
        self::BANKING_ACCOUNT_ACTIVATION_COMMENTS,
        self::ICICI_LEAD_ACCOUNT_ACTIVATION_COMMENTS,
        self::ICICI_STP_MIS,
        self::RBL_BULK_UPLOAD_COMMENTS,
        self::ICICI_BULK_UPLOAD_COMMENTS,
        self::ICICI_VIDEO_KYC_BULK_UPLOAD,
        self::NACH_MIGRATION,
        self::PGOS_RMDETAILS_BULK,
        self::MERCHANT_ACTIVATION,
        self::INTERNAL_INSTRUMENT_REQUEST,
        self::SUBMERCHANT_LINK,
        self::SUBMERCHANT_DELINK,
        self::SUBMERCHANT_PARTNER_CONFIG_UPSERT,
        self::SUBMERCHANT_TYPE_UPDATE,
        self::RETRY_REFUNDS_TO_BA,
        self::UPI_TERMINAL_ONBOARDING,
        self::UPI_ONBOARDED_TERMINAL_EDIT,
        self::HITACHI_FULCRUM_ONBOARD,
        self::WEBSITE_CHECKER,
        self::HITACHI_CBK_MASTERCARD,
        self::HITACHI_CBK_VISA,
        self::HITACHI_CBK_RUPAY,
        self::CREATE_EXEC_RISK_ACTION,
        self::LEDGER_ONBOARD_OLD_ACCOUNT,
        self::LEDGER_BULK_JOURNAL_CREATE,
        self::CHARGEBACK_POC,
        self::WHITELISTED_DOMAIN,
        self::ED_MERCHANT_SEARCH,
        self::DEBIT_NOTE,
        self::CREATE_PAYMENT_FRAUD,
        self::PAYMENT_TRANSFER_RETRY,
        self::PARTNER_REFERRAL_FETCH,
        self::COLLECT_LOCAL_CONSENTS_TO_CREATE_TOKENS,
        self::EZETAP_SETTLEMENT,
        self::MERCHANT_UPLOAD_MIQ,
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
        self::FUND_ACCOUNT_V2,
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
        self::VIRTUAL_ACCOUNT_EDIT,
        self::EZETAP_SETTLEMENT,
        self::LOC_WITHDRAWAL,
        self::ADMIN_BATCH,
        self::ADJUSTMENT,
        self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG,
        self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG,
        self::EARLY_SETTLEMENT_TRIAL,
        self::MERCHANT_CAPITAL_TAGS,
        self::CREDIT,
        self::UPI_TERMINAL_ONBOARDING,
        self::UPI_ONBOARDED_TERMINAL_EDIT,
        self::HITACHI_FULCRUM_ONBOARD,
        self::VAULT_MIGRATE_TOKEN_NS,
        self::MERCHANT_UPLOAD_MIQ,
        self::TOKEN_HQ_CHARGE,
        self::PAYMENT_PAGE
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
        self::UPDATE,
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
        self::FUND_ACCOUNT_V2,
        self::SUBMERCHANT_ASSIGN,
        self::PRICING_RULE,
        self::BUY_PRICING_RULE,
        self::BUY_PRICING_ASSIGN,
        self::VIRTUAL_ACCOUNT_EDIT,
        self::EZETAP_SETTLEMENT,
        self::LOC_WITHDRAWAL,
        self::RECURRING_CHARGE,
        self::RECURRING_CHARGE_BULK,
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
        self::ECOLLECT_YESBANK,
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
        self::EARLY_SETTLEMENT_TRIAL,
        self::MERCHANT_CAPITAL_TAGS,
        self::PARTNER_SUBMERCHANT_INVITE,
        self::REFUND,
        self::RAW_ADDRESS,
        self::FULFILLMENT_ORDER_UPDATE,
        self::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST,
        self::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST,
        self::LINKED_ACCOUNT_CREATE,
        self::RETRY_REFUNDS_TO_BA,
        self::UPI_TERMINAL_ONBOARDING,
        self::UPI_ONBOARDED_TERMINAL_EDIT,
        self::PAYMENT_TRANSFER,
        self::TRANSFER_REVERSAL,
        self::PAYMENT_TRANSFER_RETRY,
        self::RECURRING_CHARGE_AXIS,
        self::EMANDATE_DEBIT_HDFC,
        self::LEDGER_ONBOARD_OLD_ACCOUNT,
        self::LEDGER_BULK_JOURNAL_CREATE,
        self::PARTNER_REFERRAL_FETCH,
        self::VAULT_MIGRATE_TOKEN_NS,
        self::TOKEN_HQ_CHARGE,
        self::MERCHANT_UPLOAD_MIQ,
        self::HITACHI_FULCRUM_ONBOARD,
        self::PAYMENT_PAGE,
        self::CREATE_WALLET_ACCOUNTS,
        self::CREATE_WALLET_LOADS,
        self::CREATE_WALLET_CONTAINER_LOADS,
        self::PARTNER_SUBMERCHANT_REFERRAL_INVITE,
        self::CREATE_WALLET_USER_CONTAINERS
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
        self::FUND_ACCOUNT_V2,
        self::PRICING_RULE,
        self::BUY_PRICING_RULE,
        self::BUY_PRICING_ASSIGN,
        self::VIRTUAL_ACCOUNT_EDIT,
        self::PGOS_RMDETAILS_BULK,
        self::EZETAP_SETTLEMENT,
        self::LOC_WITHDRAWAL,
        self::MERCHANT_CONFIG_INHERITANCE,
        self::ENTITY_UPDATE_ACTION,
        self::ADJUSTMENT,
        self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG,
        self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG,
        self::EARLY_SETTLEMENT_TRIAL,
        self::MERCHANT_CAPITAL_TAGS,
        self::PAYMENT_LINK_V2,
        self::RECURRING_CHARGE_BSE,
        self::ECOLLECT_ICICI,
        self::ECOLLECT_RBL,
        self::ECOLLECT_YESBANK,
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
        self::PARTNER_SUBMERCHANT_INVITE_CAPITAL,
        self::SUBMERCHANT_LINK,
        self::SUBMERCHANT_DELINK,
        self::SUBMERCHANT_PARTNER_CONFIG_UPSERT,
        self::SUBMERCHANT_TYPE_UPDATE,
        self::RECURRING_CHARGE,
        self::RECURRING_CHARGE_BULK,
        self::AUTH_LINK,
        self::REFUND,
        self::RAW_ADDRESS,
        self::FULFILLMENT_ORDER_UPDATE,
        self::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST,
        self::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST,
        self::LINKED_ACCOUNT_CREATE,
        self::RETRY_REFUNDS_TO_BA,
        self::UPI_TERMINAL_ONBOARDING,
        self::UPI_ONBOARDED_TERMINAL_EDIT,
        self::PAYMENT_TRANSFER,
        self::TRANSFER_REVERSAL,
        self::PAYMENT_TRANSFER_RETRY,
        self::SUB_MERCHANT,
        self::RECURRING_CHARGE_AXIS,
        self::WEBSITE_CHECKER,
        self::EMANDATE_DEBIT_HDFC,
        self::HITACHI_CBK_MASTERCARD,
        self::HITACHI_CBK_RUPAY,
        self::HITACHI_CBK_VISA,
        self::CREATE_EXEC_RISK_ACTION,
        self::LEDGER_ONBOARD_OLD_ACCOUNT,
        self::LEDGER_BULK_JOURNAL_CREATE,
        self::CHARGEBACK_POC,
        self::WHITELISTED_DOMAIN,
        self::ED_MERCHANT_SEARCH,
        self::DEBIT_NOTE,
        self::CREATE_PAYMENT_FRAUD,
        self::PARTNER_REFERRAL_FETCH,
        self::VAULT_MIGRATE_TOKEN_NS,
        self::COLLECT_LOCAL_CONSENTS_TO_CREATE_TOKENS,
        self::MERCHANT_UPLOAD_MIQ,
        self::TOKEN_HQ_CHARGE,
        self::HITACHI_FULCRUM_ONBOARD,
        self::PAYMENT_PAGE,
        self::CREATE_WALLET_ACCOUNTS,
        self::CREATE_WALLET_LOADS,
        self::CREATE_WALLET_CONTAINER_LOADS,
        self::PARTNER_SUBMERCHANT_REFERRAL_INVITE,
        self::CREATE_WALLET_USER_CONTAINERS
    ];

    // For following batches, sensitive data is encrypted in storeInputFileAndSaveBatchWithSettings() so that file with sensitive/PCI data
    // don't get saved on disk. We decrypt the data again at the time of processing in api, so that batch service don't come under PCI scope
    // See SENSITIVE_HEADERS in Header.php
    public static $haveSensitiveData = [
        self::MPAN,
        self::TERMINAL_CREATION,
    ];

    // map of type of batch -> permission
    public static $batchToAdminPermissionMapping = [
        self::ADJUSTMENT                         => Name::ADJUSTMENT_BATCH_UPLOAD,
        self::REPORT                             => Name::REPORTING_BATCH_UPLOAD,
        self::CREDIT                             => Name::CREDITS_BATCH_UPLOAD,
        self::LOC_WITHDRAWAL                     => Name::LOC_WITHDRAWAL_EDIT,
        self::REFUND                             => Name::EDIT_PAYMENT_REFUND,
        self::RETRY_REFUNDS_TO_BA                => Name::BULK_RETRY_REFUNDS_VIA_FTA,
        self::BUY_PRICING_RULE                   => Name::PAYMENTS_CREATE_BUY_PRICING_PLAN,
        self::BUY_PRICING_ASSIGN                 => Name::EDIT_TERMINAL,
        self::TERMINAL_CREATION                  => Name::PAYMENTS_BATCH_CREATE_TERMINALS_BULK,
        self::TERMINAL                           => Name::PAYMENTS_BATCH_CREATE_TERMINALS_BULK,
        self::UPI_TERMINAL_ONBOARDING            => Name::PAYMENTS_BATCH_CREATE_TERMINALS_BULK,
        self::UPI_ONBOARDED_TERMINAL_EDIT        => Name::EDIT_TERMINAL,
        self::HITACHI_FULCRUM_ONBOARD            => Name::PAYMENTS_BATCH_CREATE_TERMINALS_BULK,
        self::INTERNAL_INSTRUMENT_REQUEST        => Name::INTERNAL_INSTRUMENT_CREATE_BULK,
        self::PAYOUT_LINK_BULK                   => Name::PAYOUT_LINKS_ADMIN_BULK_CREATE,
        self::TALLY_PAYOUT                       => Name::TALLY_PAYOUT_BULK_CREATE,
        self::CAPTURE_SETTING                    => Name::CAPTURE_SETTING_BATCH_UPLOAD,
        self::WEBSITE_CHECKER                    => Name::WEBSITE_CHECKER,
        self::IIN_HITACHI_VISA                   => Name::IIN_BATCH_UPLOAD,
        self::IIN_MC_MASTERCARD                  => Name::IIN_BATCH_UPLOAD,
        self::IIN_NPCI_RUPAY                     => Name::IIN_BATCH_UPLOAD,
        self::MPAN                               => Name::MPAN_BATCH_UPLOAD,
        self::EMANDATE                           => Name::EMANDATE_BATCH_UPLOAD,
        self::NACH                               => Name::NACH_BATCH_UPLOAD,
        self::HITACHI_CBK_MASTERCARD             => Name::BULK_HITACHI_CHARGEBACK,
        self::HITACHI_CBK_VISA                   => Name::BULK_HITACHI_CHARGEBACK,
        self::HITACHI_CBK_RUPAY                  => Name::BULK_HITACHI_CHARGEBACK,

        self::SUBMERCHANT_ASSIGN                 => Name::ADMIN_MANAGE_PARTNERS,
        self::SUBMERCHANT_LINK                   => Name::ADMIN_MANAGE_PARTNERS,
        self::SUBMERCHANT_DELINK                 => Name::ADMIN_MANAGE_PARTNERS,
        self::SUB_MERCHANT                       => Name::ADMIN_MANAGE_PARTNERS,
        self::SUBMERCHANT_PARTNER_CONFIG_UPSERT  => Name::ADMIN_MANAGE_PARTNERS,
        self::SUBMERCHANT_TYPE_UPDATE            => Name::ADMIN_MANAGE_PARTNERS,
        self::PARTNER_SUBMERCHANTS               => Name::ADMIN_MANAGE_PARTNERS,
        self::ECOLLECT_ICICI                     => Name::ECOLLECT_ICICI_BATCH_UPLOAD,
        self::ECOLLECT_RBL                       => Name::ECOLLECT_RBL_BATCH_UPLOAD,
        self::ECOLLECT_YESBANK                   => Name::ECOLLECT_YESBANK_BATCH_UPLOAD,
        self::VIRTUAL_BANK_ACCOUNT               => Name::VIRTUAL_BANK_ACCOUNT_BATCH_UPLOAD,
        self::BANK_TRANSFER_EDIT                 => Name::BANK_TRANSFER_INSERT,
        self::BANK_TRANSFER                      => Name::BANK_TRANSFER_INSERT,
        self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG => Name::SETTLEMENT_ONDEMAND_FEATURE_ENABLE,
        self::MERCHANT_CAPITAL_TAGS              => Name::MERCHANT_CAPITAL_TAGS_UPLOAD,
        self::LEDGER_ONBOARD_OLD_ACCOUNT         => Name::LEDGER_SERVICE_ACTIONS,
        self::LEDGER_BULK_JOURNAL_CREATE         => Name::LEDGER_SERVICE_ACTIONS,
        self::CHARGEBACK_POC                     => Name::BULK_UPDATE_CHARGEBACK_POC,
        self::ED_MERCHANT_SEARCH                 => Name::ED_MERCHANT_SEARCH,
        self::ENTITY_UPDATE_ACTION               => Name::ADMIN_BATCH_CREATE,
        self::CREATE_EXEC_RISK_ACTION            => Name::ADMIN_BATCH_CREATE,
        self::WHITELISTED_DOMAIN                 => Name::BULK_UPDATE_WHITELISTED_DOMAIN,
        self::PGOS_RMDETAILS_BULK                => Name::ADMIN_BATCH_CREATE,
        self::EARLY_SETTLEMENT_TRIAL             => Name::ADMIN_BATCH_CREATE,
        self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG=> Name::ADMIN_BATCH_CREATE,
        self::REWARDS                            => Name::ADMIN_BATCH_CREATE,
        self::MERCHANT_STATUS_ACTION             => Name::ADMIN_BATCH_CREATE,
        self::ENTITY_MAPPING                     => Name::ADMIN_BATCH_CREATE,
        self::ADMIN_BATCH                        => Name::ADMIN_BATCH_CREATE,
        self::VAULT_MIGRATE_TOKEN_NS             => Name::ADMIN_BATCH_CREATE,
        self::TOKEN_HQ_CHARGE                    => Name::ADMIN_BATCH_CREATE,
        self::INSTANT_ACTIVATION                 => Name::INSTANT_ACTIVATION,
        self::MDR_ADJUSTMENTS                    => Name::MDR_ADJUSTMENTS,
        self::MERCHANT_ACTIVATION                => Name::MERCHANT_ACTIVATION,
        self::MERCHANT_CONFIG_INHERITANCE        => Name::MERCHANT_CONFIG_INHERITANCE,
        self::MERCHANT_ONBOARDING                => Name::MERCHANT_ONBOARDING,
        self::MERCHANT_STATUS_ACTIVATION         => Name::MERCHANT_STATUS_ACTIVATION,
        self::PRICING_RULE                       => Name::PRICING_RULE,
        self::PARTNER_REFERRAL_FETCH             => Name::ADMIN_BATCH_CREATE,

        self::MERCHANT_UPLOAD_MIQ                => Name::MERCHANT_BULK_UPLOAD_MIQ,

        self::DEBIT_NOTE                         => Name::CREATE_DEBIT_NOTE,
        self::NACH_MIGRATION                     => Name::ADMIN_BATCH_CREATE,
        self::CREATE_PAYMENT_FRAUD               => Name::ADMIN_BATCH_CREATE,
        self::ICICI_LEAD_ACCOUNT_ACTIVATION_COMMENTS => Name::ADMIN_BATCH_CREATE,
        self::RBL_BULK_UPLOAD_COMMENTS           => Name::ADMIN_BATCH_CREATE,
        self::ICICI_BULK_UPLOAD_COMMENTS          => Name::ADMIN_BATCH_CREATE,
        self::ICICI_VIDEO_KYC_BULK_UPLOAD         => Name::ADMIN_BATCH_CREATE,
        self::BANKING_ACCOUNT_ACTIVATION_COMMENTS => Name::ADMIN_BATCH_CREATE,
        self::IRCTC_REFUND                        => Name::MERCHANT_BATCH_UPLOAD,
        self::IRCTC_DELTA_REFUND                  => Name::MERCHANT_BATCH_UPLOAD,
        self::IRCTC_SETTLEMENT                    => Name::MERCHANT_BATCH_UPLOAD,
        self::ICICI_STP_MIS                       => Name::ADMIN_BATCH_CREATE,
        self::PAYMENT_TRANSFER_RETRY              => Name::ADMIN_BATCH_CREATE,
        self::COLLECT_LOCAL_CONSENTS_TO_CREATE_TOKENS => Name::ADMIN_BATCH_CREATE,
        self::LINKED_ACCOUNT                      => Name::ADMIN_BATCH_CREATE
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
