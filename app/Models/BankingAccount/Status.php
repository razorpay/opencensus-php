<?php

namespace RZP\Models\BankingAccount;

use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const CREATED           = 'created';       // Application Received
    const PICKED            = 'picked';        // Razorpay Processing
    const INITIATED         = 'initiated';     // Sent to Bank
    const PROCESSING        = 'processing';    // Bank Processing
    const PROCESSED         = 'processed';     // CA Opened
    const CANCELLED         = 'cancelled';     // Merchant Cancelled
    const ACTIVATED         = 'activated';     // CA Activated
    const UNSERVICEABLE     = 'unserviceable'; // Temp Unserviceable
    const REJECTED          = 'rejected';      // Bank Rejected

    //
    // Account details can be saved only if the status
    // of banking account is in below array
    //
    public static $allowedStatusForDetails = [self::PROCESSED];

    protected static $initialStatuses = [
        Status::CREATED,
        Status::UNSERVICEABLE,
    ];

    public static $activatedStatuses = [
        self::ACTIVATED,
    ];

    protected static $statuses = [
        self::CREATED,
        self::INITIATED,
        self::PROCESSING,
        // when user cancels his application to open CA.
        self::CANCELLED,
        self::PROCESSED,
        // when the user's pincode does not belong
        // to the region of pincodes serviceable
        self::UNSERVICEABLE,
        // when the user's application to open CA
        //is rejected by RBL for some reason
        self::REJECTED,
    ];

    /**
     * @var array
     * This contains a status map that keeps mapping of a status
     * to next possible statuses. This is to ensure the status
     * change on Banking Account Entity happens in an order.
     *
     * Processed should be accessible from any previous state
     * so that we are be able to consume the webhook payload details
     * and update the status to CA opened, agnostic to the status on admin dashboard.
     */
    protected static $fromToStatusMap = [
        self::CREATED => [
            self::PICKED,
            self::CANCELLED,
            self::PROCESSED,
        ],
        self::PICKED => [
            self::INITIATED,
            self::UNSERVICEABLE,
            self::CANCELLED,
            self::PROCESSED,
        ],
        self::INITIATED => [
            self::PROCESSING,
            self::PROCESSED,
            self::CANCELLED,
            self::REJECTED,
        ],
        self::PROCESSING => [
            self::PROCESSED,
            self::CANCELLED,
            self::REJECTED,
        ],
        self::PROCESSED => [
            self::ACTIVATED,
        ],
        self::UNSERVICEABLE => [
            self::PICKED,
            self::PROCESSED,
        ],

        self::ACTIVATED => [],
        self::CANCELLED => [
            // Sometimes Sales team is able to revive leads who
            // had earlier cancelled their request. This is to
            // restart the process.
            self::CREATED,
            self::PROCESSED,
        ],
        self::REJECTED  => [
            self::PROCESSED,
        ],
    ];

    public static $internallyEditStatuses = [
        self::CREATED,
        self::PICKED,
        self::INITIATED,
        self::PROCESSED,
        self::PROCESSING,
        self::UNSERVICEABLE,
        self::REJECTED,
        self::CANCELLED,
        self::ACTIVATED,
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

    public static function getActivatedStatuses(): array
    {
        return self::$activatedStatuses;
    }
}
