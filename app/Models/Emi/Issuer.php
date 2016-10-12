<?php

namespace RZP\Models\Emi;

use RZP\Models\Bank\IFSC;

class Issuer
{
    const KOTAK         = 'Kotak';
    const AXIS          = 'Axis';
    const INDUS_IND     = 'Indusind';
    const RBL           = 'Rbl';

    public static $emiFileBanks = array(
        IFSC::KKBK  => self::KOTAK,
        IFSC::UTIB  => self::AXIS,
        IFSC::INDB  => self::INDUS_IND,
        IFSC::RATN  => self::RBL,
    );

}
