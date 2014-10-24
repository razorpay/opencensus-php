<?php

namespace Models\Payment;

class Bank
{
    const ALLA = 'ALLA';
    const AXIS = 'AXIS';
    const CITI = 'CITI';
    const HDFC = 'HDFC';
    const IBKL = 'IBKL';
    const ICIC = 'ICIC';
    const KKBK = 'KKBK';
    const PUNB = 'PUNB';
    const SBIN = 'SBIN';

    public static $banks = array(
        self::ALLA => 'Allahabad Bank',
        self::CITI => 'Citi Bank',
        self::HDFC => 'HDFC Bank',
        self::IBKL => 'IDBI Bank',
        self::ICIC => 'ICICI Bank',
        self::KKBK => 'Kotak Mahindra Bank',
        self::PUNB => 'Punjab National Bank',
        self::SBIN => 'State Bank of India',
    );

    public static function isValidBank($bank)
    {
        return (defined(__CLASS__.'::'.$bank));
    }
}
