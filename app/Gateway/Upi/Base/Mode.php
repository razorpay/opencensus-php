<?php

namespace RZP\Gateway\Upi\Base;

class Mode
{
    const DEFAULT_TXN      = '00';

    const QR_CODE          = '01';

    const SECURE_QR_CODE   = '02';

    const INTENT           = '04';

    const SECURE_INTENT    = '05';

    const NFC              = '06';

    const BLE              = '07';

    const UHF              = '08';
}
