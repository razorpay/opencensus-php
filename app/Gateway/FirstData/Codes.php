<?php

namespace RZP\Gateway\FirstData;

use RZP\Models\Card;

final class Codes
{
    const ASIA_KOLKATA_TIME_ZONE = 'Asia/Kolkata';

    const HASH_ALGORITHM_SHA256 = 'SHA256';

    const ENGLISH_UK_LANG_CODE = 'en_GB';

    const DATE_TIME_FORMAT = 'Y:m:d-h:m:s';

    const PAYMENT_MODE_PAYONLY = 'payonly';
    const PAYMENT_MODE_PAYPLUS = 'payplus';
    const PAYMENT_MODE_FULLPAY = 'fullpay';

    public static $paymentModes = array(
        self::PAYMENT_MODE_PAYONLY,
        self::PAYMENT_MODE_PAYPLUS,
        self::PAYMENT_MODE_FULLPAY,
    );

    const TXNTYPE_SALE      = 'sale';
    const TXNTYPE_PREAUTH   = 'preauth';
    const TXNTYPE_POSTAUTH  = 'postauth';
    const TXNTYPE_VOID      = 'void';

    public static $txnTypes = array(
        self::TXNTYPE_SALE,
        self::TXNTYPE_PREAUTH,
        self::TXNTYPE_POSTAUTH,
        self::TXNTYPE_VOID,
    );

    public static $paymentMethodCodes = array(
        // MasterCard
        Card\Network::MC    => 'M',
        // Visa
        Card\Network::VISA  => 'V',
        // American Express
        Card\Network::AMEX  => 'A',
        // Diners
        Card\Network::DICL  => 'C',
        // JCB
        Card\Network::JCB   => 'J',
        // Maestro
        Card\Network::MAES  => 'MA',
        //RuPay
        Card\Network::RUPAY => 'RU',
    );

    public static $isoNumericCodes = array(
        //Brazilian Real
        'BRL' => '986',
        //Euro
        'EUR' => '978',
        //Indian Rupee
        'INR' => '356',
        //Pound Sterling
        'GBP' => '826',
        //US Dollar
        'USD' => '840',
        //South African Rand
        'ZAR' => '710',
        //Swiss Franc
        'CHF' => '756',
        //Australian Dollar
        'AUD' => '036',
        //Bahrain Dinar
        'BHD' => '048',
        //Canadian Dollar
        'CAD' => '124',
        //Chinese Renmibi
        'CNY' => '156',
        //Croatian Kuna
        'HRK' => '191',
        //Czech Koruna
        'CZK' => '203',
        //Danish Krone
        'DKK' => '208',
        //Hong Kong Dollar
        'HKD' => '344',
        //Hungarian Forint
        'HUF' => '348',
        //Israeli New Shekel
        'ISL' => '376',
        //Japanese Yen
        'JPY' => '392',
        //Kuwaiti Dinar
        'KWD' => '414',
        //Lithuanian Litas
        'LTL' => '440',
        //Mexican Peso
        'MXN' => '484',
        //New Zealand Dollar
        'NZD' => '554',
        //Norwegian Krone
        'NOK' => '578',
        //Polish Zloty
        'PLN' => '985',
        //Romanian New Leu
        'RON' => '946',
        //Saudi Rihal
        'SAR' => '682',
        //Singapore Dollar
        'SGD' => '702',
        //South Korean Won
        'KRW' => '410',
        //Swedish Krona
        'SEK' => '752',
        //Turkish Lira
        'TRY' => '949',
        //UAE Dirham
        'AED' => '784',
    );
}
