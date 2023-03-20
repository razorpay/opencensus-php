<?php


namespace RZP\Models\CreditTransfer;


class Status
{
    // max length in db column is 14
    const CREATED    = 'created';
    const FAILED     = 'failed';
    const PROCESSED  = 'processed';
    const PROCESSING = 'processing';

    /**
     * These statuses have corresponding timestamps column in credit_transfer (_at)
     * created_at, updated_at are handled in the base class
     *
     * @var array
     */
    public static $timestampedStatuses = [
        self::PROCESSED,
        self::FAILED,
    ];

    public static $internalToPublicStatusMap = [
        self::CREATED   => self::PROCESSING,
        self::FAILED    => self::FAILED,
        self::PROCESSED => self::PROCESSED,
    ];

    public static function getPublicStatusFromInternalStatus($internalStatus): string
    {
        return static::$internalToPublicStatusMap[$internalStatus] ?? $internalStatus;
    }
}
