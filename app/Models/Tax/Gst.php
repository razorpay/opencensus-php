<?php

namespace RZP\Models\Tax;

class Gst
{
    const INDIA_TAX_SLABS = [0, 500, 1200, 1800, 2800];

    // IGST
    const IGST_0     = '9nDpYboKAK9j7t';
    const IGST_500   = '9nDpYciCWeNBzE';
    const IGST_1200  = '9nDpYdbYNqD4Rw';
    const IGST_1800  = '9nDpYf1tTUs2Vh';
    const IGST_2800  = '9nDpYfqgnYW5Dx';

    // CGST
    const CGST_0     = '9nDpYglSpU58lc';
    const CGST_250   = '9nDpYhZ0d60X7V';
    const CGST_500   = '9nDpYiArP6j0qT';
    const CGST_600   = '9nDpYivRHUQQV8';
    const CGST_900   = '9nDpYjuyZsOlMK';
    const CGST_1200  = '9nDpYkng64GyTa';
    const CGST_1400  = '9nDpYlTs7cWM80';
    const CGST_1800  = '9nDpYmPK2K2mVi';
    const CGST_2800  = '9nDpYnFEoqJQ5v';

    // SGST
    const SGST_0     = '9nDpYnvgiGXrZh';
    const SGST_250   = '9nDpYoeYBsXRvC';
    const SGST_500   = '9nDpYpMRZgJEgU';
    const SGST_600   = '9nDpYpuN72gdfY';
    const SGST_900   = '9nDpYqgYcqpr8q';
    const SGST_1200  = '9nDpYrIMXQTtPd';
    const SGST_1400  = '9nDpYs1yK0pndD';
    const SGST_1800  = '9nDpYsoU7subph';
    const SGST_2800  = '9nDpYtb0S0JhMP';

    // UTGST
    const UTGST_0    = '9nDpYuFVNQcVaU';
    const UTGST_250  = '9nDpYv53mqSsip';
    const UTGST_500  = '9nDpYvgwu0p8WP';
    const UTGST_600  = '9nDpYwRScK0Mz2';
    const UTGST_900  = '9nDpYxMkO0LLhz';
    const UTGST_1200 = '9nDpYyC50acDzW';
    const UTGST_1400 = '9nDpYz26oaOHgI';
    const UTGST_1800 = '9nDpYznDzU7NKP';
    const UTGST_2800 = '9nDpZ0hEw4vZky';

    public static function getIndiaTaxSlabs(): array
    {
        return self::INDIA_TAX_SLABS;
    }

    public static function getIndiaTaxIds(): array
    {
        $taxIds = [
            'IGST_0'     => self::IGST_0,
            'IGST_500'   => self::IGST_500,
            'IGST_1200'  => self::IGST_1200,
            'IGST_1800'  => self::IGST_1800,
            'IGST_2800'  => self::IGST_2800,
            'CGST_0'     => self::CGST_0,
            'CGST_250'   => self::CGST_250,
            'CGST_500'   => self::CGST_500,
            'CGST_600'   => self::CGST_600,
            'CGST_900'   => self::CGST_900,
            'CGST_1200'  => self::CGST_1200,
            'CGST_1400'  => self::CGST_1400,
            'CGST_1800'  => self::CGST_1800,
            'CGST_2800'  => self::CGST_2800,
            'SGST_0'     => self::SGST_0,
            'SGST_250'   => self::SGST_250,
            'SGST_500'   => self::SGST_500,
            'SGST_600'   => self::SGST_600,
            'SGST_900'   => self::SGST_900,
            'SGST_1200'  => self::SGST_1200,
            'SGST_1400'  => self::SGST_1400,
            'SGST_1800'  => self::SGST_1800,
            'SGST_2800'  => self::SGST_2800,
            'UTGST_0'    => self::UTGST_0,
            'UTGST_250'  => self::UTGST_250,
            'UTGST_500'  => self::UTGST_500,
            'UTGST_600'  => self::UTGST_600,
            'UTGST_900'  => self::UTGST_900,
            'UTGST_1200' => self::UTGST_1200,
            'UTGST_1400' => self::UTGST_1400,
            'UTGST_1800' => self::UTGST_1800,
            'UTGST_2800' => self::UTGST_2800,
        ];

        array_walk($taxIds, function(& $taxId)
        {
            $taxId = Entity::getSignedId($taxId);
        });

        return $taxIds;
    }
}
