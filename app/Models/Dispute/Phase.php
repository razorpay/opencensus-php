<?php

namespace RZP\Models\Dispute;

class Phase
{
    const CHARGEBACK      = 'chargeback';
    const PRE_ARBITRATION = 'pre_arbitration';
    const ARBITRATION     = 'arbitration';
    const RETRIEVAL       = 'retrieval';
    const FRAUD           = 'fraud';

    protected static $nonTransactionalPhase = [
        self::RETRIEVAL,
        self::FRAUD,
    ];

    public static function exists(string $phase)
    {
        return defined(get_class() . '::' . strtoupper($phase));
    }

    public static function getNonTransactionalPhases()
    {
        return self::$nonTransactionalPhase;
    }
}
