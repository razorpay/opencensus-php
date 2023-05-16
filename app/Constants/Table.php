<?php

namespace RZP\Constants;

use App;

class Table
{
    // Core entities
    const P2P                          = 'p2p';
    const VPA                          = 'vpas';
    const IIN                          = 'iins';
    const MPAN                         = 'mpan';
    const KEY                          = 'keys';
    const RISK                         = 'risk';
    const PLAN                         = 'plans';
    const CARD                         = 'cards';
    const ITEM                         = 'items';
    const USER                         = 'users';
    const OFFER                        = 'offers';
    const ORDER                        = 'orders';
    const ORDER_OUTBOX                 = 'order_outbox';
    const ORDER_META                   = 'order_meta';
    const TOKEN                        = 'tokens';
    const BLADE                        = 'blade';
    const ADDON                        = 'addons';
    const GEO_IP                       = 'geo_ips';
    const COUPON                       = 'coupons';
    const BATCH                        = 'batches';
    const DEVICE                       = 'devices';
    const PAYOUT                       = 'payouts';
    const PAYOUT_OUTBOX                = 'payout_outbox';
    const LEDGER_OUTBOX                = 'ledger_outbox';
    const REFUND                       = 'refunds';
    const REPORT                       = 'reports';
    const CONTACT                      = 'contacts';
    const COUNTER                      = 'counters';
    const QR_CODE                      = 'qr_code';
    const QR_CODE_CONFIG               = 'qr_code_config';
    const QR_PAYMENT_REQUEST           = 'qr_payment_request';
    const QR_PAYMENT                   = 'qr_payment';
    const BALANCE                      = 'balance';
    const PRICING                      = 'pricing';
    const PRODUCT                      = 'product';
    const INVOICE                      = 'invoices';
    const PAYMENT                      = 'payments';
    const FEATURE                      = 'features';
    const DISPUTE                      = 'disputes';
    const DISPUTE_EVIDENCE             = 'dispute_evidence';
    const DISPUTE_EVIDENCE_DOCUMENT    = 'dispute_evidence_document';
    const DEBIT_NOTE                   = 'debit_note';
    const DEBIT_NOTE_DETAIL            = 'debit_note_detail';
    const ADDRESS                      = 'addresses';
    const RAW_ADDRESS                  = 'raw_addresses';
    const ADDRESS_CONSENT_1CC_AUDITS   = 'address_consent_1cc_audits';
    const ADDRESS_CONSENT_1CC          = 'address_consent_1cc';
    const ZIPCODE_DIRECTORY            = 'zipcode_directory';
    const CUSTOMER_CONSENT_1CC         = 'customer_consent_1cc';
    const DISCOUNT                     = 'discounts';
    const MERCHANT                     = 'merchants';
    const PARTNER_ACTIVATION           = 'partner_activation';
    const COMMISSION                   = 'commissions';
    const COMMISSION_COMPONENT         = 'commission_components';
    const PAYOUT_LINK                  = 'payout_links';
    const PAYOUT_SOURCE                = 'payout_sources';
    const FEE_RECOVERY                 = 'fee_recovery';
    const SUB_VIRTUAL_ACCOUNT          = 'sub_virtual_accounts';
    const LEGAL_ENTITY                 = 'legal_entity';
    const FUND_ACCOUNT                 = 'fund_accounts';
    const PAYMENT_LINK                 = 'payment_links';
    const ENTITY_ORIGIN                = 'entity_origins';
    const BALANCE_CONFIG               = 'balance_config';
    const OFFLINE_DEVICE               = 'offline_devices';
    const IDEMPOTENCY_KEY              = 'idempotency_keys';
    const PAYMENT_PAGE_ITEM            = 'payment_page_items';
    const PAYMENT_PAGE_RECORD          = 'payment_page_records';
    const LOW_BALANCE_CONFIG           = 'low_balance_configs';
    const MERCHANT_NOTIFICATION_CONFIG = 'merchant_notification_configs';
    const PAYOUTS_META                 = 'payouts_meta';
    const PAYOUTS_DETAILS              = 'payouts_details';
    const PAYOUTS_BATCH                = 'payouts_batch';
    const CORPORATE_CARD               = 'corporate_cards';
    const CREDIT_TRANSFER              = 'credit_transfers';

    const SUB_BALANCE_MAP              = 'sub_balance_map';
    const PAYOUTS_STATUS_DETAILS       = 'payouts_status_details';

    const PAYOUTS_INTERMEDIATE_TRANSACTIONS = 'payouts_intermediate_transactions';

    // Account entity is currently pointing to the 'merchants' table.
    // It will be used for basic CRUD operations over regular merchants,
    // sub-merchants as well as linked accounts. For more information, please
    // follow the discussions in #tech_accounts channel and PR: #2179
    const ACCOUNT                   = 'merchants';

    const EMI_PLAN                  = 'emi_plans';
    const SCHEDULE                  = 'schedules';
    const TERMINAL                  = 'terminals';
    const CUSTOMER                  = 'customers';
    const TRANSFER                  = 'transfers';
    // Statement is public exposed version of transaction, ref /Models/Transaction/Statement.
    const STATEMENT                 = 'transactions';

    const REVERSAL                  = 'reversals';
    const BHARAT_QR                 = 'bharat_qr';
    const PROMOTION                 = 'promotions';
    const LINE_ITEM                 = 'line_items';
    const INVITATION                = 'invitations';
    const FILE_STORE                = 'files';
    const ADJUSTMENT                = 'adjustment';
    const SETTLEMENT                = 'settlements';
    const ENTITY_OFFER              = 'entity_offer';
    const FEE_BREAKUP               = 'fees_breakup';
    const TRANSACTION               = 'transactions';
    const APP_TOKEN                 = 'customer_apps';
    const BANK_ACCOUNT              = 'bank_accounts';
    const OFFLINE_CHALLAN           = 'offline_challans';
    const OFFLINE_PAYMENT           = 'offline_payments';
    const SETTLEMENT_BUCKET         = 'settlement_bucket';
    const SETTLEMENT_TRANSFER       = 'settlement_transfer';
    const SETTLEMENT_DESTINATION    = 'settlement_destination';
    const BANK_TRANSFER_HISTORY     = 'bank_transfer_history';
    const VIRTUAL_VPA_PREFIX            = 'virtual_vpa_prefixes';
    const VIRTUAL_VPA_PREFIX_HISTORY    = 'virtual_vpa_prefix_history';

    const SUBSCRIPTION_OFFERS_MASTER     = 'subscription_offers_master';
    // Subscriptions Tables
    const SUBSCRIPTION                   = 'subscriptions';
    const SUBSCRIPTION_CYCLE             = 'subscription_cycles';
    const SUBSCRIPTION_VERSION           = 'subscription_versions';
    const SUBSCRIPTION_UPDATE_REQUEST    = 'subscription_update_requests';
    const SUBSCRIPTION_TRANSACTION       = 'subscription_transactions';
    //Subscriptions Tables end

    const REFERRALS                  = 'referrals';
    const UPI_TRANSFER               = 'upi_transfers';
    const PAPER_MANDATE              = 'paper_mandates';
    const METHODS                    = 'merchant_banks';
    const BANK_TRANSFER              = 'bank_transfers';
    const GATEWAY_TOKEN              = 'gateway_tokens';
    const SCHEDULE_TASK              = 'schedule_tasks';
    const MERCHANT_USERS             = 'merchant_users';
    const MERCHANT_OFFER             = 'merchant_offer';
    const MERCHANT_USER              = 'merchant_users';
    const PARTNER_CONFIG             = 'partner_configs';
    const LINE_ITEM_TAX              = 'line_item_taxes';
    const DISPUTE_REASON             = 'dispute_reasons';
    const MERCHANT_EMAIL             = 'merchant_emails';
    const VIRTUAL_ACCOUNT            = 'virtual_accounts';
    const MERCHANT_DETAIL            = 'merchant_details';
    const MERCHANT_BUSINESS_DETAIL   = 'merchant_business_details';
    const AMP_EMAIL                  = 'amp_emails';
    const M2M_REFERRAL               = 'm2m_referrals';
    const STAKEHOLDER                = 'stakeholders';
    const CUSTOMER_BALANCE           = 'customer_balance';
    const MERCHANT_INVOICE           = 'merchant_invoice';
    const MERCHANT_E_INVOICE         = 'merchant_e_invoice';
    const MERCHANT_REQUEST           = 'merchant_requests';
    const MERCHANT_TERMINAL          = 'merchant_terminal';
    const BATCH_FUND_TRANSFER        = 'daily_settlements';
    const MERCHANT_PROMOTION         = 'merchant_promotion';
    const MERCHANT_PRODUCT           = 'merchant_products';
    const COMMISSION_INVOICE         = 'commission_invoice';
    const CREDIT_TRANSACTION         = 'credit_transaction';
    const SETTLEMENT_DETAILS         = 'settlement_details';
    const MERCHANT_DOCUMENT          = 'merchant_documents';
    const MERCHANT_EMI_PLANS         = 'merchant_emi_plans';
    const VIRTUAL_ACCOUNT_TPV        = 'virtual_account_tpv';
    const BANKING_ACCOUNT_TPV        = 'banking_account_tpvs';
    const NODAL_BENEFICIARY          = 'nodal_beneficiaries';
    const MERCHANT_ACCESS_MAP        = 'merchant_access_map';
    const PARTNER_KYC_ACCESS_STATE   = 'partner_kyc_access_state';
    const MERCHANT_APPLICATION       = 'merchant_applications';
    const PAPER_MANDATE_UPLOAD       = 'paper_mandate_uploads';
    const CUSTOMER_TRANSACTION       = 'customer_transactions';
    const FUND_TRANSFER_ATTEMPT      = 'fund_transfer_attempts';
    const MERCHANT_INHERITANCE_MAP   = 'merchant_inheritance_map';
    const FUND_ACCOUNT_VALIDATION    = 'fund_account_validations';
    const SUBSCRIPTION_REGISTRATION  = 'subscription_registrations';
    const MERCHANT_FRESHDESK_TICKETS = 'merchant_freshdesk_tickets';
    const MERCHANT_ATTRIBUTE         = 'merchant_attributes';
    const UPI_TRANSFER_REQUEST       = 'upi_transfer_requests';
    const BANK_TRANSFER_REQUEST      = 'bank_transfer_requests';
    const VIRTUAL_ACCOUNT_PRODUCTS   = 'virtual_account_products';
    const MERCHANT_INTERNATIONAL_INTEGRATIONS = 'merchant_international_integrations';
    const SETTLEMENT_INTERNATIONAL_REPATRIATION = 'settlement_international_repatriation';
    const MERCHANT_OWNER_DETAILS     = 'merchant_owner_details';

    const D2C_BUREAU_DETAIL         = 'd2c_bureau_details';
    const D2C_BUREAU_REPORT         = 'd2c_bureau_reports';
    const PAYMENT_META              = 'payment_meta';

    //user device details
    const USER_DEVICE_DETAIL = 'user_device_details';

    const APP_ATTRIBUTION_DETAIL = 'app_attribution_details';

    const MERCHANT_AUTO_KYC_ESCALATIONS = 'merchant_auto_kyc_escalations';
    const MERCHANT_AVG_ORDER_VALUE      = 'merchant_avg_order_value';
    const MERCHANT_WEBSITE              = 'merchant_website';
    const MERCHANT_CONSENTS             = 'merchant_consents';
    const MERCHANT_CONSENT_DETAILS      = 'merchant_consent_details';
    const MERCHANT_VERIFICATION_DETAIL  = 'merchant_verification_details';
    const MERCHANT_CHECKOUT_DETAIL      = 'merchant_checkout_details';
    const AUDIT_INFO                    = 'audit_info';

    const MERCHANT_ONBOARDING_ESCALATIONS   = 'merchant_onboarding_escalations';
    const ONBOARDING_ESCALATION_ACTIONS     = 'onboarding_escalation_actions';

    // This table does not belong to api service but is stored in api db.
    // API Service should be owner of its DB and all the migrations for other
    // services have to be stored in API source only
    // Entity and Business logic is part of another codebase
    const NODAL_STATEMENT       = 'nodal_statements';

    // organization roles permissions
    const ORG                   = 'orgs';
    const ROLE                  = 'roles';
    const ADMIN                 = 'admins';
    const ROLE_MAP              = 'role_map';
    const GROUP_MAP             = 'group_map';
    const GROUP                 = 'org_groups';
    const PERMISSION            = 'permissions';
    const ADMIN_LEAD            = 'admin_leads';
    const MERCHANT_MAP          = 'merchant_map';
    const ORG_HOSTNAME          = 'org_hostname';
    const ADMIN_TOKEN           = 'admin_tokens';
    const ORG_FIELD_MAP         = 'org_field_map';
    const PERMISSION_MAP        = 'permission_map';
    const LOGIN_ATTEMPT         = 'login_attempts';

    // Mapping auditors to entities for a generic use case
    const ADMIN_AUDIT_MAP       = 'admin_audit_map';

    // Workflows
    const WORKFLOW              = 'workflows';
    const ACTION_STATE          = 'action_state';
    const WORKFLOW_STEP         = 'workflow_steps';
    const ACTION_COMMENT        = 'action_comments';
    const WORKFLOW_ACTION       = 'workflow_actions';
    //
    // Currently constants comment and state points to same table
    // as action_comment and action_state but later we plan to rename
    // the table and drop usage of formers.
    //
    const COMMENT               = 'action_comments';
    const STATE                 = 'action_state';
    const ACTION_CHECKER        = 'action_checker';
    const WORKFLOW_PERMISSION   = 'workflow_permissions';
    const STATE_REASON          = 'action_state_reasons';

    // Gateway related
    const ISG                   = 'isg';
    const EBS                   = 'ebs';
    const UPI                   = 'upi';
    const AEPS                  = 'aeps';
    const ATOM                  = 'atom';
    const HDFC                  = 'hdfc';
    const MIGS                  = 'axis';
    const MPI                   = 'blade';
    const ENACH                 = 'enach';
    const PAYTM                 = 'paytm';
    const WALLET                = 'wallet';
    const MOZART                = 'mozart';
    const HITACHI               = 'hitachi';
    const BILLDESK              = 'billdesk';
    const MOBIKWIK              = 'mobikwik';
    const CARD_FSS              = 'card_fss';
    const WORLDLINE             = 'worldline';
    const PAYSECURE             = 'paysecure';
    const NETBANKING            = 'netbanking';
    const FIRST_DATA            = 'first_data';
    const CYBERSOURCE           = 'cybersource';
    const CARDLESS_EMI          = 'cardless_emi';

    // Upi Related
    // UPI Metadata entity is now pointing to a new temporary table
    // https://razorpay.slack.com/archives/C3BPZHG8P/p1594905339294000
    const UPI_METADATA          = 'upi_metadata_new';

    // Sessions table
    const SESSION               = 'sessions';

    // Internal Purposes
    const CREDITS               = 'credits';
    const CREDIT_BALANCE        = 'credit_balance';

    const NODAL_BENEFICIARIES    = 'nodal_beneficiaries';

    const GATEWAY_DOWNTIME          = 'gateway_downtimes';
    const GATEWAY_DOWNTIME_ARCHIVE  = 'gateway_downtimes_archive';
    const PAYMENT_DOWNTIME          = 'payment_downtimes';
    const TERMINAL_ACTION           = 'terminal_action_logs';

    const GATEWAY_RULE          = 'gateway_rules';
    const GATEWAY_FILE          = 'gateway_files';

    // Payment Analytics
    const PAYMENT_ANALYTICS     = 'payment_analytics';

    // Tax and Tax Groups

    const TAX                   = 'taxes';
    const TAX_GROUP             = 'tax_groups';
    const TAX_GROUP_TAX_MAP     = 'tax_group_tax_map';

    const SETTINGS              = 'settings';

    // Banking Accounts Tables
    const EXTERNAL                             = 'external';
    const BANKING_ACCOUNT                      = 'banking_accounts';
    // Banking Account Bank LMS is public exposed version of Banking Account , ref /Models/BankingAccount/BankLms.
    const BANKING_ACCOUNT_BANK_LMS             = 'banking_accounts';
    const BANKING_ACCOUNT_STATE                = 'banking_account_state';
    const BANKING_ACCOUNT_DETAIL               = 'banking_account_details';
    const BANKING_ACCOUNT_STATEMENT            = 'banking_account_statement';
    const BANKING_ACCOUNT_COMMENT              = 'banking_account_comments';
    const BANKING_ACCOUNT_CALL_LOG             = 'banking_account_call_log';
    const BANKING_ACCOUNT_ACTIVATION_DETAIL    = 'banking_account_activation_details';
    const BANKING_ACCOUNT_STATEMENT_DETAILS    = 'banking_account_statement_details';
    const BANKING_ACCOUNT_STATEMENT_POOL_RBL   = 'banking_account_statement_pool_rbl';
    const BANKING_ACCOUNT_STATEMENT_POOL_ICICI = 'banking_account_statement_pool_icici';
    const INTERNAL                             = 'internal_entity';

    // P2P Service Tables
    const P2P_VPA               = 'p2p_vpa';
    const P2P_BANK              = 'p2p_banks';
    const P2P_HANDLE            = 'p2p_handles';
    const P2P_DEVICE            = 'p2p_devices';
    const P2P_CLIENT            = 'p2p_clients';
    const P2P_CONCERN           = 'p2p_concerns';
    const P2P_TRANSACTION       = 'p2p_transactions';
    const P2P_DEVICE_TOKEN      = 'p2p_device_tokens';
    const P2P_BANK_ACCOUNT      = 'p2p_bank_accounts';
    const P2P_BENEFICIARY       = 'p2p_beneficiaries';
    const P2P_REGISTER_TOKEN    = 'p2p_register_tokens';
    const P2P_UPI_TRANSACTION   = 'p2p_upi_transactions';
    const P2P_MANDATE           = 'p2p_mandates';
    const P2P_UPI_MANDATE       = 'p2p_upi_mandates';
    const P2P_MANDATE_PATCH     = 'p2p_mandate_patch';
    const P2P_BLACKLIST         = 'p2p_blacklist';

    const SETTLEMENT_ONDEMAND          = 'settlement_ondemands';
    const SETTLEMENT_ONDEMAND_PAYOUT   = 'settlement_ondemand_payouts';

    // Payments UPI Service, Store in different database
    const PAYMENTS_UPI_VPA              = 'vpas';
    const PAYMENTS_UPI_BANK_ACCOUNT     = 'bank_accounts';
    const PAYMENTS_UPI_VPA_BANK_ACCOUNT = 'vpas_bank_accounts';

    const SETTLEMENT_ONDEMAND_FUND_ACCOUNT   = 'settlement_ondemand_fund_accounts';
    const SETTLEMENT_ONDEMAND_TRANSFER       = 'settlement_ondemand_transfer';
    const SETTLEMENT_ONDEMAND_BULK           = 'settlement_ondemand_bulk';
    const SETTLEMENT_ONDEMAND_ATTEMPT        = 'settlement_ondemand_attempts';
    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG = 'settlement_ondemand_feature_configs';
    const EARLY_SETTLEMENT_FEATURE_PERIOD    = 'early_settlement_feature_period';

    const CREDITNOTE           = 'creditnote';

    const CREDITNOTE_INVOICE   = 'creditnote_invoices';

    const INVOICE_REMINDER   = 'invoice_reminders';

    const MERCHANT_REMINDERS   = 'merchant_reminders';

    const WORKFLOW_PAYOUT_AMOUNT_RULES = 'workflow_payout_amount_rules';

    const OPTIONS              = 'options';

    const CONFIG               = 'payment_configs';

    const UPI_MANDATE          = 'upi_mandates';

    const CARD_MANDATE              = 'card_mandates';

    const CARD_MANDATE_NOTIFICATION = 'card_mandate_notifications';

    const PROMOTION_EVENT      = 'promotions_events';
    //Payout downtimes table
    const PAYOUT_DOWNTIMES       = 'payout_downtimes';
    const FUND_LOADING_DOWNTIMES = 'fund_loading_downtimes';

    const WORKFLOW_CONFIG      = 'workflow_config';
    const WORKFLOW_ENTITY_MAP  = 'workflow_entity_map';
    const WORKFLOW_STATE_MAP   = 'workflow_state_map';

    //App framework
    const APPLICATION                      = 'application';
    const APPLICATION_MAPPING              = 'application_mapping';
    const APPLICATION_MERCHANT_MAPPING     = 'application_merchant_mapping';
    const APPLICATION_MERCHANT_TAG         = 'application_merchant_tag';

    const BVS_VALIDATION  = 'bvs_validation';

    const REWARD           = 'rewards';
    const MERCHANT_REWARD  = 'merchant_rewards';
    const REWARD_COUPON    = 'reward_coupons';

    // Checkout Entities
    const CHECKOUT_ORDER = 'checkout_orders';

    // Razorpay Trusted Badge
    const TRUSTED_BADGE          = 'trusted_badge';
    const TRUSTED_BADGE_HISTORY  = 'trusted_badge_history';

    // this table doesn't exist in api db. this constant is required for transaction queries
    const CREDIT_REPAYMENT = 'credit_repayment';

    const CAPITAL_TRANSACTION = 'capital_transaction';
    const REPAYMENT_BREAKUP   = 'repayment_breakup';
    const INSTALLMENT         = 'installment';
    const CHARGE              = 'charge';

    // API Request Log
    const REQUEST_LOG = 'request_logs';

    // AppStore
    const APP_STORE = 'app_store';

    // NPS Survey
    const SURVEY            = 'survey';
    const SURVEY_TRACKER    = 'survey_tracker';
    const SURVEY_RESPONSE   = 'survey_response';

    // X WalletAccounts
    const WALLET_ACCOUNT    = 'wallet_accounts';

    // Account management & config
    const MERCHANT_PRODUCTS         = 'merchant_products';
    const MERCHANT_PRODUCT_REQUEST  = 'merchant_product_requests';
    const TNC_MAP                   = 'tnc_map';
    const MERCHANT_TNC_ACCEPTANCE   = 'merchant_tnc_acceptance';

    // International Enablement
    const INTERNATIONAL_ENABLEMENT_DETAIL   = 'international_enablement_details';
    const INTERNATIONAL_ENABLEMENT_DOCUMENT = 'international_enablement_documents';

    // Merchant Risk Notes
    const MERCHANT_RISK_NOTE = 'merchant_risk_notes';

    // Merchant configs for 1cc
    const MERCHANT_SLABS = 'merchant_slabs';
    const MERCHANT_1CC_CONFIGS = 'merchant_1cc_configs';

    // Merchant comments for 1cc
    const MERCHANT_1CC_COMMENTS =  'merchant_1cc_comments';

    const TOKENISED_IIN = 'tokenised_iins';
    const MERCHANT_1CC_AUTH_CONFIGS = 'merchant_1cc_auth_configs';

    // Ledger
    const JOURNAL           = 'journal';
    const LEDGER_ACCOUNT    = 'accounts';
    const ACCOUNT_DETAIL    = 'account_details';
    const LEDGER_ENTRY      = 'ledger_entries';
    const LEDGER_STATEMENT  = 'ledger_entries';

    // Payment Fraud Entity
    const PAYMENT_FRAUD = 'payment_fraud';
    // Channel health Entity
    const PARTNER_BANK_HEALTH = 'partner_bank_health';

    // Nocode slug management table
    const NOCODE_CUSTOM_URL = 'nocode_custom_urls';

    const MERCHANT_OTP_VERIFICATION_LOGS =  'merchant_otp_verification_logs';

    // CAC tables
    const ACCESS_CONTROL_PRIVILEGES     = 'access_control_privileges';
    const ACCESS_CONTROL_ROLES          = 'access_control_roles';
    const ACCESS_POLICY_AUTHZ_ROLES_MAP = 'access_policy_authz_roles_map';
    const ROLE_ACCESS_POLICY_MAP        = 'role_access_policy_map';
    const ACCESS_CONTROL_HISTORY_LOGS   = 'access_control_history_logs';

    const LINKED_ACCOUNT_REFERENCE_DATA = 'linked_account_reference_data';

    const CLARIFICATION_DETAIL          = 'clarification_details';

    protected static $entityToTableMap = [
        Entity::AXIS_MIGS                          => self::MIGS,
        Entity::AXIS_GENIUS                        => self::MIGS,
        Entity::AMEX                               => self::MIGS,
        Entity::WALLET_FREECHARGE                  => self::WALLET,
        Entity::WALLET_OLAMONEY                    => self::WALLET,
        Entity::WALLET_AIRTELMONEY                 => self::WALLET,
        Entity::WALLET_PAYUMONEY                   => self::WALLET,
        Entity::MPI_BLADE                          => self::BLADE,
        Entity::MPI_ENSTAGE                        => self::BLADE,
        Entity::PAYMENT_DOWNTIME                   => self::PAYMENT_DOWNTIME,
        Entity::SETTLEMENT_ONDEMAND_FUND_ACCOUNT   => self::SETTLEMENT_ONDEMAND_FUND_ACCOUNT,
        Entity::SETTLEMENT_ONDEMAND                => self::SETTLEMENT_ONDEMAND,
        Entity::SETTLEMENT_ONDEMAND_PAYOUT         => self::SETTLEMENT_ONDEMAND_PAYOUT,
        Entity::SETTLEMENT_ONDEMAND_BULK           => self::SETTLEMENT_ONDEMAND_BULK,
        Entity::SETTLEMENT_ONDEMAND_TRANSFER       => self::SETTLEMENT_ONDEMAND_TRANSFER,
        Entity::SETTLEMENT_ONDEMAND_ATTEMPT        => self::SETTLEMENT_ONDEMAND_ATTEMPT,
        Entity::SETTLEMENT_ONDEMAND_FEATURE_CONFIG => self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG,
        Entity::EARLY_SETTLEMENT_FEATURE_PERIOD    => self::EARLY_SETTLEMENT_FEATURE_PERIOD,
        Entity::MERCHANT_SLABS                     => self::MERCHANT_SLABS,
        Entity::MERCHANT_1CC_CONFIGS               => self::MERCHANT_1CC_CONFIGS,
        Entity::DIRECT_ACCOUNT_STATEMENT           => self::BANKING_ACCOUNT_STATEMENT,
        Entity::ROLES                              => self::ACCESS_CONTROL_ROLES,
    ];

    public static function getTableNameForEntity(string $entity)
    {
        Entity::validateEntityOrFail($entity);

        if (isset(self::$entityToTableMap[$entity]))
        {
            return self::$entityToTableMap[$entity];
        }

        $tableName = constant(Table::class . '::' . strtoupper($entity));

        if (self::isLedgerTableName($tableName) === true)
        {
            return self::getTableNameForLedgerTable($tableName);
        }

        return $tableName;
    }

    /**
     * Ledger services' tables data is going to come only from TiDB, so appending tidb db name before table name
     *
     * @param string $tableName
     * @return string
     */
    public static function getTableNameForLedgerTable(string $tableName)
    {
        $app = App::getFacadeRoot();

        $mode = $app['rzp.mode'];

        $config = $app['config']->get('applications.ledger');

        $tidbName = ($mode === Mode::LIVE) ? $config['tidb_db_name']['live'] : $config['tidb_db_name']['test'];

        return $tidbName . '.' . $tableName;
    }

    public static function isLedgerTableName($tableName)
    {
        if (in_array($tableName, [self::JOURNAL, self::ACCOUNT_DETAIL, self::LEDGER_ENTRY, self::LEDGER_ACCOUNT]) === true)
        {
            return true;
        }
    }
}
