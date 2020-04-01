<?php

namespace RZP\Constants;

class Table
{
    // Core entities
    const P2P                   = 'p2p';
    const VPA                   = 'vpas';
    const IIN                   = 'iins';
    const MPAN                  = 'mpan';
    const KEY                   = 'keys';
    const RISK                  = 'risk';
    const PLAN                  = 'plans';
    const CARD                  = 'cards';
    const ITEM                  = 'items';
    const USER                  = 'users';
    const OFFER                 = 'offers';
    const ORDER                 = 'orders';
    const TOKEN                 = 'tokens';
    const BLADE                 = 'blade';
    const ADDON                 = 'addons';
    const GEO_IP                = 'geo_ips';
    const COUPON                = 'coupons';
    const BATCH                 = 'batches';
    const DEVICE                = 'devices';
    const PAYOUT                = 'payouts';
    const REFUND                = 'refunds';
    const REPORT                = 'reports';
    const CONTACT               = 'contacts';
    const QR_CODE               = 'qr_code';
    const BALANCE               = 'balance';
    const PRICING               = 'pricing';
    const INVOICE               = 'invoices';
    const PAYMENT               = 'payments';
    const WEBHOOK               = 'webhooks';
    const FEATURE               = 'features';
    const DISPUTE               = 'disputes';
    const ADDRESS               = 'addresses';
    const DISCOUNT              = 'discounts';
    const MERCHANT              = 'merchants';
    const COMMISSION            = 'commissions';
    const PAYOUT_LINK           = 'payout_links';
    const LEGAL_ENTITY          = 'legal_entity';
    const FUND_ACCOUNT          = 'fund_accounts';
    const PAYMENT_LINK          = 'payment_links';
    const BALANCE_CONFIG        = 'balance_config';
    const ENTITY_ORIGIN         = 'entity_origins';
    const OFFLINE_DEVICE        = 'offline_devices';
    const IDEMPOTENCY_KEY       = 'idempotency_keys';
    const PAYMENT_PAGE_ITEM     = 'payment_page_items';

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
    const SETTLEMENT_BUCKET         = 'settlement_bucket';
    const SETTLEMENT_TRANSFER       = 'settlement_transfer';
    const SETTLEMENT_DESTINATION    = 'settlement_destination';

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
    const CUSTOMER_BALANCE           = 'customer_balance';
    const MERCHANT_INVOICE           = 'merchant_invoice';
    const MERCHANT_REQUEST           = 'merchant_requests';
    const MERCHANT_TERMINAL          = 'merchant_terminal';
    const BATCH_FUND_TRANSFER        = 'daily_settlements';
    const MERCHANT_PROMOTION         = 'merchant_promotion';
    const COMMISSION_INVOICE         = 'commission_invoice';
    const CREDIT_TRANSACTION         = 'credit_transaction';
    const SETTLEMENT_DETAILS         = 'settlement_details';
    const MERCHANT_DOCUMENT          = 'merchant_documents';
    const MERCHANT_EMI_PLANS         = 'merchant_emi_plans';
    const NODAL_BENEFICIARY          = 'nodal_beneficiaries';
    const MERCHANT_ACCESS_MAP        = 'merchant_access_map';
    const PAPER_MANDATE_UPLOAD       = 'paper_mandate_uploads';
    const CUSTOMER_TRANSACTION       = 'customer_transactions';
    const FUND_TRANSFER_ATTEMPT      = 'fund_transfer_attempts';
    const MERCHANT_INHERITANCE_MAP   = 'merchant_inheritance_map';
    const FUND_ACCOUNT_VALIDATION    = 'fund_account_validations';
    const SUBSCRIPTION_REGISTRATION  = 'subscription_registrations';
    const MERCHANT_FRESHDESK_TICKETS = 'merchant_freshdesk_tickets';
    const TERMINAL_ONBOARDING_DETAIL = 'terminal_onboarding_details';

    const D2C_BUREAU_DETAIL         = 'd2c_bureau_details';
    const D2C_BUREAU_REPORT         = 'd2c_bureau_reports';
    const PAYMENT_META              = 'payment_meta';

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
    const UPI_METADATA          = 'upi_metadata';

    // Sessions table
    const SESSION               = 'sessions';

    // Internal Purposes
    const CREDITS               = 'credits';

    const NODAL_BENEFICIARIES    = 'nodal_beneficiaries';

    const GATEWAY_DOWNTIME      = 'gateway_downtimes';
    const PAYMENT_DOWNTIME      = 'payment_downtimes';
    const TERMINAL_ACTION       = 'terminal_action_logs';

    const GATEWAY_RULE          = 'gateway_rules';
    const GATEWAY_FILE          = 'gateway_files';

    // Payment Analytics
    const PAYMENT_ANALYTICS     = 'payment_analytics';
    const TERMINAL_ANALYTICS    = 'terminal_analytics';

    // Tax and Tax Groups

    const TAX                   = 'taxes';
    const TAX_GROUP             = 'tax_groups';
    const TAX_GROUP_TAX_MAP     = 'tax_group_tax_map';

    const SETTING               = 'settings';

    // Banking Accounts Tables
    const EXTERNAL                        = 'external';
    const BANKING_ACCOUNT                 = 'banking_accounts';
    const BANKING_ACCOUNT_STATE           = 'banking_account_state';
    const BANKING_ACCOUNT_DETAIL          = 'banking_account_details';
    const BANKING_ACCOUNT_STATEMENT       = 'banking_account_statement';

    // P2P Service Tables
    const P2P_VPA               = 'p2p_vpa';
    const P2P_BANK              = 'p2p_banks';
    const P2P_HANDLE            = 'p2p_handles';
    const P2P_DEVICE            = 'p2p_devices';
    const P2P_CONCERN           = 'p2p_concerns';
    const P2P_TRANSACTION       = 'p2p_transactions';
    const P2P_DEVICE_TOKEN      = 'p2p_device_tokens';
    const P2P_BANK_ACCOUNT      = 'p2p_bank_accounts';
    const P2P_BENEFICIARY       = 'p2p_beneficiaries';
    const P2P_REGISTER_TOKEN    = 'p2p_register_tokens';
    const P2P_UPI_TRANSACTION   = 'p2p_upi_transactions';

    // Payments UPI Service, Store in different database
    const PAYMENTS_UPI_VPA              = 'vpas';
    const PAYMENTS_UPI_BANK_ACCOUNT     = 'bank_accounts';
    const PAYMENTS_UPI_VPA_BANK_ACCOUNT = 'vpas_bank_accounts';

    const CREDITNOTE           = 'creditnote';

    const CREDITNOTE_INVOICE   = 'creditnote_invoices';

    const INVOICE_REMINDER   = 'invoice_reminders';

    const WORKFLOW_PAYOUT_AMOUNT_RULES = 'workflow_payout_amount_rules';

    const OPTIONS              = 'options';

    const CONFIG               = 'payment_configs';

    const UPI_MANDATE          = 'upi_mandates';

    protected static $entityToTableMap = [
        Entity::AXIS_MIGS           => self::MIGS,
        Entity::AXIS_GENIUS         => self::MIGS,
        Entity::AMEX                => self::MIGS,
        Entity::WALLET_FREECHARGE   => self::WALLET,
        Entity::WALLET_OLAMONEY     => self::WALLET,
        Entity::WALLET_AIRTELMONEY  => self::WALLET,
        Entity::WALLET_PAYUMONEY    => self::WALLET,
        Entity::MPI_BLADE           => self::BLADE,
        Entity::MPI_ENSTAGE         => self::BLADE,
        Entity::PAYMENT_DOWNTIME    => self::PAYMENT_DOWNTIME,
    ];

    public static function getTableNameForEntity(string $entity)
    {
        Entity::validateEntityOrFail($entity);

        if (isset(self::$entityToTableMap[$entity]))
        {
            return self::$entityToTableMap[$entity];
        }

        return constant(Table::class . '::' . strtoupper($entity));
    }
}
