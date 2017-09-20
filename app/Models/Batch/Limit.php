<?php

namespace RZP\Models\Batch;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Limit
{
    const PER_TYPE = [
        Type::REFUND           => 1000,
        Type::PAYMENT_LINK     => 5000,
        Type::IRCTC_REFUND     => 100000,
        Type::IRCTC_SETTLEMENT => 100000,
    ];

    /**
     * Validates that given total count lies between set limits
     * per type of batch.
     *
     * @param string $type
     * @param int    $total
     *
     * @throws BadRequestException
     */
    public static function validate(string $type, int $total)
    {
        if ($total === 0)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_FILE_EMPTY,
                null,
                [
                    'type'  => $type,
                    'total' => $total,
                ]);
        }

        if ($total > self::PER_TYPE[$type])
        {
           throw new BadRequestException(
               ErrorCode::BAD_REQUEST_BATCH_FILE_EXCEED_LIMIT,
               null,
               [
                   'type'  => $type,
                   'total' => $total,
               ]);
        }
    }
}
