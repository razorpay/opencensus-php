<?php

namespace RZP\Constants;

class IndianStates
{
    const AN = 'AN';
    const AD = 'AD'; // Andhra Pradesh (new)
    const AP = 'AP';
    const AR = 'AR';
    const AS = 'AS';
    const BI = 'BI';
    const CH = 'CH';
    const CT = 'CT';
    const DD = 'DD';
    const DL = 'DL';
    const DN = 'DN';
    const GJ = 'GJ';
    const GO = 'GO';
    const HA = 'HA';
    const HP = 'HP';
    const JH = 'JH';
    const JK = 'JK';
    const KA = 'KA';
    const KE = 'KE';
    const LD = 'LD';
    const MA = 'MA';
    const ME = 'ME';
    const MH = 'MH';
    const MI = 'MI';
    const MP = 'MP';
    const NA = 'NA';
    const OR = 'OR';
    const PB = 'PB';
    const PO = 'PO';
    const RJ = 'RJ';
    const SK = 'SK';
    const TG = 'TG';
    const TN = 'TN';
    const TR = 'TR';
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
