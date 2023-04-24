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

    protected function shouldNotify(array $bankingAccount, Event $event): bool
    {
        // no need for logic at this point
        // we only this listener for the event - BANK_PARTNER_POC_ASSIGNED
        // We send the emails whenever Bank Partner assigns a POC to the lead

        return true;
    }

    protected function getNameAndEmails(array $bankingAccount, Event $event): array
    {
        $bankPocUserId = $bankingAccount[BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][BankingAccount\Activation\Detail\Entity::BANK_POC_USER_ID];

        $bankPocUser = $this->repo->user->find($bankPocUserId);

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

    public function update(array $bankingAccount, Event $event)
    {
        if ($this->shouldNotify($bankingAccount, $event) === true)
        {
            $this->notifyViaEmail($bankingAccount, $event);
        }
    }
}
