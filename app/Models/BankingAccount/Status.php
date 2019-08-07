<?php

namespace RZP\Models\BankingAccount;

use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const CREATED           = 'created';
    const INITIATED         = 'initiated';
    const PROCESSING        = 'processing';
    const PROCESSED         = 'processed';
    const CANCELLED         = 'cancelled';
    const ACTIVATED         = 'activated';
    const UNSERVICEABLE     = 'unserviceable';

    protected static $initialStatuses = [
        Status::CREATED,
        Status::UNSERVICEABLE,
    ];

    protected static $statuses = [
        self::CREATED,
        self::INITIATED,
        self::PROCESSING,
        self::CANCELLED,
        self::PROCESSED,
        self::UNSERVICEABLE,
    ];

    /**
     * @var array
     * This contains a status map that keeps mapping of a status
     * to next possible statuses. This is to ensure the status
     * change on Banking Account Entity happens in an order.
     */
    protected static $fromToStatusMap = [
        self::CREATED => [
            self::INITIATED,
            self::UNSERVICEABLE,
            self::CANCELLED
        ],
        self::INITIATED => [
            self::PROCESSING,
            self::PROCESSED,
            self::UNSERVICEABLE,
            self::CANCELLED
        ],
        self::PROCESSING => [
            self::PROCESSED,
            self::UNSERVICEABLE,
            self::CANCELLED
        ],
        self::PROCESSED => [
            self::ACTIVATED
        ],
        self::UNSERVICEABLE => [],
        self::CANCELLED => [],
    ];

    public static $internallyEditStatuses = [
      self::INITIATED,
      self::PROCESSED,
      self::CANCELLED,
      self::PROCESSING,
      self::UNSERVICEABLE,
    ];

    public static function isValidStatus(string $status = null)
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
    }

    public static function validate(string $status = null)
    {
        if (self::isValidStatus($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid Razorpay Banking status',
                Entity::STATUS,
                [
                    Entity::STATUS => $status
                ]);
        }
    }

    public static function validatePreviousToCurrentMapping(string $previousStatus, string $currentStatus)
    {
        $nextStatusList = self::$fromToStatusMap[$previousStatus];

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

    public static function getAll(): array
    {
        return self::$statuses;
    }

    public static function validateInInitialStatuses(string $status)
    {
        $statusList = self::$initialStatuses;

        if (in_array($status, $statusList, true) === false)
        {
            throw new BadRequestValidationFailureException(
                'bank status' . $status. 'cannot be saved',
                Entity::BANK_INTERNAL_STATUS,
                [
                    Entity::STATUS               => $status
                ]);
        }
    }
}
