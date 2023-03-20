<?php

namespace RZP\Models\Payout;

use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    //
    // The following three constants are required by the
    // FTA module to update the source status. Things will
    // get wrecked if these are removed.
    //
    const PROCESSED                          = 'processed';
    const INITIATED                          = Attempt\Status::INITIATED;
    const REVERSED                           = 'reversed';
    const FAILED                             = 'failed';

    const CREATED                            = 'created';
    const PENDING                            = 'pending';
    const REJECTED                           = 'rejected';
    const QUEUED                             = 'queued';
    const CANCELLED                          = 'cancelled';
    const BATCH_SUBMITTED                    = 'batch_submitted';
    const SCHEDULED                          = 'scheduled';
    const CREATE_REQUEST_SUBMITTED           = 'create_request_submitted';
    const LEDGER_RESPONSE_AWAITED            = 'ledger_response_awaited';
    const ON_HOLD                            = 'on_hold';
    const PENDING_ON_CONFIRMATION            = 'pending_on_confirmation';
    const PENDING_ON_OTP                     = 'pending_on_otp';

    /**
     * Used only to expose publicly.
     * It's used in place of created/initiated.
     */
    const PROCESSING   = 'processing';

    public static $finalStates = [
        self::PROCESSED,
        self::REVERSED,
        self::REJECTED,
        self::CANCELLED,
        self::FAILED
    ];

    public static $failureStatus = [
        self::REVERSED,
        self::FAILED,
    ];

    public static $moneyTransferredStates = [
        self::PROCESSED,
        self::REVERSED,
    ];

    public static $internalToPublicStatusMap = [
        self::PENDING                      => self::PENDING,
        self::CREATED                      => self::PROCESSING,
        self::INITIATED                    => self::PROCESSING,
        self::PROCESSED                    => self::PROCESSED,
        self::REVERSED                     => self::REVERSED,
        self::REJECTED                     => self::REJECTED,
        self::QUEUED                       => self::QUEUED,
        self::CANCELLED                    => self::CANCELLED,
        self::FAILED                       => self::FAILED,
        self::BATCH_SUBMITTED              => self::PROCESSING,
        self::CREATE_REQUEST_SUBMITTED     => self::PROCESSING,
        self::LEDGER_RESPONSE_AWAITED      => self::PROCESSING,
        self::ON_HOLD                      => self::QUEUED,
        self::PENDING_ON_OTP               => self::PENDING,
    ];

    /**
     * Map to deemed_success status initiated and failed status
     * to get details while creating error object in payout response
     *
     * @var array
     */
    public static $statusMappingToErrorFailureStatuses = [
        self::INITIATED =>[
            self::PENDING,
            self::CREATED,
            self::INITIATED,
            self::QUEUED,
            self::BATCH_SUBMITTED,
            self::CREATE_REQUEST_SUBMITTED,
            self::SCHEDULED,
            self::PENDING_ON_OTP  // TODO: check if this is needed
        ],
        self::FAILED => [
            self::REVERSED,
            self::REJECTED,
            self::CANCELLED,
            self::FAILED
        ]
    ];

    /**
     * This contains a status map that keeps mapping of a status
     * to next possible statuses. This is to ensure the status
     * change on Payout Entity happens in an order.
     *
     * TODO: Complete this and use it before setting status in payout entity.
     * @var array
     */
    protected static $fromToStatusMap = [
        null => [
            self::CREATED,
            self::PENDING,
            self::QUEUED,
            self::BATCH_SUBMITTED,
            self::SCHEDULED,
            self::CREATE_REQUEST_SUBMITTED,
            self::ON_HOLD,
        ],
        self::QUEUED => [
            self::CREATED,
            self::FAILED,
            self::CANCELLED,
        ],
        self::PENDING => [
            self::REJECTED,
            self::QUEUED,
            self::CREATED,
            self::SCHEDULED,
            self::BATCH_SUBMITTED,
            self::CREATE_REQUEST_SUBMITTED,
            self::ON_HOLD,
            self::PENDING_ON_OTP,
        ],
        self::ON_HOLD => [
            self::QUEUED,
            self::FAILED,
            self::CANCELLED,
            self::CREATED,
        ],
        self::CREATED => [
            self::INITIATED,
            self::FAILED,
        ],
        self::INITIATED => [
            // FTA tries to update to initiated multiple times.
            self::INITIATED,
            self::REVERSED,
            self::FAILED,
            self::PROCESSED,
        ],
        self::PROCESSED => [
            self::REVERSED,
        ],
        self::REVERSED => [
            // this is empty because it's the final status
            // need this to check state transitions
        ],
        self::FAILED => [
            // this is empty because it's the final status
        ],
        self::BATCH_SUBMITTED => [
            self::CREATED,
            self::FAILED,
            self::ON_HOLD
        ],
        self::SCHEDULED => [
            self::CREATED,
            self::CANCELLED,
            self::FAILED,
            self::BATCH_SUBMITTED,
            self::ON_HOLD
        ],
        self::CREATE_REQUEST_SUBMITTED => [
            self::CREATED,
            self::FAILED,
            self::INITIATED,
            self::QUEUED,
            self::ON_HOLD,
        ],
        self::PENDING_ON_OTP => [
            self::PENDING,
            self::CREATED,
        ],
    ];

    /**
     * These statuses have corresponding timestamps column in payout (_at)
     *
     * @var array
     */
    public static $timestampedStatuses = [
        // We have a special logic for `created` in entity status setter
        self::CREATED,
        self::PENDING,
        self::PROCESSED,
        self::REVERSED,
        self::FAILED,
        self::REJECTED,
        self::QUEUED,
        self::CANCELLED,
        self::BATCH_SUBMITTED,
        self::CREATE_REQUEST_SUBMITTED,
        self::INITIATED,
        self::ON_HOLD,
    ];

    /**
     * These statuses have corresponding timestamps column in payout (_on)
     *
     * @var array
     */
    public static $timestampedStatuses2 = [
        self::SCHEDULED
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
        self::BATCH_SUBMITTED,
        self::FAILED,
        self::SCHEDULED,
        self::ON_HOLD,
        self::REJECTED,
        self::CREATE_REQUEST_SUBMITTED,
        self::LEDGER_RESPONSE_AWAITED,
        self::PENDING_ON_OTP,
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
                Attempt\Status::CREATED   => self::CREATED,
                Attempt\Status::INITIATED => self::INITIATED,
                Attempt\Status::REVERSED  => self::REVERSED,
                Attempt\Status::FAILED    => self::REVERSED,
                Attempt\Status::PROCESSED => self::PROCESSED,
            ],
            Channel::AXIS2   => [],
            Channel::ICICI   => [],
        ],
        // Eg: Virtual Accounts
        AccountType::SHARED => [
            Entity::DEFAULT => [
                Attempt\Status::CREATED   => self::CREATED,
                Attempt\Status::INITIATED => self::INITIATED,
                Attempt\Status::REVERSED  => self::REVERSED,
                Attempt\Status::FAILED    => self::REVERSED,
                Attempt\Status::PROCESSED => self::PROCESSED,
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
                Attempt\Status::CREATED   => self::CREATED,
                Attempt\Status::INITIATED => self::INITIATED,
                Attempt\Status::REVERSED  => self::REVERSED,
                Attempt\Status::FAILED    => self::FAILED,
                Attempt\Status::PROCESSED => self::PROCESSED,
            ],
            Channel::ICICI => [
                Attempt\Status::CREATED   => self::CREATED,
                Attempt\Status::INITIATED => self::INITIATED,
                Attempt\Status::REVERSED  => self::REVERSED,
                Attempt\Status::FAILED    => self::FAILED,
                Attempt\Status::PROCESSED => self::PROCESSED,
            ],
            Channel::AXIS => [
                Attempt\Status::CREATED   => self::CREATED,
                Attempt\Status::INITIATED => self::INITIATED,
                Attempt\Status::REVERSED  => self::REVERSED,
                Attempt\Status::FAILED    => self::FAILED,
                Attempt\Status::PROCESSED => self::PROCESSED,
            ],
            Channel::YESBANK => [
                Attempt\Status::CREATED   => self::CREATED,
                Attempt\Status::INITIATED => self::INITIATED,
                Attempt\Status::REVERSED  => self::REVERSED,
                Attempt\Status::FAILED    => self::FAILED,
                Attempt\Status::PROCESSED => self::PROCESSED,
            ],
        ],
    ];
    public static $payoutStatusToLedgerEventMap = [
        self::CREATED   => Ledger\Payout::PAYOUT_INITIATED,
        self::PROCESSED => Ledger\Payout::PAYOUT_PROCESSED,
        self::REVERSED  => Ledger\Payout::PAYOUT_REVERSED,
        self::FAILED    => Ledger\Payout::PAYOUT_FAILED,
    ];

    public static $payoutStatusToLedgerEventMapForInterAccount = [
        self::CREATED   => Ledger\Payout::INTER_ACCOUNT_PAYOUT_INITIATED,
        self::PROCESSED => Ledger\Payout::INTER_ACCOUNT_PAYOUT_PROCESSED,
        self::REVERSED  => Ledger\Payout::INTER_ACCOUNT_PAYOUT_REVERSED,
        self::FAILED    => Ledger\Payout::INTER_ACCOUNT_PAYOUT_FAILED,
    ];

    public static $payoutStatusToLedgerEventMapForVaToVaPayouts = [
        self::CREATED  => Ledger\Payout::VA_TO_VA_PAYOUT_INITIATED,
        self::REVERSED => Ledger\Payout::VA_TO_VA_PAYOUT_FAILED
    ];

    /**
     * @param string $payoutStatus
     * @param string $purpose
     * @return mixed|string
     * Return ledger event mapped to a payout status. If no such mapping is found, return
     * DEFAULT_EVENT. This is then handled in isDefaultEvent() in Transaction/Processor/Ledger
     */

    public static function getLedgerEventFromPayoutStatus(string $payoutStatus, string $purpose)
    {
        if ($purpose === Purpose::INTER_ACCOUNT_PAYOUT)
        {
            return self::$payoutStatusToLedgerEventMapForInterAccount[$payoutStatus] ?? Ledger\Base::DEFAULT_EVENT;
        }
        return self::$payoutStatusToLedgerEventMap[$payoutStatus] ?? Ledger\Base::DEFAULT_EVENT;
    }

    /**
     * @param Entity $payout
     * @param string|null $payoutStatus
     * @return mixed|string
     * Return ledger event mapped to a payout.
     */
    public static function getLedgerEventForPayout(Entity $payout, string $payoutStatus = null)
    {
        if ($payoutStatus === null){
            $payoutStatus = $payout->getStatus();
        }

        if (($payout->isVaToVaPayout() === true) or ($payout->isSubAccountPayout() === true))
        {
            // for VA to VA payouts or sub account payouts
            return self::$payoutStatusToLedgerEventMapForVaToVaPayouts[$payoutStatus] ?? Ledger\Base::DEFAULT_EVENT;
        }

        if ($payout->isInterAccountPayout() === true)
        {
            return self::$payoutStatusToLedgerEventMapForInterAccount[$payoutStatus] ?? Ledger\Base::DEFAULT_EVENT;
        }

        return self::$payoutStatusToLedgerEventMap[$payoutStatus] ?? Ledger\Base::DEFAULT_EVENT;
    }

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

        return self::$ftaToPayoutStatusMap[$accountType][$channel][$ftaStatus] ??
               self::$ftaToPayoutStatusMap[Entity::DEFAULT][Entity::DEFAULT][$ftaStatus];
    }

    /**
     * Validate status change based on state machine
     *
     * @param string $currentStatus
     * @param string|null $previousStatus
     * @throws BadRequestValidationFailureException
     */
    public static function validateStatusUpdate(string $currentStatus, string $previousStatus = null)
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

    public static function isFailureState($status): bool
    {
        return in_array($status,
                        self::$failureStatus,
                        true);
    }

    public static function isFinalState($status): bool
    {
        return in_array($status,
                        self::$finalStates,
                        true);
    }

    public static function isMoneyTransferredState($status): bool
    {
        return in_array($status,
            self::$moneyTransferredStates,
            true) === true;
    }

    public static function getErrorStatus($status)
    {
        $defaultStatus = self::FAILED;

        foreach (self::$statusMappingToErrorFailureStatuses as $statusMappingToErrorFailureStatus => $statuses)
        {
            if (in_array($status, $statuses))
            {
                return $statusMappingToErrorFailureStatus;
            }
        }

        return $defaultStatus;
    }
}
