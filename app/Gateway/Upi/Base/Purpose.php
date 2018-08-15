<?php

namespace RZP\Gateway\Upi\Base;

class Purpose
{
    const DEFAULT_PURPOSE   = '00';

    const SEBI              = '01';

    const AMC               = '02';

    const TRAVEL            = '03';

    const HOSPITALITY       = '04';

    const HOSPITAL          = '05';

    const TELECOM           = '06';

    const INSURANCE         = '07';

    const EDUCATION         = '08';

    const GIFTING           = '09';

    const OTHERS            = '10';

    /**
     * Returns mapped purpose for a payment
     *
     * @param string $categoryCode
     * @return string
     */
    public static function getMappedPurpose(array $request)
    {
        $categoryCode = $request[IntentParams::MCC] ?? null;

        // TODO: Implement the logic to map purpose from categoryCode
        return self::DEFAULT_PURPOSE;
    }

}
