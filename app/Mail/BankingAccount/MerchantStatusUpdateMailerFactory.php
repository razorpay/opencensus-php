<?php

namespace RZP\Mail\BankingAccount;

use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Status;
use RZP\Exception\BadRequestValidationFailureException;

class MerchantStatusUpdateMailerFactory
{
    public static function getMailer(Entity $bankingAccount)
    {
        $status = $bankingAccount->getStatus();

        switch($status)
        {
            case Status::CANCELLED:
                return new Cancelled($bankingAccount);

            case Status::CREATED:
                return new Created($bankingAccount);

            case Status::PROCESSED:
                return new Processed($bankingAccount);

            case Status::PROCESSING:
                return new Processing($bankingAccount);

            case Status::UNSERVICEABLE:
                return new Unservicable($bankingAccount);

            default:
                throw new BadRequestValidationFailureException("Invalid Status, cannot send email, status: $status");
        }
    }
}
