<?php

namespace RZP\Models\Merchant\Detail;

class TransactionVolume
{
    const RANGE1    = 1;
    const RANGE2    = 500000;
    const RANGE3    = 2500000;
    const RANGE4    = 5000000;
    const RANGE5    = 10000000;
    const RANGE6    = 1000000000;
    const RANGE7    = 'NA';

    const DefaultVolume = 7;

    public static function getVolume($num)
    {
        if (empty($num) === true)
        {
            return;
        }

        return constant(__CLASS__.'::'.'RANGE'.$num);
    }

    public static function getDefaultVolume()
    {
        return self::DefaultVolume;
    }

    public static function mapTransactionVolume($volume)
    {
        $transactionVolumeMap = [
            1 => 'Haven\'t started processing yet',
            2 => 'Less than 5 Lac',
            3 => '5 Lacs to 25 Lacs',
            4 => '25 Lacs to 50 Lacs',
            5 => '50 Lacs to 1 Crore',
            6 => 'More than 1 Crore',
        ];

        if (array_key_exists($volume, $transactionVolumeMap) === true)
        {
            return $transactionVolumeMap[$volume];
        }

        return '';
    }
}
