<?php

namespace RZP\Constants;

use App;

use RZP\Exception;
use RZP\Gateway;
use RZP\Trace\TraceCode;
use RZP\Models;
use Trace;

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
    const EMI_PLAN              = 'emi_plan';
    const CUSTOMER              = 'customer';
    const MERCHANT              = 'merchant';
    const REVERSAL              = 'reversal';
    const SCHEDULE              = 'schedule';
    const TERMINAL              = 'terminal';
    const TRANSFER              = 'transfer';
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
    const SUBSCRIPTION          = 'subscription';
    const GATEWAY_TOKEN         = 'gateway_token';
    const BANK_TRANSFER         = 'bank_transfer';
    const SCHEDULE_TASK         = 'schedule_task';
    const LINE_ITEM_TAX         = 'line_item_tax';
    const DISPUTE_REASON        = 'dispute_reason';
    const VIRTUAL_ACCOUNT       = 'virtual_account';
    const MERCHANT_DETAIL       = 'merchant_detail';
    const TERMINAL_ACTION       = 'terminal_action';
    const CUSTOMER_BALANCE      = 'customer_balance';
    const GATEWAY_DOWNTIME      = 'gateway_downtime';
    const PAYMENT_ANALYTICS     = 'payment_analytics';
    const SETTLEMENT_DETAILS    = 'settlement_details';
    const MERCHANT_PROMOTION    = 'merchant_promotion';
    const CREDIT_TRANSACTION    = 'credit_transaction';
    const MERCHANT_INVOICE      = 'merchant_invoice';
    const TERMINAL_ANALYTICS    = 'terminal_analytics';
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

    //
    // Gateway entities
    const EBS                    = 'ebs';
    const UPI                    = 'upi';
    const AEPS                   = 'aeps';
    const AMEX                   = 'amex';
    const BLADE                  = 'blade';
    const ATOM                   = 'atom';
    const HDFC                   = 'hdfc';
    const PAYTM                  = 'paytm';
    const SHARP                  = 'sharp';
    const WALLET                 = 'wallet';
    const BILLDESK               = 'billdesk';
    const MOBIKWIK               = 'mobikwik';
    const UPI_NPCI               = 'upi_npci';
    const AXIS_MIGS              = 'axis_migs';
    const FIRST_DATA             = 'first_data';
    const AXIS_GENIUS            = 'axis_genius';
    const NETBANKING             = 'netbanking';
    const CYBERSOURCE            = 'cybersource';
    const AEPS_ICICI             = 'aeps_icici';
    const UPI_MINDGATE           = 'upi_mindgate';
    const UPI_ICICI              = 'upi_icici';
    const NETBANKING_AXIS        = 'netbanking_axis';
    const NETBANKING_HDFC        = 'netbanking_hdfc';
    const NETBANKING_CORPORATION = 'netbanking_corporation';
    const NETBANKING_ICICI       = 'netbanking_icici';
    const NETBANKING_KOTAK       = 'netbanking_kotak';
    const NETBANKING_AIRTEL      = 'netbanking_airtel';
    const NETBANKING_FEDERAL     = 'netbanking_federal';
    const NETBANKING_RBL         = 'netbanking_rbl';
    const NETBANKING_INDUSIND    = 'netbanking_indusind';
    const NETBANKING_PNB         = 'netbanking_pnb';
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

    public static $namespace = [
        self::IIN                   => \RZP\Models\Card\IIN::class,
        self::P2P                   => \RZP\Models\P2p::class,
        self::VPA                   => \RZP\Models\Upi\Vpa::class,
        self::UPI                   => \RZP\Gateway\Upi\Base::class,
        self::IIN                   => \RZP\Models\Card\IIN::class,
        self::EBS                   => \RZP\Gateway\Ebs::class,
        self::ATOM                  => \RZP\Gateway\Atom::class,
        self::AMEX                  => \RZP\Gateway\Amex::class,
        self::BLADE                 => \RZP\Gateway\Blade::class,
        self::HDFC                  => \RZP\Gateway\Hdfc::class,
        self::USER                  => \RZP\Models\User::class,
        self::OFFER                 => \RZP\Models\Offer::class,
        self::ADDON                 => \RZP\Models\Plan\Subscription\Addon::class,
        self::ORDER                 => \RZP\Models\Order::class,
        self::TOKEN                 => \RZP\Models\Customer\Token::class,
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
        self::SCHEDULE              => \RZP\Models\Schedule::class,
        self::APP_TOKEN             => \RZP\Models\Customer\AppToken::class,
        self::INVITATION            => \RZP\Models\Invitation::class,
        self::FILE_STORE            => \RZP\Models\FileStore::class,
        self::FEE_BREAKUP           => \RZP\Models\Transaction\FeeBreakup::class,
        self::BANK_ACCOUNT          => \RZP\Models\BankAccount::class,
        self::SUBSCRIPTION          => \RZP\Models\Plan\Subscription::class,
        self::GATEWAY_TOKEN         => \RZP\Models\Customer\GatewayToken::class,
        self::SCHEDULE_TASK         => \RZP\Models\Schedule\Task::class,
        self::DISPUTE_REASON        => \RZP\Models\Dispute\Reason::class,
        self::MERCHANT_DETAIL       => \RZP\Models\Merchant\Detail::class,
        self::TERMINAL_ACTION       => \RZP\Models\Terminal\Action::class,
        self::CUSTOMER_BALANCE      => \RZP\Models\Customer\Balance::class,
        self::GATEWAY_DOWNTIME      => \RZP\Models\Gateway\Downtime::class,
        self::GATEWAY_RULE          => \RZP\Models\Gateway\Rule::class,
        self::GATEWAY_FILE          => \RZP\Models\Gateway\File::class,
        self::PAYMENT_ANALYTICS     => \RZP\Models\Payment\Analytics::class,
        self::CREDIT_TRANSACTION    => \RZP\Models\Merchant\Credits\Transaction::class,
        self::MERCHANT_PROMOTION    => \RZP\Models\Merchant\Promotion::class,
        self::MERCHANT_INVOICE      => \RZP\Models\Merchant\Invoice::class,
        self::SETTLEMENT_DETAILS    => \RZP\Models\Settlement\Details::class,
        self::TERMINAL_ANALYTICS    => \RZP\Models\Payment\TerminalAnalytics::class,
        self::BATCH_FUND_TRANSFER   => \RZP\Models\FundTransfer\Batch::class,
        self::CUSTOMER_TRANSACTION  => \RZP\Models\Customer\Transaction::class,
        self::FUND_TRANSFER_ATTEMPT => \RZP\Models\FundTransfer\Attempt::class,

        // gateways
        self::EBS                    => \RZP\Gateway\Ebs::class,
        self::ATOM                   => \RZP\Gateway\Atom::class,
        self::AMEX                   => \RZP\Gateway\Amex::class,
        self::HDFC                   => \RZP\Gateway\Hdfc::class,
        self::PAYTM                  => \RZP\Gateway\Paytm::class,
        self::SHARP                  => \RZP\Gateway\Sharp::class,
        self::WALLET                 => \RZP\Gateway\Wallet\Base::class,
        self::BILLDESK               => \RZP\Gateway\Billdesk::class,
        self::MOBIKWIK               => \RZP\Gateway\Mobikwik::class,
        self::UPI_NPCI               => \RZP\Gateway\Upi\Npci::class,
        self::UPI_MINDGATE           => \RZP\Gateway\Upi\Mindgate::class,
        self::UPI_ICICI              => \RZP\Gateway\Upi\Icici::class,
        self::AEPS                   => \RZP\Gateway\Aeps\Base::class,
        self::AEPS_ICICI             => \RZP\Gateway\Aeps\Icici::class,
        self::AXIS_MIGS              => \RZP\Gateway\AxisMigs::class,
        self::FIRST_DATA             => \RZP\Gateway\FirstData::class,
        self::NETBANKING             => \RZP\Gateway\Netbanking\Base::class,
        self::AXIS_GENIUS            => \RZP\Gateway\AxisGenius::class,
        self::CYBERSOURCE            => \RZP\Gateway\Cybersource::class,
        self::WALLET_PAYZAPP         => \RZP\Gateway\Wallet\Payzapp::class,
        self::WALLET_OLAMONEY        => \RZP\Gateway\Wallet\Olamoney::class,
        self::WALLET_JIOMONEY        => \RZP\Gateway\Wallet\Jiomoney::class,
        self::WALLET_SBIBUDDY        => \RZP\Gateway\Wallet\Sbibuddy::class,
        self::NETBANKING_AXIS        => \RZP\Gateway\Netbanking\Axis::class,
        self::NETBANKING_HDFC        => \RZP\Gateway\Netbanking\Hdfc::class,
        self::NETBANKING_CORPORATION => \RZP\Gateway\Netbanking\Corporation::class,
        self::NETBANKING_KOTAK       => \RZP\Gateway\Netbanking\Kotak::class,
        self::NETBANKING_ICICI       => \RZP\Gateway\Netbanking\Icici::class,
        self::NETBANKING_AIRTEL      => \RZP\Gateway\Netbanking\Airtel::class,
        self::NETBANKING_FEDERAL     => \RZP\Gateway\Netbanking\Federal::class,
        self::NETBANKING_RBL         => \RZP\Gateway\Netbanking\Rbl::class,
        self::NETBANKING_INDUSIND    => \RZP\Gateway\Netbanking\Indusind::class,
        self::NETBANKING_PNB         => \RZP\Gateway\Netbanking\Pnb::class,
        self::WALLET_PAYUMONEY       => \RZP\Gateway\Wallet\Payumoney::class,
        self::WALLET_OPENWALLET      => \RZP\Gateway\Wallet\Openwallet::class,
        self::WALLET_FREECHARGE      => \RZP\Gateway\Wallet\Freecharge::class,
        self::WALLET_AIRTELMONEY     => \RZP\Gateway\Wallet\Airtelmoney::class,
        self::WALLET_MPESA           => \RZP\Gateway\Wallet\Mpesa::class,

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

        self::UPI_MINDGATE          => \RZP\Gateway\Upi\Base::class,
        self::UPI_ICICI             => \RZP\Gateway\Upi\Base::class,
        self::UPI_NPCI              => \RZP\Gateway\Upi\Base::class,

        self::AEPS_ICICI            => \RZP\Gateway\Aeps\Base::class,

        self::WALLET_AIRTELMONEY    => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_FREECHARGE     => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_JIOMONEY       => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_SBIBUDDY       => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_MPESA          => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_OLAMONEY       => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_PAYUMONEY      => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_PAYZAPP        => \RZP\Gateway\Wallet\Base::class,
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

    public static function isEntitySyncedInLiveAndTest($entity)
    {
        return in_array($entity, self::$syncedInLiveAndTest, true);
    }
}
