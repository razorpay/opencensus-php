<?php


namespace RZP\Models\Merchant\AvgOrderValue;


class Constants
{
    const AOV_RANGES = [
        [1, 150],
        [151, 300],
        [301, 600],
        [601, 1000],
        [1001, 2000],
        [2001, 3000],
        [3001, 5000],
        [5001, 10000],
        [10001, 20000],
        [20001, 50000],
        [50001, 100000],
        [100001, 0]
    ];
}
