<?php

namespace RZP\Models\Settlement\Ondemand;

use RZP\Models\Merchant\Balance\AccountType;
use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const CREATED               = 'created';
    const INITIATED             = 'initiated';
    const PROCESSED             = 'processed';
    const PARTIALLY_PROCESSED   = 'partially_processed';
    const REVERSED              = 'reversed';


    public static $finalStates = [
        self::PROCESSED,
        self::PARTIALLY_PROCESSED,
        self::REVERSED
    ];

    /**
     * This contains a status map that keeps mapping of a status
     * to next possible statuses. This is to ensure the status
     * change on SettlementOndemand Entity happens in an order.
     *
     * @var array
     */
    protected static $fromToStatusMap = [
        null => [
            self::CREATED,
        ],
        self::CREATED => [
            self::INITIATED,
            self::REVERSED,
        ],
        self::INITIATED => [
            self::PROCESSED,
            self::PARTIALLY_PROCESSED,
            self::REVERSED,
            self::INITIATED,
        ],
        self::PARTIALLY_PROCESSED => [
            self::PARTIALLY_PROCESSED,
            self::PROCESSED,
            self::REVERSED,
            self::INITIATED,
        ],     
        self::PROCESSED => [
            self::PARTIALLY_PROCESSED,
            self::REVERSED,
        ],
        self::REVERSED => [
            // this is empty because it's the final status
        ],
    ];

    public static function validateStatusUpdate(string $currentStatus, string $previousStatus = null)
    {
        $nextStatusList = self::$fromToStatusMap[$previousStatus];

        self::validate($currentStatus);

        if (in_array($currentStatus, $nextStatusList, true) === false)
        {
            throw new BadRequestValidationFailureException(
                'Status change not permitted',
                Entity::STATUS,
                [
                    'current_status'  => $currentStatus,
                    'previous_status' => $previousStatus,
                ]);
        }
    }

    public static function isValid(string $status): bool
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
    }

    public static function validate(string $status)
    {
        if (self::isValid($status) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid payout status: ' . $status);
        }
    }

    public static function isFinalState($status): bool
    {
        return in_array($status,
                        self::$finalStates,
                        true);
    }
}
