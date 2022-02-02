<?php

namespace RZP\Models\Settlement\Ondemand\Transfer;

use RZP\Models\Merchant\Balance\AccountType;
use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const CREATED               = 'created';
    const PROCESSING            = 'processing';
    const PROCESSED             = 'processed';
    const REVERSED              = 'reversed';


    public static $finalStates = [
        self::PROCESSED,
        self::REVERSED
    ];

    /**
     * This contains a status map that keeps mapping of a status
     * to next possible statuses. This is to ensure the status
     * change on SettlementOndemandTransfer Entity happens in an order.
     *
     * @var array
     */
    protected static $fromToStatusMap = [
        null => [
            self::CREATED,
        ],
        self::CREATED => [
            self::PROCESSING,
            self::REVERSED,
            self::PROCESSED,
        ],
        self::PROCESSING => [
            self::PROCESSED,
            self::REVERSED,
            self::PROCESSING,
        ],
        self::PROCESSED => [
            self::PROCESSING,
            self::REVERSED,
        ],
        self::REVERSED => [
            self::PROCESSING,
            self::PROCESSED,
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
            throw new BadRequestValidationFailureException('Not a valid settlement_ondemand_transfer status: ' . $status);
        }
    }

    public static function isFinalState($status): bool
    {
        return in_array($status,
            self::$finalStates,
            true);
    }
}
