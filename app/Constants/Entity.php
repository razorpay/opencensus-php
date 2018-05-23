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
    const IIN                   = 'iin';
    const KEY                   = 'key';
    const P2P                   = 'p2p';
    const VPA                   = 'vpa';
    const CARD                  = 'card';
    const PLAN                  = 'plan';
    const ITEM                  = 'item';
    const USER                  = 'user';
    const RISK                  = 'risk';
    const ADDON                 = 'addon';
    const BATCH                 = 'batch';
    const OFFER                 = 'offer';
    const ORDER                 = 'order';
    const TOKEN                 = 'token';
    const GEO_IP                = 'geo_ip';
    const COUPON                = 'coupon';
    const DEVICE                = 'device';
    const PAYOUT                = 'payout';
    const REFUND                = 'refund';
    const REPORT                = 'report';
    const DISPUTE               = 'dispute';
    const ADDRESS               = 'address';
    const BALANCE               = 'balance';
    const CREDITS               = 'credits';
    const FEATURE               = 'feature';
    const INVOICE               = 'invoice';
    const METHODS               = 'methods';
    const PAYMENT               = 'payment';
    const PRICING               = 'pricing';
    const WEBHOOK               = 'webhook';
    const DISCOUNT              = 'discount';
    const EMI_PLAN              = 'emi_plan';
    const CUSTOMER              = 'customer';
    const MERCHANT              = 'merchant';
    const ACCOUNT               = 'account';
    const REVERSAL              = 'reversal';
    const SCHEDULE              = 'schedule';
    const TERMINAL              = 'terminal';
    const TRANSFER              = 'transfer';
    const QR_CODE               = 'qr_code';
    const BHARAT_QR             = 'bharat_qr';
    const PROMOTION             = 'promotion';
    const LINE_ITEM             = 'line_item';
    const APP_TOKEN             = 'app_token';
    const INVITATION            = 'invitation';
    const ADJUSTMENT            = 'adjustment';
    const FILE_STORE            = 'file_store';
    const SETTLEMENT            = 'settlement';
    const TRANSACTION           = 'transaction';
    const FEE_BREAKUP           = 'fee_breakup';
    const GATEWAY_RULE          = 'gateway_rule';
    const GATEWAY_FILE          = 'gateway_file';
    const BANK_ACCOUNT          = 'bank_account';
    const FILE_HANDLER          = 'file_handler';
    const DISPUTE_FILE          = 'dispute_file';
    const SUBSCRIPTION          = 'subscription';
    const GATEWAY_TOKEN         = 'gateway_token';
    const BANK_TRANSFER         = 'bank_transfer';
    const SCHEDULE_TASK         = 'schedule_task';
    const LINE_ITEM_TAX         = 'line_item_tax';
    const DISPUTE_REASON        = 'dispute_reason';
    const NODAL_STATEMENT       = 'nodal_statement';
    const VIRTUAL_ACCOUNT       = 'virtual_account';
    const MERCHANT_DETAIL       = 'merchant_detail';
    const TERMINAL_ACTION       = 'terminal_action';
    const MERCHANT_REQUEST      = 'merchant_request';
    const CUSTOMER_BALANCE      = 'customer_balance';
    const GATEWAY_DOWNTIME      = 'gateway_downtime';
    const PAYMENT_ANALYTICS     = 'payment_analytics';
    const SETTLEMENT_DETAILS    = 'settlement_details';
    const MERCHANT_PROMOTION    = 'merchant_promotion';
    const CREDIT_TRANSACTION    = 'credit_transaction';
    const MERCHANT_INVOICE      = 'merchant_invoice';
    const MERCHANT_EMI_PLANS    = 'merchant_emi_plans';
    const TERMINAL_ANALYTICS    = 'terminal_analytics';
    const MERCHANT_ACCESS_MAP   = 'merchant_access_map';
    const BATCH_FUND_TRANSFER   = 'batch_fund_transfer';
    const CUSTOMER_TRANSACTION  = 'customer_transaction';
    const FUND_TRANSFER_ATTEMPT = 'fund_transfer_attempt';

    // heimdall
    const ORG                   = 'org';
    const ORG_HOSTNAME          = 'org_hostname';
    const ROLE                  = 'role';
    const PERMISSION            = 'permission';
    const GROUP                 = 'group';
    const ADMIN                 = 'admin';
    const ADMIN_LEAD            = 'admin_lead';
    const ADMIN_TOKEN           = 'admin_token';
    const ORG_FIELD_MAP         = 'org_field_map';

    //
    // Workflow Entities
    //
    const WORKFLOW              = 'workflow';
    const WORKFLOW_STEP         = 'workflow_step';
    const WORKFLOW_ACTION       = 'workflow_action';
    const ACTION_CHECKER        = 'action_checker';
    const ACTION_STATE          = 'action_state';
    const ACTION_COMMENT        = 'action_comment';

    // Generic comment and state entities
    const COMMENT               = 'comment';
    const STATE                 = 'state';
    const STATE_REASON          = 'state_reason';

    //
    // Gateway entities
    const EBS                    = 'ebs';
    const UPI                    = 'upi';
    const AEPS                   = 'aeps';
    const AMEX                   = 'amex';
    const MPI                    = 'mpi';
    const MPI_BLADE              = 'mpi_blade';
    const ATOM                   = 'atom';
    const ENACH                  = 'enach';
    const HDFC                   = 'hdfc';
    const HITACHI                = 'hitachi';
    const PAYTM                  = 'paytm';
    const SHARP                  = 'sharp';
    const WALLET                 = 'wallet';
    const BILLDESK               = 'billdesk';
    const MOBIKWIK               = 'mobikwik';
    const UPI_NPCI               = 'upi_npci';
    const AXIS_MIGS              = 'axis_migs';
    const FIRST_DATA             = 'first_data';
    const CARD_FSS               = 'card_fss';
    const AXIS_GENIUS            = 'axis_genius';
    const NETBANKING             = 'netbanking';
    const CYBERSOURCE            = 'cybersource';
    const AEPS_ICICI             = 'aeps_icici';
    const UPI_MINDGATE           = 'upi_mindgate';
    const UPI_SBI                = 'upi_sbi';
    const UPI_ICICI              = 'upi_icici';
    const UPI_HULK               = 'upi_hulk';
    const ENACH_RBL              = 'enach_rbl';
    const ESIGNER_DIGIO          = 'esigner_digio';
    const NETBANKING_AXIS        = 'netbanking_axis';
    const NETBANKING_HDFC        = 'netbanking_hdfc';
    const NETBANKING_BOB         = 'netbanking_bob';
    const NETBANKING_CORPORATION = 'netbanking_corporation';
    const NETBANKING_ICICI       = 'netbanking_icici';
    const NETBANKING_KOTAK       = 'netbanking_kotak';
    const NETBANKING_AIRTEL      = 'netbanking_airtel';
    const NETBANKING_FEDERAL     = 'netbanking_federal';
    const NETBANKING_RBL         = 'netbanking_rbl';
    const NETBANKING_INDUSIND    = 'netbanking_indusind';
    const NETBANKING_PNB         = 'netbanking_pnb';
    const NETBANKING_OBC         = 'netbanking_obc';
    const NETBANKING_CSB         = 'netbanking_csb';
    const WALLET_PAYZAPP         = 'wallet_payzapp';
    const WALLET_JIOMONEY        = 'wallet_jiomoney';
    const WALLET_SBIBUDDY        = 'wallet_sbibuddy';
    const WALLET_OLAMONEY        = 'wallet_olamoney';
    const WALLET_PAYUMONEY       = 'wallet_payumoney';
    const WALLET_FREECHARGE      = 'wallet_freecharge';
    const WALLET_OPENWALLET      = 'wallet_openwallet';
    const WALLET_AIRTELMONEY     = 'wallet_airtelmoney';
    const WALLET_MPESA           = 'wallet_mpesa';

    // Tax and Tax Groups
    const TAX                   = 'tax';
    const TAX_GROUP             = 'tax_group';

    // External Service Entity (ServiceName.EntityName)
    const REPORTING_LOGS               = 'reporting.logs';
    const REPORTING_CONFIGS            = 'reporting.configs';
    const REPORTING_SCHEDULES          = 'reporting.schedules';
    const SHIELD_RULES                 = 'shield.rules';
    const SHIELD_RULE_ANALYTICS        = 'shield.rule_analytics';

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
        self::CUSTOMER,
        self::SUBSCRIPTION,
    ];

    public static $namespace = [
        self::IIN                   => \RZP\Models\Card\IIN::class,
        self::P2P                   => \RZP\Models\P2p::class,
        self::VPA                   => \RZP\Models\Upi\Vpa::class,
        self::UPI                   => \RZP\Gateway\Upi\Base::class,
        self::IIN                   => \RZP\Models\Card\IIN::class,
        self::EBS                   => \RZP\Gateway\Ebs::class,
        self::ATOM                  => \RZP\Gateway\Atom::class,
        self::AMEX                  => \RZP\Gateway\Amex::class,
        self::HDFC                  => \RZP\Gateway\Hdfc::class,
        self::USER                  => \RZP\Models\User::class,
        self::OFFER                 => \RZP\Models\Offer::class,
        self::ADDON                 => \RZP\Models\Plan\Subscription\Addon::class,
        self::ORDER                 => \RZP\Models\Order::class,
        self::TOKEN                 => \RZP\Models\Customer\Token::class,
        self::GEO_IP                => \RZP\Models\GeoIP::class,
        self::REFUND                => \RZP\Models\Payment\Refund::class,
        self::REPORT                => \RZP\Models\Report::class,
        self::BALANCE               => \RZP\Models\Merchant\Balance::class,
        self::CREDITS               => \RZP\Models\Merchant\Credits::class,
        self::METHODS               => \RZP\Models\Merchant\Methods::class,
        self::PRICING               => \RZP\Models\Pricing::class,
        self::FEATURE               => \RZP\Models\Feature::class,
        self::WEBHOOK               => \RZP\Models\Merchant\Webhook::class,
        self::DISPUTE               => \RZP\Models\Dispute::class,
        self::CUSTOMER              => \RZP\Models\Customer::class,
        self::EMI_PLAN              => \RZP\Models\Emi::class,
        self::MERCHANT              => \RZP\Models\Merchant::class,
        self::ACCOUNT               => \RZP\Models\Merchant\Account::class,
        self::SCHEDULE              => \RZP\Models\Schedule::class,
        self::APP_TOKEN             => \RZP\Models\Customer\AppToken::class,
        self::INVITATION            => \RZP\Models\Invitation::class,
        self::FILE_STORE            => \RZP\Models\FileStore::class,
        self::FEE_BREAKUP           => \RZP\Models\Transaction\FeeBreakup::class,
        self::BANK_ACCOUNT          => \RZP\Models\BankAccount::class,
        self::SUBSCRIPTION          => \RZP\Models\Plan\Subscription::class,
        self::DISPUTE_FILE          => \RZP\Models\Dispute\File::class,
        self::GATEWAY_TOKEN         => \RZP\Models\Customer\GatewayToken::class,
        self::SCHEDULE_TASK         => \RZP\Models\Schedule\Task::class,
        self::DISPUTE_REASON        => \RZP\Models\Dispute\Reason::class,
        self::MERCHANT_DETAIL       => \RZP\Models\Merchant\Detail::class,
        self::TERMINAL_ACTION       => \RZP\Models\Terminal\Action::class,
        self::MERCHANT_REQUEST      => \RZP\Models\Merchant\Request::class,
        self::CUSTOMER_BALANCE      => \RZP\Models\Customer\Balance::class,
        self::GATEWAY_DOWNTIME      => \RZP\Models\Gateway\Downtime::class,
        self::GATEWAY_RULE          => \RZP\Models\Gateway\Rule::class,
        self::GATEWAY_FILE          => \RZP\Models\Gateway\File::class,
        self::PAYMENT_ANALYTICS     => \RZP\Models\Payment\Analytics::class,
        self::CREDIT_TRANSACTION    => \RZP\Models\Merchant\Credits\Transaction::class,
        self::MERCHANT_PROMOTION    => \RZP\Models\Merchant\Promotion::class,
        self::MERCHANT_INVOICE      => \RZP\Models\Merchant\Invoice::class,
        self::MERCHANT_EMI_PLANS    => \RZP\Models\Merchant\EmiPlans::class,
        self::NODAL_STATEMENT       => \RZP\Models\Nodal\Statement::class,
        self::SETTLEMENT_DETAILS    => \RZP\Models\Settlement\Details::class,
        self::TERMINAL_ANALYTICS    => \RZP\Models\Payment\TerminalAnalytics::class,
        self::MERCHANT_ACCESS_MAP   => \RZP\Models\Merchant\AccessMap::class,
        self::BATCH_FUND_TRANSFER   => \RZP\Models\FundTransfer\Batch::class,
        self::CUSTOMER_TRANSACTION  => \RZP\Models\Customer\Transaction::class,
        self::FUND_TRANSFER_ATTEMPT => \RZP\Models\FundTransfer\Attempt::class,
        self::VIRTUAL_ACCOUNT       => \RZP\Models\VirtualAccount::class,

        // gateways
        self::EBS                    => \RZP\Gateway\Ebs::class,
        self::ATOM                   => \RZP\Gateway\Atom::class,
        self::AMEX                   => \RZP\Gateway\Amex::class,
        self::HDFC                   => \RZP\Gateway\Hdfc::class,
        self::HITACHI                => \RZP\Gateway\Hitachi::class,
        self::PAYTM                  => \RZP\Gateway\Paytm::class,
        self::SHARP                  => \RZP\Gateway\Sharp::class,
        self::WALLET                 => \RZP\Gateway\Wallet\Base::class,
        self::BILLDESK               => \RZP\Gateway\Billdesk::class,
        self::MOBIKWIK               => \RZP\Gateway\Mobikwik::class,
        self::UPI_NPCI               => \RZP\Gateway\Upi\Npci::class,
        self::UPI_MINDGATE           => \RZP\Gateway\Upi\Mindgate::class,
        self::UPI_SBI                => \RZP\Gateway\Upi\Sbi::class,
        self::UPI_ICICI              => \RZP\Gateway\Upi\Icici::class,
        self::UPI_HULK               => \RZP\Gateway\Upi\Hulk::class,
        self::AEPS                   => \RZP\Gateway\Aeps\Base::class,
        self::AEPS_ICICI             => \RZP\Gateway\Aeps\Icici::class,
        self::AXIS_MIGS              => \RZP\Gateway\AxisMigs::class,
        self::FIRST_DATA             => \RZP\Gateway\FirstData::class,
        self::NETBANKING             => \RZP\Gateway\Netbanking\Base::class,
        self::AXIS_GENIUS            => \RZP\Gateway\AxisGenius::class,
        self::CYBERSOURCE            => \RZP\Gateway\Cybersource::class,
        self::CARD_FSS               => \RZP\Gateway\Card\Fss::class,
        self::ENACH                  => \RZP\Gateway\Enach\Base::class,
        self::ENACH_RBL              => \RZP\Gateway\Enach\Rbl::class,
        self::ESIGNER_DIGIO          => \RZP\Gateway\Esigner\Digio::class,
        self::WALLET_PAYZAPP         => \RZP\Gateway\Wallet\Payzapp::class,
        self::WALLET_OLAMONEY        => \RZP\Gateway\Wallet\Olamoney::class,
        self::WALLET_JIOMONEY        => \RZP\Gateway\Wallet\Jiomoney::class,
        self::WALLET_SBIBUDDY        => \RZP\Gateway\Wallet\Sbibuddy::class,
        self::NETBANKING_AXIS        => \RZP\Gateway\Netbanking\Axis::class,
        self::NETBANKING_HDFC        => \RZP\Gateway\Netbanking\Hdfc::class,
        self::NETBANKING_BOB         => \RZP\Gateway\Netbanking\Bob::class,
        self::NETBANKING_CORPORATION => \RZP\Gateway\Netbanking\Corporation::class,
        self::NETBANKING_KOTAK       => \RZP\Gateway\Netbanking\Kotak::class,
        self::NETBANKING_ICICI       => \RZP\Gateway\Netbanking\Icici::class,
        self::NETBANKING_OBC         => \RZP\Gateway\Netbanking\Obc::class,
        self::NETBANKING_AIRTEL      => \RZP\Gateway\Netbanking\Airtel::class,
        self::NETBANKING_FEDERAL     => \RZP\Gateway\Netbanking\Federal::class,
        self::NETBANKING_RBL         => \RZP\Gateway\Netbanking\Rbl::class,
        self::NETBANKING_INDUSIND    => \RZP\Gateway\Netbanking\Indusind::class,
        self::NETBANKING_PNB         => \RZP\Gateway\Netbanking\Pnb::class,
        self::NETBANKING_CSB         => \RZP\Gateway\Netbanking\Csb::class,
        self::WALLET_PAYUMONEY       => \RZP\Gateway\Wallet\Payumoney::class,
        self::WALLET_OPENWALLET      => \RZP\Gateway\Wallet\Openwallet::class,
        self::WALLET_FREECHARGE      => \RZP\Gateway\Wallet\Freecharge::class,
        self::WALLET_AIRTELMONEY     => \RZP\Gateway\Wallet\Airtelmoney::class,
        self::WALLET_MPESA           => \RZP\Gateway\Wallet\Mpesa::class,
        self::MPI                    => \RZP\Gateway\Mpi\Base::class,
        self::MPI_BLADE              => \RZP\Gateway\Mpi\Blade::class,


        // heimdall
        self::ORG                   => \RZP\Models\Admin\Org::class,
        self::ROLE                  => \RZP\Models\Admin\Role::class,
        self::ADMIN                 => \RZP\Models\Admin\Admin::class,
        self::GROUP                 => \RZP\Models\Admin\Group::class,
        self::ADMIN_LEAD            => \RZP\Models\Admin\AdminLead::class,
        self::PERMISSION            => \RZP\Models\Admin\Permission::class,
        self::ADMIN_TOKEN           => \RZP\Models\Admin\Admin\Token::class,
        self::ORG_HOSTNAME          => \RZP\Models\Admin\Org\Hostname::class,
        self::ORG_FIELD_MAP         => \RZP\Models\Admin\Org\FieldMap::class,
        self::WORKFLOW              => \RZP\Models\Workflow::class,
        self::WORKFLOW_STEP         => \RZP\Models\Workflow\Step::class,
        self::WORKFLOW_ACTION       => \RZP\Models\Workflow\Action::class,
        self::ACTION_CHECKER        => \RZP\Models\Workflow\Action\Checker::class,
        self::ACTION_STATE          => \RZP\Models\Workflow\Action\State::class,
        self::ACTION_COMMENT        => \RZP\Models\Workflow\Action\Comment::class,
        self::STATE_REASON          => \RZP\Models\State\Reason::class,

        self::TAX_GROUP             => \RZP\Models\Tax\Group::class,
        self::LINE_ITEM_TAX         => \RZP\Models\LineItem\Tax::class,
    ];

    protected static $repository = [
        self::NETBANKING_AIRTEL      => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_AXIS        => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_FEDERAL     => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_HDFC        => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_CORPORATION => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_ICICI       => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_INDUSIND    => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_KOTAK       => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_RBL         => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_PNB         => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_OBC         => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_CSB         => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_BOB         => \RZP\Gateway\Netbanking\Base::class,

        self::MPI_BLADE              => \RZP\Gateway\Mpi\Base::class,

        self::ENACH_RBL              => \RZP\Gateway\Enach\Base::class,

        self::UPI_MINDGATE           => \RZP\Gateway\Upi\Base::class,
        self::UPI_SBI                => \RZP\Gateway\Upi\Base::class,
        self::UPI_ICICI              => \RZP\Gateway\Upi\Base::class,
        self::UPI_HULK               => \RZP\Gateway\Upi\Base::class,
        self::UPI_NPCI               => \RZP\Gateway\Upi\Base::class,

        self::AEPS_ICICI             => \RZP\Gateway\Aeps\Base::class,

        self::WALLET_AIRTELMONEY     => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_FREECHARGE      => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_JIOMONEY        => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_SBIBUDDY        => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_MPESA           => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_OLAMONEY        => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_PAYUMONEY       => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_PAYZAPP         => \RZP\Gateway\Wallet\Base::class,

        self::NODAL_STATEMENT       => \RZP\Models\Nodal\Statement::class,
    ];

    protected static $externalServiceClass = [
        self::REPORTING_LOGS               => \RZP\Services\Reporting::class,
        self::REPORTING_CONFIGS            => \RZP\Services\Reporting::class,
        self::REPORTING_SCHEDULES          => \RZP\Services\Reporting::class,
        self::SHIELD_RULES                 => \RZP\Services\ShieldClient::class,
        self::SHIELD_RULE_ANALYTICS        => \RZP\Services\ShieldClient::class,
    ];

    protected static $syncedInLiveAndTest = [
        self::ORG,
        self::IIN,
        self::FEATURE,
        self::METHODS,
        self::PRICING,
        self::EMI_PLAN,
        self::MERCHANT,
        self::USER,
        self::SCHEDULE,
        self::MERCHANT_ACCESS_MAP,
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
}
