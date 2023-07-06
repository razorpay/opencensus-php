<?php

namespace RZP\Constants;

use App;
use Trace;

use RZP\Models;
use RZP\Gateway;
use RZP\Exception;
use RZP\Base\Fetch;
use RZP\Models\Card;
use RZP\Trace\TraceCode;
use RZP\Models\Base\Observer as BaseObserver;
use RZP\Models\Base\QueryCache\Constants as QueryCacheConstants;

class Entity
{
    //
    // Core entities
    //
    const IIN                        = 'iin';
    const KEY                        = 'key';
    const P2P                        = 'p2p';
    const VPA                        = 'vpa';
    const MPAN                       = 'mpan';
    const CARD                       = 'card';
    const PLAN                       = 'plan';
    const ITEM                       = 'item';
    const USER                       = 'user';
    const RISK                       = 'risk';
    const ADDON                      = 'addon';
    const BATCH                      = 'batch';
    const OFFER                      = 'offer';
    const ORDER                      = 'order';
    const TOKEN                      = 'token';
    const GEO_IP                     = 'geo_ip';
    const COUPON                     = 'coupon';
    const DEVICE                     = 'device';
    const PAYOUT                     = 'payout';
    const PAYOUT_OUTBOX              = 'payout_outbox';
    const REFUND                     = 'refund';
    const REPORT                     = 'report';
    const COUNTER                    = 'counter';
    const CONTACT                    = 'contact';
    const CORPORATE_CARD             = 'corporate_card';
    const DISPUTE                    = 'dispute';
    const DISPUTE_EVIDENCE           = 'dispute_evidence';
    const DISPUTE_EVIDENCE_DOCUMENT  = 'dispute_evidence_document';
    const DEBIT_NOTE                 = 'debit_note';
    const DEBIT_NOTE_DETAIL          = 'debit_note_detail';
    const ADDRESS                    = 'address';
    const BALANCE                    = 'balance';
    const CREDITS                    = 'credits';
    const FEATURE                    = 'feature';
    const INVOICE                    = 'invoice';
    const METHODS                    = 'methods';
    const PAYMENT                    = 'payment';
    const PRICING                    = 'pricing';
    const PRODUCT                    = 'product';
    const WEBHOOK                    = 'webhook';
    const QR_CODE                    = 'qr_code';
    const QR_CODE_CONFIG             = 'qr_code_config';
    const QR_PAYMENT_REQUEST         = 'qr_payment_request';
    const QR_PAYMENT                 = 'qr_payment';
    const OPTIONS                    = 'options';
    const ACCOUNT                    = 'account';
    const DISCOUNT                   = 'discount';
    const EMI_PLAN                   = 'emi_plan';
    const CUSTOMER                   = 'customer';
    const CAPITAL_VIRTUAL_CARDS      = 'capital_virtual_cards';
    const MERCHANT                   = 'merchant';
    const REVERSAL                   = 'reversal';
    const SCHEDULE                   = 'schedule';
    const TERMINAL                   = 'terminal';
    const TRANSFER                   = 'transfer';
    const EXTERNAL                   = 'external';
    const INTERNAL                   = 'internal';
    const REFERRALS                  = 'referrals';
    const STATEMENT                  = 'statement';
    const BHARAT_QR                  = 'bharat_qr';
    const PROMOTION                  = 'promotion';
    const LINE_ITEM                  = 'line_item';
    const APP_TOKEN                  = 'app_token';
    const AUTH_TOKEN                 = 'auth_token';
    const INVITATION                 = 'invitation';
    const ADJUSTMENT                 = 'adjustment';
    const CREDIT_TRANSFER            = 'credit_transfer';
    const CREDITNOTE                 = 'creditnote';
    const FILE_STORE                 = 'file_store';
    const SETTLEMENT                 = 'settlement';
    const TRANSACTION                = 'transaction';
    const FEE_BREAKUP                = 'fee_breakup';
    const UPI_MANDATE                = 'upi_mandate';
    const CARD_MANDATE               = 'card_mandate';
    const CARD_MANDATE_NOTIFICATION  = 'card_mandate_notification';
    const PAYOUT_LINK                = 'payout_link';
    const PAYOUT_SOURCE              = 'payout_source';
    const SETTINGS                   = 'settings';
    const FEE_RECOVERY               = 'fee_recovery';
    const SUB_VIRTUAL_ACCOUNT        = 'sub_virtual_account';
    const LEGAL_ENTITY               = 'legal_entity';
    const PAYMENT_LINK               = 'payment_link';
    const NOCODE_CUSTOM_URL          = 'nocode_custom_url';
    const PAYMENT_PAGE               = 'payment_page';
    const GATEWAY_RULE               = 'gateway_rule';
    const GATEWAY_FILE               = 'gateway_file';
    const BANK_ACCOUNT               = 'bank_account';
    const FILE_HANDLER               = 'file_handler';
    const ENTITY_OFFER               = 'entity_offer';
    const FUND_ACCOUNT               = 'fund_account';
    const SUBSCRIPTION               = 'subscription';
    const UPI_TRANSFER               = 'upi_transfer';
    const UPI_METADATA               = 'upi_metadata';
    const PAPER_MANDATE              = 'paper_mandate';
    const ENTITY_ORIGIN              = 'entity_origin';
    const GATEWAY_TOKEN              = 'gateway_token';
    const BANK_TRANSFER              = 'bank_transfer';
    const OFFLINE_CHALLAN            = 'offline_challan';
    const OFFLINE_PAYMENT            = 'offline_payment';
    const SCHEDULE_TASK              = 'schedule_task';
    const LINE_ITEM_TAX              = 'line_item_tax';
    const MERCHANT_USER              = 'merchant_user';
    const BALANCE_CONFIG             = 'balance_config';
    const MERCHANT_EMAIL             = 'merchant_email';
    const PARTNER_CONFIG             = 'partner_config';
    const OFFLINE_DEVICE             = 'offline_device';
    const DISPUTE_REASON             = 'dispute_reason';
    const IDEMPOTENCY_KEY            = 'idempotency_key';
    const NODAL_STATEMENT            = 'nodal_statement';
    const VIRTUAL_ACCOUNT            = 'virtual_account';
    const TERMINAL_ACTION            = 'terminal_action';
    const PAYMENT_DOWNTIME           = 'payment.downtime';
    const MERCHANT_REQUEST           = 'merchant_request';
    const TRUECALLER_AUTH_REQUEST    = 'truecaller_auth_request';
    const CUSTOMER_BALANCE           = 'customer_balance';
    const GATEWAY_DOWNTIME           = 'gateway_downtime';
    const GATEWAY_DOWNTIME_ARCHIVE   = 'gateway_downtime_archive';
    const MERCHANT_INVOICE           = 'merchant_invoice';
    const MERCHANT_E_INVOICE         = 'merchant_e_invoice';
    const INVOICE_REMINDER           = 'invoice_reminder';
    const MERCHANT_REMINDERS         = 'merchant_reminders';
    const NODAL_BENEFICIARY          = 'nodal_beneficiary';
    const PAYMENT_PAGE_ITEM          = 'payment_page_item';
    const PAYMENT_PAGE_RECORD        = 'payment_page_record';
    const PAYMENT_ANALYTICS          = 'payment_analytics';
    const D2C_BUREAU_DETAIL          = 'd2c_bureau_detail';
    const D2C_BUREAU_REPORT          = 'd2c_bureau_report';
    const SETTLEMENT_BUCKET          = 'settlement_bucket';
    const SETTLEMENT_DETAILS         = 'settlement_details';
    const CREDITNOTE_INVOICE         = 'creditnote_invoice';
    const MERCHANT_PROMOTION         = 'merchant_promotion';
    const MERCHANT_PRODUCT           = 'merchant_product';
    const MERCHANT_PRODUCT_REQUEST   = 'merchant_product_request';
    const TNC_MAP                    = 'tnc_map';
    const MERCHANT_TNC_ACCEPTANCE    = 'merchant_tnc_acceptance';
    const CREDIT_TRANSACTION         = 'credit_transaction';
    const CREDIT_BALANCE             = 'credit_balance';
    const MERCHANT_EMI_PLANS         = 'merchant_emi_plans';
    const SETTING                    = 'setting';
    const COMMISSION_INVOICE         = 'commission_invoice';
    const SETTLEMENT_TRANSFER        = 'settlement_transfer';
    const MERCHANT_ACCESS_MAP        = 'merchant_access_map';
    const PARTNER_KYC_ACCESS_STATE   = 'partner_kyc_access_state';
    const MERCHANT_APPLICATION       = 'merchant_application';
    const BATCH_FUND_TRANSFER        = 'batch_fund_transfer';
    const VIRTUAL_ACCOUNT_TPV        = 'virtual_account_tpv';
    const BANKING_ACCOUNT_TPV        = 'banking_account_tpv';
    const PAPER_MANDATE_UPLOAD       = 'paper_mandate_upload';
    const CUSTOMER_TRANSACTION       = 'customer_transaction';
    const UPI_TRANSFER_REQUEST       = 'upi_transfer_request';
    const BANKING_ACCOUNT_COMMENT    = 'banking_account_comment';
    const ORDER_META                 = 'order_meta';
    const TOKENISED_IIN              = 'tokenised_iin';
    const TOKEN_CARD                 = 'token_card';
    const LEDGER_OUTBOX              = 'ledger_outbox';
    const ORDER_OUTBOX               = 'order_outbox';

    const RAW_ADDRESS                = 'raw_address';
    const FUND_TRANSFER_ATTEMPT      = 'fund_transfer_attempt';
    const BANK_TRANSFER_HISTORY      = 'bank_transfer_history';
    const BANK_TRANSFER_REQUEST      = 'bank_transfer_request';
    const SETTLEMENT_DESTINATION     = 'settlement_destination';

    const FUND_ACCOUNT_VALIDATION    = 'fund_account_validation';
    const MERCHANT_INHERITANCE_MAP   = 'merchant_inheritance_map';
    const SUBSCRIPTION_REGISTRATION  = 'subscription_registration';

    const MERCHANT_FRESHDESK_TICKETS   = 'merchant_freshdesk_tickets';
    const MERCHANT_ATTRIBUTE           = 'merchant_attribute';
    const PAYMENT_META                 = 'payment_meta';
    const LOW_BALANCE_CONFIG           = 'low_balance_config';
    const MERCHANT_NOTIFICATION_CONFIG = 'merchant_notification_config';
    const VIRTUAL_ACCOUNT_PRODUCTS     = 'virtual_account_products';
    const SUB_BALANCE_MAP              = 'sub_balance_map';

    const ORG_FEATURE                  = 'org_feature';

    const PAYOUTS_INTERMEDIATE_TRANSACTIONS = 'payouts_intermediate_transactions';

    const WORKFLOW_CONFIG            = 'workflow_config';
    const WORKFLOW_ENTITY_MAP        = 'workflow_entity_map';
    const WORKFLOW_STATE_MAP         = 'workflow_state_map';

    //App framework
    const APPLICATION                            = 'application';
    const APPLICATION_MAPPING                    = 'application_mapping';
    const APPLICATION_MERCHANT_MAPPING           = 'application_merchant_mapping';
    const APPLICATION_MERCHANT_TAG               = 'application_merchant_tag';

    //ondemand
    const SETTLEMENT_ONDEMAND_FUND_ACCOUNT   = 'settlement.ondemand_fund_account';
    const SETTLEMENT_ONDEMAND                = 'settlement.ondemand';
    const SETTLEMENT_ONDEMAND_PAYOUT         = 'settlement.ondemand_payout';
    const SETTLEMENT_ONDEMAND_BULK           = 'settlement.ondemand.bulk';
    const SETTLEMENT_ONDEMAND_TRANSFER       = 'settlement.ondemand.transfer';
    const SETTLEMENT_ONDEMAND_ATTEMPT        = 'settlement.ondemand.attempt';
    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG = 'settlement.ondemand.feature_config';

    const EARLY_SETTLEMENT_FEATURE_PERIOD    = 'early_settlement_feature_period';

    const VIRTUAL_VPA_PREFIX            = 'virtual_vpa_prefix';
    const VIRTUAL_VPA_PREFIX_HISTORY    = 'virtual_vpa_prefix_history';

    const STORE = 'store';

    // Banking Account Entities
    const BANKING_ACCOUNT                   = 'banking_account';
    const BANKING_ACCOUNT_STATE             = 'banking_account_state';
    const BANKING_ACCOUNT_DETAIL            = 'banking_account_detail';
    const BANKING_ACCOUNT_ACTIVATION_DETAIL = 'banking_account_activation_detail';
    const BANKING_ACCOUNT_CALL_LOG          = 'banking_account_call_log';
    const BANKING_ACCOUNT_BANK_LMS          = 'banking_account_bank_lms';

    // Banking Account Statement Entities
    const BANKING_ACCOUNT_STATEMENT            = 'banking_account_statement';
    const BANKING_ACCOUNT_STATEMENT_DETAILS    = 'banking_account_statement_details';
    const BANKING_ACCOUNT_STATEMENT_POOL_RBL   = 'banking_account_statement_pool_rbl';
    const BANKING_ACCOUNT_STATEMENT_POOL_ICICI = 'banking_account_statement_pool_icici';

    // heimdall
    const ORG                   = 'org';
    const ROLE                  = 'role';
    const ADMIN                 = 'admin';
    const GROUP                 = 'group';
    const PERMISSION            = 'permission';
    const ADMIN_LEAD            = 'admin_lead';
    const ADMIN_TOKEN           = 'admin_token';
    const ORG_HOSTNAME          = 'org_hostname';
    const ORG_FIELD_MAP         = 'org_field_map';
    const ADMIN_REPORT          = 'admin_report';

    // free payout migration
    const ENABLE            = 'enable';
    const DISABLE           = 'disable';
    const ACTION            = 'action';
    const RESPONSE          = 'response';
    const FEATURE_NAME      = 'feature_name';
    const BALANCE_TYPE      = 'balance_type';
    const COUNTER_MIGRATED  = 'counter_migrated';
    const SETTINGS_MIGRATED = 'settings_migrated';
    const COUNTERS_ROLLBACK = 'counters_rollback';
    const SETTINGS_ROLLBACK = 'settings_rollback';
    const CREATED_AT        = 'created_at';
    const UPDATED_AT        = 'updated_at';
    const VALUE             = 'value';

    // free payout migration - counter attributes
    const COUNTER_ID         = 'counter_id';
    const COUNTER_CREATED_AT = 'counter_created_at';

    // free payout migration - free payout count - settings attributes
    const FREE_PAYOUT_COUNT_CREATED_AT  = 'free_payout_count_created_at';

    // free payout migration - free payout supported modes - settings attributes
    const FREE_PAYOUT_SUPPORTED_MODES_CREATED_AT  = 'free_payout_supported_modes_created_at';

    //
    // Workflow Entities
    //
    const WORKFLOW                      = 'workflow';
    const ACTION_STATE                  = 'action_state';
    const WORKFLOW_STEP                 = 'workflow_step';
    const ACTION_COMMENT                = 'action_comment';
    const ACTION_CHECKER                = 'action_checker';
    const WORKFLOW_ACTION               = 'workflow_action';
    const BULK_WORKFLOW_ACTION          = 'bulk_workflow_action';
    const WORKFLOW_PAYOUT_AMOUNT_RULES  = 'workflow_payout_amount_rules';
    const PAYMENT_LIMIT                 = 'payment_limit';

    // Generic comment and state entities
    const STATE                 = 'state';
    const COMMENT               = 'comment';
    const STATE_REASON          = 'state_reason';

    //
    // Gateway entities
    const EBS                    = 'ebs';
    const UPI                    = 'upi';
    const MPI                    = 'mpi';
    const ISG                    = 'isg';
    const CRED                   = 'cred';
    const AEPS                   = 'aeps';
    const AMEX                   = 'amex';
    const HDFC                   = 'hdfc';
    const ATOM                   = 'atom';
    const PAYU                   = 'payu';
    const OPTIMIZER_RAZORPAY     = 'optimizer_razorpay';
    const BT_RBL                 = 'bt_rbl';
    const CASHFREE               = 'cashfree';
    const ZAAKPAY                = 'zaakpay';
    const CCAVENUE               = 'ccavenue';
    const PINELABS               = 'pinelabs';
    const INGENICO               = 'ingenico';
    const BILLDESK_OPTIMIZER     = 'billdesk_optimizer';
    const ENACH                  = 'enach';
    const SHARP                  = 'sharp';
    const PAYTM                  = 'paytm';
    const MOZART                 = 'mozart';
    const WALLET                 = 'wallet';
    const UPI_SBI                = 'upi_sbi';
    const HITACHI                = 'hitachi';
    const FULCRUM                = "fulcrum";
    const UPI_RBL                = 'upi_rbl';
    const UPI_HULK               = 'upi_hulk';
    const UPI_AXIS               = 'upi_axis';
    const BILLDESK               = 'billdesk';
    const MOBIKWIK               = 'mobikwik';
    const UPI_NPCI               = 'upi_npci';
    const PAYLATER               = 'paylater';
    const UPI_CITI               = 'upi_citi';
    const GETSIMPL               = 'getsimpl';
    const CARD_FSS               = 'card_fss';
    const UPI_ICICI              = 'upi_icici';
    const AXIS_MIGS              = 'axis_migs';
    const AXIS_TOKENHQ           = 'axis_tokenhq';
    const MPI_BLADE              = 'mpi_blade';
    const NACH_CITI              = 'nach_citi';
    const NACH_ICICI             = 'nach_icici';
    const PAYSECURE              = 'paysecure';
    const WORLDLINE              = 'worldline';
    const ENACH_RBL              = 'enach_rbl';
    const NETBANKING             = 'netbanking';
    const AEPS_ICICI             = 'aeps_icici';
    const UPI_AIRTEL             = 'upi_airtel';
    const GOOGLE_PAY             = 'google_pay';
    const UPI_JUSPAY             = 'upi_juspay';
    const FIRST_DATA             = 'first_data';
    const UPI_YESBANK            = 'upi_yesbank';
    const MPI_ENSTAGE            = 'mpi_enstage';
    const AXIS_GENIUS            = 'axis_genius';
    const CYBERSOURCE            = 'cybersource';
    const CARDLESS_EMI           = 'cardless_emi';
    const BAJAJFINSERV           = 'bajajfinserv';
    const UPI_MINDGATE           = 'upi_mindgate';
    const UPI_AXISOLIVE          = 'upi_axisolive';
    const WALLET_MPESA           = 'wallet_mpesa';
    const WALLET_PAYPAL          = 'wallet_paypal';
    const ESIGNER_DIGIO          = 'esigner_digio';
    const NETBANKING_CUB         = 'netbanking_cub';
    const NETBANKING_SIB         = 'netbanking_sib';
    const NETBANKING_SARASWAT    = 'netbanking_saraswat';
    const NETBANKING_CBI         = 'netbanking_cbi';
    const PAYLATER_ICICI         = 'paylater_icici';
    const HDFC_DEBIT_EMI         = 'hdfc_debit_emi';
    const NETBANKING_OBC         = 'netbanking_obc';
    const NETBANKING_RBL         = 'netbanking_rbl';
    const NETBANKING_SBI         = 'netbanking_sbi';
    const NETBANKING_KVB         = 'netbanking_kvb';
    const NETBANKING_SVC         = 'netbanking_svc';
    const NETBANKING_JSB         = 'netbanking_jsb';
    const NETBANKING_IOB         = 'netbanking_iob';
    const NETBANKING_FSB         = 'netbanking_fsb';
    const NETBANKING_AUSF        = 'netbanking_ausf';
    const NETBANKING_DLB         = 'netbanking_dlb';
    const NETBANKING_TMB         = 'netbanking_tmb';
    const NETBANKING_KARNATAKA   = 'netbanking_karnataka';
    const WALLET_PHONEPE         = 'wallet_phonepe';
    const WALLET_PHONEPESWITCH   = 'wallet_phonepeswitch';
    const NETBANKING_CSB         = 'netbanking_csb';
    const NETBANKING_IBK         = 'netbanking_ibk';
    const NETBANKING_UBI         = 'netbanking_ubi';
    const NETBANKING_SCB         = 'netbanking_scb';
    const NETBANKING_JKB         = 'netbanking_jkb';
    const NETBANKING_PNB         = 'netbanking_pnb';
    const WALLET_PAYZAPP         = 'wallet_payzapp';
    const NETBANKING_BOB         = 'netbanking_bob';
    const WALLET_JIOMONEY        = 'wallet_jiomoney';
    const WALLET_SBIBUDDY        = 'wallet_sbibuddy';
    const WALLET_OLAMONEY        = 'wallet_olamoney';
    const NETBANKING_YESB        = 'netbanking_yesb';
    const NETBANKING_IDBI        = 'netbanking_idbi';
    const NETBANKING_IDFC        = 'netbanking_idfc';
    const NETBANKING_AXIS        = 'netbanking_axis';
    const NETBANKING_HDFC        = 'netbanking_hdfc';
    const WALLET_AMAZONPAY       = 'wallet_amazonpay';
    const NETBANKING_KOTAK       = 'netbanking_kotak';
    const NETBANKING_ICICI       = 'netbanking_icici';
    const WALLET_PAYUMONEY       = 'wallet_payumoney';
    const WALLET_FREECHARGE      = 'wallet_freecharge';
    const WALLET_OPENWALLET      = 'wallet_openwallet';
    const WALLET_RAZORPAYWALLET  = 'wallet_razorpaywallet';
    const NETBANKING_BOB_V2      = 'netbanking_bob_v2';
    const NETBANKING_CANARA      = 'netbanking_canara';
    const NETBANKING_VIJAYA      = 'netbanking_vijaya';
    const ESIGNER_LEGALDESK      = 'esigner_legaldesk';
    const NETBANKING_AIRTEL      = 'netbanking_airtel';
    const WALLET_AIRTELMONEY     = 'wallet_airtelmoney';
    const NETBANKING_FEDERAL     = 'netbanking_federal';
    const NETBANKING_EQUITAS     = 'netbanking_equitas';
    const NETBANKING_INDUSIND    = 'netbanking_indusind';
    const NETBANKING_ALLAHABAD   = 'netbanking_allahabad';
    const ENACH_NPCI_NETBANKING  = 'enach_npci_netbanking';
    const NETBANKING_CORPORATION = 'netbanking_corporation';
    const UPI_MOZART             = 'upi_mozart';
    const NETBANKING_DCB         = 'netbanking_dcb';
    const NETBANKING_NSDL        = 'netbanking_nsdl';
    const BILLDESK_SIHUB         = 'billdesk_sihub';
    const CHECKOUT_DOT_COM       = 'checkout_dot_com';
    const CHECKOUT_ORDER         = 'checkout_order';
    const EMERCHANTPAY           = 'emerchantpay';
    const RUPAY_SIHUB            = 'rupay_sihub';
    const NETBANKING_UJJIVAN     = 'netbanking_ujjivan';
    const NETBANKING_DBS         = 'netbanking_dbs';
    const CURRENCY_CLOUD         = 'currency_cloud';
    const UPI_KOTAK              = 'upi_kotak';
    const UPI_RZPRBL             = 'upi_rzprbl';

    // P2P Service Entities
    const P2P_VPA                = 'p2p_vpa';
    const P2P_BANK               = 'p2p_bank';
    const P2P_DEVICE             = 'p2p_device';
    const P2P_HANDLE             = 'p2p_handle';
    const P2P_CONCERN            = 'p2p_concern';
    const P2P_TRANSACTION        = 'p2p_transaction';
    const P2P_BENEFICIARY        = 'p2p_beneficiary';
    const P2P_DEVICE_TOKEN       = 'p2p_device_token';
    const P2P_BANK_ACCOUNT       = 'p2p_bank_account';
    const P2P_REGISTER_TOKEN     = 'p2p_register_token';
    const P2P_UPI_TRANSACTION    = 'p2p_upi_transaction';
    const P2P_CLIENT             = 'p2p_client';
    const P2P_MANDATE            = 'p2p_mandate';
    const P2P_UPI_MANDATE        = 'p2p_upi_mandate';
    const P2P_MANDATE_PATCH      = 'p2p_mandate_patch';
    const P2P_BLACKLIST          = 'p2p_blacklist';
    const P2P_COMPLAINT          = 'p2p_complaint';

    // P2P Gateways
    const P2P_UPI_AXIS           = 'p2p_upi_axis';
    const P2P_UPI_SHARP          = 'p2p_upi_sharp';

    // P2M Gateways
    const P2M_UPI_AXIS_OLIVE     = 'p2m_upi_axis_olive';

    // Tax and Tax Groups
    const TAX                   = 'tax';
    const TAX_GROUP             = 'tax_group';

    // External Service Entity (ServiceName.EntityName)
    // Service: Batch
    const BATCH_SERVICE                = 'batch.service';
    const REPORTING_LOGS               = 'reporting.logs';
    const BATCH_FILE_STORE             = 'batch.file_store';
    const REPORTING_CONFIGS            = 'reporting.configs';
    const REPORTING_SCHEDULES          = 'reporting.schedules';


    // Service: Auth Service
    const AUTH_SERVICE_APPLICATIONS    = 'auth_service.applications';
    const AUTH_SERVICE_CLIENTS         = 'auth_service.clients';
    const AUTH_SERVICE_TOKENS          = 'auth_service.tokens';
    const AUTH_SERVICE_REFRESH_TOKENS  = 'auth_service.refresh_tokens';

    // Service: Shield
    const SHIELD_RULES                    = 'shield.rules';
    const SHIELD_RISKS                    = 'shield.risks';
    const SHIELD_RISK_LOGS                = 'shield.risk_logs';
    const SHIELD_LISTS                    = 'shield.lists';
    const SHIELD_LIST_ITEMS               = 'shield.list_items';
    const SHIELD_RULE_ANALYTICS           = 'shield.rule_analytics';
    const SHIELD_RISK_THRESHOLD_CONFIGS   = 'shield.risk_threshold_configs';
    const SHIELD_MERCHANT_RISK_THRESHOLDS = 'shield.merchant_risk_thresholds';

    // Service: RAS
    const RAS_RULES = 'ras.rules';

    // Service: Disputes
    const DISPUTES_DISPUTES                     = 'disputes.disputes';
    const DISPUTES_DISPUTE_EVIDENCE             = 'disputes.dispute_evidence';
    const DISPUTES_DISPUTE_EVIDENCE_DOCUMENT    = 'disputes.dispute_evidence_document';
    const DISPUTES_DISPUTE_REASONS              = 'disputes.dispute_reasons';


    // Service: Governor
    const GOVERNOR_RULES                = 'governor.rules';
    const GOVERNOR_RULE_GROUPS          = 'governor.rule_groups';

    const PAYMENTS_CARDS_AUTHORIZATION  = 'payments_cards.authorization';
    const PAYMENTS_CARDS_AUTHENTICATION = 'payments_cards.authentication';
    const PAYMENTS_CARDS_CAPTURE        = 'payments_cards.capture';

    const SUBSCRIPTION_OFFERS_MASTER     = 'subscription_offers_master';
    // Service: Subscription
    const SUBSCRIPTIONS_PLAN             = 'subscriptions.plan';
    const SUBSCRIPTIONS_ADDON            = 'subscriptions.addon';
    const SUBSCRIPTIONS_SUBSCRIPTION     = 'subscriptions.subscription';
    const SUBSCRIPTIONS_UPDATE_REQUEST   = 'subscription_update_request';
    const SUBSCRIPTIONS_CYCLE            = 'subscriptions.subscription_cycle';
    const SUBSCRIPTIONS_VERSION          = 'subscriptions.subscription_version';
    const SUBSCRIPTIONS_TRANSACTION      = 'subscriptions.subscription_transaction';
    // Service: Stork
    const STORK_WEBHOOK = 'stork.webhook';

    const FTS_ATTEMPTS                     = 'fts.attempts';
    const FTS_TRANSFERS                    = 'fts.transfers';
    const FTS_FUND_ACCOUNT                 = 'fts.fund_accounts';
    const FTS_SOURCE_ACCOUNT               = 'fts.source_accounts';
    const FTS_BENEFICIARY_STATUS           = 'fts.beneficiary_status';
    const FTS_SOURCE_ACCOUNT_MAPPING       = 'fts.source_account_mappings';
    const FTS_DIRECT_ACCOUNT_ROUTING_RULES = 'fts.direct_account_routing_rules';
    const FTS_PREFERRED_ROUTING_WEIGHTS    = 'fts.preferred_routing_weights';
    const FTS_ACCOUNT_TYPE_MAPPINGS        = 'fts.account_type_mappings';
    const FTS_MERCHANT_CONFIGURATIONS      = 'fts.merchant_configurations';
    const FTS_KEY_VALUE_STORE_LOGS         = 'fts.key_value_store_logs';

    // FTS Routing V2
    const FTS_SCHEDULES                       = 'fts.schedules';
    const FTS_TRIGGER_STATUS_LOGS             = 'fts.trigger_status_logs';
    const FTS_CHANNEL_INFORMATION_STATUS_LOGS = 'fts.channel_information_status_logs';

    // FTS Fail Fast Healths
    const FTS_FAIL_FAST_STATUS_LOGS   = 'fts.fail_fast_status_logs';

    const UFH_FILES                      = 'ufh.files';

    // capital-collections
    const CAPITAL_COLLECTIONS_PLAN                 = 'capital_collections.plan';
    const CAPITAL_COLLECTIONS_LEDGER_BALANCE       = 'capital_collections.ledger_balance';
    const CAPITAL_COLLECTIONS_PRIORITIZATION       = 'capital_collections.prioritization';
    const CAPITAL_COLLECTIONS_INSTALLMENT          = 'capital_collections.installment';
    const CAPITAL_COLLECTIONS_CHARGE               = 'capital_collections.charge';
    const CAPITAL_COLLECTIONS_CREDIT_REPAYMENT     = 'capital_collections.credit_repayment';
    const CAPITAL_COLLECTIONS_REPAYMENT            = 'capital_collections.repayment';
    const CAPITAL_COLLECTIONS_REPAYMENT_BREAKUP    = 'capital_collections.repayment_breakup';
    const CAPITAL_COLLECTIONS_INTEREST_WAIVER      = 'capital_collections.interest_waiver';

    // line_of_credit
    const LINE_OF_CREDIT_ACCOUNT_BALANCES                         = 'line_of_credit.account_balances';
    const LINE_OF_CREDIT_ONBOARDINGS                              = 'line_of_credit.onboardings';
    const LINE_OF_CREDIT_REPAYMENTS                               = 'line_of_credit.repayments';
    const LINE_OF_CREDIT_WITHDRAWAL_CONFIGS                       = 'line_of_credit.withdrawal_configs';
    const LINE_OF_CREDIT_DESTINATION_ACCOUNTS                     = 'line_of_credit.destination_accounts';
    const LINE_OF_CREDIT_REPAYMENT_BREAKDOWNS                     = 'line_of_credit.repayment_breakdowns';
    const LINE_OF_CREDIT_SOURCE_ACCOUNTS                          = 'line_of_credit.source_accounts';
    const LINE_OF_CREDIT_WITHDRAWALS                              = 'line_of_credit.withdrawals';

    //loan_origination_system
    const CAPITAL_LOS_D2C_BUREAU_REPORTS                          = 'loan_origination_system.d2c_bureau_reports';
    const CAPITAL_LOS_APPLICATIONS                                = 'loan_origination_system.applications';
    const CAPITAL_LOS_APPLICATION_CREDIT_POLICY_MAPPINGS          = 'loan_origination_system.application_credit_policy_mappings';
    const CAPITAL_LOS_BUSINESSES                                  = 'loan_origination_system.businesses';
    const CAPITAL_LOS_BUSINESS_APPLICANTS                         = 'loan_origination_system.business_applicants';
    const CAPITAL_LOS_CARD_OFFERS                                 = 'loan_origination_system.card_offers';
    const CAPITAL_LOS_CONTRACTS                                   = 'loan_origination_system.contracts';
    const CAPITAL_LOS_CREDIT_OFFERS                               = 'loan_origination_system.credit_offers';
    const CAPITAL_LOS_LOC_OFFERS                                  = 'loan_origination_system.loc_offers';
    const CAPITAL_LOS_CREDIT_POLICIES                             = 'loan_origination_system.credit_policies';
    const CAPITAL_LOS_DISBURSALS                                  = 'loan_origination_system.disbursals';
    const CAPITAL_LOS_DOC_SIGN_FILES                              = 'loan_origination_system.doc_sign_files';
    const CAPITAL_LOS_DOCUMENTS                                   = 'loan_origination_system.documents';
    const CAPITAL_LOS_DOCUMENT_GROUPS                             = 'loan_origination_system.document_groups';
    const CAPITAL_LOS_DOCUMENT_MASTERS                            = 'loan_origination_system.document_masters';
    const CAPITAL_LOS_DOCUMENT_MASTERS_GROUPS                     = 'loan_origination_system.document_masters_groups';
    const CAPITAL_LOS_DOCUMENT_SIGNS                              = 'loan_origination_system.document_signs';
    const CAPITAL_LOS_LEAD_TYPES                                  = 'loan_origination_system.lead_types';
    const CAPITAL_LOS_LENDERS                                     = 'loan_origination_system.lenders';
    const CAPITAL_LOS_NACH_APPLICATIONS                           = 'loan_origination_system.nach_applications';
    const CAPITAL_LOS_NACH_MANDATES                               = 'loan_origination_system.nach_mandates';
    const CAPITAL_LOS_OFFER_VERIFICATION_TASKS                    = 'loan_origination_system.offer_verification_tasks';
    const CAPITAL_LOS_PRODUCTS                                    = 'loan_origination_system.products';
    const CAPITAL_LOS_PRODUCT_LENDERS                             = 'loan_origination_system.product_lenders';
    const CAPITAL_LOS_SIGN_INVITEES                               = 'loan_origination_system.sign_invitees';
    const CAPITAL_LOS_VENDORS                                     = 'loan_origination_system.vendors';

    // Payout service
    const PAYOUTS_PAYOUTS        = 'payouts.payouts';
    const PAYOUTS_REVERSALS      = 'payouts.reversals';
    const PAYOUTS_PAYOUT_LOGS    = 'payouts.payout_logs';
    const PAYOUTS_PAYOUT_SOURCES = 'payouts.payout_sources';

    // care service
    const CARE_CALLBACK                 = 'care.callback';
    const CARE_CALLBACK_OPERATOR        = 'care.callback_operator';
    const CARE_CALLBACK_LOG             = 'care.callback_log';


    const COMMISSION = 'commission';
    const COMMISSION_COMPONENT = 'commission_component';
    const PARTNER_ACTIVATION             = 'partner_activation';

    // these entities doesn't exist in api. they are required for transaction entity operations.
    const CREDIT_REPAYMENT               = 'credit_repayment';
    const CAPITAL_TRANSACTION            = 'capital_transaction';
    const REPAYMENT_BREAKUP              = 'repayment_breakup';
    const INTEREST_WAIVER                = 'interest_waiver';
    const INSTALLMENT                    = 'installment';
    const CHARGE                         = 'charge';

    const PAYMENTS_NBPLUS_PAYMENTS     = 'payments_nbplus.payments';
    const PAYMENTS_NBPLUS_NETBANKING   = 'payments_nbplus.netbanking';
    const NBPLUS_EMANDATE_REGISTRATION = 'payments_nbplus.emandate_registration';
    const NBPLUS_EMANDATE_DEBIT        = 'payments_nbplus.emandate_debit';
    const PAYMENTS_NBPLUS_APP_GATEWAY  = 'payments_nbplus.app_gateway';
    const PAYMENTS_NBPLUS_WALLET_TRANSACTION       = 'payments_nbplus.wallet_transaction';
    const PAYMENTS_NBPLUS_WALLET_AUTHORIZATION     = 'payments_nbplus.wallet_authorization';

    const PAYMENTS_NBPLUS_CARDLESS_EMI_GATEWAY     = 'payments_nbplus.cardless_emi_gateway';

    const PAYMENTS_NBPLUS_PAYLATER_GATEWAY         = 'payments_nbplus.paylater_gateway';

    const VENDOR_PAYMENTS_VENDOR_PAYMENTS         = 'vendor_payments.vendor_payments';
    const VENDOR_PAYMENTS_ICICI_TAX_PAY_REQUESTS  = 'vendor_payments.icici_tax_pay_requests';
    const VENDOR_PAYMENTS_TAX_PAYMENTS            = 'vendor_payments.tax_payments';
    const VENDOR_PAYMENTS_DIRECT_TAX_PAYMENTS     = 'vendor_payments.direct_tax_payments';
    const VENDOR_PAYMENTS_PG_PAYMENTS             = 'vendor_payments.pg_payments';

    // Service: Payments UPi
    const PAYMENTS_UPI_VPA              = 'payments_upi_vpa';
    const PAYMENTS_UPI_BANK_ACCOUNT     = 'payments_upi_bank_account';
    const PAYMENTS_UPI_VPA_BANK_ACCOUNT = 'payments_upi_vpa_bank_account';
    const PAYMENTS_UPI_FISCAL           = 'payments_upi.fiscal';
    const CONFIG                        = 'config';

    const PROMOTION_EVENT = 'promotion_event';
    //payout downtime
    const PAYOUT_DOWNTIMES              = Table::PAYOUT_DOWNTIMES;
    const FUND_LOADING_DOWNTIMES        = Table::FUND_LOADING_DOWNTIMES;

    const PAYOUTS_META                  = Table::PAYOUTS_META;

    const PAYOUTS_DETAILS = 'payouts_details';

    const PAYOUTS_BATCH = 'payouts_batch';

    const PAYOUTS_STATUS_DETAILS = Table::PAYOUTS_STATUS_DETAILS;

    //user device details
    const USER_DEVICE_DETAIL = 'user_device_detail';

    const APP_ATTRIBUTION_DETAIL = 'app_attribution_detail';

    //merchant on-boarding
    const MERCHANT_DETAIL   = 'merchant_detail';
    const MERCHANT_DOCUMENT = 'merchant_document';
    const BVS_VALIDATION    = 'bvs_validation';
    const STAKEHOLDER       = 'stakeholder';
    const REWARD            = 'reward';
    const MERCHANT_REWARD   = 'merchant_reward';
    const REWARD_COUPON     = 'reward_coupon';
    const AUDIT_INFO        = 'audit_info';

    const MERCHANT_INTERNATIONAL_ENABLEMENT = 'merchant_international_enablement';
    const INTERNATIONAL_ENABLEMENT_DETAIL   = 'international_enablement_detail';
    const INTERNATIONAL_ENABLEMENT_DOCUMENT = 'international_enablement_document';

    // merchant auto kyc escalations
    const MERCHANT_AUTO_KYC_ESCALATIONS  = 'merchant_auto_kyc_escalations';

    const MERCHANT_ONBOARDING_ESCALATIONS = 'merchant_onboarding_escalations';
    const ONBOARDING_ESCALATION_ACTIONS   = 'onboarding_escalation_actions';

    const MERCHANT_AVG_ORDER_VALUE      = 'merchant_avg_order_value';
    const MERCHANT_WEBSITE              = 'merchant_website';
    const MERCHANT_CONSENT_DETAILS      = 'merchant_consent_details';
    const MERCHANT_CONSENTS             = 'merchant_consents';
    const MERCHANT_VERIFICATION_DETAIL  = 'merchant_verification_detail';
    const CLARIFICATION_DETAIL          = 'clarification_detail';
    const MERCHANT_BUSINESS_DETAIL      = 'merchant_business_detail';

    const M2M_REFERRAL                  = 'm2m_referral';
    const AMP_EMAIL                     = 'amp_email';
    const MERCHANT_CHECKOUT_DETAIL      = 'merchant_checkout_detail';
    const MERCHANT_SLABS                = 'merchant_slabs';
    const MERCHANT_1CC_CONFIGS          = 'merchant_1cc_configs';
    const ADDRESS_CONSENT_1CC_AUDITS    = 'address_consent_1cc_audits';
    const ADDRESS_CONSENT_1CC           = 'address_consent_1cc';
    const ZIPCODE_DIRECTORY             = 'zipcode_directory';
    const MERCHANT_1CC_COMMENTS          = 'merchant_1cc_comments';
    const CUSTOMER_CONSENT_1CC          = 'customer_consent_1cc';

    // merchant international integrations
    const MERCHANT_INTERNATIONAL_INTEGRATIONS = 'merchant_international_integrations';
    const SETTLEMENT_INTERNATIONAL_REPATRIATION = 'settlement_international_repatriation';

    // merchant owner details entity
    const MERCHANT_OWNER_DETAILS = 'merchant_owner_details';

    //api request log entity
    const REQUEST_LOG = 'request_log';

    //AppStore
    const APP_STORE = 'app_store';

    //GupShup
    const GUP_SHUP = 'gup_shup';

    // NPS Survey
    const SURVEY = 'survey';
    const SURVEY_TRACKER = 'survey_tracker';
    const SURVEY_RESPONSE = 'survey_response';

    // Merchant Risk Alert
    const MERCHANT_RISK_ALERT = 'merchant_risk_alert';

    //X Wallet Account payouts
    const WALLET_ACCOUNT = 'wallet_account';

    const SEGMENTATION = 'segmentation';

    // Merchant Fraud
    const PAYMENT_FRAUD = 'payment_fraud';

    const MERCHANT_HEALTH_CHECKER = 'merchant_health_checker';

    const MERCHANT_FRAUD_CHECKER = 'merchant_fraud_checker';

    const MERCHANT_BULK_FRAUD_NOTIFY = 'merchant_bulk_fraud_notify';

    const BULK_FRAUD_NOTIFICATION = 'bulk_fraud_notification';

    const MERCHANT_RISK_NOTE = 'merchant_risk_note';

    // Razorpay Trusted Badge
    const TRUSTED_BADGE         = 'trusted_badge';
    const TRUSTED_BADGE_HISTORY = 'trusted_badge_history';

    const ONE_CLICK_CHECKOUT    = 'one_click_checkout';

    // Network Tokenization
    const SERVICE_PROVIDER_TOKEN    = 'service_provider_token';
    const PAYMENT_ACCOUNT_REFERENCE = 'payment_account_reference';
    const TOKEN_REFERENCE_NUMBER    = 'token_reference_number';
    const TOKENISED_TERMINAL_ID     = 'tokenised_terminal_id';
    const TOKEN_REFERENCE_ID        = 'token_reference_id';
    const NETWORK_REFERENCE_ID      = 'network_reference_id';
    const PROVIDER_DATA             = 'provider_data';
    const TOKENISED                 = 'tokenised';
    const PROVIDER_TYPE             = 'provider_type';

    // Ledger
    const JOURNAL             = 'journal';
    const LEDGER_ENTRY        = 'ledger_entry';
    const ACCOUNT_DETAIL      = 'account_detail';
    const LEDGER_ACCOUNT      = 'ledger_account';
    const LEDGER_STATEMENT    = 'ledger_statement';
    const FTS_FUND_ACCOUNT_ID = 'fts_fund_account_id';
    const FTS_ACCOUNT_TYPE    = 'fts_account_type';
    const FTS_STATUS          = 'fts_status';

    const MERCHANT_1CC_AUTH_CONFIGS    = 'merchant_1cc_auth_configs';
    const DIRECT_ACCOUNT_STATEMENT = 'direct_account_statement';

    const PARTNER_BANK_HEALTH = 'partner_bank_health';

    const MERCHANT_OTP_VERIFICATION_LOGS = 'merchant_otp_verification_logs';
    // CAC entities
    const ACCESS_CONTROL_PRIVILEGES = 'access_control_privileges';

    const ROLES                     = 'roles';

    const ACCESS_POLICY_AUTHZ_ROLES_MAP = 'access_policy_authz_roles_map';
    const ROLE_ACCESS_POLICY_MAP        = 'role_access_policy_map';

    const ACCESS_CONTROL_HISTORY_LOGS   = 'access_control_history_logs';

    const NETWORK_CODE = 'network_code';

    const GATEWAY_MERCHANT_ID = 'gateway_merchant_id';

    const GATEWAY_MERCHANT_ID2 = 'gateway_merchant_id2';

    const GATEWAY_TERMINAL_ID = 'gateway_terminal_id';

    const LINKED_ACCOUNT_REFERENCE_DATA = 'linked_account_reference_data';

    const CYBER_CRIME_HELP_DESK         = 'cyber_crime_help_desk';

    const CORP_CARD = 'corp_card';

    /**
     * Defines a map of entites which are currently
     * being cached and associated cache version prefixes
     * and the specific cache ttl for any entities.
     * Note: Here TTL is in minutes, we are converting in seconds while calling put method
     */
    const CACHED_ENTITIES = [
        self::KEY      => [
            QueryCacheConstants::VERSION => 'v1',
            QueryCacheConstants::TTL     => 30,
        ],
        self::MERCHANT => [
            QueryCacheConstants::VERSION => 'v1',
            QueryCacheConstants::TTL     => 10,
        ],
        self::ACCOUNT  => [
            QueryCacheConstants::VERSION => 'v1',
            QueryCacheConstants::TTL     => 1,
        ],
        self::FEATURE => [
            QueryCacheConstants::VERSION => 'v1',
            QueryCacheConstants::TTL     => 30,
        ],
        self::TERMINAL  => [
            QueryCacheConstants::VERSION => 'v1',
            QueryCacheConstants::TTL     => 15,
        ],
        self::PRICING  => [
            QueryCacheConstants::VERSION => 'v1',
            QueryCacheConstants::TTL     => 15,
        ],
        self::AUTH_TOKEN  => [
            QueryCacheConstants::VERSION => 'v2',
            QueryCacheConstants::TTL     => 5,
        ],
        self::METHODS  => [
            QueryCacheConstants::VERSION => 'v1',
            QueryCacheConstants::TTL     => 15,
        ],
        self::IIN  => [
            QueryCacheConstants::VERSION => 'v1',
            QueryCacheConstants::TTL     => 60,
        ],
    ];

    /**
     * Entities that are auditable via entity audit trait
     */

    public const AUDITED_ENTITIES = [
        self::PRICING,
        self::MERCHANT,
        self::MERCHANT_BUSINESS_DETAIL,
        self::STAKEHOLDER,
        self::MERCHANT_DETAIL,
        self::MERCHANT_VERIFICATION_DETAIL,
        self::CLARIFICATION_DETAIL,
        self::MERCHANT_PROMOTION,
        self::MERCHANT_DOCUMENT,
        self::USER,
        self::MERCHANT_WEBSITE,
        self::MERCHANT_CONSENTS
    ];


    /**
     * Entities for which database access(retrieved, created, updated, deleted)
     * are instrumented
     */
    const INSTRUMENTED_ENTITIES = [
        self::MERCHANT,
        self::MERCHANT_DETAIL,
        self::STAKEHOLDER
    ];

    /**
     * Entities for which sync events are triggered to account service.
     * Should be in sync with entities queried in
     * \RZP\Models\Merchant\Service::getMerchantDetailsForAccountService
     */
    const ACS_SYNCED_ENTITIES = [
        self::MERCHANT,
        self::MERCHANT_DETAIL,
        self::STAKEHOLDER,
        self::MERCHANT_DOCUMENT,
        self::MERCHANT_EMAIL,
        self::MERCHANT_WEBSITE,
        self::MERCHANT_BUSINESS_DETAIL
    ];

    /**
     * Id corresponding to following listed entities are allowed for x_entity_id (header or query parameter) during
     * keyless auth to public routes.
     * Ref: KeylessPublicAuth's retrieveMerchant() for usage.
     */
    const KEYLESS_ALLOWED_ENTITIES = [
        self::ORDER,
        self::INVOICE,
        self::PAYMENT,
        self::CONTACT,
        self::CUSTOMER,
        self::SUBSCRIPTION,
        self::PAYMENT_LINK,
        self::OPTIONS,
        self::PAYOUT_LINK
    ];

    /**
     * These entities have BALANCE_ID columns added recently. This is a temporary list to validate API operation to
     * backfill balance_id column for old rows in batches.
     * Refer: AdminController@updateEntityBalanceIdInBulk()
     */
    const ENTITIES_WITH_BALANCE_ID_COLUMN = [
        Entity::TRANSACTION,
        Entity::VIRTUAL_ACCOUNT,
        Entity::PAYOUT,
        Entity::COUNTER,
        Entity::BANK_TRANSFER,
        Entity::REFUND,
        Entity::FUND_ACCOUNT_VALIDATION,
        Entity::ADJUSTMENT,
        Entity::CREDIT_TRANSFER,
        Entity::SETTLEMENT,
    ];

    const ENTITIES_FETCH_FROM_DATA_WAREHOUSE = [
        Entity::PAYMENT,
    ];

    /**
     * Entities for which DB migration metric is emitted
     */
    const DB_MIGRATION_ENTITIES = [
        Entity::MERCHANT_SLABS,
        Entity::APP_STORE,
        Entity::MERCHANT_1CC_AUTH_CONFIGS,
        Entity::MERCHANT_1CC_CONFIGS,
        Entity::MERCHANT_CHECKOUT_DETAIL,
        Entity::MERCHANT_WEBSITE,
        Entity::MERCHANT_TNC_ACCEPTANCE,
        Entity::OFFER,
        Entity::OFFLINE_DEVICE,
        Entity::REWARD_COUPON,
        Entity::REWARD,
        Entity::MERCHANT_FRESHDESK_TICKETS,
        Entity::MERCHANT_NOTIFICATION_CONFIG,
        Entity::MERCHANT_AVG_ORDER_VALUE,
        Entity::MOZART,
        Entity::ADDON,
        Entity::ADJUSTMENT,
        Entity::AEPS,
        Entity::ATOM,
        Entity::BHARAT_QR,
        Entity::BILLDESK,
        Entity::CARD_FSS,
        Entity::CARD_MANDATE_NOTIFICATION,
        Entity::CARD_MANDATE,
        Entity::CARDLESS_EMI,
        Entity::CYBERSOURCE,
        Entity::DEBIT_NOTE,
        Entity::DEBIT_NOTE_DETAIL,
        Entity::DISPUTE_EVIDENCE,
        Entity::DISPUTE_EVIDENCE_DOCUMENT,
        Entity::DISPUTE_REASON,
        Entity::DISPUTE,
        Entity::EARLY_SETTLEMENT_FEATURE_PERIOD,
        Entity::EBS,
        Entity::EMI_PLAN,
        Entity::ENACH,
        Entity::FIRST_DATA,
        Entity::GATEWAY_DOWNTIME,
        Entity::GATEWAY_DOWNTIME_ARCHIVE,
        Entity::GATEWAY_FILE,
        Entity::GATEWAY_TOKEN,
        Entity::HDFC,
        Entity::HITACHI,
        Entity::TOKENISED_IIN,
        Entity::INTERNATIONAL_ENABLEMENT_DETAIL,
        Entity::INTERNATIONAL_ENABLEMENT_DOCUMENT,
        Entity::INVOICE_REMINDER,
        Entity::INVOICE,
        Entity::ISG,
        Entity::ITEM,
        Entity::LINE_ITEM,
        Entity::LINE_ITEM_TAX,
        Entity::MERCHANT_EMI_PLANS,
        Entity::MOBIKWIK,
        Entity::MPAN,
        Entity::NETBANKING,
        Entity::OPTIONS,
        Entity::ORDER_META,
        Entity::ORDER,
        Entity::P2P,
        Entity::P2P_BENEFICIARY,
        Entity::P2P_CONCERN,
        Entity::P2P_REGISTER_TOKEN,
        Entity::P2P_TRANSACTION,
        Entity::P2P_UPI_TRANSACTION,
        Entity::P2P_MANDATE,
        Entity::P2P_UPI_MANDATE,
        Entity::PAYSECURE,
        Entity::PAYTM,
        Entity::PLAN,
        Entity::PRODUCT,
        Entity::QR_CODE,
        Entity::QR_CODE_CONFIG,
        Entity::QR_PAYMENT,
        Entity::QR_PAYMENT_REQUEST,
        Entity::REFUND,
        Entity::RISK,
        Entity::SETTLEMENT_BUCKET,
        Entity::SETTLEMENT_DESTINATION,
        Entity::SETTLEMENT_DETAILS,
        Entity::SETTLEMENT_ONDEMAND_ATTEMPT,
        Entity::SETTLEMENT_ONDEMAND_BULK,
        Entity::SETTLEMENT_ONDEMAND_FEATURE_CONFIG,
        Entity::SETTLEMENT_ONDEMAND_FUND_ACCOUNT,
        Entity::SETTLEMENT_ONDEMAND_PAYOUT,
        Entity::SETTLEMENT_ONDEMAND_TRANSFER,
        Entity::SETTLEMENT_ONDEMAND,
        Entity::SETTLEMENT_TRANSFER,
        Entity::SETTLEMENT,
        Entity::SUB_VIRTUAL_ACCOUNT,
        Entity::SUBSCRIPTION_OFFERS_MASTER,
        Entity::SUBSCRIPTION_REGISTRATION,
        Entity::SUBSCRIPTION,
        Entity::TAX_GROUP,
        Entity::TNC_MAP,
        Entity::TRANSFER,
        Entity::TRUSTED_BADGE,
        Entity::TRUSTED_BADGE_HISTORY,
        Entity::UPI,
        Entity::UPI_MANDATE,
        Entity::UPI_TRANSFER_REQUEST,
        Entity::UPI_TRANSFER,
        Entity::WALLET,
        Entity::WALLET_ACCOUNT,
        Entity::WORLDLINE,
        Entity::VIRTUAL_ACCOUNT_PRODUCTS,
        Entity::VIRTUAL_ACCOUNT_TPV,
        Entity::VIRTUAL_ACCOUNT,
        Entity::VIRTUAL_VPA_PREFIX_HISTORY,
        Entity::VIRTUAL_VPA_PREFIX,
        Entity::PAYMENT,
        Entity::TRANSACTION,
        Entity::FEE_BREAKUP,
        Entity::ENTITY_ORIGIN,
        Entity::UPI_METADATA,
        Entity::BATCH_FUND_TRANSFER,
        self::CHECKOUT_ORDER,
    ];

    const ARCHIVED_ENTITIES = [
        self::CARD,
        self::PAYMENT,
    ];

    public static $namespace = [
        self::TOKENISED_IIN             => \RZP\Models\Card\TokenisedIIN::class,
        self::IIN                       => \RZP\Models\Card\IIN::class,
        self::P2P                       => \RZP\Models\P2p::class,
        self::VPA                       => \RZP\Models\Vpa::class,
        self::WALLET_ACCOUNT            => \RZP\Models\WalletAccount::class,
        self::UPI                       => \RZP\Gateway\Upi\Base::class,
        self::IIN                       => \RZP\Models\Card\IIN::class,
        self::EBS                       => \RZP\Gateway\Ebs::class,
        self::ATOM                      => \RZP\Gateway\Atom::class,
        self::AMEX                      => \RZP\Gateway\Amex::class,
        self::HDFC                      => \RZP\Gateway\Hdfc::class,
        self::USER                      => \RZP\Models\User::class,
        self::OFFER                     => \RZP\Models\Offer::class,
        self::ORDER                     => \RZP\Models\Order::class,
        self::ORDER_OUTBOX              => \RZP\Models\OrderOutbox::class,
        self::ORDER_META                => \RZP\Models\Order\OrderMeta::class,
        self::TOKEN                     => \RZP\Models\Customer\Token::class,
        self::GEO_IP                    => \RZP\Models\GeoIP::class,
        self::REFUND                    => \RZP\Models\Payment\Refund::class,
        self::REPORT                    => \RZP\Models\Report::class,
        self::BALANCE                   => \RZP\Models\Merchant\Balance::class,
        self::BALANCE_CONFIG            => \RZP\Models\Merchant\Balance\BalanceConfig::class,
        self::CREDITS                   => \RZP\Models\Merchant\Credits::class,
        self::METHODS                   => \RZP\Models\Merchant\Methods::class,
        self::PRICING                   => \RZP\Models\Pricing::class,
        self::PRODUCT                   => \RZP\Models\Order\Product::class,
        self::FEATURE                   => \RZP\Models\Feature::class,
        self::DISPUTE                   => \RZP\Models\Dispute::class,
        self::DISPUTE_EVIDENCE          => \RZP\Models\Dispute\Evidence::class,
        self::DISPUTE_EVIDENCE_DOCUMENT => \RZP\Models\Dispute\Evidence\Document::class,
        self::DEBIT_NOTE                => \RZP\Models\Dispute\DebitNote::class,
        self::DEBIT_NOTE_DETAIL         => \RZP\Models\Dispute\DebitNote\Detail::class,
        self::CUSTOMER                  => \RZP\Models\Customer::class,
        self::CAPITAL_VIRTUAL_CARDS     => \RZP\Models\CapitalVirtualCards::class,
        self::EMI_PLAN                  => \RZP\Models\Emi::class,
        self::MERCHANT                  => \RZP\Models\Merchant::class,
        self::LEGAL_ENTITY              => \RZP\Models\Merchant\LegalEntity::class,
        self::ACCOUNT                   => \RZP\Models\Merchant\Account::class,
        self::SCHEDULE                  => \RZP\Models\Schedule::class,
        self::COUPON                    => \RZP\Models\Coupon::class,
        self::PROMOTION                 => \RZP\Models\Promotion::class,
        self::APP_TOKEN                 => \RZP\Models\Customer\AppToken::class,
        self::STATEMENT                 => \RZP\Models\Transaction\Statement::class,
        self::JOURNAL                   => \RZP\Models\Transaction\Statement\Ledger\Journal::class,
        self::LEDGER_ENTRY              => \RZP\Models\Transaction\Statement\Ledger\LedgerEntry::class,
        self::LEDGER_ACCOUNT            => \RZP\Models\Transaction\Statement\Ledger\Account::class,
        self::ACCOUNT_DETAIL            => \RZP\Models\Transaction\Statement\Ledger\AccountDetail::class,
        self::LEDGER_STATEMENT          => \RZP\Models\Transaction\Statement\Ledger\Statement::class,
        self::DIRECT_ACCOUNT_STATEMENT  => \RZP\Models\Transaction\Statement\DirectAccount\Statement::class,
        self::INVITATION                => \RZP\Models\Invitation::class,
        self::FILE_STORE                => \RZP\Models\FileStore::class,
        self::FEE_BREAKUP               => \RZP\Models\Transaction\FeeBreakup::class,
        self::LEDGER_OUTBOX             => \RZP\Models\LedgerOutbox::class,
        self::ENTITY_OFFER              => \RZP\Models\Offer\EntityOffer::class,
        self::BANK_ACCOUNT              => \RZP\Models\BankAccount::class,
        self::OFFLINE_CHALLAN           => \RZP\Models\OfflineChallan::class,
        self::OFFLINE_PAYMENT           => \RZP\Models\OfflinePayment::class,
        self::SUBSCRIPTION              => \RZP\Models\Plan\Subscription::class,
        self::PAYMENT_LINK              => \RZP\Models\PaymentLink::class,
        self::NOCODE_CUSTOM_URL         => \RZP\Models\PaymentLink\NocodeCustomUrl::class,
        self::PAYOUT_LINK               => \RZP\Models\PayoutLink::class,
        self::SETTINGS                  => \RZP\Models\Settings::class,
        self::PAYMENT_PAGE_ITEM         => \RZP\Models\PaymentLink\PaymentPageItem::class,
        self::PAYMENT_PAGE_RECORD       => \RZP\Models\PaymentLink\PaymentPageRecord::class,
        self::GATEWAY_TOKEN             => \RZP\Models\Customer\GatewayToken::class,
        self::ENTITY_ORIGIN             => \RZP\Models\EntityOrigin::class,
        self::SCHEDULE_TASK             => \RZP\Models\Schedule\Task::class,
        self::DISPUTE_REASON            => \RZP\Models\Dispute\Reason::class,
        self::TERMINAL_ACTION           => \RZP\Models\Terminal\Action::class,
        self::BANKING_ACCOUNT_BANK_LMS  => \RZP\Models\BankingAccount\BankLms::class,
        self::BANKING_ACCOUNT           => \RZP\Models\BankingAccount::class,
        self::BANKING_ACCOUNT_STATE     => \RZP\Models\BankingAccount\State::class,
        self::BANKING_ACCOUNT_ACTIVATION_DETAIL
                                        => \RZP\Models\BankingAccount\Activation\Detail::class,
        self::BANKING_ACCOUNT_COMMENT   => \RZP\Models\BankingAccount\Activation\Comment::class,
        self::BANKING_ACCOUNT_CALL_LOG  => Models\BankingAccount\Activation\CallLog::class,
        self::MERCHANT_REQUEST          => \RZP\Models\Merchant\Request::class,
        self::CUSTOMER_BALANCE          => \RZP\Models\Customer\Balance::class,
        self::GATEWAY_DOWNTIME          => \RZP\Models\Gateway\Downtime::class,
        self::GATEWAY_DOWNTIME_ARCHIVE  => \RZP\Models\Gateway\Downtime\Archive::class,
        self::PAYMENT_DOWNTIME          => \RZP\Models\Payment\Downtime::class,
        self::GATEWAY_RULE              => \RZP\Models\Gateway\Rule::class,
        self::GATEWAY_FILE              => \RZP\Models\Gateway\File::class,
        self::MERCHANT_USER             => \RZP\Models\Merchant\MerchantUser::class,
        self::MERCHANT_EMAIL            => \RZP\Models\Merchant\Email::class,
        self::REFERRALS                 => \RZP\Models\Merchant\Referral::class,
        self::PAYMENT_ANALYTICS         => \RZP\Models\Payment\Analytics::class,
        self::CREDIT_TRANSACTION        => \RZP\Models\Merchant\Credits\Transaction::class,
        self::CREDIT_BALANCE            => \RZP\Models\Merchant\Credits\Balance::class,
        self::MERCHANT_PROMOTION        => \RZP\Models\Merchant\Promotion::class,
        self::MERCHANT_PRODUCT          => \RZP\Models\Merchant\Product::class,
        self::MERCHANT_PRODUCT_REQUEST  => \RZP\Models\Merchant\Product\Request::class,
        self::TNC_MAP                   => \RZP\Models\Merchant\Product\TncMap::class,
        self::MERCHANT_TNC_ACCEPTANCE   => \RZP\Models\Merchant\Product\TncMap\Acceptance::class,
        self::MERCHANT_INVOICE          => \RZP\Models\Merchant\Invoice::class,
        self::MERCHANT_E_INVOICE        => \RZP\Models\Merchant\Invoice\EInvoice::class,
        self::MERCHANT_EMI_PLANS        => \RZP\Models\Merchant\EmiPlans::class,
        self::NODAL_STATEMENT           => \RZP\Models\Nodal\Statement::class,
        self::SETTLEMENT_DETAILS        => \RZP\Models\Settlement\Details::class,
        self::SETTLEMENT_BUCKET         => \RZP\Models\Settlement\Bucket::class,
        self::SETTLEMENT_DESTINATION    => \RZP\Models\Settlement\Destination::class,
        self::SETTLEMENT_TRANSFER       => \RZP\Models\Settlement\Transfer::class,
        self::MERCHANT_ACCESS_MAP       => \RZP\Models\Merchant\AccessMap::class,
        self::PARTNER_KYC_ACCESS_STATE  => \RZP\Models\Partner\KycAccessState::class,
        self::MERCHANT_APPLICATION      => \RZP\Models\Merchant\MerchantApplications::class,
        self::MERCHANT_INHERITANCE_MAP  => \RZP\Models\Merchant\InheritanceMap::class,
        self::BATCH_FUND_TRANSFER       => \RZP\Models\FundTransfer\Batch::class,
        self::CUSTOMER_TRANSACTION      => \RZP\Models\Customer\Transaction::class,
        self::QR_CODE                   => \RZP\Models\QrCode\NonVirtualAccountQrCode::class,
        self::QR_CODE_CONFIG            => \RZP\Models\QrCodeConfig::class,
        self::FUND_TRANSFER_ATTEMPT     => \RZP\Models\FundTransfer\Attempt::class,
        self::VIRTUAL_ACCOUNT           => \RZP\Models\VirtualAccount::class,
        self::FUND_ACCOUNT_VALIDATION   => \RZP\Models\FundAccount\Validation::class,
        self::SUBSCRIPTION_REGISTRATION => \RZP\Models\SubscriptionRegistration::class,
        self::PAPER_MANDATE             => \RZP\Models\PaperMandate::class,
        self::PAPER_MANDATE_UPLOAD      => \RZP\Models\PaperMandate\PaperMandateUpload::class,
        self::PARTNER_CONFIG            => \RZP\Models\Partner\Config::class,
        self::CREDITNOTE                => \RZP\Models\CreditNote::class,
        self::CREDITNOTE_INVOICE        => \RZP\Models\CreditNote\Invoice::class,
        self::INVOICE_REMINDER          => \RZP\Models\Invoice\Reminder::class,
        self::MERCHANT_REMINDERS        => \RZP\Models\Merchant\Reminders::class,
        self::D2C_BUREAU_DETAIL         => \RZP\Models\D2cBureauDetail::class,
        self::D2C_BUREAU_REPORT                 => \RZP\Models\D2cBureauReport::class,
        self::ADDON                             => \RZP\Models\Plan\Subscription\Addon::class,
        self::BANKING_ACCOUNT_DETAIL            => \RZP\Models\BankingAccount\Detail::class,
        self::FEE_RECOVERY                      => \RZP\Models\FeeRecovery::class,
        self::SUB_VIRTUAL_ACCOUNT               => \RZP\Models\SubVirtualAccount::class,
        self::OFFLINE_DEVICE                    => \RZP\Models\Offline\Device::class,
        self::PAYMENT_META                      => \RZP\Models\Payment\PaymentMeta::class,
        self::UPI_METADATA                      => \RZP\Models\Payment\UpiMetadata::class,
        self::MERCHANT_ATTRIBUTE                => \RZP\Models\Merchant\Attribute::class,
        self::LOW_BALANCE_CONFIG                => \RZP\Models\Merchant\Balance\LowBalanceConfig::class,
        self::PAYOUTS_INTERMEDIATE_TRANSACTIONS => \RZP\Models\Payout\PayoutsIntermediateTransactions::class,
        self::UPI_MANDATE                       => \RZP\Models\UpiMandate::class,
        self::CARD_MANDATE                      => \RZP\Models\CardMandate::class,
        self::CARD_MANDATE_NOTIFICATION         => \RZP\Models\CardMandate\CardMandateNotification::class,
        self::SUB_BALANCE_MAP                   => \RZP\Models\Merchant\Balance\SubBalanceMap::class,

        self::BANKING_ACCOUNT_STATEMENT_DETAILS    => \RZP\Models\BankingAccountStatement\Details::class,
        self::BANKING_ACCOUNT_STATEMENT_POOL_RBL   => \RZP\Models\BankingAccountStatement\Pool\Rbl::class,
        self::BANKING_ACCOUNT_STATEMENT_POOL_ICICI => \RZP\Models\BankingAccountStatement\Pool\Icici::class,

        self::PAYOUTS_DETAILS => \RZP\Models\PayoutsDetails::class,

        self::PAYOUTS_BATCH => \RZP\Models\Payout\Batch::class,

        self::PAYOUTS_STATUS_DETAILS => \RZP\Models\PayoutsStatusDetails::class,

        self::SUBSCRIPTION_OFFERS_MASTER  => \RZP\Models\Offer\SubscriptionOffer::class,

        //ondemand
        self::SETTLEMENT_ONDEMAND_FUND_ACCOUNT   => \RZP\Models\Settlement\OndemandFundAccount::class,
        self::SETTLEMENT_ONDEMAND                => \RZP\Models\Settlement\Ondemand::class,
        self::SETTLEMENT_ONDEMAND_PAYOUT         => \RZP\Models\Settlement\OndemandPayout::class,
        self::SETTLEMENT_ONDEMAND_BULK           => \RZP\Models\Settlement\Ondemand\Bulk::class,
        self::SETTLEMENT_ONDEMAND_TRANSFER       => \RZP\Models\Settlement\Ondemand\Transfer::class,
        self::SETTLEMENT_ONDEMAND_ATTEMPT        => \RZP\Models\Settlement\Ondemand\Attempt::class,
        self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG => \RZP\Models\Settlement\Ondemand\FeatureConfig::class,

        self::EARLY_SETTLEMENT_FEATURE_PERIOD   => \RZP\Models\Settlement\EarlySettlementFeaturePeriod::class,

        self::MERCHANT_NOTIFICATION_CONFIG      => \RZP\Models\Merchant\MerchantNotificationConfig::class,

        self::LINKED_ACCOUNT_REFERENCE_DATA     => \RZP\Models\Merchant\LinkedAccountReferenceData::class,


        // gateways
        self::EBS                    => \RZP\Gateway\Ebs::class,
        self::ATOM                   => \RZP\Gateway\Atom::class,
        self::AMEX                   => \RZP\Gateway\Amex::class,
        self::HDFC                   => \RZP\Gateway\Hdfc::class,
        self::HITACHI                => \RZP\Gateway\Hitachi::class,
        self::ISG                    => \RZP\Gateway\Isg::class,
        self::PAYTM                  => \RZP\Gateway\Paytm::class,
        self::SHARP                  => \RZP\Gateway\Sharp::class,
        self::WALLET                 => \RZP\Gateway\Wallet\Base::class,
        self::BILLDESK               => \RZP\Gateway\Billdesk::class,
        self::MOBIKWIK               => \RZP\Gateway\Mobikwik::class,
        self::UPI_NPCI               => \RZP\Gateway\Upi\Npci::class,
        self::CASHFREE               => \RZP\Gateway\Upi\Cashfree::class,
        self::BILLDESK_OPTIMIZER     => \RZP\Gateway\Upi\BilldeskOptimizer::class,
        self::PINELABS               => \RZP\Gateway\Upi\Pinelabs::class,
        self::PAYU                   => \RZP\Gateway\Upi\Payu::class,
        self::OPTIMIZER_RAZORPAY     => \RZP\Gateway\Upi\OptimizerRazorpay::class,
        self::UPI_MINDGATE           => \RZP\Gateway\Upi\Mindgate::class,
        self::UPI_JUSPAY             => \RZP\Gateway\Upi\Juspay::class,
        self::UPI_SBI                => \RZP\Gateway\Upi\Sbi::class,
        self::UPI_ICICI              => \RZP\Gateway\Upi\Icici::class,
        self::UPI_AXIS               => \RZP\Gateway\Upi\Axis::class,
        self::UPI_HULK               => \RZP\Gateway\Upi\Hulk::class,
        self::UPI_RBL                => \RZP\Gateway\Upi\Rbl::class,
        self::UPI_YESBANK            => \RZP\Gateway\Upi\Yesbank::class,
        self::UPI_MOZART             => \RZP\Gateway\Upi\Mozart::class,
        self::AEPS                   => \RZP\Gateway\Aeps\Base::class,
        self::AEPS_ICICI             => \RZP\Gateway\Aeps\Icici::class,
        self::AXIS_MIGS              => \RZP\Gateway\AxisMigs::class,
        self::FIRST_DATA             => \RZP\Gateway\FirstData::class,
        self::NETBANKING             => \RZP\Gateway\Netbanking\Base::class,
        self::AXIS_GENIUS            => \RZP\Gateway\AxisGenius::class,
        self::CYBERSOURCE            => \RZP\Gateway\Cybersource::class,
        self::PAYSECURE              => \RZP\Gateway\Paysecure::class,
        self::CARD_FSS               => \RZP\Gateway\Card\Fss::class,
        self::ENACH                  => \RZP\Gateway\Enach\Base::class,
        self::ENACH_RBL              => \RZP\Gateway\Enach\Rbl::class,
        self::ESIGNER_DIGIO          => \RZP\Gateway\Esigner\Digio::class,
        self::ESIGNER_LEGALDESK      => \RZP\Gateway\Esigner\Legaldesk::class,
        self::ENACH_NPCI_NETBANKING  => \RZP\Gateway\Enach\Npci\Netbanking::class,
        self::NACH_CITI              => \RZP\Gateway\Enach\Citi::class,
        self::NACH_ICICI             => \RZP\Gateway\Enach\Npci\Physical\Icici::class,
        self::WALLET_PAYZAPP         => \RZP\Gateway\Wallet\Payzapp::class,
        self::WALLET_OLAMONEY        => \RZP\Gateway\Wallet\Olamoney::class,
        self::WALLET_JIOMONEY        => \RZP\Gateway\Wallet\Jiomoney::class,
        self::WALLET_SBIBUDDY        => \RZP\Gateway\Wallet\Sbibuddy::class,
        self::NETBANKING_SIB         => \RZP\Gateway\Mozart::class,
        self::NETBANKING_SARASWAT    => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_CBI         => \RZP\Gateway\Mozart::class,
        self::NETBANKING_IDFC        => \RZP\Gateway\Netbanking\Idfc::class,
        self::NETBANKING_AXIS        => \RZP\Gateway\Netbanking\Axis::class,
        self::NETBANKING_HDFC        => \RZP\Gateway\Netbanking\Hdfc::class,
        self::NETBANKING_BOB         => \RZP\Gateway\Netbanking\Bob::class,
        self::NETBANKING_BOB_V2      => \RZP\Gateway\Mozart::class,
        self::NETBANKING_VIJAYA      => \RZP\Gateway\Netbanking\Vijaya::class,
        self::NETBANKING_CORPORATION => \RZP\Gateway\Netbanking\Corporation::class,
        self::NETBANKING_KOTAK       => \RZP\Gateway\Netbanking\Kotak::class,
        self::NETBANKING_CUB         => \RZP\Gateway\Mozart::class,
        self::NETBANKING_IBK         => \RZP\Gateway\Mozart::class,
        self::NETBANKING_IDBI        => \RZP\Gateway\Mozart::class,
        self::NETBANKING_ALLAHABAD   => \RZP\Gateway\Netbanking\Allahabad::class,
        self::NETBANKING_ICICI       => \RZP\Gateway\Netbanking\Icici::class,
        self::NETBANKING_UBI         => \RZP\Gateway\Mozart::class,
        self::NETBANKING_SCB         => \RZP\Gateway\Mozart::class,
        self::NETBANKING_OBC         => \RZP\Gateway\Netbanking\Obc::class,
        self::NETBANKING_AIRTEL      => \RZP\Gateway\Netbanking\Airtel::class,
        self::NETBANKING_FEDERAL     => \RZP\Gateway\Netbanking\Federal::class,
        self::NETBANKING_RBL         => \RZP\Gateway\Netbanking\Rbl::class,
        self::NETBANKING_INDUSIND    => \RZP\Gateway\Netbanking\Indusind::class,
        self::NETBANKING_PNB         => \RZP\Gateway\Netbanking\Pnb::class,
        self::NETBANKING_CSB         => \RZP\Gateway\Netbanking\Csb::class,
        self::NETBANKING_CANARA      => \RZP\Gateway\Netbanking\Canara::class,
        self::NETBANKING_EQUITAS     => \RZP\Gateway\Netbanking\Equitas::class,
        self::NETBANKING_SBI         => \RZP\Gateway\Netbanking\Sbi::class,
        self::NETBANKING_YESB        => \RZP\Gateway\Mozart::class,
        self::NETBANKING_KVB         => \RZP\Gateway\Mozart::class,
        self::NETBANKING_JSB         => \RZP\Gateway\Mozart::class,
        self::WALLET_PAYUMONEY       => \RZP\Gateway\Wallet\Payumoney::class,
        self::WALLET_OPENWALLET      => \RZP\Gateway\Wallet\Openwallet::class,
        self::WALLET_RAZORPAYWALLET  => \RZP\Gateway\Wallet\Razorpaywallet::class,
        self::WALLET_FREECHARGE      => \RZP\Gateway\Wallet\Freecharge::class,
        self::WALLET_AIRTELMONEY     => \RZP\Gateway\Wallet\Airtelmoney::class,
        self::WALLET_MPESA           => \RZP\Gateway\Wallet\Mpesa::class,
        self::MPI                    => \RZP\Gateway\Mpi\Base::class,
        self::MPI_BLADE              => \RZP\Gateway\Mpi\Blade::class,
        self::MPI_ENSTAGE            => \RZP\Gateway\Mpi\Enstage::class,
        self::WALLET_AMAZONPAY       => \RZP\Gateway\Wallet\Amazonpay::class,
        self::CARDLESS_EMI           => \RZP\Gateway\CardlessEmi::class,
        self::MOZART                 => \RZP\Gateway\Mozart::class,
        self::BAJAJFINSERV           => \RZP\Gateway\Mozart::class,
        self::GOOGLE_PAY             => \RZP\Gateway\GooglePay::class,
        self::WALLET_PHONEPE         => \RZP\Gateway\Mozart::class,
        self::WALLET_PHONEPESWITCH   => \RZP\Gateway\Mozart::class,
        self::WALLET_PAYPAL          => \RZP\Gateway\Mozart::class,
        self::UPI_AIRTEL             => \RZP\Gateway\Mozart::class,
        self::UPI_CITI               => \RZP\Gateway\Mozart::class,
        self::UPI_AXISOLIVE          => \RZP\Gateway\Mozart::class,
        self::UPI_KOTAK              => \RZP\Gateway\Mozart::class,
        self::UPI_RZPRBL             => \RZP\Gateway\Mozart::class,
        self::PAYLATER               => \RZP\Gateway\CardlessEmi::class,
        self::WORLDLINE              => \RZP\Gateway\Worldline::class,
        self::GETSIMPL               => \RZP\Gateway\Mozart::class,
        self::PAYLATER_ICICI         => \RZP\Gateway\Mozart::class,
        self::HDFC_DEBIT_EMI         => \RZP\Gateway\Mozart::class,
        self::CRED                   => \RZP\Gateway\Mozart::class,
        self::RUPAY_SIHUB            => \RZP\Gateway\Mozart::class,
        self::BILLDESK_SIHUB         => \RZP\Gateway\Mozart::class,
        self::CHECKOUT_DOT_COM       => \RZP\Gateway\Mozart::class,
        self::CCAVENUE               => \RZP\Gateway\Ccavenue::class,
        self::EMERCHANTPAY           => \RZP\Gateway\Mozart::class,
        self::BT_RBL                 => \RZP\Gateway\Mozart::class,
        self::CURRENCY_CLOUD         => \RZP\Gateway\Mozart::class,

        // heimdall
        self::ORG                          => \RZP\Models\Admin\Org::class,
        self::ROLE                         => \RZP\Models\Admin\Role::class,
        self::ADMIN                        => \RZP\Models\Admin\Admin::class,
        self::GROUP                        => \RZP\Models\Admin\Group::class,
        self::ADMIN_REPORT                 => \RZP\Models\Admin\Report::class,
        self::ADMIN_LEAD                   => \RZP\Models\Admin\AdminLead::class,
        self::PERMISSION                   => \RZP\Models\Admin\Permission::class,
        self::ADMIN_TOKEN                  => \RZP\Models\Admin\Admin\Token::class,
        self::ORG_HOSTNAME                 => \RZP\Models\Admin\Org\Hostname::class,
        self::ORG_FIELD_MAP                => \RZP\Models\Admin\Org\FieldMap::class,
        self::WORKFLOW                     => \RZP\Models\Workflow::class,
        self::WORKFLOW_STEP                => \RZP\Models\Workflow\Step::class,
        self::WORKFLOW_ACTION              => \RZP\Models\Workflow\Action::class,
        self::ACTION_CHECKER               => \RZP\Models\Workflow\Action\Checker::class,
        self::ACTION_STATE                 => \RZP\Models\Workflow\Action\State::class,
        self::ACTION_COMMENT               => \RZP\Models\Workflow\Action\Comment::class,
        self::STATE_REASON                 => \RZP\Models\State\Reason::class,
        self::WORKFLOW_PAYOUT_AMOUNT_RULES => \RZP\Models\Workflow\PayoutAmountRules::class,
        self::COMMENT                      => \RZP\Models\Comment::class,

        self::TAX_GROUP             => \RZP\Models\Tax\Group::class,
        self::LINE_ITEM_TAX         => \RZP\Models\LineItem\Tax::class,

        self::P2P_DEVICE            => \RZP\Models\P2p\Device::class,
        self::P2P_DEVICE_TOKEN      => \RZP\Models\P2p\Device\DeviceToken::class,
        self::P2P_REGISTER_TOKEN    => \RZP\Models\P2p\Device\RegisterToken::class,
        self::P2P_BANK              => \RZP\Models\P2p\BankAccount\Bank::class,
        self::P2P_BANK_ACCOUNT      => \RZP\Models\P2p\BankAccount::class,
        self::P2P_VPA               => \RZP\Models\P2p\Vpa::class,
        self::P2P_HANDLE            => \RZP\Models\P2p\Vpa\Handle::class,
        self::P2P_BENEFICIARY       => \RZP\Models\P2p\Beneficiary::class,
        self::P2P_TRANSACTION       => \RZP\Models\P2p\Transaction::class,
        self::P2P_UPI_TRANSACTION   => \RZP\Models\P2p\Transaction\UpiTransaction::class,
        self::P2P_CONCERN           => \RZP\Models\P2p\Transaction\Concern::class,
        self::P2P_CLIENT            => \RZP\Models\P2p\Client::class,
        self::P2P_MANDATE           => \RZP\Models\P2p\Mandate::class,
        self::P2P_UPI_MANDATE       => \RZP\Models\P2p\Mandate\UpiMandate::class,
        self::P2P_MANDATE_PATCH    => \RZP\Models\P2p\Mandate\Patch::class,
        self::P2P_BLACKLIST         => \RZP\Models\P2p\BlackList::class,
        self::P2P_COMPLAINT         => \RZP\Models\P2p\Complaint::class,

        self::P2P_UPI_SHARP      => \RZP\Gateway\P2p\Upi::class,
        self::P2P_UPI_AXIS       => \RZP\Gateway\P2p\Upi::class,
        self::P2M_UPI_AXIS_OLIVE => \RZP\Gateway\P2p\Upi::class,

        self::COMMISSION            => \RZP\Models\Partner\Commission::class,
        self::COMMISSION_COMPONENT => \RZP\Models\Partner\Commission\Component::class,
        self::COMMISSION_INVOICE    => \RZP\Models\Partner\Commission\Invoice::class,
        self::PARTNER_ACTIVATION    => \RZP\Models\Partner\Activation::class,

        self::OPTIONS               => \RZP\Models\Options::class,

        self::PAYMENTS_UPI_VPA              => \RZP\Models\PaymentsUpi\Vpa::class,
        self::PAYMENTS_UPI_BANK_ACCOUNT     => \RZP\Models\PaymentsUpi\BankAccount::class,
        self::PAYMENTS_UPI_VPA_BANK_ACCOUNT => \RZP\Models\PaymentsUpi\Vpa\BankAccount::class,

        self::MERCHANT_FRESHDESK_TICKETS    => \RZP\Models\Merchant\FreshdeskTicket::class,

        self::CONFIG                        => \RZP\Models\Payment\Config::class,
        self::PROMOTION_EVENT               => \RZP\Models\Promotion\Event::class,
        self::PAYOUT_DOWNTIMES              => \RZP\Models\PayoutDowntime::class,
        self::FUND_LOADING_DOWNTIMES        => \RZP\Models\FundLoadingDowntime::class,
        self::PARTNER_BANK_HEALTH           => \RZP\Models\PartnerBankHealth::class,

        self::PAYOUTS_META                  => \RZP\Models\PayoutMeta::class,
        self::PAYOUT_OUTBOX                 => \RZP\Models\PayoutOutbox::class,

        self::WORKFLOW_CONFIG               => \RZP\Models\Workflow\Service\Config::class,
        self::WORKFLOW_ENTITY_MAP           => \RZP\Models\Workflow\Service\EntityMap::class,
        self::WORKFLOW_STATE_MAP            => \RZP\Models\Workflow\Service\StateMap::class,

        //App framework
        self::APPLICATION                   => \RZP\Models\Application::class,
        self::APPLICATION_MAPPING           => \RZP\Models\Application\ApplicationTags::class,
        self::APPLICATION_MERCHANT_MAPPING  => \RZP\Models\Application\ApplicationMerchantMaps::class,
        self::APPLICATION_MERCHANT_TAG      => \RZP\Models\Application\ApplicationMerchantTags::class,

        self::MERCHANT_DETAIL   => \RZP\Models\Merchant\Detail::class,
        self::STAKEHOLDER       => \RZP\Models\Merchant\Stakeholder::class,
        self::MERCHANT_DOCUMENT => \RZP\Models\Merchant\Document::class,
        self::BVS_VALIDATION    => \RZP\Models\Merchant\BvsValidation::class,

        self::MERCHANT_INTERNATIONAL_ENABLEMENT => \RZP\Models\Merchant\InternationalEnablement::class,
        self::INTERNATIONAL_ENABLEMENT_DETAIL   => \RZP\Models\Merchant\InternationalEnablement\Detail::class,
        self::INTERNATIONAL_ENABLEMENT_DOCUMENT => \RZP\Models\Merchant\InternationalEnablement\Document::class,

        self::REPAYMENT_BREAKUP => \RZP\Models\CapitalTransaction::class,
        self::INTEREST_WAIVER   => \RZP\Models\CapitalTransaction::class,
        self::INSTALLMENT       => \RZP\Models\CapitalTransaction::class,
        self::CHARGE            => \RZP\Models\CapitalTransaction::class,

        self::USER_DEVICE_DETAIL              => \RZP\Models\DeviceDetail::class,
        self::APP_ATTRIBUTION_DETAIL          => \RZP\Models\DeviceDetail\Attribution::class,
        self::MERCHANT_AUTO_KYC_ESCALATIONS   => \RZP\Models\Merchant\AutoKyc\Escalations::class,
        self::MERCHANT_AVG_ORDER_VALUE        => \RZP\Models\Merchant\AvgOrderValue::class,
        self::MERCHANT_WEBSITE                => \RZP\Models\Merchant\Website::class,
        self::MERCHANT_CONSENTS               => \RZP\Models\Merchant\Consent::class,
        self::MERCHANT_CONSENT_DETAILS        => \RZP\Models\Merchant\Consent\Details::class,
        self::MERCHANT_VERIFICATION_DETAIL    => \RZP\Models\Merchant\VerificationDetail::class,
        self::CLARIFICATION_DETAIL            => \RZP\Models\ClarificationDetail::class,
        self::AUDIT_INFO                      => \RZP\Models\Base\Audit::class,
        self::MERCHANT_BUSINESS_DETAIL        => \RZP\Models\Merchant\BusinessDetail::class,
        self::M2M_REFERRAL                    => \RZP\Models\Merchant\M2MReferral::class,
        self::AMP_EMAIL                       => \RZP\Models\AMPEmail::class,
        self::MERCHANT_ONBOARDING_ESCALATIONS   => \RZP\Models\Merchant\Escalations::class,
        self::ONBOARDING_ESCALATION_ACTIONS     => \RZP\Models\Merchant\Escalations\Actions::class,
        self::MERCHANT_CHECKOUT_DETAIL        => \RZP\Models\Merchant\CheckoutDetail::class,
        self::REWARD            => \RZP\Models\Reward::class,
        self::MERCHANT_REWARD   => \RZP\Models\Reward\MerchantReward::class,
        self::REWARD_COUPON     => \RZP\Models\Reward\RewardCoupon::class,
        self::MERCHANT_INTERNATIONAL_INTEGRATIONS  => \RZP\Models\Merchant\InternationalIntegration::class,
        self::SETTLEMENT_INTERNATIONAL_REPATRIATION => \RZP\Models\Settlement\InternationalRepatriation::class,
        self::MERCHANT_OWNER_DETAILS => \RZP\Models\Merchant\OwnerDetail::class,

        self::TRUSTED_BADGE          => \RZP\Models\TrustedBadge::class,
        self::TRUSTED_BADGE_HISTORY  => \RZP\Models\TrustedBadge\TrustedBadgeHistory::class,

        self::APP_STORE         => \RZP\Models\AppStore::class,
        self::GUP_SHUP          => \RZP\Models\GupShup::class,

        self::SURVEY_TRACKER    => \RZP\Models\Survey\Tracker::class,
        self::SURVEY            => \RZP\Models\Survey::class,
        self::SURVEY_RESPONSE   => \RZP\Models\Survey\Response::class,

        self::MERCHANT_RISK_ALERT => \RZP\Models\MerchantRiskAlert::class,

        self::CYBER_CRIME_HELP_DESK => \RZP\Models\CyberCrimeHelpDesk::class,

        self::BANKING_ACCOUNT_TPV   => \RZP\Models\BankingAccountTpv::class,

        self::SEGMENTATION   => \RZP\Models\Segmentation::class,

        self::MERCHANT_HEALTH_CHECKER   => \RZP\Models\Merchant\Fraud\HealthChecker::class,

        self::MERCHANT_FRAUD_CHECKER   => \RZP\Models\Merchant\Fraud\Checker::class,

        self::MERCHANT_BULK_FRAUD_NOTIFY   => \RZP\Models\Merchant\Fraud\BulkNotification::class,

        self::MERCHANT_RISK_NOTE       => \RZP\Models\Merchant\RiskNotes::class,

        self::MERCHANT_SLABS            => \RZP\Models\Merchant\Slab::class,

        self::MERCHANT_1CC_CONFIGS       => \RZP\Models\Merchant\Merchant1ccConfig::class,

        self::MERCHANT_1CC_AUTH_CONFIGS  => \RZP\Models\Merchant\OneClickCheckout\AuthConfig::class,

        self::PAYMENT_FRAUD              => \RZP\Models\Payment\Fraud::class,

        self::MERCHANT_1CC_COMMENTS      => \RZP\Models\Merchant\Merchant1ccComments::class,

        self::MERCHANT_OTP_VERIFICATION_LOGS       => \RZP\Models\Merchant\Product\Otp::class,
        self::ADDRESS_CONSENT_1CC               => \RZP\Models\Address\AddressConsent1cc::class,

        self::ZIPCODE_DIRECTORY               => Models\Pincode\ZipcodeDirectory::class,

        self::ADDRESS_CONSENT_1CC_AUDITS        => \RZP\Models\Address\AddressConsent1ccAudits::class,

        self::ROLES                         => \RZP\Models\Roles::class,

        self::ACCESS_CONTROL_PRIVILEGES     => \RZP\Models\AccessControlPrivileges::class,

        self::ACCESS_POLICY_AUTHZ_ROLES_MAP => \RZP\Models\AccessPolicyAuthzRolesMap::class,

        self::ACCESS_CONTROL_HISTORY_LOGS   => \RZP\Models\AccessControlHistoryLogs::class,

        self::ROLE_ACCESS_POLICY_MAP        => \RZP\Models\RoleAccessPolicyMap::class,

        self::CHECKOUT_ORDER => \RZP\Models\Checkout\Order::class,

        self::PAYMENT_LIMIT => \RZP\Models\Merchant\PaymentLimit::class,

        self::CUSTOMER_CONSENT_1CC          => \RZP\Models\Customer\CustomerConsent1cc::class,

        self::TRUECALLER_AUTH_REQUEST => \RZP\Models\Customer\Truecaller\AuthRequest::class,
    ];

    protected static $repository = [
        self::NETBANKING_AIRTEL      => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_AXIS        => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_FEDERAL     => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_HDFC        => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_CORPORATION => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_ICICI       => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_CANARA      => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_INDUSIND    => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_KOTAK       => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_RBL         => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_PNB         => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_OBC         => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_CSB         => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_BOB         => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_ALLAHABAD   => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_EQUITAS     => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_SBI         => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_AUSF        => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_DLB         => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_TMB         => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_IDFC        => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_VIJAYA      => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_UJJIVAN     => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_DBS         => \RZP\Gateway\Netbanking\Base::class,

        self::MPI_BLADE              => \RZP\Gateway\Mpi\Base::class,
        self::MPI_ENSTAGE            => \RZP\Gateway\Mpi\Base::class,

        self::ENACH_RBL              => \RZP\Gateway\Enach\Base::class,

        self::ESIGNER_LEGALDESK      => \RZP\Gateway\Esigner\Base::class,

        self::UPI_MINDGATE           => \RZP\Gateway\Upi\Base::class,
        self::UPI_SBI                => \RZP\Gateway\Upi\Base::class,
        self::UPI_ICICI              => \RZP\Gateway\Upi\Base::class,
        self::UPI_AXIS               => \RZP\Gateway\Upi\Base::class,
        self::UPI_HULK               => \RZP\Gateway\Upi\Base::class,
        self::UPI_NPCI               => \RZP\Gateway\Upi\Base::class,
        self::UPI_RBL                => \RZP\Gateway\Upi\Base::class,
        self::UPI_AXISOLIVE          => \RZP\Gateway\Upi\Base::class,
        self::UPI_YESBANK            => \RZP\Gateway\Upi\Base::class,
        self::UPI_MOZART             => \RZP\Gateway\Mozart::class,
        self::UPI_JUSPAY             => \RZP\Gateway\Upi\Base::class,
        self::CASHFREE               => \RZP\Gateway\Upi\Base::class,
        self::BILLDESK_OPTIMIZER     => \RZP\Gateway\Upi\Base::class,
        self::PINELABS               => \RZP\Gateway\Upi\Base::class,
        self::PAYU                   => \RZP\Gateway\Upi\Base::class,
        self::OPTIMIZER_RAZORPAY     => \RZP\Gateway\Upi\Base::class,
        self::PAYTM                  => \RZP\Gateway\Upi\Base::class,
        self::UPI_KOTAK              => \RZP\Gateway\Upi\Base::class,
        self::UPI_RZPRBL             => \RZP\Gateway\Upi\Base::class,

        self::AEPS_ICICI             => \RZP\Gateway\Aeps\Base::class,

        self::WALLET_AIRTELMONEY     => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_FREECHARGE      => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_JIOMONEY        => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_SBIBUDDY        => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_MPESA           => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_OLAMONEY        => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_PAYUMONEY       => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_PAYZAPP         => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_AMAZONPAY       => \RZP\Gateway\Wallet\Base::class,
        self::CCAVENUE               => \RZP\Gateway\Wallet\Base::class,

        self::CARDLESS_EMI           => \RZP\Gateway\CardlessEmi::class,
        self::PAYLATER               => \RZP\Gateway\CardlessEmi::class,

        self::NODAL_STATEMENT        => \RZP\Models\Nodal\Statement::class,

        self::PAYMENT_DOWNTIME       => \RZP\Models\Payment\Downtime::class,
        self::BANKING_ACCOUNT_STATE  => \RZP\Models\BankingAccount\State::class,
        self::PROMOTION_EVENT        => \RZP\Models\Promotion\Event::class,


        self::SETTLEMENT_ONDEMAND_FUND_ACCOUNT   => \RZP\Models\Settlement\OndemandFundAccount::class,
        self::SETTLEMENT_ONDEMAND                => \RZP\Models\Settlement\Ondemand::class,
        self::SETTLEMENT_ONDEMAND_PAYOUT         => \RZP\Models\Settlement\OndemandPayout::class,
        self::SETTLEMENT_ONDEMAND_BULK           => \RZP\Models\Settlement\Ondemand\Bulk::class,
        self::SETTLEMENT_ONDEMAND_TRANSFER       => \RZP\Models\Settlement\Ondemand\Transfer::class,
        self::SETTLEMENT_ONDEMAND_ATTEMPT        => \RZP\Models\Settlement\Ondemand\Attempt::class,
        self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG => \RZP\Models\Settlement\Ondemand\FeatureConfig::class,

        self::EARLY_SETTLEMENT_FEATURE_PERIOD   => \RZP\Models\Settlement\EarlySettlementFeaturePeriod::class,

        self::MERCHANT_E_INVOICE                => \RZP\Models\Merchant\Invoice\EInvoice::class,
    ];

    protected static $externalRepoSingleton = [
        self::PAYMENT => 'pg_router',
        self::CARD    => 'pg_router',
        self::ORDER   => 'pg_router',
        self::REFUND  => 'scrooge',
    ];

    protected static $externalRepoConfigKey = [
        self::PAYMENT => Models\Admin\ConfigKey::PG_ROUTER_SERVICE_ENABLED,
        self::CARD    => Models\Admin\ConfigKey::PG_ROUTER_SERVICE_ENABLED,
        self::ORDER   => Models\Admin\ConfigKey::PG_ROUTER_SERVICE_ENABLED,
        self::REFUND  => Models\Admin\ConfigKey::SCROOGE_0LOC_ENABLED,
    ];

    public static $archivalFallbackConfigKey = [
        self::CARD    => Models\Admin\ConfigKey::CARD_ARCHIVAL_FALLBACK_ENABLED,
        self::PAYMENT => Models\Admin\ConfigKey::PAYMENT_ARCHIVAL_FALLBACK_ENABLED,
    ];

    public static $dualWriteConfigKey = [
        'payments' => Models\Admin\ConfigKey::PAYMENTS_DUAL_WRITE,
    ];

    protected static array $customEagerLoadEntityMapping = [
        self::CARD                        => self::CARD,
        Card\Entity::RELATION_GLOBAL_CARD => self::CARD,
        self::PAYMENT                     => self::PAYMENT,
    ];

    protected static array $customEagerLoadEntityKeys = [
        self::CARD                        => 'card_id',
        self::PAYMENT                     => 'payment_id',
        Card\Entity::RELATION_GLOBAL_CARD => 'global_card_id',
    ];

    protected static $externalServiceClass = [
        self::REPORTING_LOGS                => \RZP\Services\Reporting::class,
        self::REPORTING_CONFIGS             => \RZP\Services\Reporting::class,
        self::REPORTING_SCHEDULES           => \RZP\Services\Reporting::class,
        self::AUTH_SERVICE_APPLICATIONS     => \RZP\Services\AuthService::class,
        self::AUTH_SERVICE_CLIENTS          => \RZP\Services\AuthService::class,
        self::AUTH_SERVICE_TOKENS           => \RZP\Services\AuthService::class,
        self::AUTH_SERVICE_REFRESH_TOKENS   => \RZP\Services\AuthService::class,

        self::SHIELD_RULES                    => \RZP\Services\ShieldClient::class,
        self::RAS_RULES                       => \RZP\Services\MerchantRiskAlertClient::class,
        self::SHIELD_RULE_ANALYTICS           => \RZP\Services\ShieldClient::class,
        self::SHIELD_RISKS                    => \RZP\Services\ShieldClient::class,
        self::SHIELD_RISK_LOGS                => \RZP\Services\ShieldClient::class,
        self::SHIELD_LISTS                    => \RZP\Services\ShieldClient::class,
        self::SHIELD_LIST_ITEMS               => \RZP\Services\ShieldClient::class,
        self::SHIELD_RISK_THRESHOLD_CONFIGS   => \RZP\Services\ShieldClient::class,
        self::SHIELD_MERCHANT_RISK_THRESHOLDS => \RZP\Services\ShieldClient::class,

        self::DISPUTES_DISPUTES                     => \RZP\Services\DisputesClient::class,
        self::DISPUTES_DISPUTE_EVIDENCE             => \RZP\Services\DisputesClient::class,
        self::DISPUTES_DISPUTE_EVIDENCE_DOCUMENT    => \RZP\Services\DisputesClient::class,
        self::DISPUTES_DISPUTE_REASONS              => \RZP\Services\DisputesClient::class,

        self::BATCH_SERVICE                 => \RZP\Services\BatchMicroService::class,
        self::BATCH_FILE_STORE              => \RZP\Services\BatchMicroService::class,
        self::PAYMENTS_CARDS_AUTHENTICATION => \RZP\Services\CardPaymentService::class,
        self::PAYMENTS_CARDS_AUTHORIZATION  => \RZP\Services\CardPaymentService::class,
        self::PAYMENTS_CARDS_CAPTURE        => \RZP\Services\CardPaymentService::class,
        self::SUBSCRIPTIONS_SUBSCRIPTION    => \RZP\Models\Plan\Subscription\Service::class,
        self::SUBSCRIPTIONS_ADDON           => \RZP\Models\Plan\Subscription\Service::class,
        self::SUBSCRIPTIONS_PLAN            => \RZP\Models\Plan\Subscription\Service::class,
        self::SUBSCRIPTIONS_CYCLE           => \RZP\Models\Plan\Subscription\Service::class,
        self::SUBSCRIPTIONS_VERSION         => \RZP\Models\Plan\Subscription\Service::class,
        self::SUBSCRIPTIONS_UPDATE_REQUEST  => \RZP\Models\Plan\Subscription\Service::class,
        self::SUBSCRIPTIONS_TRANSACTION     => \RZP\Models\Plan\Subscription\Service::class,
        self::STORK_WEBHOOK                 => \RZP\Services\Stork::class,
        self::FTS_TRANSFERS                 => \RZP\Services\FTS\FtsAdminClient::class,
        self::FTS_FUND_ACCOUNT              => \RZP\Services\FTS\FtsAdminClient::class,
        self::FTS_BENEFICIARY_STATUS        => \RZP\Services\FTS\FtsAdminClient::class,
        self::FTS_ATTEMPTS                  => \RZP\Services\FTS\FtsAdminClient::class,
        self::FTS_SOURCE_ACCOUNT            => \RZP\Services\FTS\FtsAdminClient::class,
        self::FTS_SOURCE_ACCOUNT_MAPPING    => \RZP\Services\FTS\FtsAdminClient::class,
        self::FTS_DIRECT_ACCOUNT_ROUTING_RULES => \RZP\Services\FTS\FtsAdminClient::class,
        self::FTS_PREFERRED_ROUTING_WEIGHTS => \RZP\Services\FTS\FtsAdminClient::class,
        self::FTS_ACCOUNT_TYPE_MAPPINGS     => \RZP\Services\FTS\FtsAdminClient::class,
        self::FTS_SCHEDULES                 => \RZP\Services\FTS\FtsAdminClient::class,
        self::FTS_TRIGGER_STATUS_LOGS       => \RZP\Services\FTS\FtsAdminClient::class,
        self::FTS_MERCHANT_CONFIGURATIONS   => \RZP\Services\FTS\FtsAdminClient::class,
        self::FTS_CHANNEL_INFORMATION_STATUS_LOGS => \RZP\Services\FTS\FtsAdminClient::class,
        self::FTS_FAIL_FAST_STATUS_LOGS     => \RZP\Services\FTS\FtsAdminClient::class,
        self::FTS_KEY_VALUE_STORE_LOGS      => \RZP\Services\FTS\FtsAdminClient::class,
        self::UFH_FILES                     => \RZP\Services\UfhClient::class,
        self::PAYMENTS_NBPLUS_PAYMENTS      => \RZP\Services\NbPlus\Service::class,
        self::PAYMENTS_NBPLUS_NETBANKING    => \RZP\Services\NbPlus\Netbanking::class,
        self::NBPLUS_EMANDATE_REGISTRATION  => \RZP\Services\NbPlus\Emandate::class,
        self::NBPLUS_EMANDATE_DEBIT         => \RZP\Services\NbPlus\Emandate::class,
        self::PAYMENTS_NBPLUS_CARDLESS_EMI_GATEWAY  => \RZP\Services\NbPlus\CardlessEmi::class,
        self::PAYMENTS_NBPLUS_APP_GATEWAY   => \RZP\Services\NbPlus\AppMethod::class,
        self::PAYMENTS_NBPLUS_PAYLATER_GATEWAY      => \RZP\Services\NbPlus\Paylater::class,
        self::PAYMENTS_NBPLUS_WALLET_TRANSACTION          => \RZP\Services\NbPlus\Wallet::class,
        self::PAYMENTS_NBPLUS_WALLET_AUTHORIZATION        => \RZP\Services\NbPlus\Wallet::class,

        self::PAYOUT_LINK                   => \RZP\Models\PayoutLink\Service::class,
        self::SETTINGS                      => \RZP\Models\Settings\Service::class,

        self::CAPITAL_COLLECTIONS_PLAN              => \RZP\Services\CapitalCollectionsClient::class,
        self::CAPITAL_COLLECTIONS_LEDGER_BALANCE    => \RZP\Services\CapitalCollectionsClient::class,
        self::CAPITAL_COLLECTIONS_PRIORITIZATION    => \RZP\Services\CapitalCollectionsClient::class,
        self::CAPITAL_COLLECTIONS_INSTALLMENT       => \RZP\Services\CapitalCollectionsClient::class,
        self::CAPITAL_COLLECTIONS_CHARGE            => \RZP\Services\CapitalCollectionsClient::class,
        self::CAPITAL_COLLECTIONS_CREDIT_REPAYMENT  => \RZP\Services\CapitalCollectionsClient::class,
        self::CAPITAL_COLLECTIONS_REPAYMENT         => \RZP\Services\CapitalCollectionsClient::class,
        self::CAPITAL_COLLECTIONS_REPAYMENT_BREAKUP => \RZP\Services\CapitalCollectionsClient::class,
        self::CAPITAL_COLLECTIONS_INTEREST_WAIVER   => \RZP\Services\CapitalCollectionsClient::class,

        self::CARE_CALLBACK                         => \RZP\Services\CareServiceClient::class,
        self::CARE_CALLBACK_LOG                     => \RZP\Services\CareServiceClient::class,
        self::CARE_CALLBACK_OPERATOR                => \RZP\Services\CareServiceClient::class,

        self::LINE_OF_CREDIT_ACCOUNT_BALANCES                       => \RZP\Services\CapitalLineOfCreditClient::class,
        self::LINE_OF_CREDIT_ONBOARDINGS                            => \RZP\Services\CapitalLineOfCreditClient::class,
        self::LINE_OF_CREDIT_REPAYMENTS                             => \RZP\Services\CapitalLineOfCreditClient::class,
        self::LINE_OF_CREDIT_WITHDRAWAL_CONFIGS                     => \RZP\Services\CapitalLineOfCreditClient::class,
        self::LINE_OF_CREDIT_DESTINATION_ACCOUNTS                   => \RZP\Services\CapitalLineOfCreditClient::class,
        self::LINE_OF_CREDIT_REPAYMENT_BREAKDOWNS                   => \RZP\Services\CapitalLineOfCreditClient::class,
        self::LINE_OF_CREDIT_SOURCE_ACCOUNTS                        => \RZP\Services\CapitalLineOfCreditClient::class,
        self::LINE_OF_CREDIT_WITHDRAWALS                            => \RZP\Services\CapitalLineOfCreditClient::class,

        self::CAPITAL_LOS_D2C_BUREAU_REPORTS                        => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_APPLICATIONS                              => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_APPLICATION_CREDIT_POLICY_MAPPINGS        => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_BUSINESSES                                => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_BUSINESS_APPLICANTS                       => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_CARD_OFFERS                               => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_CONTRACTS                                 => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_CREDIT_OFFERS                             => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_LOC_OFFERS                                => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_CREDIT_POLICIES                           => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_DISBURSALS                                => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_DOC_SIGN_FILES                            => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_DOCUMENTS                                 => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_DOCUMENT_GROUPS                           => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_DOCUMENT_MASTERS                          => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_DOCUMENT_MASTERS_GROUPS                   => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_DOCUMENT_SIGNS                            => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_LEAD_TYPES                                => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_LENDERS                                   => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_NACH_APPLICATIONS                         => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_NACH_MANDATES                             => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_OFFER_VERIFICATION_TASKS                  => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_PRODUCTS                                  => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_PRODUCT_LENDERS                           => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_SIGN_INVITEES                             => \RZP\Services\ExternalServiceClient::class,
        self::CAPITAL_LOS_VENDORS                                   => \RZP\Services\ExternalServiceClient::class,

        self::PAYOUTS_PAYOUTS        => \RZP\Services\PayoutService\AdminFetch::class,
        self::PAYOUTS_REVERSALS      => \RZP\Services\PayoutService\AdminFetch::class,
        self::PAYOUTS_PAYOUT_LOGS    => \RZP\Services\PayoutService\AdminFetch::class,
        self::PAYOUTS_PAYOUT_SOURCES => \RZP\Services\PayoutService\AdminFetch::class,

        self::GOVERNOR_RULES        => \RZP\Services\GovernorService::class,
        self::GOVERNOR_RULE_GROUPS  => \RZP\Services\GovernorService::class,

        self::VENDOR_PAYMENTS_VENDOR_PAYMENTS                       => \RZP\Services\VendorPayments\Service::class,
        self::VENDOR_PAYMENTS_ICICI_TAX_PAY_REQUESTS                => \RZP\Services\VendorPayments\Service::class,
        self::VENDOR_PAYMENTS_TAX_PAYMENTS                          => \RZP\Services\VendorPayments\Service::class,
        self::VENDOR_PAYMENTS_DIRECT_TAX_PAYMENTS                   => \RZP\Services\VendorPayments\Service::class,
        self::VENDOR_PAYMENTS_PG_PAYMENTS                           => \RZP\Services\VendorPayments\Service::class,

        self::PAYMENTS_UPI_FISCAL      => \RZP\Services\UpiPayment\Service::class,
    ];

    protected static $syncedInLiveAndTest = [
        self::ORG,
        self::IIN,
        self::USER,
        self::FEATURE,
        self::METHODS,
        self::PRICING,
        self::EMI_PLAN,
        self::MERCHANT,
        self::SCHEDULE,
        self::PARTNER_CONFIG,
        self::MERCHANT_ACCESS_MAP,
        self::PARTNER_KYC_ACCESS_STATE,
        self::BVS_VALIDATION,
        self::MERCHANT_APPLICATION,
        self::STAKEHOLDER,
        self::APP_STORE,
        self::PARTNER_KYC_ACCESS_STATE,
        self::MERCHANT_OTP_VERIFICATION_LOGS,
        self::ROLES,
        self::ACCESS_CONTROL_HISTORY_LOGS,
        self::ACCESS_CONTROL_PRIVILEGES,
        self::ACCESS_POLICY_AUTHZ_ROLES_MAP,
        self::ROLE_ACCESS_POLICY_MAP,
    ];

    protected static $externalEntities = [
        self::SUBSCRIPTION,
        self::PAYOUT_LINK
    ];

    public static function getAllEntities()
    {
        return array_keys(self::$namespace);
    }

    public static function getEntityNamespace(string $entity)
    {
        self::validateIsEntity($entity);

        if (array_key_exists($entity, self::$namespace))
        {
            return self::$namespace[$entity];
        }

        // Converts first character of the
        // words (delimited by underscores/hyphens/spaces) to uppercase
        return 'RZP\Models\\' . studly_case($entity);
    }

    public static function getEntityClass(string $entity)
    {
        $ns = self::getEntityNamespace($entity);

        $class = $ns . '\Entity';

        return $class;
    }

    /**
     * Returns the observer class for a given entity
     * If no observer class is defined for the entity,
     * we return the Base Observer class
     *
     * @param  string $entity
     * @return string
     */
    public static function getEntityObserverClass(string $entity): string
    {
        $entityNamespace = self::getEntityNamespace($entity);

        $entityObserverClass = $entityNamespace . '\\Observer';

        return (class_exists($entityObserverClass) === true) ?
            $entityObserverClass :
            BaseObserver::class;
    }

    public static function getEntityObject($entity)
    {
        $class = self::getEntityClass($entity);

        if (class_exists($class) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid entity: ' . $entity);
        }

        return new $class;
    }

    public static function getEntityRepository(string $entity, $repositoryType = 'Repository'): string
    {
        $class = self::getEntityNamespace($entity) . '\\' . $repositoryType;
        if (class_exists($class) === false)
        {
            if (isset(self::$repository[$entity]))
            {
                $class = self::$repository[$entity] . '\\' . $repositoryType;
            }
            else
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Not a valid repository: ' . $entity);
            }
        }

        return $class;
    }

    public static function getEntityService(string $entity)
    {
        $class = self::getEntityNamespace($entity) . '\\' . 'Service';

        return $class;
    }

    public static function getEntityEsRepository(string $entity)
    {
        return self::getEntityRepository($entity, 'EsRepository');
    }

    /**
     * @param string $entity
     *
     * @return null|Fetch
     */
    public static function getEntityFetch(string $entity)
    {
        $class = self::getEntityNamespace($entity) . '\\' . 'Fetch';

        if (class_exists($class) === true)
        {
            return new $class;
        }
    }

    public static function getTableNameForEntity(string $entity)
    {
        return Table::getTableNameForEntity($entity);
    }

    public static function validateIsEntity($entity)
    {
        if (self::isValidEntity($entity) === false)
        {
            App::getFacadeRoot()['trace']->error(
                TraceCode::ERROR_INVALID_ARGUMENT,
                ['entity' => $entity]);

            throw new Exception\BadRequestValidationFailureException(
                'Not a valid entity.');
        }
    }

    public static function isValidEntity($entity)
    {
        // For dealing with sub.entity types

        $entity = str_replace('.', '_', $entity);

        return (defined(__CLASS__ . '::' . strtoupper($entity)));
    }

    public static function validateEntityOrFail($entity)
    {
        if (self::isValidEntity($entity) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid entity.');
        }
    }

    public static function validateEntityOrFailPublic($entity)
    {
        if (self::isValidEntity($entity) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid entity.');
        }
    }

    /**
     * For API entities there would only be entity, which would be verified by normal flow
     * For other, we need to validate the service should exists, and entity is exposed
     *
     * @param string $entity
     * @return bool
     * @throws Exception\BadRequestValidationFailureException
     */
    public static function validateExternalServiceEntity(string $entity)
    {
        return (isset(self::$externalServiceClass[$entity]) === true);
    }

    public static function getExternalServiceClass(string $entity)
    {
        $class = self::$externalServiceClass[$entity];

        return new $class;
    }

    public static function validateExternalRepoEntity(string $entity)
    {
        return (isset(self::$externalRepoSingleton[$entity]) === true);
    }

    public static function archivedEntityDbFallbackEnabled(string $entity) : bool
    {
        if (in_array($entity, self::ARCHIVED_ENTITIES, true) === true)
        {
            return true;
        }

        return false;
    }

    public static function getExternalConfigKeyName(string $entity)
    {
        return self::$externalRepoConfigKey[$entity];
    }

    public static function getExternalRepoSingleton(string $entity)
    {
        $singletonName = self::$externalRepoSingleton[$entity];

        return App::getFacadeRoot()[$singletonName];
    }

    public static function getExternalEntityName(string $entity, $object)
    {
        if($object instanceof \RZP\Services\ExternalServiceClient)
        {
            return $entity;
        }

        if(strpos($entity, '.') == true)
        {
            return explode('.', $entity)[1];
        }
        else
        {
            return $entity;
        }
    }

    public static function getCustomEagerLoadRelations() : array
    {
        return array_keys(self::$customEagerLoadEntityMapping);
    }

    public static function getCustomEagerLoadRelationEntity(string $entity) : string
    {
        return self::$customEagerLoadEntityMapping[$entity];
    }

    public static function getCustomEagerLoadEntityKey(string $relation) : string
    {
        return self::$customEagerLoadEntityKeys[$relation];
    }

    public static function isEntitySyncedInLiveAndTest($entity)
    {
        return in_array($entity, self::$syncedInLiveAndTest, true);
    }

    /**
     * Returns the entity name from given sign. Only iterates over the scope of allowed entities for keyless auth.
     * @param  string      $sign
     * @return string|null
     */
    public static function getKeylessAllowedEntityFromSign(string $sign)
    {
        foreach (self::KEYLESS_ALLOWED_ENTITIES as $allowedEntity)
        {
            $allowedEntityClass = self::getEntityClass($allowedEntity);

            if ($sign === $allowedEntityClass::getSign())
            {
                return $allowedEntity;
            }
        }
    }

    public static function isExternalEntity($entity)
    {
        return in_array($entity, self::$externalEntities);
    }

    /**
     * @param string $entity
     *
     * @return mixed
     */
    public static function getEntityCoreClass(string $entity)
    {
        $class = self::getEntityNamespace($entity) . '\\' . 'Core';

        if (class_exists($class) === true)
        {
            return new $class;
        }
    }
}
