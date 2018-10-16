<?php

namespace RZP\Gateway\Mpi\Base;

use RZP\Models\Card\Network;

class Eci
{
    const VISA_ENROLLED              = '05';

    const VISA_NOT_ENROLLED          = '06';

    const VISA_AUTH_NOT_ATTEMPTED    = '07';

    const MASTER_AUTH_NOT_ATTEMPTED  = '00';

    const MASTER_NOT_ENROLLED        = '01';

    const MASTER_ENROLLED            = '02';

    const VISA_MOTO                  = '02';

    const MASTER_MOTO                = '07';

    const VISA_SI                    = '02';

    const MASTER_SI                  = '07';

    public static $siEciValues = [
        Network::VISA => self::VISA_SI,
        Network::MC   => self::MASTER_SI,
    ];

    public static $motoEciValues = [
        Network::VISA => self::VISA_MOTO,
        Network::MC   => self::MASTER_MOTO,
        Network::MAES => self::MASTER_MOTO,
    ];

    public static function getEciValueForMoto($network)
    {
        return self::$siEciValues[$network];
    }

    public static function getEciValueforSI($network)
    {
        return self::$motoEciValues[$network];
    }
}
