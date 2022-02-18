<?php

namespace RZP\Models\Order\OrderMeta\OfflineAdditionalInfo;

class Fields
{
    const PROPERTY_ID         = 'property_id';
    const ROLL_NUMBER         = 'roll_number';
    const ADMISSION_NUMBER    = 'admission_number';
    const TENDER_ID           = 'tender_id';

    public static $dataFields = [
        self::PROPERTY_ID,
        self::ROLL_NUMBER,
        self::ADMISSION_NUMBER,
        self::TENDER_ID,
    ];

}
