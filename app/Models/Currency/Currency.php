<?php

namespace RZP\Models\Currency;

class Currency
{
    //Brazilian Real
    const BRL = 'BRL';
    //Euro
    const EUR = 'EUR';
    //Indian Rupee
    const INR = 'INR';
    //Pound Sterling
    const GBP = 'GBP';
    //US Dollar
    const USD = 'USD';
    //South African Rand
    const ZAR = 'ZAR';
    //Swiss Franc
    const CHF = 'CHF';
    //Australian Dollar
    const AUD = 'AUD';
    //Bahrain Dinar
    const BHD = 'BHD';
    //Canadian Dollar
    const CAD = 'CAD';
    //Chinese Renmibi
    const CNY = 'CNY';
    //Croatian Kuna
    const HRK = 'HRK';
    //Czech Koruna
    const CZK = 'CZK';
    //Danish Krone
    const DKK = 'DKK';
    //Hong Kong Dollar
    const HKD = 'HKD';
    //Hungarian Forint
    const HUF = 'HUF';
    //Israeli New Shekel
    const ISL = 'ISL';
    //Japanese Yen
    const JPY = 'JPY';
    //Kuwaiti Dinar
    const KWD = 'KWD';
    //Lithuanian Litas
    const LTL = 'LTL';
    //Mexican Peso
    const MXN = 'MXN';
    //New Zealand Dollar
    const NZD = 'NZD';
    //Norwegian Krone
    const NOK = 'NOK';
    //Polish Zloty
    const PLN = 'PLN';
    //Romanian New Leu
    const RON = 'RON';
    //Saudi Rihal
    const SAR = 'SAR';
    //Singapore Dollar
    const SGD = 'SGD';
    //South Korean Won
    const KRW = 'KRW';
    //Swedish Krona
    const SEK = 'SEK';
    //Turkish Lira
    const TRY = 'TRY';
    //UAE Dirham
    const AED = 'AED';

    const SUPPORTED_CURRENCIES = [
        self::INR,
        self::USD,
    ];

    const ISO_NUMERIC_CODES = [
        self::BRL => '986',
        self::EUR => '978',
        self::INR => '356',
        self::GBP => '826',
        self::USD => '840',
        self::ZAR => '710',
        self::CHF => '756',
        self::AUD => '036',
        self::BHD => '048',
        self::CAD => '124',
        self::CNY => '156',
        self::HRK => '191',
        self::CZK => '203',
        self::DKK => '208',
        self::HKD => '344',
        self::HUF => '348',
        self::ISL => '376',
        self::JPY => '392',
        self::KWD => '414',
        self::LTL => '440',
        self::MXN => '484',
        self::NZD => '554',
        self::NOK => '578',
        self::PLN => '985',
        self::RON => '946',
        self::SAR => '682',
        self::SGD => '702',
        self::KRW => '410',
        self::SEK => '752',
        self::TRY => '949',
        self::AED => '784',
    ];

    const DENOMINATION_FACTOR = [
        self::INR => 100,
        self::USD => 100,
    ];

    const EXPONENT = [];

    const SYMBOL = [
        self::INR => '₹',
        self::USD => '$',
    ];

    public static function getIsoCode(string $currency)
    {
        return self::ISO_NUMERIC_CODES[$currency] ?? null;
    }

    public static function getSymbol(string $currency)
    {
        return self::SYMBOL[$currency] ?? '';
    }

    public static function getExponent(string $currency)
    {
        return self::EXPONENT[$currency] ?? 2;
    }
}
