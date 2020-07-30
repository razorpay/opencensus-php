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


    // External Statuses as interpreted by Product
    const APPLICATION_RECEIVED = 'Application Received';
    const RAZORPAY_PROCESSING  = 'Razorpay Processing';
    const SENT_TO_BANK         = 'Sent to Bank';
    const BANK_PROCESSING      = 'Bank Processing';
    const CA_OPENED            = 'CA Opened';
    const MERCHANT_CANCELLED   = 'Merchant Cancelled';
    const CA_ACTIVATED         = 'CA Activated';
    const TEMP_UNSERVICEABLE   = 'Temp Unserviceable';
    const BANK_REJECTED        = 'Bank Rejected';

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
        // When Razorpay starts processing the application
        self::PICKED,
        // When application is sent to the bank
        self::INITIATED,
        // When bank starts processing the application
        self::PROCESSING,
        // when user cancels his application to open CA.
        self::CANCELLED,
        // when Bank has processed, and opened the CA.
        // The webhook that we received from the bank on
        // CA opening sets this state.
        self::PROCESSED,
        // when the user's pincode does not belong
        // to the region of pincodes serviceable
        self::UNSERVICEABLE,
        // when the user's application to open CA
        //is rejected by RBL for some reason
        self::REJECTED,
        // API banking has been tested. CA is activated
        // and ready to use.
        self::ACTIVATED
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

    /**
     * @var array
     * This contains a status map that keeps mapping of an external status
     * to internal status. External signifies the status as understood by the Product.
     * Internal signifies the status as understood by BE.
     *
     * This is used in Admin batch upload when Ops/Sales/Bank teams use a
     * csv to upload change in statuses in bulk.
     *
     * CA Activated and CA Opened are intentionally left out of this list
     * to prevent change to these states, which should only be allowed via webhook/
     * manual activation operation via admin dashboard.
     */
    public static $externalToInternalStatusMap = [
        self::APPLICATION_RECEIVED => self::CREATED,
        self::RAZORPAY_PROCESSING  => self::PICKED,
        self::SENT_TO_BANK         => self::INITIATED,
        self::BANK_PROCESSING      => self::PROCESSING,
        self::MERCHANT_CANCELLED   => self::CANCELLED,
        self::TEMP_UNSERVICEABLE   => self::UNSERVICEABLE,
        self::BANK_REJECTED        => self::REJECTED,
    ];

    public static function isValidStatus(string $status = null)
    {
        return in_array($status, self::$statuses);
    }

    public static function isValidExternalStatus(string $status)
    {
        return in_array($status, array_keys(self::$externalToInternalStatusMap));
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

    public static function validateExternalStatus(string $status)
    {
        if (self::isValidExternalStatus($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid Razorpay Banking External status',
                Entity::STATUS,
                [
                    Entity::STATUS => $status
                ]);
        }
    }

    public static function transformFromExternalToInternal(string $status)
    {
        self::validateExternalStatus($status);

        return self::$externalToInternalStatusMap[$status];
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
