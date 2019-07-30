<?php

namespace RZP\Models\Payout;

use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Merchant\Balance\AccountType;
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
    const REVERSED      = 'reversed';
    const FAILED        = 'failed';

    const CREATED       = 'created';
    const PENDING       = 'pending';
    const REJECTED      = 'rejected';
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
        self::FAILED    => self::FAILED,
    ];

    /**
     * This contains a status map that keeps mapping of a status
     * to next possible statuses. This is to ensure the status
     * change on Payout Entity happens in an order.
     *
     * TODO: Complete this and use it before setting status in payout entity.
     *
     * @var array
     */
    protected static $fromToStatusMap = [
        self::CREATED => [
            self::REVERSED,
        ],
        self::INITIATED => [
            self::REVERSED,
        ],
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

    /**
     * Mapping the FTA status to Payout status. This is required because,
     * for some gateways `failed` at FTA means `reversed` for payout.
     *
     * @var array
     */
    public static $ftaToPayoutStatusMap = [
        // Eg: Primary accounts
        Entity::DEFAULT => [
            Entity::DEFAULT => [
                Attempt\Status::CREATED   => Status::CREATED,
                Attempt\Status::INITIATED => Status::INITIATED,
                Attempt\Status::REVERSED  => Status::REVERSED,
                Attempt\Status::FAILED    => Status::REVERSED,
                Attempt\Status::PROCESSED => Status::PROCESSED,
            ],
            Channel::AXIS2   => [],
            Channel::ICICI   => [],
        ],
        // Eg: Virtual Accounts
        AccountType::SHARED => [
            Entity::DEFAULT => [
                Attempt\Status::CREATED   => Status::CREATED,
                Attempt\Status::INITIATED => Status::INITIATED,
                Attempt\Status::REVERSED  => Status::REVERSED,
                Attempt\Status::FAILED    => Status::REVERSED,
                Attempt\Status::PROCESSED => Status::PROCESSED,
            ],
            Channel::YESBANK => [],
        ],
        // Eg: Current Accounts
        AccountType::DIRECT => [
            Entity::DEFAULT => [
                // Don't set default in case of direct because an explicit mapping
                // should be added for each gateway, if not we expect failures here
            ],
            Channel::RBL => [
                Attempt\Status::CREATED   => Status::CREATED,
                Attempt\Status::INITIATED => Status::INITIATED,
                Attempt\Status::REVERSED  => Status::REVERSED,
                Attempt\Status::FAILED    => Status::FAILED,
                Attempt\Status::PROCESSED => Status::PROCESSED,
            ],
        ],
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

    public static function getPayoutStatusFromFtaStatus(Entity $payout, string $ftaStatus)
    {
        $channel     = $payout->getChannel();
        $accountType = optional($payout->balance)->getAccountType();

        return Status::$ftaToPayoutStatusMap[$accountType][$channel][$ftaStatus] ??
               Status::$ftaToPayoutStatusMap[Entity::DEFAULT][Entity::DEFAULT][$ftaStatus];
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
}
