<?php

namespace Lib;

class Gstin
{
    /**
     * Details here: https://cleartax.in/s/know-your-gstin#struct
     *
     * First 2 digits matched are captured
     */
    const GSTIN_REGEX = '/^(\d{2})[A-Z]{5}\d{4}[A-Z]{1}[A-Z\d]{3}$/';

    //
    // State codes from http://www.ddvat.gov.in/docs/List%20of%20State%20Code.pdf
    //
    const AN = 'AN';
    const AP = 'AP';
    const AD = 'AD';
    const AR = 'AR';
    const AS = 'AS';
    const BH = 'BH';
    const CH = 'CH';
    const CT = 'CT';
    const DN = 'DN';
    const DD = 'DD';
    const DL = 'DL';
    const GA = 'GA';
    const GJ = 'GJ';
    const HR = 'HR';
    const HP = 'HP';
    const JK = 'JK';
    const JH = 'JH';
    const KA = 'KA';
    const KL = 'KL';
    const LD = 'LD';
    const MP = 'MP';
    const MH = 'MH';
    const MN = 'MN';
    const ME = 'ME';
    const MI = 'MI';
    const NL = 'NL';
    const OR = 'OR';
    const PY = 'PY';
    const PB = 'PB';
    const RJ = 'RJ';
    const SK = 'SK';
    const TN = 'TN';
    const TS = 'TS';
    const TR = 'TR';
    const UP = 'UP';
    const UT = 'UT';
    const WB = 'WB';

    protected static $gstinToStateCodeMap = [
        '35' => self::AN,
        '28' => self::AP,
        '37' => self::AD,
        '12' => self::AR,
        '18' => self::AS,
        '10' => self::BH,
        '04' => self::CH,
        '22' => self::CT,
        '26' => self::DN,
        '25' => self::DD,
        '07' => self::DL,
        '30' => self::GA,
        '24' => self::GJ,
        '06' => self::HR,
        '02' => self::HP,
        '01' => self::JK,
        '20' => self::JH,
        '29' => self::KA,
        '32' => self::KL,
        '31' => self::LD,
        '23' => self::MP,
        '27' => self::MH,
        '14' => self::MN,
        '17' => self::ME,
        '15' => self::MI,
        '13' => self::NL,
        '21' => self::OR,
        '34' => self::PY,
        '03' => self::PB,
        '08' => self::RJ,
        '11' => self::SK,
        '33' => self::TN,
        '36' => self::TS,
        '16' => self::TR,
        '09' => self::UP,
        '05' => self::UT,
        '19' => self::WB,
    ];

    protected static $indianUnionTerritories = [
        self::AN,
        self::CH,
        self::DD,
        self::DL,
        self::DN,
        self::LD,
        self::PY,
    ];

    protected static $nameToStateCodeMap = [
        'Andaman and Nicobar Islands' => self::AN,
        'Andhra Pradesh'              => self::AP,
        'Andhra Pradesh (New)'        => self::AD,
        'Arunachal Pradesh'           => self::AR,
        'Assam'                       => self::AS,
        'Bihar'                       => self::BH,
        'Chandigarh'                  => self::CH,
        'Chattisgarh'                 => self::CT,
        'Dadra and Nagar Haveli'      => self::DN,
        'Daman and Diu'               => self::DD,
        'Delhi'                       => self::DL,
        'Goa'                         => self::GA,
        'Gujarat'                     => self::GJ,
        'Haryana'                     => self::HR,
        'Himachal Pradesh'            => self::HP,
        'Jammu and Kashmir'           => self::JK,
        'Jharkhand'                   => self::JH,
        'Karnataka'                   => self::KA,
        'Kerala'                      => self::KL,
        'Lakshadweep Islands'         => self::LD,
        'Madhya Pradesh'              => self::MP,
        'Maharashtra'                 => self::MH,
        'Manipur'                     => self::MN,
        'Meghalaya'                   => self::ME,
        'Mizoram'                     => self::MI,
        'Nagaland'                    => self::NL,
        'Odisha'                      => self::OR,
        'Pondicherry'                 => self::PY,
        'Punjab'                      => self::PB,
        'Rajasthan'                   => self::RJ,
        'Sikkim'                      => self::SK,
        'Tamil Nadu'                  => self::TN,
        'Telangana'                   => self::TS,
        'Tripura'                     => self::TR,
        'Uttar Pradesh'               => self::UP,
        'Uttarakhand'                 => self::UT,
        'West Bengal'                 => self::WB,
    ];

    public static function isValidStateCode(string $stateCode): bool
    {
        return (array_key_exists($stateCode, static::$gstinToStateCodeMap) === true);
    }

    public static function isValid(string $gstin): bool
    {
        $valid = preg_match(self::GSTIN_REGEX, $gstin, $matches);

        $stateCode = $matches[1] ?? '00';

        //
        // - Regex is valid and
        // - state code is valid as per list in `$stateCodes`
        //
        return (($valid === 1) and self::isValidStateCode($stateCode));
    }

    public static function getGstinStateMetadata(): array
    {
        $stateToTinCodeMap = array_flip(self::$gstinToStateCodeMap);

        $data = [];

        foreach (self::$nameToStateCodeMap as $name => $stateCode)
        {
            $isUnionTerritory = in_array($stateCode, self::$indianUnionTerritories, true);

            $data[] = [
                'name'  => $name,
                'code'  => (string) $stateToTinCodeMap[$stateCode],
                'is_ut' => $isUnionTerritory
            ];
        }

        return $data;
    }

    public static function getStateNameByCode(string $code): string
    {
        return array_flip(self::$nameToStateCodeMap)[self::$gstinToStateCodeMap[$code]];
    }
}
