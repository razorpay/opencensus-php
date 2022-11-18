<?php

namespace RZP\Constants;

/**
 * List of table partitions
 */
class Partitions
{
    const DAILY = 'daily';
    const MONTHLY = 'monthly';
    const YEARLY = 'yearly';

    public static $futurePartitionCount = [
        self::DAILY => 7,
        self::MONTHLY => 5,
        self::YEARLY => 3,
    ];
}
