<?php

namespace RZP\Models\Merchant\HsCode;

class HsCodeList
{
    const CODES         = "codes";
    const HSCODE        = "hsCode";
    const DESCRIPTION   = "description";
    const HSGROUP       = "hsGroup";

    const SOFTWARE      = "Software";
    const GOODS         = "Goods";

    // HS codes
    const HS85238020    = '85238020';

    // HS codes description
    const HS85238020_DESC   = 'Software';

    //purpose code mapping
    protected static $hsCodeDescriptionMappings = [
        self::HS85238020 => self::HS85238020_DESC,
    ];

    const SOFTWARE_CODES = [
        self::HS85238020,
    ];

    const GOODS_CODES = [
    ];

    public static function getHsCodeDescDescription($hsCode): string
    {
        return self::$hsCodeDescriptionMappings[$hsCode];
    }

    public static function isGoodsMerchant($hsCode): bool
    {
        return in_array($hsCode,self::GOODS_CODES);
    }

    public static function getHsCode(): array
    {
        $data = array();

        $hsGroupMapping = array(
            array(self::HSGROUP => self::SOFTWARE,
                self::CODES => self::SOFTWARE_CODES
            ),
            array(self::HSGROUP => self::GOODS,
                self::CODES => self::GOODS_CODES
            ));

        foreach ($hsGroupMapping as $hsCode) {
            array_push($data, HsCodeList::getHsGroupDetails($hsCode[self::HSGROUP], $hsCode[self::CODES]));
        }

        return $data;
    }

    public static function getHsGroupDetails($hsGroup, $codes): array
    {
        $data = array(
            self::HSGROUP => $hsGroup,
            self::CODES => array()
        );

        foreach ($codes as $code) {
            $data[self::CODES][] = array(
                self::HSCODE => $code,
                self::DESCRIPTION => self::$hsCodeDescriptionMappings[$code],
            );
        }

        return $data;
    }
}
