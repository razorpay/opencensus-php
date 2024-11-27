<?php

namespace RZP\Constants;

class IndianStates
{
    const AN = 'AN';
    const AP = 'AP';
    const AR = 'AR';
    const AS = 'AS';
    const BI = 'BI';
    const BR = 'BR';
    const CH = 'CH';
    const CT = 'CT';
    const DN = 'DN';
    const DD = 'DD';
    const DL = 'DL';
    const GO = 'GO';
    const GA = 'GA';
    const GJ = 'GJ';
    const HA = 'HA';
    const HP = 'HP';
    const HR = 'HR';
    const JK = 'JK';
    const JH = 'JH';
    const KA = 'KA';
    const KE = 'KE';
    const KL = 'KL';
    const LD = 'LD';
    const MP = 'MP';
    const MH = 'MH';
    const MA = 'MA';
    const ME = 'ME';
    const MI = 'MI';
    const MN = 'MN';
    const NA = 'NA';
    const NL = 'NL';
    const OR = 'OR';
    const PB = 'PB';
    const PO = 'PO';
    const PY = 'PY';
    const RJ = 'RJ';
    const SK = 'SK';
    const TN = 'TN';
    const TR = 'TR';
    const TS = 'TS';
    const TG = 'TG';
    const UP = 'UP';
    const UT = 'UT';
    const WB = 'WB';

    protected static $stateCodeMap = [
        'ANDAMAN & NICOBAR ISLANDS'     => self::AN,
        'ANDAMAN AND NICOBAR ISLANDS'   => self::AN,
        'ANDHRA PRADESH'                => self::AP,
        'ARUNACHAL PRADESH'             => self::AR,
        'ASSAM'                         => self::AS,
        'BIHAR'                         => self::BI,
        'CHANDIGARH'                    => self::CH,
        'CHATTISGARH'                   => self::CT,
        'DADRA & NAGAR HAVELI'          => self::DN,
        'DADRA AND NAGAR HAVELI'        => self::DN,
        'DAMAN & DIU'                   => self::DD,
        'DAMAN AND DIU'                 => self::DD,
        'DELHI'                         => self::DL,
        'GOA'                           => self::GO,
        'GUJARAT'                       => self::GJ,
        'HARYANA'                       => self::HA,
        'HIMACHAL PRADESH'              => self::HP,
        'JAMMU & KASHMIR'               => self::JK,
        'JAMMU AND KASHMIR'             => self::JK,
        'JHARKHAND'                     => self::JH,
        'KARNATAKA'                     => self::KA,
        'KERALA'                        => self::KE,
        'LAKSHADWEEP'                   => self::LD,
        'MADHYA PRADESH'                => self::MP,
        'MAHARASHTRA'                   => self::MH,
        'MANIPUR'                       => self::MA,
        'MEGHALAYA'                     => self::ME,
        'MIZORAM'                       => self::MI,
        'NAGALAND'                      => self::NA,
        'ODISHA'                        => self::OR,
        'PONDICHERRY'                   => self::PO,
        'PUNJAB'                        => self::PB,
        'RAJASTHAN'                     => self::RJ,
        'SIKKIM'                        => self::SK,
        'TAMIL NADU'                    => self::TN,
        'TRIPURA'                       => self::TR,
        'TELANGANA'                     => self::TG,
        'UTTAR PRADESH'                 => self::UP,
        'UTTARAKHAND'                   => self::UT,
        'WEST BENGAL'                   => self::WB,
    ];

    // State codes which don't match with above
    protected static $gstStateCodeMap = [
        'BIHAR'                         => self::BR,
        'GOA'                           => self::GA,
        'HARYANA'                       => self::HR,
        'KERALA'                        => self::KL,
        'MANIPUR'                       => self::MN,
        'NAGALAND'                      => self::NL,
        'PONDICHERRY'                   => self::PY,
        'TELANGANA'                     => self::TS,
        'TRIPURA'                       => self::TR,
    ];

    public static function getStateCode(string $value, bool $useGstCodes = false)
    {
        $value = strtoupper($value);

        if ($useGstCodes === true and isset(self::$gstStateCodeMap[$value]) === true)
        {
            return self::$gstStateCodeMap[$value];
        }

        if (isset(self::$stateCodeMap[$value]) === true)
        {
            return self::$stateCodeMap[$value];
        }

        return null;
    }

    public static function getStateNameByCode(string $code)
    {
        $stateName = array_search($code, self::$stateCodeMap);

        return $stateName ?: null;
    }

    public static function getStateName($code)
    {
        if (empty($code) === false)
        {
            $stateName = array_search($code, self::$stateCodeMap);

            return $stateName ?: null;
        }

        return null;
    }

    public static function stateValueExist(string $value):bool
    {
        $value = strtoupper($value);

        if (in_array($value, self::$stateCodeMap, true) === true)
        {
            return true;
        }

        return false;
    }

    public static function checkIfValidStateCodeOrName(string $value)
    {
        if ((self::getStateCode($value) === null) and (self::stateValueExist($value) === false))
        {
            return false;
        }

        return true;
    }
}
