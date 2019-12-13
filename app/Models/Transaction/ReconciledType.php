<?php

namespace RZP\Models\Transaction;

use RZP\Exception\InvalidArgumentException;

class ReconciledType
{
    const NA        = 'na';
    const MIS       = 'mis';
    const MANUAL    = 'manual';

    public static function isReconciledTypeValid($reconciledType) : bool
    {
        $key = __CLASS__ . '::' . strtoupper($reconciledType);

        return ((defined($key) === true) and (constant($key) === $reconciledType));
    }

    public static function validateReconciledType($reconciledType)
    {
        if ((empty($reconciledType) === false) and
            (self::isReconciledTypeValid($reconciledType) === false))
        {
            throw new InvalidArgumentException(
                'Invalid reconciled type',
                [
                    'field'          => Entity::RECONCILED_TYPE,
                    'reconciled_type' => $reconciledType
                ]);
        }
    }
}
