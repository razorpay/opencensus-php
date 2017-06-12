<?php

namespace RZP\Models\Batch;

use RZP\Exception\BadRequestException;
use RZP\Error\ErrorCode;

class Limit
{
    const PER_TYPE = [
        Type::REFUND       => 1000,
        Type::PAYMENT_LINK => 5000,
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
