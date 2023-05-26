<?php

namespace RZP\Models\Payment;

use RZP\Exception;

class Method
{
    const CARD                  = 'card';
    const NETBANKING            = 'netbanking';
    const WALLET                = 'wallet';
    const EMI                   = 'emi';
    const UPI                   = 'upi';
    const TRANSFER              = 'transfer';
    const BANK_TRANSFER         = 'bank_transfer';
    const AEPS                  = 'aeps';
    const EMANDATE              = 'emandate';
    const CARDLESS_EMI          = 'cardless_emi';
    const PAYLATER              = 'paylater';
    const NACH                  = 'nach';
    const APP                   = 'app';
    const COD                   = 'cod';
    const OFFLINE               = 'offline';
    const UNSELECTED            = 'unselected';
    const INTL_BANK_TRANSFER    = 'intl_bank_transfer';
    const FPX                   = 'fpx';

    protected static $methods = [
        self::CARD                  => 'Card',
        self::NETBANKING            => 'Net Banking',
        self::WALLET                => 'Wallet',
        self::UPI                   => 'UPI',
        self::AEPS                  => 'AEPS',
        self::EMI                   => 'EMI',
        self::TRANSFER              => 'Marketplace Transfer',
        self::BANK_TRANSFER         => 'Bank Transfer',
        self::EMANDATE              => 'E-Mandate',
        self::CARDLESS_EMI          => 'Cardless EMI',
        self::PAYLATER              => 'Pay Later',
        self::NACH                  => 'nach',
        self::APP                   => 'App',
        self::COD                   => 'Cash on Delivery',
        self::OFFLINE               => 'Offline',
        self::INTL_BANK_TRANSFER    => 'Intl Bank Transfer',
        self::FPX                   => 'Financial Process Exchange',
    ];

    protected static $nonEsAutomaticMethods = [
        self::EMANDATE      => 'E-Mandate',
        self::BANK_TRANSFER => 'Bank Transfer',
        self::OFFLINE       => 'Offline',
    ];

    protected static $preAuthorizeGooglePayMethods = [
        self::UNSELECTED   => 'unselected',
    ];

    public static $bankMethods = [
        self::NETBANKING,
        self::AEPS,
        self::EMANDATE,
        self::UPI,
        self::FPX,
    ];

    public static $cpsEnabledMethods = [
        self::CARD,
        self::NETBANKING,
        self::EMI,
        self::EMANDATE,
        self::UPI,
        self::CARDLESS_EMI,
    ];

    /**
     * Payment methods where amount validation is skipped
     *
     * @var array
     */
    public static $methodsWithoutAmountValidation = [
        self::TRANSFER,
        self::BANK_TRANSFER,
        self::OFFLINE,
    ];

    public static $recurringMethods = [
        self::CARD,
        self::EMANDATE,
        self::UPI,
        self::NACH,
    ];

    protected static $asynchronous = [
        self::UPI,
    ];

    const INSTANT_REFUND_SUPPORTED_METHODS = [
        self::CARD,
        self::UPI,
        self::NETBANKING,
    ];

    const GOOGLE_PAY_SUPPORTED_METHODS = [
        self::CARD,
        self::UPI,
    ];

    const OPGSP_IMPORT_SUPPORTED_METHODS= [
        self::CARD,
        self::NETBANKING,
    ];

    public static function formatted($method)
    {
        return self::$methods[$method];
    }

    public static function getAllPaymentMethods()
    {
        return array_keys(self::$methods);
    }

    public static function getPreAuthorizeGooglePayMethods()
    {
        return array_keys(self::$preAuthorizeGooglePayMethods);
    }

    public static function getPostAuthorizeGooglePayMethods()
    {
        return self::GOOGLE_PAY_SUPPORTED_METHODS;
    }

    public static function getNonEsPaymentMethods()
    {
        return array_keys(self::$nonEsAutomaticMethods);
    }

    public static function isValid($method)
    {
        return in_array($method, self::getAllPaymentMethods(), true);
    }

    public static function isValidEsMethod($method)
    {
        return ((in_array($method, self::getNonEsPaymentMethods(), true) === false) and
                (in_array($method, self::getAllPaymentMethods(), true) === true));
    }

    public static function isValidPreAuthorizeGooglePayMethod($method)
    {
        return in_array($method, self::getPreAuthorizeGooglePayMethods(), true);
    }

    public static function validateMethod($method)
    {
        if (self::isValid($method) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid Payment method: ' . $method);
        }
    }

    public static function validateEsMethod($method)
    {
        if (self::isValidEsMethod($method) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid Payment method for early settlement: ' . $method);
        }
    }

    public static function supportsAsync($method)
    {
        return in_array($method, self::$asynchronous, true);
    }

    public static function getMethodsNamesMap()
    {
        return self::$methods;
    }

    public static function getCpsEnabledMethods(): array
    {
        return self::$cpsEnabledMethods;
    }

    public static array $timeoutDisabledMethods = [
        self::BANK_TRANSFER,
    ];
}
