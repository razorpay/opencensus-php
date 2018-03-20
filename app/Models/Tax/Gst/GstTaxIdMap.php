<?php

namespace RZP\Models\Tax\Gst;

use RZP\Models\Tax\Entity;

class GstTaxIdMap
{
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

    public static function get(): array
    {
        $map = (new \ReflectionClass(__CLASS__))->getConstants();

        array_walk(
            $map,
            function(& $id)
            {
                $id = Entity::getSignedId($id);
            });

        return $map;
    }
}
