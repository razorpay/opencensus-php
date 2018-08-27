<?php

namespace RZP\Models\Tax\Gst;

use RZP\Models\Tax\Entity;

class GstTaxIdMap
{
    // Todo: Remove backward compatibility code once dashboard starts consuming new response

    // IGST
    const DEPRECATED_IGST_0     = '9nDpYboKAK9j7t';
    const DEPRECATED_IGST_500   = '9nDpYciCWeNBzE';
    const DEPRECATED_IGST_1200  = '9nDpYdbYNqD4Rw';
    const DEPRECATED_IGST_1800  = '9nDpYf1tTUs2Vh';
    const DEPRECATED_IGST_2800  = '9nDpYfqgnYW5Dx';

    // CGST
    const DEPRECATED_CGST_0     = '9nDpYglSpU58lc';
    const DEPRECATED_CGST_250   = '9nDpYhZ0d60X7V';
    const DEPRECATED_CGST_300   = '9nDzjuY7cmkaSC';
    const DEPRECATED_CGST_500   = '9nDpYiArP6j0qT';
    const DEPRECATED_CGST_600   = '9nDpYivRHUQQV8';
    const DEPRECATED_CGST_900   = '9nDpYjuyZsOlMK';
    const DEPRECATED_CGST_1200  = '9nDpYkng64GyTa';
    const DEPRECATED_CGST_1400  = '9nDpYlTs7cWM80';
    const DEPRECATED_CGST_1800  = '9nDpYmPK2K2mVi';
    const DEPRECATED_CGST_2800  = '9nDpYnFEoqJQ5v';

    // SGST
    const DEPRECATED_SGST_0     = '9nDpYnvgiGXrZh';
    const DEPRECATED_SGST_250   = '9nDpYoeYBsXRvC';
    const DEPRECATED_SGST_500   = '9nDpYpMRZgJEgU';
    const DEPRECATED_SGST_600   = '9nDpYpuN72gdfY';
    const DEPRECATED_SGST_900   = '9nDpYqgYcqpr8q';
    const DEPRECATED_SGST_1200  = '9nDpYrIMXQTtPd';
    const DEPRECATED_SGST_1400  = '9nDpYs1yK0pndD';
    const DEPRECATED_SGST_1800  = '9nDpYsoU7subph';
    const DEPRECATED_SGST_2800  = '9nDpYtb0S0JhMP';

    // UTGST
    const DEPRECATED_UTGST_0    = '9nDpYuFVNQcVaU';
    const DEPRECATED_UTGST_250  = '9nDpYv53mqSsip';
    const DEPRECATED_UTGST_500  = '9nDpYvgwu0p8WP';
    const DEPRECATED_UTGST_600  = '9nDpYwRScK0Mz2';
    const DEPRECATED_UTGST_900  = '9nDpYxMkO0LLhz';
    const DEPRECATED_UTGST_1200 = '9nDpYyC50acDzW';
    const DEPRECATED_UTGST_1400 = '9nDpYz26oaOHgI';
    const DEPRECATED_UTGST_1800 = '9nDpYznDzU7NKP';
    const DEPRECATED_UTGST_2800 = '9nDpZ0hEw4vZky';

    // Naming convention: <IGST|CGST|SGST>_<TAX_RATE * 10000>

    // IGST
    const IGST_0       = '9nDpYboKAK9j7t';
    const IGST_1000    = 'AqSY0bpgK3J3L4';
    const IGST_2500    = 'AqSY17pgWiMndv';
    const IGST_30000   = 'AqSY1bGQyRH8du';
    const IGST_50000   = '9nDpYciCWeNBzE';
    const IGST_120000  = '9nDpYdbYNqD4Rw';
    const IGST_180000  = '9nDpYf1tTUs2Vh';
    const IGST_280000  = '9nDpYfqgnYW5Dx';

    // CGST
    const CGST_0       = '9nDpYglSpU58lc';
    const CGST_500     = 'AqSY27zssKqQYv';
    const CGST_1000    = 'AqSY2gRw8rXidu';
    const CGST_1250    = 'AqSY3I36snGvG5';
    const CGST_2500    = 'AqSY3sZX9uhEoh';
    const CGST_15000   = 'AqSY4M0KynG1zy';
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
    const SGST_500     = 'AqSY58G31xXO33';
    const SGST_1000    = 'AqSY5lJRF4XvKe';
    const SGST_1250    = 'AqSY6EtkXIwsOv';
    const SGST_2500    = 'AqSY6wibitLtWR';
    const SGST_15000   = 'AqSY7SThuRH1yb';
    const SGST_25000   = '9nDpYoeYBsXRvC';
    const SGST_30000   = 'AqSY7zvl5MzoD3';
    const SGST_50000   = '9nDpYpMRZgJEgU';
    const SGST_60000   = '9nDpYpuN72gdfY';
    const SGST_90000   = '9nDpYqgYcqpr8q';
    const SGST_120000  = '9nDpYrIMXQTtPd';
    const SGST_140000  = '9nDpYs1yK0pndD';
    const SGST_180000  = '9nDpYsoU7subph';
    const SGST_280000  = '9nDpYtb0S0JhMP';

    // UTGST
    const UTGST_0      = '9nDpYuFVNQcVaU';
    const UTGST_500    = 'AqSY8YT5PVIuFN';
    const UTGST_1000   = 'AqSY8yMeDrsbR7';
    const UTGST_1250   = 'AqSY9SeixpidMS';
    const UTGST_2500   = 'AqSY9uhngUqPOO';
    const UTGST_15000  = 'AqSYAPBxtHYPaH';
    const UTGST_25000  = '9nDpYv53mqSsip';
    const UTGST_30000  = 'AqSYAumyWQVE5H';
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
