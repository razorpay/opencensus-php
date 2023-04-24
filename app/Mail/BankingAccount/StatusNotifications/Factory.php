<?php

namespace RZP\Mail\BankingAccount\StatusNotifications;

use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Status;
use RZP\Exception\BadRequestValidationFailureException;

class Factory
{
    public static function getMailer(array $bankingAccount)
    {
        $status = $bankingAccount[Entity::STATUS];

        switch($status)
        {
            case Status::CANCELLED:
                return new Cancelled($bankingAccount);

            case Status::CREATED:
                return new Created($bankingAccount);

            case Status::PROCESSED:
                return new Processed($bankingAccount);

            // CA Opened (Processed) → API Onboarding
            // Could fail if done before webhook is received
            // because Account Number won’t be present
            case Status::API_ONBOARDING:
                return new Processed($bankingAccount);

            case Status::PROCESSING:
                return new Processing($bankingAccount);

            // According to new status Bank Processing → Account Opening
            case Status::ACCOUNT_OPENING:
                return new Processing($bankingAccount);

            case Status::UNSERVICEABLE:
                return new Unserviceable($bankingAccount);

            case Status::ACTIVATED:
                return new Activated($bankingAccount);

            case Status::REJECTED:
                return new Rejected($bankingAccount);

            case Status::ARCHIVED:
                $substatus = $bankingAccount[Entity::SUB_STATUS];

                switch ($substatus) {
                    case Status::NEGATIVE_PROFILE_SVR_ISSUE:
                        return new Rejected($bankingAccount);

                    case Status::NOT_SERVICEABLE:
                        return new Unserviceable($bankingAccount);

                    case Status::CANCELLED:
                        return new Cancelled($bankingAccount);
                }


            default:
                throw new BadRequestValidationFailureException("Invalid Status, cannot send email, status: $status");
        }
    }
}
