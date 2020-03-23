<?php

namespace RZP\Constants;

use App;
use Trace;

use RZP\Models;
use RZP\Gateway;
use RZP\Exception;
use RZP\Base\Fetch;
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
    const REFUND                     = 'refund';
    const REPORT                     = 'report';
    const CONTACT                    = 'contact';
    const DISPUTE                    = 'dispute';
    const ADDRESS                    = 'address';
    const BALANCE                    = 'balance';
    const CREDITS                    = 'credits';
    const FEATURE                    = 'feature';
    const INVOICE                    = 'invoice';
    const METHODS                    = 'methods';
    const PAYMENT                    = 'payment';
    const PRICING                    = 'pricing';
    const WEBHOOK                    = 'webhook';
    const QR_CODE                    = 'qr_code';
    const OPTIONS                    = 'options';
    const ACCOUNT                    = 'account';
    const DISCOUNT                   = 'discount';
    const EMI_PLAN                   = 'emi_plan';
    const CUSTOMER                   = 'customer';
    const MERCHANT                   = 'merchant';
    const REVERSAL                   = 'reversal';
    const SCHEDULE                   = 'schedule';
    const TERMINAL                   = 'terminal';
    const TRANSFER                   = 'transfer';
    const EXTERNAL                   = 'external';
    const REFERRALS                  = 'referrals';
    const STATEMENT                  = 'statement';
    const BHARAT_QR                  = 'bharat_qr';
    const PROMOTION                  = 'promotion';
    const LINE_ITEM                  = 'line_item';
    const APP_TOKEN                  = 'app_token';
    const AUTH_TOKEN                 = 'auth_token';
    const INVITATION                 = 'invitation';
    const ADJUSTMENT                 = 'adjustment';
    const CREDITNOTE                 = 'creditnote';
    const FILE_STORE                 = 'file_store';
    const SETTLEMENT                 = 'settlement';
    const TRANSACTION                = 'transaction';
    const FEE_BREAKUP                = 'fee_breakup';
    const UPI_MANDATE                = 'upi_mandate';
    const PAYOUT_LINK                = 'payout_link';
    const LEGAL_ENTITY               = 'legal_entity';
    const PAYMENT_LINK               = 'payment_link';
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
    const MERCHANT_DETAIL            = 'merchant_detail';
    const TERMINAL_ACTION            = 'terminal_action';
    const BANKING_ACCOUNT            = 'banking_account';
    const PAYMENT_DOWNTIME           = 'payment.downtime';
    const MERCHANT_REQUEST           = 'merchant_request';
    const CUSTOMER_BALANCE           = 'customer_balance';
    const GATEWAY_DOWNTIME           = 'gateway_downtime';
    const MERCHANT_INVOICE           = 'merchant_invoice';
    const INVOICE_REMINDER           = 'invoice_reminder';
    const NODAL_BENEFICIARY          = 'nodal_beneficiary';
    const PAYMENT_PAGE_ITEM          = 'payment_page_item';
    const PAYMENT_ANALYTICS          = 'payment_analytics';
    const D2C_BUREAU_DETAIL          = 'd2c_bureau_detail';
    const D2C_BUREAU_REPORT          = 'd2c_bureau_report';
    const SETTLEMENT_BUCKET          = 'settlement_bucket';
    const MERCHANT_DOCUMENT          = 'merchant_document';
    const SETTLEMENT_DETAILS         = 'settlement_details';
    const CREDITNOTE_INVOICE         = 'creditnote_invoice';
    const MERCHANT_PROMOTION         = 'merchant_promotion';
    const CREDIT_TRANSACTION         = 'credit_transaction';
    const MERCHANT_EMI_PLANS         = 'merchant_emi_plans';
    const COMMISSION_INVOICE         = 'commission_invoice';
    const TERMINAL_ANALYTICS         = 'terminal_analytics';
    const SETTLEMENT_TRANSFER        = 'settlement_transfer';
    const MERCHANT_ACCESS_MAP        = 'merchant_access_map';
    const BATCH_FUND_TRANSFER        = 'batch_fund_transfer';
    const PAPER_MANDATE_UPLOAD       = 'paper_mandate_upload';
    const CUSTOMER_TRANSACTION       = 'customer_transaction';
    const BANKING_ACCOUNT_STATE      = 'banking_account_state';
    const FUND_TRANSFER_ATTEMPT      = 'fund_transfer_attempt';
    const SETTLEMENT_DESTINATION     = 'settlement_destination';
    const BANKING_ACCOUNT_DETAIL     = 'banking_account_detail';
    const FUND_ACCOUNT_VALIDATION    = 'fund_account_validation';
    const MERCHANT_INHERITANCE_MAP   = 'merchant_inheritance_map';
    const SUBSCRIPTION_REGISTRATION  = 'subscription_registration';
    const BANKING_ACCOUNT_STATEMENT  = 'banking_account_statement';
    const MERCHANT_FRESHDESK_TICKETS = 'merchant_freshdesk_tickets';
    const TERMINAL_ONBOARDING_DETAIL = 'terminal_onboarding_detail';


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

    //
    // Workflow Entities
    //
    const WORKFLOW                      = 'workflow';
    const ACTION_STATE                  = 'action_state';
    const WORKFLOW_STEP                 = 'workflow_step';
    const ACTION_COMMENT                = 'action_comment';
    const ACTION_CHECKER                = 'action_checker';
    const WORKFLOW_ACTION               = 'workflow_action';
    const WORKFLOW_PAYOUT_AMOUNT_RULES  = 'workflow_payout_amount_rules';

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
    const AEPS                   = 'aeps';
    const AMEX                   = 'amex';
    const HDFC                   = 'hdfc';
    const ATOM                   = 'atom';
    const ENACH                  = 'enach';
    const SHARP                  = 'sharp';
    const PAYTM                  = 'paytm';
    const MOZART                 = 'mozart';
    const WALLET                 = 'wallet';
    const UPI_SBI                = 'upi_sbi';
    const HITACHI                = 'hitachi';
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
    const MPI_BLADE              = 'mpi_blade';
    const NACH_CITI              = 'nach_citi';
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
    const WALLET_MPESA           = 'wallet_mpesa';
    const WALLET_PAYPAL          = 'wallet_paypal';
    const ESIGNER_DIGIO          = 'esigner_digio';
    const NETBANKING_CUB         = 'netbanking_cub';
    const NETBANKING_SIB         = 'netbanking_sib';
    const NETBANKING_CBI         = 'netbanking_cbi';
    const PAYLATER_ICICI         = 'paylater_icici';
    const HDFC_DEBIT_EMI         = 'hdfc_debit_emi';
    const NETBANKING_OBC         = 'netbanking_obc';
    const NETBANKING_RBL         = 'netbanking_rbl';
    const NETBANKING_SBI         = 'netbanking_sbi';
    const NETBANKING_KVB         = 'netbanking_kvb';
    const WALLET_PHONEPE         = 'wallet_phonepe';
    const NETBANKING_CSB         = 'netbanking_csb';
    const NETBANKING_IBK         = 'netbanking_ibk';
    const NETBANKING_UBI         = 'netbanking_ubi';
    const NETBANKING_SCB         = 'netbanking_scb';
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

    // P2P Gateways
    const P2P_UPI_AXIS           = 'p2p_upi_axis';
    const P2P_UPI_SHARP          = 'p2p_upi_sharp';

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
    // Service: Shield
    const SHIELD_RULES                 = 'shield.rules';
    const SHIELD_RISKS                 = 'shield.risks';
    const SHIELD_LISTS                 = 'shield.lists';
    const SHIELD_LIST_ITEMS            = 'shield.list_items';
    const SHIELD_RULE_ANALYTICS        = 'shield.rule_analytics';

    const PAYMENTS_CARDS_AUTHORIZATION  = 'payments_cards.authorization';
    const PAYMENTS_CARDS_AUTHENTICATION = 'payments_cards.authentication';

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

    const FTS_ATTEMPTS                   = 'fts.attempts';
    const FTS_TRANSFERS                  = 'fts.transfers';
    const FTS_FUND_ACCOUNT               = 'fts.fund_accounts';
    const FTS_BENEFICIARY_STATUS         = 'fts.beneficiary_status';

    const UFH_FILES                      = 'ufh.files';

    const COMMISSION = 'commission';


    const PAYMENTS_NBPLUS_NETBANKING = 'payments_nbplus.netbanking';

    // Service: Payments UPi
    const PAYMENTS_UPI_VPA              = 'payments_upi_vpa';
    const PAYMENTS_UPI_BANK_ACCOUNT     = 'payments_upi_bank_account';
    const PAYMENTS_UPI_VPA_BANK_ACCOUNT = 'payments_upi_vpa_bank_account';
    const CONFIG                        = 'config';


    /**
     * Defines a map of entites which are currently
     * being cached and associated cache version prefixes
     * and the specific cache ttl for any entities.
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
            QueryCacheConstants::VERSION => 'v1',
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
        Entity::BANK_TRANSFER,
        Entity::REFUND,
        Entity::FUND_ACCOUNT_VALIDATION,
        Entity::ADJUSTMENT,
        Entity::SETTLEMENT,
    ];

    public static $namespace = [
        self::IIN                       => \RZP\Models\Card\IIN::class,
        self::P2P                       => \RZP\Models\P2p::class,
        self::VPA                       => \RZP\Models\Vpa::class,
        self::UPI                       => \RZP\Gateway\Upi\Base::class,
        self::IIN                       => \RZP\Models\Card\IIN::class,
        self::EBS                       => \RZP\Gateway\Ebs::class,
        self::ATOM                      => \RZP\Gateway\Atom::class,
        self::AMEX                      => \RZP\Gateway\Amex::class,
        self::HDFC                      => \RZP\Gateway\Hdfc::class,
        self::USER                      => \RZP\Models\User::class,
        self::OFFER                     => \RZP\Models\Offer::class,
        self::ORDER                     => \RZP\Models\Order::class,
        self::TOKEN                     => \RZP\Models\Customer\Token::class,
        self::GEO_IP                    => \RZP\Models\GeoIP::class,
        self::REFUND                    => \RZP\Models\Payment\Refund::class,
        self::REPORT                    => \RZP\Models\Report::class,
        self::BALANCE                   => \RZP\Models\Merchant\Balance::class,
        self::BALANCE_CONFIG            => \RZP\Models\Merchant\Balance\BalanceConfig::class,
        self::CREDITS                   => \RZP\Models\Merchant\Credits::class,
        self::METHODS                   => \RZP\Models\Merchant\Methods::class,
        self::PRICING                   => \RZP\Models\Pricing::class,
        self::FEATURE                   => \RZP\Models\Feature::class,
        self::WEBHOOK                   => \RZP\Models\Merchant\Webhook::class,
        self::DISPUTE                   => \RZP\Models\Dispute::class,
        self::CUSTOMER                  => \RZP\Models\Customer::class,
        self::EMI_PLAN                  => \RZP\Models\Emi::class,
        self::MERCHANT                  => \RZP\Models\Merchant::class,
        self::LEGAL_ENTITY              => \RZP\Models\Merchant\LegalEntity::class,
        self::ACCOUNT                   => \RZP\Models\Merchant\Account::class,
        self::SCHEDULE                  => \RZP\Models\Schedule::class,
        self::COUPON                    => \RZP\Models\Coupon::class,
        self::PROMOTION                 => \RZP\Models\Promotion::class,
        self::APP_TOKEN                 => \RZP\Models\Customer\AppToken::class,
        self::STATEMENT                 => \RZP\Models\Transaction\Statement::class,
        self::INVITATION                => \RZP\Models\Invitation::class,
        self::FILE_STORE                => \RZP\Models\FileStore::class,
        self::FEE_BREAKUP               => \RZP\Models\Transaction\FeeBreakup::class,
        self::ENTITY_OFFER              => \RZP\Models\Offer\EntityOffer::class,
        self::BANK_ACCOUNT              => \RZP\Models\BankAccount::class,
        self::SUBSCRIPTION              => \RZP\Models\Plan\Subscription::class,
        self::PAYMENT_LINK              => \RZP\Models\PaymentLink::class,
        self::PAYOUT_LINK               => \RZP\Models\PayoutLink::class,
        self::PAYMENT_PAGE_ITEM         => \RZP\Models\PaymentLink\PaymentPageItem::class,
        self::GATEWAY_TOKEN             => \RZP\Models\Customer\GatewayToken::class,
        self::ENTITY_ORIGIN             => \RZP\Models\EntityOrigin::class,
        self::SCHEDULE_TASK             => \RZP\Models\Schedule\Task::class,
        self::DISPUTE_REASON            => \RZP\Models\Dispute\Reason::class,
        self::MERCHANT_DETAIL           => \RZP\Models\Merchant\Detail::class,
        self::TERMINAL_ACTION           => \RZP\Models\Terminal\Action::class,
        self::BANKING_ACCOUNT           => \RZP\Models\BankingAccount::class,
        self::BANKING_ACCOUNT_STATE     => \RZP\Models\BankingAccount\State::class,
        self::MERCHANT_REQUEST          => \RZP\Models\Merchant\Request::class,
        self::CUSTOMER_BALANCE          => \RZP\Models\Customer\Balance::class,
        self::GATEWAY_DOWNTIME          => \RZP\Models\Gateway\Downtime::class,
        self::PAYMENT_DOWNTIME          => \RZP\Models\Payment\Downtime::class,
        self::GATEWAY_RULE              => \RZP\Models\Gateway\Rule::class,
        self::GATEWAY_FILE              => \RZP\Models\Gateway\File::class,
        self::MERCHANT_USER             => \RZP\Models\Merchant\MerchantUser::class,
        self::MERCHANT_EMAIL            => \RZP\Models\Merchant\Email::class,
        self::REFERRALS                 => \RZP\Models\Merchant\Referral::class,
        self::PAYMENT_ANALYTICS         => \RZP\Models\Payment\Analytics::class,
        self::CREDIT_TRANSACTION        => \RZP\Models\Merchant\Credits\Transaction::class,
        self::MERCHANT_PROMOTION        => \RZP\Models\Merchant\Promotion::class,
        self::MERCHANT_INVOICE          => \RZP\Models\Merchant\Invoice::class,
        self::MERCHANT_EMI_PLANS        => \RZP\Models\Merchant\EmiPlans::class,
        self::NODAL_STATEMENT           => \RZP\Models\Nodal\Statement::class,
        self::SETTLEMENT_DETAILS        => \RZP\Models\Settlement\Details::class,
        self::SETTLEMENT_BUCKET         => \RZP\Models\Settlement\Bucket::class,
        self::SETTLEMENT_DESTINATION    => \RZP\Models\Settlement\Destination::class,
        self::SETTLEMENT_TRANSFER       => \RZP\Models\Settlement\Transfer::class,
        self::TERMINAL_ANALYTICS        => \RZP\Models\Payment\TerminalAnalytics::class,
        self::MERCHANT_ACCESS_MAP       => \RZP\Models\Merchant\AccessMap::class,
        self::MERCHANT_INHERITANCE_MAP  => \RZP\Models\Merchant\InheritanceMap::class,
        self::BATCH_FUND_TRANSFER       => \RZP\Models\FundTransfer\Batch::class,
        self::CUSTOMER_TRANSACTION      => \RZP\Models\Customer\Transaction::class,
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
        self::MERCHANT_DOCUMENT         => \RZP\Models\Merchant\Document::class,
        self::D2C_BUREAU_DETAIL         => \RZP\Models\D2cBureauDetail::class,
        self::D2C_BUREAU_REPORT         => \RZP\Models\D2cBureauReport::class,
        self::ADDON                     => \RZP\Models\Plan\Subscription\Addon::class,
        self::BANKING_ACCOUNT_DETAIL    => \RZP\Models\BankingAccount\Detail::class,
        self::OFFLINE_DEVICE            => \RZP\Models\Offline\Device::class,
        self::UPI_METADATA              => \RZP\Models\Payment\UpiMetadata::class,

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
        self::UPI_MINDGATE           => \RZP\Gateway\Upi\Mindgate::class,
        self::UPI_SBI                => \RZP\Gateway\Upi\Sbi::class,
        self::UPI_ICICI              => \RZP\Gateway\Upi\Icici::class,
        self::UPI_AXIS               => \RZP\Gateway\Upi\Axis::class,
        self::UPI_HULK               => \RZP\Gateway\Upi\Hulk::class,
        self::UPI_RBL                => \RZP\Gateway\Upi\Rbl::class,
        self::UPI_YESBANK            => \RZP\Gateway\Upi\Yesbank::class,
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
        self::WALLET_PAYZAPP         => \RZP\Gateway\Wallet\Payzapp::class,
        self::WALLET_OLAMONEY        => \RZP\Gateway\Wallet\Olamoney::class,
        self::WALLET_JIOMONEY        => \RZP\Gateway\Wallet\Jiomoney::class,
        self::WALLET_SBIBUDDY        => \RZP\Gateway\Wallet\Sbibuddy::class,
        self::NETBANKING_SIB         => \RZP\Gateway\Mozart::class,
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
        self::WALLET_PAYUMONEY       => \RZP\Gateway\Wallet\Payumoney::class,
        self::WALLET_OPENWALLET      => \RZP\Gateway\Wallet\Openwallet::class,
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
        self::WALLET_PAYPAL          => \RZP\Gateway\Mozart::class,
        self::UPI_AIRTEL             => \RZP\Gateway\Mozart::class,
        self::UPI_JUSPAY             => \RZP\Gateway\Mozart::class,
        self::UPI_CITI               => \RZP\Gateway\Mozart::class,
        self::PAYLATER               => \RZP\Gateway\CardlessEmi::class,
        self::WORLDLINE              => \RZP\Gateway\Worldline::class,
        self::GETSIMPL               => \RZP\Gateway\Mozart::class,
        self::PAYLATER_ICICI         => \RZP\Gateway\Mozart::class,
        self::HDFC_DEBIT_EMI         => \RZP\Gateway\Mozart::class,

        // heimdall
        self::ORG                          => \RZP\Models\Admin\Org::class,
        self::ROLE                         => \RZP\Models\Admin\Role::class,
        self::ADMIN                        => \RZP\Models\Admin\Admin::class,
        self::GROUP                        => \RZP\Models\Admin\Group::class,
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

        self::P2P_UPI_SHARP         => \RZP\Gateway\P2p\Upi::class,
        self::P2P_UPI_AXIS          => \RZP\Gateway\P2p\Upi::class,

        self::COMMISSION            => \RZP\Models\Partner\Commission::class,
        self::COMMISSION_INVOICE    => \RZP\Models\Partner\Commission\Invoice::class,

        self::OPTIONS               => \RZP\Models\Options::class,

        self::PAYMENTS_UPI_VPA              => \RZP\Models\PaymentsUpi\Vpa::class,
        self::PAYMENTS_UPI_BANK_ACCOUNT     => \RZP\Models\PaymentsUpi\BankAccount::class,
        self::PAYMENTS_UPI_VPA_BANK_ACCOUNT => \RZP\Models\PaymentsUpi\Vpa\BankAccount::class,

        self::MERCHANT_FRESHDESK_TICKETS    => \RZP\Models\Merchant\FreshdeskTicket::class,

        self::CONFIG                        => \RZP\Models\Payment\Config::class,
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

        self::NETBANKING_IDFC        => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_VIJAYA      => \RZP\Gateway\Netbanking\Base::class,

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
        self::UPI_YESBANK            => \RZP\Gateway\Upi\Base::class,

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

        self::CARDLESS_EMI           => \RZP\Gateway\CardlessEmi::class,
        self::PAYLATER               => \RZP\Gateway\CardlessEmi::class,

        self::NODAL_STATEMENT        => \RZP\Models\Nodal\Statement::class,

        self::PAYMENT_DOWNTIME       => \RZP\Models\Payment\Downtime::class,
        self::BANKING_ACCOUNT_STATE  => \RZP\Models\BankingAccount\State::class,
    ];

    protected static $externalServiceClass = [
        self::REPORTING_LOGS                => \RZP\Services\Reporting::class,
        self::REPORTING_CONFIGS             => \RZP\Services\Reporting::class,
        self::REPORTING_SCHEDULES           => \RZP\Services\Reporting::class,
        self::SHIELD_RULES                  => \RZP\Services\ShieldClient::class,
        self::SHIELD_RULE_ANALYTICS         => \RZP\Services\ShieldClient::class,
        self::SHIELD_RISKS                  => \RZP\Services\ShieldClient::class,
        self::SHIELD_LISTS                  => \RZP\Services\ShieldClient::class,
        self::SHIELD_LIST_ITEMS             => \RZP\Services\ShieldClient::class,
        self::BATCH_SERVICE                 => \RZP\Services\BatchMicroService::class,
        self::BATCH_FILE_STORE              => \RZP\Services\BatchMicroService::class,
        self::PAYMENTS_CARDS_AUTHENTICATION => \RZP\Services\CardPaymentService::class,
        self::PAYMENTS_CARDS_AUTHORIZATION  => \RZP\Services\CardPaymentService::class,
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
        self::UFH_FILES                     => \RZP\Services\UfhClient::class,
        self::PAYMENTS_NBPLUS_NETBANKING    => \RZP\Services\NbPlus\Netbanking::class,
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
    ];

    protected static $externalEntities = [
        self::SUBSCRIPTION
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

    public static function getEntityRepository(string $entity, $repositoryType = 'Repository')
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

    public static function getExternalEntityName(string $entity)
    {
        return explode('.', $entity)[1];
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
