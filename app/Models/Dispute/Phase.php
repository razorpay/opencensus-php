<?php

namespace RZP\Models\Dispute;

class Phase
{
    const CHARGEBACK      = 'chargeback';
    const PRE_ARBITRATION = 'pre_arbitration';
    const ARBITRATION     = 'arbitration';
    const RETRIEVAL       = 'retrieval';
    const FRAUD           = 'fraud';

    /**
     * $nonTransactionalPhases are phases where no transactions
     * take place through the dispute's lifecycle
     */
    protected static $nonTransactionalPhases = [
        self::FRAUD,
    ];

    public static function exists(string $phase): bool
    {
        return defined(get_class() . '::' . strtoupper($phase));
    }

    public static function getNonTransactionalPhases(): array
    {
        return self::$nonTransactionalPhases;
    }
}
