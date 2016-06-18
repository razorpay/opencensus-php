<?php

namespace Constants;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway;
use Models;
use Trace;
use Trace\TraceCode;

class Entity
{
    //
    // Core entities
    //

    const IIN               = 'iin';
    const KEY               = 'key';
    const CARD              = 'card';
    const ORDER             = 'order';
    const TOKEN             = 'token';
    const REFUND            = 'refund';
    const BALANCE           = 'balance';
    const METHODS           = 'methods';
    const PRICING           = 'pricing';
    const PAYMENT           = 'payment';
    const WEBHOOK           = 'webhook';
    const EMI_PLAN          = 'emi_plan';
    const MERCHANT          = 'merchant';
    const TERMINAL          = 'terminal';
    const CUSTOMER          = 'customer';
    const ADJUSTMENT        = 'adjustment';
    const SETTLEMENT        = 'settlement';
    const TRANSACTION       = 'transaction';
    const BANK_ACCOUNT      = 'bank_account';
    const DAILY_SETTLEMENT  = 'daily_settlement';

    //
    // Gateway entities
    //

    const ATOM              = 'atom';
    const HDFC              = 'hdfc';
    const AMEX              = 'amex';
    const PAYTM             = 'paytm';
    const SHARP             = 'sharp';
    const WALLET            = 'wallet';
    const BILLDESK          = 'billdesk';
    const MOBIKWIK          = 'mobikwik';
    const AXIS_MIGS         = 'axis_migs';
    const AXIS_GENIUS       = 'axis_genius';
    const NETBANKING        = 'netbanking';
    const NETBANKING_HDFC   = 'netbanking_hdfc';
    const NETBANKING_KOTAK  = 'netbanking_kotak';
    const WALLET_PAYZAPP    = 'wallet_payzapp';
    const WALLET_PAYUMONEY  = 'wallet_payumoney';

    public static $core = array(
        self::IIN,
        self::KEY,
        self::CARD,
        self::REFUND,
        self::BALANCE,
        self::METHODS,
        self::PRICING,
        self::PAYMENT,
        self::WEBHOOK,
        self::MERCHANT,
        self::TERMINAL,
        self::ADJUSTMENT,
        self::SETTLEMENT,
        self::TRANSACTION,
        self::BANK_ACCOUNT,
        self::DAILY_SETTLEMENT,
    );

    public static $list = array(
        self::IIN,
        self::KEY,
        self::CARD,
        self::REFUND,
        self::BALANCE,
        self::METHODS,
        self::PRICING,
        self::PAYMENT,
        self::WEBHOOK,
        self::MERCHANT,
        self::TERMINAL,
        self::ADJUSTMENT,
        self::SETTLEMENT,
        self::TRANSACTION,
        self::BANK_ACCOUNT,
        self::DAILY_SETTLEMENT,
        self::ATOM,
        self::HDFC,
        self::AMEX,
        self::PAYTM,
        self::BILLDESK,
        self::MOBIKWIK,
        self::AXIS_MIGS,
        self::AXIS_GENIUS,
        self::NETBANKING_HDFC,
    );

    public static $map = array(
        self::IIN,
        self::KEY,
        self::CARD,
        self::REFUND,
        self::BALANCE,
        self::METHODS,
        self::PRICING,
        self::PAYMENT,
        self::WEBHOOK,
        self::MERCHANT,
        self::TERMINAL,
        self::ADJUSTMENT,
        self::SETTLEMENT,
        self::TRANSACTION,
        self::BANK_ACCOUNT,
        self::DAILY_SETTLEMENT,
    );

    /**
     * Entities exposed outside
     * @var array
     */
    public static $public = array(
        self::PAYMENT,
        self::REFUND,
        self::ORDER,
    );

    public static $namespace = array(
        self::IIN               => Models\Card\IIN::class,
        self::ATOM              => Gateway\Atom::class,
        self::AMEX              => Gateway\Amex::class,
        self::HDFC              => Gateway\Hdfc::class,
        self::ORDER             => Models\Order::class,
        self::PAYTM             => Gateway\Paytm::class,
        self::SHARP             => Gateway\Sharp::class,
        self::TOKEN             => Models\Customer\Token::class,
        self::REFUND            => Models\Payment\Refund::class,
        self::WALLET            => Gateway\Wallet\Base::class,
        self::BALANCE           => Models\Merchant\Balance::class,
        self::METHODS           => Models\Merchant\Methods::class,
        self::PRICING           => Models\Pricing::class,
        self::WEBHOOK           => Models\Merchant\Webhook::class,
        self::BILLDESK          => Gateway\Billdesk::class,
        self::CUSTOMER          => Models\Customer::class,
        self::EMI_PLAN          => Models\Emi::class,
        self::MOBIKWIK          => Gateway\Mobikwik::class,
        self::NETBANKING        => Gateway\Netbanking\Base::class,
        self::AXIS_MIGS         => Gateway\AxisMigs::class,
        self::AXIS_GENIUS       => Gateway\AxisGenius::class,
        self::BANK_ACCOUNT      => Models\Merchant\BankAccount::class,
        self::WALLET_PAYZAPP    => Gateway\Wallet\Payzapp::class,
        self::NETBANKING_HDFC   => Gateway\Netbanking\Hdfc::class,
        self::DAILY_SETTLEMENT  => Models\Settlement\Daily::class,
        self::NETBANKING_KOTAK  => Gateway\Netbanking\Kotak::class,
        self::WALLET_PAYUMONEY  => Gateway\Wallet\Payumoney::class,
    );

    protected static $repository = array(
        self::WALLET_PAYUMONEY  => Gateway\Wallet\Base::class,
        self::WALLET_PAYZAPP    => Gateway\Wallet\Base::class,
        self::NETBANKING_HDFC   => Gateway\Netbanking\Base::class,
        self::NETBANKING_KOTAK  => Gateway\Netbanking\Base::class,
    );

    public static function getEntityNamespace($entity)
    {
        self::validateIsEntity($entity);

        if (array_key_exists($entity, self::$namespace))
        {
            return self::$namespace[$entity];
        }

        return 'Models\\' . ucfirst($entity);
    }

    public static function getPublicEntityNamespace($entity)
    {
        self::validateIsPublicEntity($entity);

        if (array_key_exists($entity, self::$namespace))
        {
            return $self::$namespace[$entity];
        }

        return 'Models\\' . ucfirst($entity);
    }

    public static function getEntityRepository($entity)
    {
        $class = self::getEntityNamespace($entity) . '\Repository';

        if (class_exists($class) === false)
        {
            if (isset(self::$repository[$entity]))
            {
                $class = self::$repository[$entity] . '\Repository';
            }
            else
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Not a valid repository: ' . $entity);
            }
        }

        return $class;
    }

    public static function getPublicEntityRepository($entity)
    {
        self::validateIsPublicEntity($entity);

        return self::getEntityRepository($entity);
    }

    public static function validateIsEntity($entity)
    {
        if (constant(__CLASS__.'::'.strtoupper($entity)) === null)
        {
            Trace::error(
                TraceCode::ERROR_INVALID_ARGUMENT,
                ['entity' => $entity]);

            throw new Exception\RuntimeException(
                'Not a valid entity');
        }
    }

    public static function validateIsPublicEntity($entity)
    {
        if (in_array($entity, self::$public) === false)
        {
            Trace::error(
                TraceCode::ERROR_INVALID_ARGUMENT,
                ['entity' => $entity]);

            throw new Exception\BadRequestValidationFailureException(
                'Not a valid input');
        }
    }
}