<?php

namespace RZP\Models\Tax\Gst;

class Gst
{
    /**
     * Tax slabs values multipled by 10000. I.e. tax slab values in UI look like 0.1%, 2.5%, 3% and so on.
     */
    const TAX_SLABS_V2 = [
        0,
        1000,
        2500,
        30000,
        50000,
        120000,
        180000,
        280000,
    ];
}
