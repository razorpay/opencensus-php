<?php

namespace RZP\Models\BankingAccount\Activation\Notification\Subscriber;

use Mail;
use RZP\Models\User;
use RZP\Models\BankingAccount;
use RZP\Models\Merchant\MerchantUser;
use RZP\Models\BankingAccount\Activation\Notification\Event;

class MidOfficeManagerSubscriber extends Base
{
    protected $name = 'midOfficeManager';

    protected function shouldNotify(array $bankingAccount, Event $event): bool
    {
        // No need for logic at this point
        // we only this listener for the event - BANK_PARTNER_ASSIGNED
        // We send the emails whenever Bank Partner is assigned

        return true;
    }

    protected function getNameAndEmails(array $bankingAccount, Event $event): array
    {
        $partnerMerchantId = $event->getProperties()[BankingAccount\BankLms\Constants::PARTNER_MERCHANT_ID];

        $merchantUsers = (new MerchantUser\Repository)->findByRolesAndMerchantId([User\BankingRole::BANK_MID_OFFICE_MANAGER], $partnerMerchantId);

        $emails = [];

        if (empty($merchantUsers) === false)
        {
            foreach ($merchantUsers as $merchantUser)
            {
                $user = $this->repo->user->findByPublicId($merchantUser->user_id);

                $emails[] = [
                    'name'  => $user->getName(),
                    'email' => $user->getEmail()
                ];
            }
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
