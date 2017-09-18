<?php

namespace RZP\Models\Emi;

use RZP\Models\Bank\IFSC;

class Issuer
{
    const KOTAK         = 'Kotak';
    const AXIS          = 'Axis';
    const INDUS_IND     = 'Indusind';
    const RBL           = 'Rbl';
    const SCBL          = 'Scbl';
    const ICICI         = 'Icici';
    const YESB           = 'Yesb';

    public static $emiFileBanks = array(
        IFSC::KKBK  => self::KOTAK,
        IFSC::UTIB  => self::AXIS,
        IFSC::INDB  => self::INDUS_IND,
        IFSC::RATN  => self::RBL,
        IFSC::SCBL  => self::SCBL,
        IFSC::ICIC  => self::ICICI,
        IFSC::YESB  => self::YESB,
    );

}
