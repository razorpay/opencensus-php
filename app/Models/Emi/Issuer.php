<?php

namespace RZP\Models\Emi;

use RZP\Models\Bank\IFSC;

class Issuer
{
    const KOTAK         = 'Kotak';
    const AXIS          = 'Axis';
    const INDUS_IND     = 'IndusInd';

    public static $emiFileBanks = array(
        IFSC::KKBK  => self::KOTAK,
        IFSC::UTIB  => self::AXIS,
        IFSC::INDB  => self::INDUS_IND,
    );

}