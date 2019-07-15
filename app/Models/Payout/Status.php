<?php

namespace RZP\Models\Payout;

use RZP\Models\Settlement\Channel;
use RZP\Models\Batch\Helpers\Payout;
use RZP\Models\FundTransfer\Attempt;
use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    //
    // The following three constants are required by the
    // FTA module to update the source status. Things will
    // get wrecked if these are removed.
    //
    const PROCESSED     = 'processed';
    const INITIATED     = Attempt\Status::INITIATED;
    const FAILED        = 'reversed';

    const CREATED       = 'created';
    const PENDING       = 'pending';
    const REJECTED      = 'rejected';
    const REVERSED      = 'reversed';
    const QUEUED        = 'queued';
    const CANCELLED     = 'cancelled';

    /**
     * Used only to expose publicly.
     * It's used in place of created/initiated.
     */
    const PROCESSING = 'processing';

    public static $internalToPublicStatusMap = [
        self::PENDING   => self::PENDING,
        self::CREATED   => self::PROCESSING,
        self::INITIATED => self::PROCESSING,
        self::PROCESSED => self::PROCESSED,
        self::REVERSED  => self::REVERSED,
        self::REJECTED  => self::REJECTED,
        self::QUEUED    => self::QUEUED,
        self::CANCELLED => self::CANCELLED,
    ];

    /**
     * These statuses have corresponding timestamps column in payout
     *
     * @var array
     */
    public static $timestampedStatuses = [
        // We have a special logic for `created` in entity status setter
        self::CREATED,
        self::PENDING,
        self::PROCESSED,
        self::REVERSED,
        self::REJECTED,
        self::QUEUED,
        self::CANCELLED,
    ];

    public static $attemptToPayoutStatusUsingChannels = [
        Attempt\Status::CREATED => [
            Channel::YESBANK => Status::CREATED,
            Channel::RBL     => Status::CREATED,
            Channel::AXIS2   => Status::CREATED,
            Channel::ICICI   => Status::CREATED,
        ],
        Attempt\Status::INITIATED => [
            Channel::YESBANK => Status::INITIATED,
            Channel::RBL     => Status::INITIATED,
            Channel::AXIS2   => Status::INITIATED,
            Channel::ICICI   => Status::INITIATED,
        ],
        Attempt\Status::FAILED => [
            Channel::YESBANK => Status::REVERSED,
            Channel::RBL     => Status::FAILED,
            Channel::AXIS2   => Status::REVERSED,
            Channel::ICICI   => Status::REVERSED,
        ],
        Attempt\Status::REVERSED => [
            Channel::YESBANK => Status::REVERSED,
            Channel::RBL     => Status::REVERSED,
            Channel::AXIS2   => Status::REVERSED,
            Channel::ICICI   => Status::REVERSED,
        ],
    ];

    /**
     * Payout statuses that are prior to the created state.
     * Transactions and FTA are not created for these payouts yet.
     *
     * @var array
     */
    public static $preCreateStatuses = [
        self::QUEUED,
        self::PENDING,
    ];

    public static function getPublicStatusFromInternalStatus($internalStatus): string
    {
        return static::$internalToPublicStatusMap[$internalStatus] ?? $internalStatus;
    }

    public static function getInternalStatusFromPublicStatus($publicStatus)
    {
        $flippedMap = [];

        foreach (self::$internalToPublicStatusMap as $internalStatus => $externalStatus)
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
