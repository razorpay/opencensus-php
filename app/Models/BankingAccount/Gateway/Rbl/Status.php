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
    const PROCESSING = 'processing';
    const ACCOUNT_OPENED = 'account_opened';
    const MERCHANT_PREPARING_API_DOCS = 'merchant_preparing_api_docs';
    const DISCREPANCY_IN_API_DOCS = 'discrepancy_in_api_docs';
    const API_ONBOARDING_IN_PROGRESS = 'api_onboarding_in_progress';
    const ACTIVATED = 'activated';
    const DROP_OFF = 'drop_off';
    const REJECTED = 'rejected';
    const RAZORPAY_DEPENDENT = 'razorpay_dependent';

    const MERCHANT_NOT_AVAILABLE_EXTERNAL = 'merchant is not available';
    const MERCHANT_PREPARING_DOCS_EXTERNAL = 'merchant is preparing docs';
    const YET_TO_PICKUP_DOCS_EXTERNAL = 'bank yet to pick up docs';
    const PICKED_UP_DOCS_EXTERNAL = 'bank has picked up docs';
    const DISCREPANCY_IN_DOCS_EXTERNAL = 'discrepancy in docs';
    const PROCESSING_EXTERNAL = 'ca in progress';
    const ACCOUNT_OPENED_EXTERNAL = 'bank has opened ca';
    const MERCHANT_PREPARING_API_DOCS_EXTERNAL = 'merchant is preparing api docs';
    const DISCREPANCY_IN_API_DOCS_EXTERNAL = 'discrepancy in api docs';
    const API_ONBOARDING_IN_PROGRESS_EXTERNAL = 'api onboarding in progress';
    const ACTIVATED_EXTERNAL = 'ca activated';
    const DROP_OFF_EXTERNAL = 'drop- off';
    const REJECTED_EXTERNAL = 'bank rejected due to compliance';
    const RAZORPAY_DEPENDENT_EXTERNAL = 'razorpay dependent';


    //
    // RBL webhook wants the final status of processing from our end.
    // If the webhook is processed properly we send a Success status to them
    // else Failure Status is sent to them
    //
    const SUCCESS           = 'Success';
    const FAILURE           = 'Failure';
    const FAIL              = 'Fail';

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
            ],
            BankingAccount\Status::NEEDS_CLARIFICATION_FROM_RZP => [
                self::RAZORPAY_DEPENDENT
            ]
        ],
        BankingAccount\Status::VERIFICATION_CALL => [
            BankingAccount\Status::CUSTOMER_NOT_RESPONDING => [
                self::MERCHANT_NOT_AVAILABLE
            ],
            BankingAccount\Status::FOLLOW_UP_REQUESTED_BY_MERCHANT => [
                self::MERCHANT_PREPARING_DOCS
            ],
            BankingAccount\Status::NEEDS_CLARIFICATION_FROM_RZP => [
                self::RAZORPAY_DEPENDENT
            ],
        ],
        BankingAccount\Status::DOC_COLLECTION => [
            BankingAccount\Status::VISIT_DUE => [
                self::YET_TO_PICKUP_DOCS
            ],
            // BankingAccount\Status::PICKED_UP_DOCS => [
            //     self::PICKED_UP_DOCS
            // ],
            BankingAccount\Status::FOLLOW_UP_API_DOCS_UNAVAILABLE => [
                self::MERCHANT_PREPARING_API_DOCS
            ],
        ],
        BankingAccount\Status::PROCESSING     => [
            null => [
                self::PROCESSING
            ],
            BankingAccount\Status::DISCREPANCY_IN_DOCS => [
                self::DISCREPANCY_IN_DOCS
            ],
            BankingAccount\Status::BANK_OPENED_ACCOUNT => [
                self::ACCOUNT_OPENED
            ],
        ],
        BankingAccount\Status::ACCOUNT_OPENING => [
            self::ALL => [
                self::CLOSED
            ],
            BankingAccount\Status::IR_IN_DISCREPANCY => [
                self::DISCREPANCY_IN_DOCS
            ],
            BankingAccount\Status::IN_REVIEW => [
                self::PROCESSING
            ],
            BankingAccount\Status::CA_OPENED_SUB_STATUS => [
                self::ACCOUNT_OPENED
            ],
        ],
        BankingAccount\Status::API_ONBOARDING => [
            self::ALL => [
                self::CLOSED
            ],
            BankingAccount\Status::IN_REVIEW => [
                self::API_ONBOARDING_IN_PROGRESS
            ],
            BankingAccount\Status::IR_IN_DISCREPANCY => [
                self::DISCREPANCY_IN_API_DOCS
            ],
        ],
        BankingAccount\Status::ACCOUNT_ACTIVATION => [
            self::ALL => [
                self::CLOSED
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
            BankingAccount\Status::MERCHANT_NOT_AVAILABLE => [
                self::MERCHANT_NOT_AVAILABLE
            ],
            BankingAccount\Status::DISCREPANCY_IN_DOCS => [
                self::DISCREPANCY_IN_API_DOCS
            ]
        ],
        BankingAccount\Status::CANCELLED      => [],
        BankingAccount\Status::UNSERVICEABLE  => [],
        BankingAccount\Status::CREATED        => [],
        BankingAccount\Status::ACTIVATED      => [
            self::ALL => [
                self::ACTIVATED
            ]
        ],
        BankingAccount\Status::REJECTED       => [
            null => [
                self::REJECTED
            ]
        ],
        BankingAccount\Status::ARCHIVED       => [
            self::ALL => [
                self::DROP_OFF
            ],
            BankingAccount\Status::NEGATIVE_PROFILE_SVR_ISSUE => [
                self::REJECTED
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
        self::PROCESSING,
        self::ACCOUNT_OPENED,
        self::MERCHANT_PREPARING_API_DOCS,
        self::DISCREPANCY_IN_API_DOCS,
        self::API_ONBOARDING_IN_PROGRESS,
        self::ACTIVATED,
        self::DROP_OFF,
        self::RAZORPAY_DEPENDENT,
        self::REJECTED
    ];

    protected static $externalToInternalStatusMap = [
        self::MERCHANT_NOT_AVAILABLE_EXTERNAL => self::MERCHANT_NOT_AVAILABLE,
        self::MERCHANT_PREPARING_DOCS_EXTERNAL => self::MERCHANT_PREPARING_DOCS,
        self::YET_TO_PICKUP_DOCS_EXTERNAL => self::YET_TO_PICKUP_DOCS,
        self::PICKED_UP_DOCS_EXTERNAL => self::PICKED_UP_DOCS,
        self::DISCREPANCY_IN_DOCS_EXTERNAL => self::DISCREPANCY_IN_DOCS,
        self::PROCESSING_EXTERNAL => self::PROCESSING,
        self::ACCOUNT_OPENED_EXTERNAL => self::ACCOUNT_OPENED,
        self::MERCHANT_PREPARING_API_DOCS_EXTERNAL => self::MERCHANT_PREPARING_API_DOCS,
        self::DISCREPANCY_IN_API_DOCS_EXTERNAL => self::DISCREPANCY_IN_API_DOCS,
        self::API_ONBOARDING_IN_PROGRESS_EXTERNAL => self::API_ONBOARDING_IN_PROGRESS,
        self::RAZORPAY_DEPENDENT_EXTERNAL => self::RAZORPAY_DEPENDENT,
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
            $allowedBankStatusList = [];

            if (isset(self::$bankToInternalStatusSubStatusMap[$status][$substatus]) === true)
            {
                $allowedBankStatusList = self::$bankToInternalStatusSubStatusMap[$status][$substatus];
            }

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

    /**
     * Transforms given status to lower case and trims whitespace.
     *
     * @param string $status
     * @return string
     */
    public static function transformStatusToStandardForm(string $status): string
    {
        $status = trim($status);
        return strtolower($status);
    }

    public static function isValidExternalStatus(string $status)
    {
        $processedStatus = self::transformStatusToStandardForm($status);
        return in_array($processedStatus, array_keys(self::$externalToInternalStatusMap));
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
        $processedStatus = self::transformStatusToStandardForm($status);
        self::validateExternalStatus($processedStatus);

        return self::$externalToInternalStatusMap[$processedStatus];
    }

    public static function getAll(): array
    {
        return self::$statuses;
    }
}
