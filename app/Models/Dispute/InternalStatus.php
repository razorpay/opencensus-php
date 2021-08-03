<?php

namespace RZP\Models\Dispute;


use RZP\Exception\BadRequestValidationFailureException;

class InternalStatus
{
    const OPEN         = 'open';
    const CONTESTED    = 'contested';
    const REPRESENTED  = 'represented';
    const LOST         = 'lost';
    const WON          = 'won';
    const CLOSED       = 'closed';


    protected static $closedStatuses = [
        self::WON,
        self::LOST,
        self::CLOSED,
    ];

    protected static $openStatuses = [
        self::OPEN,
        self::CONTESTED,
        self::REPRESENTED,
    ];

    protected static $allowedInternalStatusesForStatus = [
        Status::OPEN         => [self::OPEN],
        Status::UNDER_REVIEW => [self::CONTESTED, self::REPRESENTED],
        Status::LOST         => [self::LOST],
        Status::WON          => [self::WON],
        Status::CLOSED       => [self::CLOSED],
    ];

    protected static $defaultInternalStatusForStatus = [
        Status::OPEN         => self::OPEN,
        Status::UNDER_REVIEW => self::CONTESTED,
        Status::LOST         => self::LOST,
        Status::WON          => self::WON,
        Status::CLOSED       => self::CLOSED,
    ];

    protected static $internalStatusToStatusMap = [
        self::OPEN        => Status::OPEN,
        self::CONTESTED   => Status::UNDER_REVIEW,
        self::REPRESENTED => Status::UNDER_REVIEW,
        self::LOST        => Status::LOST,
        self::WON         => Status::WON,
        self::CLOSED      => Status::CLOSED,
    ];

    protected static $internalStatusValidNextInternalStatuses = [
        self::OPEN        => [self::CONTESTED, self::LOST],
        self::CONTESTED   => [self::OPEN, self::LOST, self::CLOSED, self::REPRESENTED],
        self::REPRESENTED => [self::WON, self::LOST, self::CLOSED],
        self::LOST        => [],
        self::WON         => [],
        self::CLOSED      => [],
    ];

    /**
     * @throws BadRequestValidationFailureException
     */
    public static function getInternalStatusCorrespondingToStatus(string $status): string
    {
        Status::validate($status);

        return self::$defaultInternalStatusForStatus[$status];
    }

    public static function getStatusCorrespondingToInternalStatus($internalStatus)
    {
        self::validate($internalStatus);

        return self::$internalStatusToStatusMap[$internalStatus];
    }

    public static function validate(string $internalStatus)
    {
        if (self::exists($internalStatus) === true)
        {
            return;
        }

        $message = "${internalStatus} is not a valid value for 'internal_status'";

        throw new BadRequestValidationFailureException($message);
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    public static function validateNextInternalStatusForCurrentInternalStatus($currentInternalStatus, $nextInternalStatus)
    {
        self::validate($currentInternalStatus);

        self::validate($nextInternalStatus);

        if ($currentInternalStatus === $nextInternalStatus)
        {
            return;
        }

        if (in_array($nextInternalStatus, self::$internalStatusValidNextInternalStatuses[$currentInternalStatus]) === true)
        {
            return;
        }

        $message = "'internal_status' of dispute cannot move from '${currentInternalStatus}' to '${nextInternalStatus}'";

        throw new BadRequestValidationFailureException($message);
    }

    public static function exists(string $status): bool
    {
        return defined(get_class() . '::' . strtoupper($status));
    }
}
