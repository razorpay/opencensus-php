<?php

namespace RZP\Constants;

use App;

use RZP\Exception;
use RZP\Error\ErrorCode;
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
    const CARD                  = 'card';
    const ORDER                 = 'order';
    const TOKEN                 = 'token';
    const BATCH                 = 'batch';
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
    const SETTLEMENT            = 'settlement';
    const DAILY_SETTLEMENT      = 'daily_settlement';
    const SETTLEMENT_DETAILS    = 'settlement_details';
    const TRANSACTION           = 'transaction';
    const BANK_ACCOUNT          = 'bank_account';
    const GATEWAY_ABSENCE       = 'gateway_absence';
    const INVOICE_ITEM          = 'invoice_item';
    const TERMINAL_ACTION       = 'terminal_action';
    const PAYMENT_ANALYTICS     = 'payment_analytics';
    const TERMINAL_ANALYTICS    = 'terminal_analytics';

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
    const AXIS_MIGS             = 'axis_migs';
    const FIRST_DATA            = 'first_data';
    const AXIS_GENIUS           = 'axis_genius';
    const NETBANKING            = 'netbanking';
    const CYBERSOURCE           = 'cybersource';
    const UPI_ICICI             = 'upi_icici';
    const WALLET_PAYZAPP        = 'wallet_payzapp';
    const WALLET_OLAMONEY       = 'wallet_olamoney';
    const NETBANKING_HDFC       = 'netbanking_hdfc';
    const WALLET_PAYUMONEY      = 'wallet_payumoney';
    const NETBANKING_KOTAK      = 'netbanking_kotak';
    const WALLET_AIRTELMONEY    = 'wallet_airtelmoney';
    const WALLET_FREECHARGE     = 'wallet_freecharge';

    public static $namespace = array(
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
        self::MOBIKWIK              => \RZP\Gateway\Mobikwik::class,
        self::UPI_ICICI             => \RZP\Gateway\Upi\Icici::class,
        self::AXIS_MIGS             => \RZP\Gateway\AxisMigs::class,
        self::FIRST_DATA            => \RZP\Gateway\FirstData::class,
        self::APP_TOKEN             => \RZP\Models\Customer\AppToken::class,
        self::NETBANKING            => \RZP\Gateway\Netbanking\Base::class,
        self::AXIS_GENIUS           => \RZP\Gateway\AxisGenius::class,
        self::CYBERSOURCE           => \RZP\Gateway\Cybersource::class,
        self::INVOICE_ITEM          => \RZP\Models\Invoice\InvoiceItem::class,
        self::BANK_ACCOUNT          => \RZP\Models\BankAccount::class,
        self::WALLET_PAYZAPP        => \RZP\Gateway\Wallet\Payzapp::class,
        self::TERMINAL_ACTION       => \RZP\Models\Terminal\Action::class,
        self::GATEWAY_ABSENCE       => \RZP\Models\GatewayStatus\Absence::class,
        self::NETBANKING_HDFC       => \RZP\Gateway\Netbanking\Hdfc::class,
        self::WALLET_OLAMONEY       => \RZP\Gateway\Wallet\Olamoney::class,
        self::DAILY_SETTLEMENT      => \RZP\Models\Settlement\Daily::class,
        self::NETBANKING_KOTAK      => \RZP\Gateway\Netbanking\Kotak::class,
        self::WALLET_PAYUMONEY      => \RZP\Gateway\Wallet\Payumoney::class,
        self::PAYMENT_ANALYTICS     => \RZP\Models\Payment\Analytics::class,
        self::WALLET_AIRTELMONEY    => \RZP\Gateway\Wallet\Airtelmoney::class,
        self::SETTLEMENT_DETAILS    => \RZP\Models\Settlement\Details::class,
        self::TERMINAL_ANALYTICS    => \RZP\Models\Payment\TerminalAnalytics::class,
        self::WALLET_FREECHARGE     => \RZP\Gateway\Wallet\Freecharge::class,
    );

    protected static $repository = array(
        self::NETBANKING_HDFC    => \RZP\Gateway\Netbanking\Base::class,
        self::NETBANKING_KOTAK   => \RZP\Gateway\Netbanking\Base::class,
        self::UPI_ICICI          => \RZP\Gateway\Upi\Base::class,
        self::WALLET_AIRTELMONEY => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_OLAMONEY    => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_PAYUMONEY   => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_PAYZAPP     => \RZP\Gateway\Wallet\Base::class,
        self::WALLET_FREECHARGE  => \RZP\Gateway\Wallet\Base::class,
    );

    public static function getEntityNamespace(string $entity)
    {
        self::validateIsEntity($entity);

        if (array_key_exists($entity, self::$namespace))
        {
            return self::$namespace[$entity];
        }

        // Converts first character of the
        // words (delimited by underscores/hyphens/spaces) to uppercase
        return '\RZP\Models\\' . studly_case($entity);
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
}
