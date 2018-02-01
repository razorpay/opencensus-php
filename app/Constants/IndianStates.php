<?php

namespace RZP\Constants;

class IndianStates
{
    const AN = 'AN';
    const AP = 'AP';
    const AR = 'AR';
    const AS = 'AS';
    const BI = 'BI';
    const CH = 'CH';
    const CT = 'CT';
    const DN = 'DN';
    const DD = 'DD';
    const DL = 'DL';
    const GO = 'GO';
    const GJ = 'GJ';
    const HA = 'HA';
    const HP = 'HP';
    const JK = 'JK';
    const JH = 'JH';
    const KA = 'KA';
    const KE = 'KE';
    const LD = 'LD';
    const MP = 'MP';
    const MH = 'MH';
    const MA = 'MA';
    const ME = 'ME';
    const MI = 'MI';
    const NA = 'NA';
    const OR = 'OR';
    const PO = 'PO';
    const PB = 'PB';
    const RJ = 'RJ';
    const SK = 'SK';
    const TN = 'TN';
    const TR = 'TR';
    const TG = 'TG';
    const UP = 'UP';
    const UT = 'UT';
    const WB = 'WB';

    protected static $stateCodeMap = [
        'ANDAMAN & NICOBAR ISLANDS'     => self::AN,
        'ANDHRA PRADESH'                => self::AP,
        'ARUNACHAL PRADESH'             => self::AR,
        'ASSAM'                         => self::AS,
        'BIHAR'                         => self::BI,
        'CHANDIGARH'                    => self::CH,
        'CHATTISGARH'                   => self::CT,
        'DADRA & NAGAR HAVELI'          => self::DN,
        'DAMAN & DIU'                   => self::DD,
        'DELHI'                         => self::DL,
        'GOA'                           => self::GO,
        'GUJARAT'                       => self::GJ,
        'HARYANA'                       => self::HA,
        'HIMACHAL PRADESH'              => self::HP,
        'JAMMU & KASHMIR'               => self::JK,
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

    public static function getStateCode(string $value)
    {
        if (isset(self::$stateCodeMap[$value]) === true)
        {
            return self::$stateCodeMap[$value];
        }

        return null;
    }
}
