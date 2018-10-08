<?php

namespace RZP\Models\Currency;

class Currency
{
    const AED = 'AED';
    const ALL = 'ALL';
    const AMD = 'AMD';
    const ARS = 'ARS';
    const AUD = 'AUD';
    const AWG = 'AWG';
    const BBD = 'BBD';
    const BDT = 'BDT';
    const BMD = 'BMD';
    const BND = 'BND';
    const BOB = 'BOB';
    const BSD = 'BSD';
    const BWP = 'BWP';
    const BZD = 'BZD';
    const CAD = 'CAD';
    const CHF = 'CHF';
    const CNY = 'CNY';
    const COP = 'COP';
    const CRC = 'CRC';
    const CUP = 'CUP';
    const CZK = 'CZK';
    const DKK = 'DKK';
    const DOP = 'DOP';
    const DZD = 'DZD';
    const EGP = 'EGP';
    const ETB = 'ETB';
    const EUR = 'EUR';
    const FJD = 'FJD';
    const GBP = 'GBP';
    const GIP = 'GIP';
    const GMD = 'GMD';
    const GTQ = 'GTQ';
    const GYD = 'GYD';
    const HKD = 'HKD';
    const HNL = 'HNL';
    const HRK = 'HRK';
    const HTG = 'HTG';
    const HUF = 'HUF';
    const IDR = 'IDR';
    const ILS = 'ILS';
    const INR = 'INR';
    const JMD = 'JMD';
    const KES = 'KES';
    const KGS = 'KGS';
    const KHR = 'KHR';
    const KYD = 'KYD';
    const KZT = 'KZT';
    const LAK = 'LAK';
    const LBP = 'LBP';
    const LKR = 'LKR';
    const LRD = 'LRD';
    const LSL = 'LSL';
    const MAD = 'MAD';
    const MDL = 'MDL';
    const MKD = 'MKD';
    const MMK = 'MMK';
    const MNT = 'MNT';
    const MOP = 'MOP';
    const MUR = 'MUR';
    const MVR = 'MVR';
    const MWK = 'MWK';
    const MXN = 'MXN';
    const MYR = 'MYR';
    const NAD = 'NAD';
    const NGN = 'NGN';
    const NIO = 'NIO';
    const NOK = 'NOK';
    const NPR = 'NPR';
    const NZD = 'NZD';
    const PEN = 'PEN';
    const PGK = 'PGK';
    const PHP = 'PHP';
    const PKR = 'PKR';
    const QAR = 'QAR';
    const RUB = 'RUB';
    const SAR = 'SAR';
    const SCR = 'SCR';
    const SEK = 'SEK';
    const SGD = 'SGD';
    const SLL = 'SLL';
    const SOS = 'SOS';
    const SSP = 'SSP';
    const SVC = 'SVC';
    const SZL = 'SZL';
    const THB = 'THB';
    const TTD = 'TTD';
    const TZS = 'TZS';
    const USD = 'USD';
    const UYU = 'UYU';
    const UZS = 'UZS';
    const YER = 'YER';
    const ZAR = 'ZAR';

    const SUPPORTED_CURRENCIES = [
        self::AED,
        self::ALL,
        self::AMD,
        self::ARS,
        self::AUD,
        self::AWG,
        self::BBD,
        self::BDT,
        self::BMD,
        self::BND,
        self::BOB,
        self::BSD,
        self::BWP,
        self::BZD,
        self::CAD,
        self::CHF,
        self::CNY,
        self::COP,
        self::CRC,
        self::CUP,
        self::CZK,
        self::DKK,
        self::DOP,
        self::DZD,
        self::EGP,
        self::ETB,
        self::EUR,
        self::FJD,
        self::GBP,
        self::GIP,
        self::GMD,
        self::GTQ,
        self::GYD,
        self::HKD,
        self::HNL,
        self::HRK,
        self::HTG,
        self::HUF,
        self::IDR,
        self::ILS,
        self::INR,
        self::JMD,
        self::KES,
        self::KGS,
        self::KHR,
        self::KYD,
        self::KZT,
        self::LAK,
        self::LBP,
        self::LKR,
        self::LRD,
        self::LSL,
        self::MAD,
        self::MDL,
        self::MKD,
        self::MMK,
        self::MNT,
        self::MOP,
        self::MUR,
        self::MVR,
        self::MWK,
        self::MXN,
        self::MYR,
        self::NAD,
        self::NGN,
        self::NIO,
        self::NOK,
        self::NPR,
        self::NZD,
        self::PEN,
        self::PGK,
        self::PHP,
        self::PKR,
        self::QAR,
        self::RUB,
        self::SAR,
        self::SCR,
        self::SEK,
        self::SGD,
        self::SLL,
        self::SOS,
        self::SSP,
        self::SVC,
        self::SZL,
        self::THB,
        self::TTD,
        self::TZS,
        self::USD,
        self::UYU,
        self::UZS,
        self::YER,
        self::ZAR,
    ];

    const ISO_NUMERIC_CODES = [
        self::AED => '784',
        self::ALL => '008',
        self::AMD => '051',
        self::ARS => '032',
        self::AUD => '036',
        self::AWG => '533',
        self::BBD => '052',
        self::BDT => '050',
        self::BMD => '060',
        self::BND => '096',
        self::BOB => '068',
        self::BSD => '044',
        self::BWP => '072',
        self::BZD => '084',
        self::CAD => '124',
        self::CHF => '756',
        self::CNY => '156',
        self::COP => '170',
        self::CRC => '188',
        self::CUP => '192',
        self::CZK => '203',
        self::DKK => '208',
        self::DOP => '214',
        self::DZD => '012',
        self::EGP => '818',
        self::ETB => '230',
        self::EUR => '978',
        self::FJD => '242',
        self::GBP => '826',
        self::GIP => '292',
        self::GMD => '270',
        self::GTQ => '320',
        self::GYD => '328',
        self::HKD => '344',
        self::HNL => '340',
        self::HRK => '191',
        self::HTG => '332',
        self::HUF => '348',
        self::IDR => '360',
        self::ILS => '376',
        self::INR => '356',
        self::JMD => '388',
        self::KES => '404',
        self::KGS => '417',
        self::KHR => '116',
        self::KYD => '136',
        self::KZT => '398',
        self::LAK => '418',
        self::LBP => '422',
        self::LKR => '144',
        self::LRD => '430',
        self::LSL => '426',
        self::MAD => '504',
        self::MDL => '498',
        self::MKD => '807',
        self::MMK => '104',
        self::MNT => '496',
        self::MOP => '446',
        self::MUR => '480',
        self::MVR => '462',
        self::MWK => '454',
        self::MXN => '484',
        self::MYR => '458',
        self::NAD => '516',
        self::NGN => '566',
        self::NIO => '558',
        self::NOK => '578',
        self::NPR => '524',
        self::NZD => '554',
        self::PEN => '604',
        self::PGK => '598',
        self::PHP => '608',
        self::PKR => '586',
        self::QAR => '634',
        self::RUB => '643',
        self::SAR => '682',
        self::SCR => '690',
        self::SEK => '752',
        self::SGD => '702',
        self::SLL => '694',
        self::SOS => '706',
        self::SSP => '728',
        self::SVC => '222',
        self::SZL => '748',
        self::THB => '764',
        self::TTD => '780',
        self::TZS => '834',
        self::USD => '840',
        self::UYU => '858',
        self::UZS => '860',
        self::YER => '886',
        self::ZAR => '710',
    ];

    const DENOMINATION_FACTOR = [
        self::AED => 100,
        self::ALL => 100,
        self::AMD => 100,
        self::ARS => 100,
        self::AUD => 100,
        self::AWG => 100,
        self::BBD => 100,
        self::BDT => 100,
        self::BMD => 100,
        self::BND => 100,
        self::BOB => 100,
        self::BSD => 100,
        self::BWP => 100,
        self::BZD => 100,
        self::CAD => 100,
        self::CHF => 100,
        self::CNY => 100,
        self::COP => 100,
        self::CRC => 100,
        self::CUP => 100,
        self::CZK => 100,
        self::DKK => 100,
        self::DOP => 100,
        self::DZD => 100,
        self::EGP => 100,
        self::ETB => 100,
        self::EUR => 100,
        self::FJD => 100,
        self::GBP => 100,
        self::GIP => 100,
        self::GMD => 100,
        self::GTQ => 100,
        self::GYD => 100,
        self::HKD => 100,
        self::HNL => 100,
        self::HRK => 100,
        self::HTG => 100,
        self::HUF => 100,
        self::IDR => 100,
        self::ILS => 100,
        self::INR => 100,
        self::JMD => 100,
        self::KES => 100,
        self::KGS => 100,
        self::KHR => 100,
        self::KYD => 100,
        self::KZT => 100,
        self::LAK => 100,
        self::LBP => 100,
        self::LKR => 100,
        self::LRD => 100,
        self::LSL => 100,
        self::MAD => 100,
        self::MDL => 100,
        self::MKD => 100,
        self::MMK => 100,
        self::MNT => 100,
        self::MOP => 100,
        self::MUR => 100,
        self::MVR => 100,
        self::MWK => 100,
        self::MXN => 100,
        self::MYR => 100,
        self::NAD => 100,
        self::NGN => 100,
        self::NIO => 100,
        self::NOK => 100,
        self::NPR => 100,
        self::NZD => 100,
        self::PEN => 100,
        self::PGK => 100,
        self::PHP => 100,
        self::PKR => 100,
        self::QAR => 100,
        self::RUB => 100,
        self::SAR => 100,
        self::SCR => 100,
        self::SEK => 100,
        self::SGD => 100,
        self::SLL => 100,
        self::SOS => 100,
        self::SSP => 100,
        self::SVC => 100,
        self::SZL => 100,
        self::THB => 100,
        self::TTD => 100,
        self::TZS => 100,
        self::USD => 100,
        self::UYU => 100,
        self::UZS => 100,
        self::YER => 100,
        self::ZAR => 100,
    ];

    const EXPONENT = [];

    const SYMBOL = [
        self::AED => 'د.إ',
        self::ALL => 'Lek',
        self::AMD => '֏',
        self::ARS => '$',
        self::AUD => '$',
        self::AWG => 'ƒ',
        self::BBD => '$',
        self::BDT => '৳',
        self::BMD => '$',
        self::BND => 'B$',
        self::BOB => 'Bs',
        self::BSD => 'B$',
        self::BWP => 'P',
        self::BZD => 'BZ$',
        self::CAD => 'C$',
        self::CHF => '₣',
        self::CNY => '¥',
        self::COP => '$',
        self::CRC => '₡',
        self::CUP => '$',
        self::CZK => 'Kč',
        self::DKK => 'kr',
        self::DOP => '$',
        self::DZD => 'د.ج',
        self::EGP => '£',
        self::ETB => 'ብር',
        self::EUR => '€',
        self::FJD => 'FJ$',
        self::GBP => '£',
        self::GIP => '£',
        self::GMD => 'D',
        self::GTQ => 'Q',
        self::GYD => 'G$',
        self::HKD => 'HK$',
        self::HNL => 'L',
        self::HRK => 'kn',
        self::HTG => 'G',
        self::HUF => 'Ft',
        self::IDR => 'Rp',
        self::ILS => '₪',
        self::INR => '₹',
        self::JMD => '$',
        self::KES => 'Ksh',
        self::KGS => 'Лв',
        self::KHR => '៛',
        self::KYD => '$',
        self::KZT => '₸',
        self::LAK => '₭',
        self::LBP => 'ل.ل.‎',
        self::LKR => 'රු',
        self::LRD => 'L$',
        self::LSL => 'L',
        self::MAD => 'د.م.',
        self::MDL => 'L',
        self::MKD => 'ден',
        self::MMK => 'K',
        self::MNT => '₮',
        self::MOP => 'P',
        self::MUR => 'Rs',
        self::MVR => 'Rf',
        self::MWK => 'MK',
        self::MXN => 'Mex$',
        self::MYR => 'RM',
        self::NAD => 'N$',
        self::NGN => '₦',
        self::NIO => 'C$',
        self::NOK => 'kr',
        self::NPR => 'रू',
        self::NZD => '$',
        self::PEN => 'S/',
        self::PGK => 'K',
        self::PHP => '₱',
        self::PKR => 'Rs',
        self::QAR => 'QR',
        self::RUB => '₽',
        self::SAR => 'SR',
        self::SCR => 'SRe',
        self::SEK => 'kr',
        self::SGD => 'S$',
        self::SLL => 'Le',
        self::SOS => 'Sh.so.',
        self::SSP => '£',
        self::SVC => '$',
        self::SZL => 'L',
        self::THB => '฿',
        self::TTD => '$',
        self::TZS => 'Sh',
        self::USD => '$',
        self::UYU => '$',
        self::UZS => 'so\'m',
        self::YER => '﷼',
        self::ZAR => 'R',
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
