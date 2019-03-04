<?php

namespace RZP\Models\Payout;

use RZP\Models\FundTransfer\Attempt;
use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    // The following three constants are required by the
    // FTA module to update the source status. Things will
    // get wrecked if these are removed. Wrecked.

    const PROCESSED     = 'processed';
    const INITIATED     = Attempt\Status::INITIATED;
    const FAILED        = 'reversed';


    const CREATED       = 'created';
    const REVERSED      = 'reversed';

    const QUEUED        = 'queued';
    const CANCELLED     = 'cancelled';

    /**
     * Used only to expose publicly.
     * It's used in place of created/initiated.
     */
    const PROCESSING = 'processing';

    public static $internalToPublicStatusMapping = [
        self::CREATED   => self::PROCESSING,
        self::INITIATED => self::PROCESSING,
        self::PROCESSED => self::PROCESSED,
        self::REVERSED  => self::REVERSED,
        self::QUEUED    => self::QUEUED,
        self::CANCELLED => self::CANCELLED,
    ];

    /**
     * These statuses have corresponding timestamps column in payout
     *
     * @var array
     */
    public static $timestampedStatuses = [
        self::PROCESSED,
        self::REVERSED,
        self::QUEUED,
        self::CANCELLED,
    ];

    public static function getPublicStatusFromInternalStatus($internalStatus)
    {
        return static::$internalToPublicStatusMapping[$internalStatus] ?? $internalStatus;
    }

    public static function getInternalStatusFromPublicStatus($publicStatus)
    {
        $flippedMap = [];

        foreach (self::$internalToPublicStatusMapping as $internalStatus => $externalStatus)
        {
            $flippedMap[$externalStatus][] = $internalStatus;
        }

        return $flippedMap[$publicStatus] ?? [$publicStatus];
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
}
