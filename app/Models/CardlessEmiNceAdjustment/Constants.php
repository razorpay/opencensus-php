<?php


namespace RZP\Models\CardlessEmiNceAdjustment;


class Constants
{
    const AXIO = 'Axio';
    const POLICY_BAZAAR = 'Policy_Bazaar';
    const AXIO_TEST = 'Axio';
    const POLICY_BAZAAR_TEST = 'Policy_Bazaar';

    const MERCHANT_MAP_FOR_ADJUSTMENT = [
        self::AXIO_TEST             => '100AxioAccount', // used for unit test
        self::POLICY_BAZAAR_TEST    => '100PoBaAccount',
        self::AXIO                  => 'LWr2cwsHOwMgfG',
        self::POLICY_BAZAAR         => '7LAuMvKMcy7s0f',
    ];

    const MERCHANT_MAP_FOR_ADJUSTMENT_DESCRIPTION = [
        '100AxioAccount'             => 'test Axio adjustment creation _', // used for unit test
        '100PoBaAccount'             => 'test PB adjustment creation _',
        'LWr2cwsHOwMgfG'             => 'PB/Axio subvention adjustment ',
        '7LAuMvKMcy7s0f'             => 'PB/Axio subvention adjustment ',
    ];

}
