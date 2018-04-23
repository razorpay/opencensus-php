<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

abstract class Status
{
    abstract public static function getSuccessfulStatus(): array;

    abstract public static function getFailureStatus(): array;
}
