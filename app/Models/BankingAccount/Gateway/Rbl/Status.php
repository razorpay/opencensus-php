<?php

namespace RZP\Models\BankingAccount\Gateway\Rbl;

use RZP\Models\BankingAccount;
use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    // Original
    const OPEN           = 'open';
    const DRAFT          = 'draft';
    const REWORK         = 'rework';
    const VERIFIED       = 'verified';
    const DISCREPANCY    = 'discrepancy';
    const CLOSED         = 'closed';
    const CANCELLED      = 'cancelled';
    const HOLD           = 'hold';

    // Newer statuses
    // https://docs.google.com/spreadsheets/d/15LSHSf4P6AJpOrZx7jqXNfH47mkrP1znk1Gbj7HtEAs/edit#gid=0
    const MERCHANT_NOT_AVAILABLE = 'merchant_not_available';
    const MERCHANT_PREPARING_DOCS = 'merchant_preparing_docs';
    const YET_TO_PICKUP_DOCS = 'yet_to_pickup_docs';
    const PICKED_UP_DOCS = 'picked_up_docs';
    const DISCREPANCY_IN_DOCS = 'discrepancy_in_docs';
    const ACCOUNT_OPENED = 'account_opened';
    const MERCHANT_PREPARING_API_DOCS = 'merchant_preparing_api_docs';
    const DISCREPANCY_IN_API_DOCS = 'discrepancy_in_api_docs';
    const API_ONBOARDING_IN_PROGRESS = 'api_onboarding_in_progress';
    const ACTIVATED = 'activated';
    const DROP_OFF = 'drop_off';
    const REJECTED = 'rejected';

    const MERCHANT_NOT_AVAILABLE_EXTERNAL = 'Merchant is not available';
    const MERCHANT_PREPARING_DOCS_EXTERNAL = 'Merchant is preparing docs';
    const YET_TO_PICKUP_DOCS_EXTERNAL = 'Bank yet to pick up docs';
    const PICKED_UP_DOCS_EXTERNAL = 'Bank has picked up docs';
    const DISCREPANCY_IN_DOCS_EXTERNAL = 'Discrepancy in docs';
    const ACCOUNT_OPENED_EXTERNAL = 'Bank has opened CA';
    const MERCHANT_PREPARING_API_DOCS_EXTERNAL = 'Merchant is preparing API docs';
    const DISCREPANCY_IN_API_DOCS_EXTERNAL = 'Discrepancy in API docs';
    const API_ONBOARDING_IN_PROGRESS_EXTERNAL = 'API Onboarding in progress';
    const ACTIVATED_EXTERNAL = 'CA Activated';
    const DROP_OFF_EXTERNAL = 'Drop- Off';
    const REJECTED_EXTERNAL = 'Bank Rejected due to Compliance';

    //
    // RBL webhook wants the final status of processing from our end.
    // If the webhook is processed properly we send a Success status to them
    // else Failure Status is sent to them
    //
    const SUCCESS           = 'Success';
    const FAILURE           = 'Failure';

    // Other constants
    const ALL = '*';

    protected static $bankToInternalStatusSubStatusMap = [
        BankingAccount\Status::INITIATED      => [
            BankingAccount\Status::NONE => [],
            BankingAccount\Status::MERCHANT_NOT_AVAILABLE => [
                self::MERCHANT_NOT_AVAILABLE
            ],
            BankingAccount\Status::MERCHANT_PREPARING_DOCS => [
                self::MERCHANT_PREPARING_DOCS
            ],
            BankingAccount\Status::BANK_TO_PICKUP_DOCS => [
                self::YET_TO_PICKUP_DOCS
            ],
            BankingAccount\Status::BANK_PICKED_UP_DOCS => [
                self::PICKED_UP_DOCS
            ]
        ],
        BankingAccount\Status::PROCESSING     => [
            BankingAccount\Status::DISCREPANCY_IN_DOCS => [
                self::DISCREPANCY_IN_DOCS
            ],
            BankingAccount\Status::BANK_OPENED_ACCOUNT => [
                self::ACCOUNT_OPENED
            ],
        ],
        BankingAccount\Status::PROCESSED      => [
            self::ALL => [
                self::ACTIVATED
            ],
            BankingAccount\Status::API_ONBOARDING_PENDING => [
                self::CLOSED
            ],
            BankingAccount\Status::API_ONBOARDING_INITIATED => [
                self::CLOSED
            ],
            BankingAccount\Status::API_ONBOARDING_IN_PROGRESS => [
                self::API_ONBOARDING_IN_PROGRESS
            ],
            BankingAccount\Status::MERCHANT_PREPARING_DOCS => [
                self::MERCHANT_PREPARING_API_DOCS
            ],
            BankingAccount\Status::DISCREPANCY_IN_DOCS => [
                self::DISCREPANCY_IN_API_DOCS
            ]
        ],
        BankingAccount\Status::CANCELLED      => [],
        BankingAccount\Status::UNSERVICEABLE  => [],
        BankingAccount\Status::CREATED        => [],
        BankingAccount\Status::ACTIVATED      => [
            null => [
                self::ACTIVATED
            ]
        ],
        BankingAccount\Status::REJECTED       => [
            null => [
                self::REJECTED
            ]
        ],
        BankingAccount\Status::ARCHIVED       => [
            null => [
                self::DROP_OFF
            ]
        ]
    ];

    protected static $internalToBankStatusForWebhookMap = [
        BankingAccount\Status::PROCESSED    => self::SUCCESS,
        BankingAccount\Status::CANCELLED    => self::FAILURE
    ];

    protected static $statuses = [
        self::OPEN,
        self::DRAFT,
        self::REWORK,
        self::VERIFIED,
        self::DISCREPANCY,
        self::CLOSED,
        self::CANCELLED,
        self::HOLD,
        self::MERCHANT_NOT_AVAILABLE,
        self::MERCHANT_PREPARING_DOCS,
        self::YET_TO_PICKUP_DOCS,
        self::PICKED_UP_DOCS,
        self::DISCREPANCY_IN_DOCS,
        self::ACCOUNT_OPENED,
        self::MERCHANT_PREPARING_API_DOCS,
        self::DISCREPANCY_IN_API_DOCS,
        self::API_ONBOARDING_IN_PROGRESS,
        self::ACTIVATED,
        self::DROP_OFF,
        self::REJECTED
    ];

    protected static $externalToInternalStatusMap = [
        self::MERCHANT_NOT_AVAILABLE_EXTERNAL => self::MERCHANT_NOT_AVAILABLE,
        self::MERCHANT_PREPARING_DOCS_EXTERNAL => self::MERCHANT_PREPARING_DOCS,
        self::YET_TO_PICKUP_DOCS_EXTERNAL => self::YET_TO_PICKUP_DOCS,
        self::PICKED_UP_DOCS_EXTERNAL => self::PICKED_UP_DOCS,
        self::DISCREPANCY_IN_DOCS_EXTERNAL => self::DISCREPANCY_IN_DOCS,
        self::ACCOUNT_OPENED_EXTERNAL => self::ACCOUNT_OPENED,
        self::MERCHANT_PREPARING_API_DOCS_EXTERNAL => self::MERCHANT_PREPARING_API_DOCS,
        self::DISCREPANCY_IN_API_DOCS_EXTERNAL => self::DISCREPANCY_IN_API_DOCS,
        self::API_ONBOARDING_IN_PROGRESS_EXTERNAL => self::API_ONBOARDING_IN_PROGRESS,
        self::ACTIVATED_EXTERNAL => self::ACTIVATED,
        self::DROP_OFF_EXTERNAL => self::DROP_OFF,
        self::REJECTED_EXTERNAL => self::REJECTED
    ];


    public static function isValid(string $status): bool
    {
        return in_array($status, self::$statuses);
    }

    public static function validate($status)
    {
        if (self::isValid($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid RBL status',
                BankingAccount\Entity::BANK_INTERNAL_STATUS,
                [
                 BankingAccount\Entity::BANK_INTERNAL_STATUS => $status
                ]);
        }
    }

    public static function validateInternalBankStatusMappingToStatus(string $bankStatus = null, string $status = null, string $substatus = null)
    {
        BankingAccount\Status::validate($status);

        self::validate($bankStatus);

        try
        {
            $allowedBankStatusList = self::$bankToInternalStatusSubStatusMap[$status][$substatus];

            if (isset(self::$bankToInternalStatusSubStatusMap[$status][self::ALL]) === true)
            {
                $allowedBankStatusList = array_merge($allowedBankStatusList, self::$bankToInternalStatusSubStatusMap[$status][self::ALL]);
            }
        }
        catch (\ErrorException $e)
        {
            throw new BadRequestValidationFailureException(
                'bank internal status ' . $bankStatus . ' cannot be passed with status= ' . $status . ' and substatus= '. $substatus,
                BankingAccount\Entity::BANK_INTERNAL_STATUS,
                [
                    BankingAccount\Entity::BANK_INTERNAL_STATUS => $bankStatus,
                    BankingAccount\Entity::STATUS               => $status
                ]);
        }

        if (in_array($bankStatus, $allowedBankStatusList, true) === false)
        {
            throw new BadRequestValidationFailureException(
                'bank internal status ' . $bankStatus . ' cannot be passed with status= ' . $status . ' and substatus= '. $substatus,
                BankingAccount\Entity::BANK_INTERNAL_STATUS,
                [
                    BankingAccount\Entity::BANK_INTERNAL_STATUS => $bankStatus,
                    BankingAccount\Entity::STATUS               => $status
                ]);
        }
    }

    public static function getInternalStatusForBankWebhook($status)
    {
        BankingAccount\Status::isValidStatus($status);

        return self::$internalToBankStatusForWebhookMap[$status];
    }

    public static function checkRblToInternalStatusMapping(string $rblStatus, $status, $substatus)
    {
        self::validateInternalBankStatusMappingToStatus($rblStatus, $status, $substatus);
    }

    public static function isValidExternalStatus(string $status)
    {
        return in_array($status, array_keys(self::$externalToInternalStatusMap));
    }

    public static function validateExternalStatus(string $status)
    {
        if (self::isValidExternalStatus($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid Razorpay Bank Internal Status',
                BankingAccount\Entity::BANK_INTERNAL_STATUS,
                [
                    BankingAccount\Entity::BANK_INTERNAL_STATUS => $status
                ]);
        }
    }

    public static function transformFromExternalToInternal(string $status)
    {
        self::validateExternalStatus($status);

        return self::$externalToInternalStatusMap[$status];
    }

    public static function getAll(): array
    {
        return self::$statuses;
    }
}
