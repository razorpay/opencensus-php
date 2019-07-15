<?php

namespace RZP\Models\BankingAccount\Gateway\Rbl;

use RZP\Models\BankingAccount;
use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const OPEN           = 'open';
    const DRAFT          = 'draft';
    const REWORK         = 'rework';
    const VERIFIED       = 'verified';
    const DISCREPANCY    = 'discrepancy';
    const CLOSED         = 'closed';
    const CANCELLED      = 'cancelled';

    // RBL webhook wants the final status of processing from our end.
    // If the webhook is processed properly we send a Success status to them
    // else Failure Status is sent to them
    const SUCCESS           = 'Success';
    const FAILURE           = 'Failure';

    protected static $bankToInternalStatusMap = [
        BankingAccount\Status::PROCESSING     => [
            self::OPEN,
            self::DRAFT,
            self::REWORK,
            self::VERIFIED,
            self::DISCREPANCY
        ],
        BankingAccount\Status::PROCESSED      => [
            self::CLOSED
        ],
        BankingAccount\Status::CANCELLED      => [
            self::CANCELLED
        ],
        BankingAccount\Status::INITIATED      => [],
        BankingAccount\Status::UNSERVICEABLE  => [],
        BankingAccount\Status::CREATED        => [],
        BankingAccount\Status::ACTIVATED      => [],
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
    ];

    public static function isValid(string $status): bool
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
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

    public static function validateInternalBankStatusMappingToStatus(string $bankStatus = null, string $status = null)
    {
        BankingAccount\Status::validate($status);

        self::validate($bankStatus);

        $statusList = self::$bankToInternalStatusMap[$status];

        if (in_array($bankStatus, $statusList, true) === false)
        {
            throw new BadRequestValidationFailureException(
                'bank internal status ' . $bankStatus . ' cannot be passed with status ' . $status,
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

    public static function checkRblToInternalStatusMapping(array $input)
    {
        if ((isset($input[BankingAccount\Entity::BANK_INTERNAL_STATUS]) === true) and
            (isset($input[BankingAccount\Entity::STATUS]) === true))
        {
            $bankInternalStatus = $input[BankingAccount\Entity::BANK_INTERNAL_STATUS];

            $status = $input[BankingAccount\Entity::STATUS];

            self::validateInternalBankStatusMappingToStatus($bankInternalStatus, $status);
        }
    }

    public static function getAll(): array
    {
        return self::$statuses;
    }
}
