<?php

namespace RZP\Constants;

class MalaysianStates
{
    const JHR = 'JHR';
    const KDH = 'KDH';
    const KTN = 'KTN';
    const KUL = 'KUL';
    const LBN = 'LBN';
    const MLK = 'MLK';
    const NSN = 'NSN';
    const PHG = 'PHG';
    const PJY = 'PJY';
    const PNG = 'PNG';
    const PRK = 'PRK';
    const PLS = 'PLS';
    const SBH = 'SBH';
    const SWK = 'SWK';
    const SGR = 'SGR';
    const TRG = 'TRG';

    protected static $stateCodeMap = [
        'JOHOR'               => self::JHR,
        'KEDAH'               => self::KDH,
        'KELANTAN'            => self::KTN,
        'KUALA LUMPUR'        => self::KUL,
        'LABUAN'              => self::LBN,
        'MALACCA'             => self::MLK,
        'NEGERI SEMBILAN'     => self::NSN,
        'PAHANG'              => self::PHG,
        'PENANG'              => self::PNG,
        'PERAK'               => self::PRK,
        'PERLIS'              => self::PLS,
        'PUTRAJAYA'           => self::PJY,
        'SABAH'               => self::SBH,
        'SARAWAK'             => self::SWK,
        'SELANGOR'            => self::SGR,
        'TERENGGANU'          => self::TRG
    ];

    public static function getStateCode(string $value)
    {
        $value = strtoupper($value);

        if (isset(self::$stateCodeMap[$value]) === true) {
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
        if (empty($code) === false) {
            $stateName = array_search($code, self::$stateCodeMap);

            return $stateName ?: null;
        }

        return null;
    }

    public static function stateValueExist(string $value): bool
    {
        $value = strtoupper($value);

        if (in_array($value, self::$stateCodeMap, true) === true) {
            return true;
        }

        return false;
    }

    public static function checkIfValidStateCodeOrName(string $value)
    {
        if ((self::getStateCode($value) === null) and (self::stateValueExist($value) === false)) {
            return false;
        }

        return true;
    }
}
