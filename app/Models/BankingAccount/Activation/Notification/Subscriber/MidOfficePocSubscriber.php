<?php

namespace RZP\Models\BankingAccount\Activation\Notification\Subscriber;

use Mail;
use RZP\Models\User;
use RZP\Models\BankingAccount;
use RZP\Models\Merchant\MerchantUser;
use RZP\Models\BankingAccount\Activation\Notification\Event;

class MidOfficePocSubscriber extends Base
{
    protected $name = 'midOfficePoc';

    protected function shouldNotify(BankingAccount\Entity $bankingAccount, Event $event): bool
    {
        // no need for logic at this point
        // we only this listener for the event - BANK_PARTNER_POC_ASSIGNED
        // We send the emails whenever Bank Partner assigns a POC to the lead

        return true;
    }

    protected function getNameAndEmails(BankingAccount\Entity $bankingAccount, Event $event): array
    {
        $bankPocUser = $bankingAccount->bankingAccountActivationDetails->getBankPOCUser();

        $emails = [];

        if (empty($bankPocUser) === false)
        {
            $emails[] = [
                'name'  => $bankPocUser->getName(),
                'email' => $bankPocUser->getEmail()
            ];
        }

        return $emails;
    }

    public function update(BankingAccount\Entity $bankingAccount, Event $event)
    {
        if ($this->shouldNotify($bankingAccount, $event) === true)
        {
            $this->notifyViaEmail($bankingAccount, $event);
        }
    }
}
