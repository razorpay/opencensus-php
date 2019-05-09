<?php

namespace RZP\Models\Tax\Gst;

use RZP\Models\Tax\Entity;

class GstTaxIdMap
{
    // Naming convention: <IGST|CGST|SGST>_<TAX_RATE * 10000>

    // IGST
    const IGST_0       = '9nDpYboKAK9j7t';
    const IGST_1000    = 'BfshA51SxQsUxK';
    const IGST_2500    = 'BfshAynA8QJFrw';
    const IGST_30000   = 'BfshBdBn4hHoad';
    const IGST_50000   = '9nDpYciCWeNBzE';
    const IGST_120000  = '9nDpYdbYNqD4Rw';
    const IGST_180000  = '9nDpYf1tTUs2Vh';
    const IGST_280000  = '9nDpYfqgnYW5Dx';

    // CGST
    const CGST_0       = '9nDpYglSpU58lc';
    const CGST_500     = 'BfshCEQAP7ajjz';
    const CGST_1000    = 'BfshCs8x6I95YV';
    const CGST_1250    = 'BfshDU2gwdUNb9';
    const CGST_2500    = 'BfshE7U0aFEoAi';
    const CGST_15000   = 'BfshEjK5qyZJq9';
    const CGST_25000   = '9nDpYhZ0d60X7V';
    const CGST_30000   = '9nDzjuY7cmkaSC';
    const CGST_50000   = '9nDpYiArP6j0qT';
    const CGST_60000   = '9nDpYivRHUQQV8';
    const CGST_90000   = '9nDpYjuyZsOlMK';
    const CGST_120000  = '9nDpYkng64GyTa';
    const CGST_140000  = '9nDpYlTs7cWM80';
    const CGST_180000  = '9nDpYmPK2K2mVi';
    const CGST_280000  = '9nDpYnFEoqJQ5v';

    // SGST
    const SGST_0       = '9nDpYnvgiGXrZh';
    const SGST_500     = 'BfshFHja7s8Vvq';
    const SGST_1000    = 'BfshFtJ8pE8N5t';
    const SGST_1250    = 'BfshGT85ZW7NJl';
    const SGST_2500    = 'BfshH5DzT0LaTL';
    const SGST_15000   = 'BfshHgO8t432o6';
    const SGST_25000   = '9nDpYoeYBsXRvC';
    const SGST_30000   = 'BfshIDuScOgtCh';
    const SGST_50000   = '9nDpYpMRZgJEgU';
    const SGST_60000   = '9nDpYpuN72gdfY';
    const SGST_90000   = '9nDpYqgYcqpr8q';
    const SGST_120000  = '9nDpYrIMXQTtPd';
    const SGST_140000  = '9nDpYs1yK0pndD';
    const SGST_180000  = '9nDpYsoU7subph';
    const SGST_280000  = '9nDpYtb0S0JhMP';

    // UTGST
    const UTGST_0      = '9nDpYuFVNQcVaU';
    const UTGST_500    = 'BfshInY3KVdZrm';
    const UTGST_1000   = 'BfshJMH0TddvbZ';
    const UTGST_1250   = 'BfshJsRIX0WZga';
    const UTGST_2500   = 'BfshKVrLHgEbvN';
    const UTGST_15000  = 'BfshL7JKKArrv9';
    const UTGST_25000  = '9nDpYv53mqSsip';
    const UTGST_30000  = 'BfshLkpOqY3Zng';
    const UTGST_50000  = '9nDpYvgwu0p8WP';
    const UTGST_60000  = '9nDpYwRScK0Mz2';
    const UTGST_90000  = '9nDpYxMkO0LLhz';
    const UTGST_120000 = '9nDpYyC50acDzW';
    const UTGST_140000 = '9nDpYz26oaOHgI';
    const UTGST_180000 = '9nDpYznDzU7NKP';
    const UTGST_280000 = '9nDpZ0hEw4vZky';

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
