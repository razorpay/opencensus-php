<?php

namespace RZP\Models\Dispute;


use RZP\Exception\BadRequestValidationFailureException;

class InternalStatus
{
    const OPEN                      = 'open';
    const CONTESTED                 = 'contested';
    const REPRESENTED               = 'represented';
    const LOST_MERCHANT_DEBITED     = 'lost_merchant_debited';
    const LOST_MERCHANT_NOT_DEBITED = 'lost_merchant_not_debited';
    const WON                       = 'won';
    const CLOSED                    = 'closed';

    protected static $internalStatuses = [
        self::OPEN,
        self::CONTESTED,
        self::REPRESENTED,
        self::LOST_MERCHANT_DEBITED,
        self::LOST_MERCHANT_NOT_DEBITED,
        self::WON,
        self::CLOSED,
    ];

    protected static $closedInternalStatuses = [
        self::WON,
        self::LOST_MERCHANT_DEBITED,
        self::LOST_MERCHANT_NOT_DEBITED,
        self::CLOSED,
    ];

    protected static $openInternalStatuses = [
        self::OPEN,
        self::CONTESTED,
        self::REPRESENTED,
    ];

    protected static $allowedInternalStatusesForStatus = [
        Status::OPEN         => [self::OPEN],
        Status::UNDER_REVIEW => [self::CONTESTED, self::REPRESENTED],
        Status::LOST         => [self::LOST_MERCHANT_DEBITED, self::LOST_MERCHANT_NOT_DEBITED,],
        Status::WON          => [self::WON],
        Status::CLOSED       => [self::CLOSED],
    ];

    protected static $defaultInternalStatusForStatus = [
        Status::OPEN         => self::OPEN,
        Status::UNDER_REVIEW => self::CONTESTED,
        Status::LOST         => self::LOST_MERCHANT_NOT_DEBITED,
        Status::WON          => self::WON,
        Status::CLOSED       => self::CLOSED,
    ];

    protected static $internalStatusToStatusMap = [
        self::OPEN                      => Status::OPEN,
        self::CONTESTED                 => Status::UNDER_REVIEW,
        self::REPRESENTED               => Status::UNDER_REVIEW,
        self::LOST_MERCHANT_NOT_DEBITED => Status::LOST,
        self::LOST_MERCHANT_DEBITED     => Status::LOST,
        self::WON                       => Status::WON,
        self::CLOSED                    => Status::CLOSED,
    ];

    protected static $internalStatusValidNextInternalStatuses = [
        self::OPEN                      => [self::CONTESTED, self::LOST_MERCHANT_NOT_DEBITED, self::LOST_MERCHANT_DEBITED],
        self::CONTESTED                 => [self::OPEN, self::LOST_MERCHANT_NOT_DEBITED, self::LOST_MERCHANT_DEBITED, self::CLOSED, self::REPRESENTED],
        self::REPRESENTED               => [self::WON, self::LOST_MERCHANT_NOT_DEBITED, self::LOST_MERCHANT_DEBITED, self::CLOSED],
        self::LOST_MERCHANT_NOT_DEBITED => [],
        self::LOST_MERCHANT_DEBITED     => [],
        self::WON                       => [],
        self::CLOSED                    => [],
    ];

    /**
     * @throws BadRequestValidationFailureException
     */
    public static function getInternalStatusCorrespondingToStatus(string $status, Entity $dispute): string
    {
        Status::validate($status);

        if (($dispute->getDeductAtOnset() === true) and
            ($status === Status::LOST))
        {
            return InternalStatus::LOST_MERCHANT_DEBITED;
        }

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
        return in_array($status, self::$internalStatuses);
    }

    public static function getInternalStatuses()
    {
        return self::$internalStatuses;
    }
}
