<?php

namespace RZP\Gateway\Enach\Base;

class CategoryCode
{
    const A001 = 'A001'; //'API mandate';
    const C001 = 'C001'; //'B2B Corporate';
    const B001 = 'B001'; //'Bill Payment Credit card';
    const D001 = 'D001'; //'Destination Bank Mandate';
    const E001 = 'E001'; //'Education fees';
    const I001 = 'I001'; //'Insurance Premium';
    const I002 = 'I002'; //'Insurance other payment';
    const L099 = 'L099'; //'Legacy One crore and Above';
    const L002 = 'L002'; //'Loan amount security';
    const L001 = 'L001'; //'Loan instalment payment';
    const M001 = 'M001'; //'Mutual Fund Payment';
    const U099 = 'U099'; //'Others';
    const F001 = 'F001'; //'Subscription Fees';
    const T002 = 'T002'; //'TReDS';
    const T001 = 'T001'; //'Tax Payment';
    const U001 = 'U001'; //'Utility Bill Payment Electricity';
    const U003 = 'U003'; //'Utility Bill payment Gas Supply Cos';
    const U005 = 'U005'; //'Utility Bill payment mobile telephone broadband';
    const U006 = 'U006'; //'Utility Bill payment water';

    protected static $catCodeToDescriptionMapping = [
      self::A001 => 'API mandate',
      self::C001 => 'B2B Corporate',
      self::B001 => 'Bill Payment Credit card',
      self::D001 => 'Destination Bank Mandate',
      self::E001 => 'Education fees',
      self::I001 => 'Insurance Premium',
      self::I002 => 'Insurance other payment',
      self::L099 => 'Legacy One crore and Above',
      self::L002 => 'Loan amount security',
      self::L001 => 'Loan instalment payment',
      self::M001 => 'Mutual Fund Payment',
      self::U099 => 'Others',
      self::F001 => 'Subscription Fees',
      self::T002 => 'TReDS',
      self::T001 => 'Tax Payment',
      self::U001 => 'Utility Bill Payment Electricity',
      self::U003 => 'Utility Bill payment Gas Supply Cos',
      self::U005 => 'Utility Bill payment mobile telephone broadband',
      self::U006 => 'Utility Bill payment water',
    ];
    
    public static $emandateCorporateNameMapping = [
        "YESB00709000028661" => "INDIANCLRCORPLTD"
    ];

    protected static $mccToCategoryCodeMapping = [
        '6012' => self::L001,
        '6050' => self::B001,
        '6051' => self::B001,
        '8299' => self::E001,
        '8211' => self::E001,
        '8220' => self::E001,
        '6300' => self::I001,
        '6211' => self::M001,
        '5399' => self::U099,
        '7399' => self::U099,
        '5817' => self::F001,
        '5968' => self::F001,
        '9311' => self::T001,
        '4814' => self::U005,
        '4899' => self::U005,
    ];

    public static function getCategoryCodeFromMcc($mcc)
    {
        if (isset(self::$mccToCategoryCodeMapping[$mcc]) === true)
        {
            return self::$mccToCategoryCodeMapping[$mcc];
        }

        return self::A001;
    }

    public static function getCategoryCodeFromMccForNach($mcc)
    {
        if (isset(self::$mccToCategoryCodeMapping[$mcc]) === true)
        {
            return self::$mccToCategoryCodeMapping[$mcc];
        }

        return self::U099;
    }

    public static function getCategoryDescriptionFromCode($code)
    {
        return self::$catCodeToDescriptionMapping[$code];
    }
    
    public static function getCorporateName($utilityCode)
    {
        return self::$emandateCorporateNameMapping[$utilityCode];
    }
    
}
