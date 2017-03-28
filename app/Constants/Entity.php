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
    const P2P                   = 'p2p';
    const VPA                   = 'vpa';
    const IIN                   = 'iin';
    const KEY                   = 'key';
    const CARD                  = 'card';
    const ITEM                  = 'item';
    const ORDER                 = 'order';
    const TOKEN                 = 'token';
    const BATCH                 = 'batch';
    const DEVICE                = 'device';
    const REFUND                = 'refund';
    const ADDRESS               = 'address';
    const BALANCE               = 'balance';
    const CREDITS               = 'credits';
    const METHODS               = 'methods';
    const PRICING               = 'pricing';
    const PAYMENT               = 'payment';
    const FEATURE               = 'feature';
    const WEBHOOK               = 'webhook';
    const INVOICE               = 'invoice';
    const SCHEDULE              = 'schedule';
    const EMI_PLAN              = 'emi_plan';
    const MERCHANT              = 'merchant';
    const TERMINAL              = 'terminal';
    const CUSTOMER              = 'customer';
    const LINE_ITEM             = 'line_item';
    const APP_TOKEN             = 'app_token';
    const ADJUSTMENT            = 'adjustment';
    const FILE_STORE            = 'file_store';
    const SETTLEMENT            = 'settlement';
    const TRANSACTION           = 'transaction';
    const FEE_BREAKUP           = 'fee_breakup';
    const BANK_ACCOUNT          = 'bank_account';
    const FILE_HANDLER          = 'file_handler';
    const TERMINAL_ACTION       = 'terminal_action';
    const GATEWAY_DOWNTIME      = 'gateway_downtime';
    const MERCHANT_DETAIL       = 'merchant_detail';
    const PAYMENT_ANALYTICS     = 'payment_analytics';
    const TERMINAL_ANALYTICS    = 'terminal_analytics';
    const BATCH_FUND_TRANSFER   = 'batch_fund_transfer';
    const ORG                   = 'org';
    const ORG_HOSTNAME          = 'org_hostname';
    const ROLE                  = 'role';
    const PERMISSION            = 'permission';
    const GROUP                 = 'group';
    const ADMIN                 = 'admin';
    const ADMIN_TOKEN           = 'admin_token';
    const FUND_TRANSFER_ATTEMPT = 'fund_transfer_attempt';
    const SETTLEMENT_DETAILS    = 'settlement_details';
    const TRANSFER              = 'transfer';
    const REVERSAL              = 'reversal';
    const CUSTOMER_BALANCE      = 'customer_balance';
    const CUSTOMER_TRANSACTION  = 'customer_transaction';
    const OFFER                 = 'offer';
    const COUPON                = 'coupon';
    const PAYOUT                = 'payout';

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
    //

    const EBS                   = 'ebs';
    const ATOM                  = 'atom';
    const HDFC                  = 'hdfc';
    const UPI                   = 'upi';
    const AMEX                  = 'amex';
    const PAYTM                 = 'paytm';
    const SHARP                 = 'sharp';
    const WALLET                = 'wallet';
    const BILLDESK              = 'billdesk';
    const MOBIKWIK              = 'mobikwik';
    const UPI_NPCI              = 'upi_npci';
    const AXIS_MIGS             = 'axis_migs';
    const FIRST_DATA            = 'first_data';
    const AXIS_GENIUS           = 'axis_genius';
    const NETBANKING            = 'netbanking';
    const CYBERSOURCE           = 'cybersource';
    const UPI_ICICI             = 'upi_icici';
    const UPI_IDFC              = 'upi_idfc';
    const NETBANKING_HDFC       = 'netbanking_hdfc';
    const NETBANKING_KOTAK      = 'netbanking_kotak';
    const NETBANKING_ICICI      = 'netbanking_icici';
    const NETBANKING_AXIS       = 'netbanking_axis';
    const NETBANKING_FEDERAL    = 'netbanking_federal';
    const NETBANKING_AIRTEL     = 'netbanking_airtel';
    const WALLET_PAYZAPP        = 'wallet_payzapp';
    const WALLET_OLAMONEY       = 'wallet_olamoney';
    const WALLET_PAYUMONEY      = 'wallet_payumoney';
    const WALLET_AIRTELMONEY    = 'wallet_airtelmoney';
    const WALLET_FREECHARGE     = 'wallet_freecharge';
    const WALLET_JIOMONEY       = 'wallet_jiomoney';
    const WALLET_OPENWALLET     = 'wallet_openwallet';

    public static $namespace = [
        self::P2P                   => \RZP\Models\P2p::class,
        self::VPA                   => \RZP\Models\Upi\Vpa::class,
        self::UPI                   => \RZP\Gateway\Upi\Base::class,
        self::IIN                   => \RZP\Models\Card\IIN::class,
        self::EBS                   => \RZP\Gateway\Ebs::class,
        self::ATOM                  => \RZP\Gateway\Atom::class,
        self::AMEX                  => \RZP\Gateway\Amex::class,
        self::HDFC                  => \RZP\Gateway\Hdfc::class,
        self::ORDER                 => \RZP\Models\Order::class,
        self::PAYTM                 => \RZP\Gateway\Paytm::class,
        self::SHARP                 => \RZP\Gateway\Sharp::class,
        self::TOKEN                 => \RZP\Models\Customer\Token::class,
        self::REFUND                => \RZP\Models\Payment\Refund::class,
        self::WALLET                => \RZP\Gateway\Wallet\Base::class,
        self::BALANCE               => \RZP\Models\Merchant\Balance::class,
        self::CREDITS               => \RZP\Models\Merchant\Credits::class,
        self::METHODS               => \RZP\Models\Merchant\Methods::class,
        self::PRICING               => \RZP\Models\Pricing::class,
        self::FEATURE               => \RZP\Models\Feature::class,
        self::WEBHOOK               => \RZP\Models\Merchant\Webhook::class,
        self::MERCHANT              => \RZP\Models\Merchant::class,
        self::BILLDESK              => \RZP\Gateway\Billdesk::class,
        self::CUSTOMER              => \RZP\Models\Customer::class,
        self::EMI_PLAN              => \RZP\Models\Emi::class,
        self::OFFER                 => \RZP\Models\Offer::class,
        self::COUPON                => \RZP\Models\Offer\Coupon::class,
        self::MOBIKWIK              => \RZP\Gateway\Mobikwik::class,
        self::UPI_NPCI              => \RZP\Gateway\Upi\Npci::class,
        self::UPI_ICICI             => \RZP\Gateway\Upi\Icici::class,
        self::UPI_IDFC              => \RZP\Gateway\Upi\Idfc::class,
        self::AXIS_MIGS             => \RZP\Gateway\AxisMigs::class,
        self::FILE_STORE            => \RZP\Models\FileStore::class,
        self::FIRST_DATA            => \RZP\Gateway\FirstData::class,
        self::APP_TOKEN             => \RZP\Models\Customer\AppToken::class,
        self::NETBANKING            => \RZP\Gateway\Netbanking\Base::class,
        self::FEE_BREAKUP           => \RZP\Models\Transaction\FeeBreakup::class,
        self::AXIS_GENIUS           => \RZP\Gateway\AxisGenius::class,
        self::CYBERSOURCE           => \RZP\Gateway\Cybersource::class,
        self::BANK_ACCOUNT          => \RZP\Models\BankAccount::class,
        self::WALLET_PAYZAPP        => \RZP\Gateway\Wallet\Payzapp::class,
        self::TERMINAL_ACTION       => \RZP\Models\Terminal\Action::class,
        self::GATEWAY_DOWNTIME      => \RZP\Models\Gateway\Downtime::class,
        self::WALLET_OLAMONEY       => \RZP\Gateway\Wallet\Olamoney::class,
        self::CUSTOMER_BALANCE      => \RZP\Models\Customer\Balance::class,
        self::BATCH_FUND_TRANSFER   => \RZP\Models\FundTransfer\Batch::class,
        self::CUSTOMER_TRANSACTION  => \RZP\Models\Customer\Transaction::class,
        self::NETBANKING_HDFC       => \RZP\Gateway\Netbanking\Hdfc::class,
        self::NETBANKING_KOTAK      => \RZP\Gateway\Netbanking\Kotak::class,
        self::NETBANKING_ICICI      => \RZP\Gateway\Netbanking\Icici::class,
        self::NETBANKING_AIRTEL     => \RZP\Gateway\Netbanking\Airtel::class,
        self::NETBANKING_AXIS       => \RZP\Gateway\Netbanking\Axis::class,
        self::NETBANKING_FEDERAL    => \RZP\Gateway\Netbanking\Federal::class,
        self::WALLET_PAYUMONEY      => \RZP\Gateway\Wallet\Payumoney::class,
        self::PAYMENT_ANALYTICS     => \RZP\Models\Payment\Analytics::class,
        self::WALLET_AIRTELMONEY    => \RZP\Gateway\Wallet\Airtelmoney::class,
        self::SETTLEMENT_DETAILS    => \RZP\Models\Settlement\Details::class,
        self::TERMINAL_ANALYTICS    => \RZP\Models\Payment\TerminalAnalytics::class,
        self::WALLET_FREECHARGE     => \RZP\Gateway\Wallet\Freecharge::class,
        self::WALLET_JIOMONEY       => \RZP\Gateway\Wallet\Jiomoney::class,
        self::WALLET_OPENWALLET     => \RZP\Gateway\Wallet\Openwallet::class,
        self::ORG                   => \RZP\Models\Admin\Org::class,
        self::ORG_HOSTNAME          => \RZP\Models\Admin\Org\Hostname::class,
        self::ROLE                  => \RZP\Models\Admin\Role::class,
        self::PERMISSION            => \RZP\Models\Admin\Permission::class,
        self::GROUP                 => \RZP\Models\Admin\Group::class,
        self::ADMIN                 => \RZP\Models\Admin\Admin::class,
        self::ADMIN_TOKEN           => \RZP\Models\Admin\Admin\Token::class,
        self::MERCHANT_DETAIL       => \RZP\Models\Merchant\Detail::class,
        self::FUND_TRANSFER_ATTEMPT => \RZP\Models\FundTransfer\Attempt::class,

        self::WORKFLOW              => \RZP\Models\Workflow::class,
        self::WORKFLOW_STEP         => \RZP\Models\Workflow\Step::class,
        self::WORKFLOW_ACTION       => \RZP\Models\Workflow\Action::class,
        self::ACTION_CHECKER        => \RZP\Models\Workflow\Action\Checker::class,
        self::ACTION_STATE          => \RZP\Models\Workflow\Action\State::class,
        self::ACTION_COMMENT        => \RZP\Models\Workflow\Action\Comment::class,
    ];

    protected static $repository = [
        self::UPI_NPCI           => \RZP\Gateway\Upi\Base::class,
        self::NETBANKING_HDFC    => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_KOTAK   => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_ICICI   => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_AIRTEL  => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_AXIS    => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_FEDERAL => \RZP\Gateway\Netbanking\Base::class,
        self::UPI_ICICI          => \RZP\Gateway\Upi\Base::class,
        self::UPI_IDFC           => \RZP\Gateway\Upi\Base::class,
        self::WALLET_AIRTELMONEY => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_OLAMONEY    => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_PAYUMONEY   => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_PAYZAPP     => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_FREECHARGE  => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_JIOMONEY    => \RZP\Gateway\Wallet\Base::class,
    ];

    protected static $syncedInLiveAndTest = [
        self::ORG,
        self::ORG_HOSTNAME,
        self::ROLE,
        self::PERMISSION,
        self::GROUP,
        self::ADMIN,
        self::ADMIN_TOKEN,
        self::IIN,
        self::FEATURE,
        self::METHODS,
        self::PRICING,
        self::EMI_PLAN,
        self::MERCHANT,
        self::SCHEDULE,

        self::WORKFLOW,
        self::WORKFLOW_STEP,
        self::WORKFLOW_ACTION,
        self::ACTION_CHECKER,
        self::ACTION_STATE,
        self::ACTION_COMMENT,
    ];

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
