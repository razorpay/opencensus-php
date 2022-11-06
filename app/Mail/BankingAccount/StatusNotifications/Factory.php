<?php

namespace RZP\Mail\BankingAccount\StatusNotifications;

use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Status;
use RZP\Exception\BadRequestValidationFailureException;

class Factory
{
    public static function getMailer(Entity $bankingAccount)
    {
        $status = $bankingAccount->getStatus();

        $bankingAccountId = $bankingAccount->getId();

        switch($status)
        {
            case Status::CANCELLED:
                return new Cancelled($bankingAccountId);

            case Status::CREATED:
                return new Created($bankingAccountId);

            case Status::PROCESSED:
                return new Processed($bankingAccountId);

            // CA Opened (Processed) → API Onboarding
            // Could fail if done before webhook is received
            // because Account Number won’t be present
            case Status::API_ONBOARDING:
                return new Processed($bankingAccountId);
    
            case Status::PROCESSING:
                return new Processing($bankingAccountId);

            // According to new status Bank Processing → Account Opening
            case Status::ACCOUNT_OPENING:
                return new Processing($bankingAccountId);

            case Status::UNSERVICEABLE:
                return new Unserviceable($bankingAccountId);

            case Status::ACTIVATED:
                return new Activated($bankingAccountId);

            case Status::REJECTED:
                return new Rejected($bankingAccountId);

            default:
                throw new BadRequestValidationFailureException("Invalid Status, cannot send email, status: $status");
        }
    }
}
