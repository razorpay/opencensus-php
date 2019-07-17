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

    protected static $statuses = [
        self::CREATED,
        self::INITIATED,
        self::PROCESSING,
        self::CANCELLED,
        self::PROCESSED,
        self::UNSERVICEABLE,
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

    public static function validateCurrentToPreviousMapping(string $currentStatus, string $previousStatus)
    {
        $previousStatuses = self::$currentToPreviousStatusMap[$currentStatus];

        if (in_array($previousStatus, $previousStatuses, true) !== true)
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

    /**
     * @var array
     * This contains a status map that keeps mapping of a status
     * to previous possible statuses. This is to ensure the status
     * change on Banking Account Entity happens in an order.
     */
    protected static $currentToPreviousStatusMap = [
        self::CREATED           => [],
        self::INITIATED         => [self::CREATED],
        self::PROCESSING        => [self::INITIATED],
        self::PROCESSED         => [self::INITIATED, self::PROCESSING],
        self::ACTIVATED         => [self::PROCESSED],
        self::UNSERVICEABLE     => [self::CREATED, self::INITIATED, self::PROCESSING],
        self::CANCELLED         => [self::CREATED, self::INITIATED, self::PROCESSING]
    ];
}
