<?php


namespace RZP\Models\BankingAccount\Activation\Notification\Subscriber;

use Mail;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Activation\Notification\Event;
use RZP\Models\BankingAccount\Activation\Notification\Constants;

class SpocSubscriber extends Base
{
    protected $name = 'spoc';

    protected function shouldNotify(array $bankingAccount, Event $event)
    {
        $doesNotHaveSpoc = empty($bankingAccount[BankingAccount\Entity::SPOCS]);

        if ($doesNotHaveSpoc === true)
        {
            return false;
        }

        switch ($event->getName())
        {
            case Event::STATUS_CHANGE:
                return in_array($event->getProperties()[Constants::NEW_STATUS],
                    [
                        BankingAccount\Status::PROCESSED,
                        BankingAccount\Status::API_ONBOARDING,
                        BankingAccount\Status::ACCOUNT_ACTIVATION,
                        BankingAccount\Status::ACTIVATED,
                        BankingAccount\Status::REJECTED,
                        BankingAccount\Status::ARCHIVED,
                    ]);
            case Event::ASSIGNEE_CHANGE:
                return ($bankingAccount[BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][BankingAccount\Entity::ASSIGNEE_TEAM] ===  'sales');
        }

        return true;
    }

    protected function getNameAndEmails(array $bankingAccount, Event $event)
    {
        $spoc = array_get($bankingAccount,BankingAccount\Entity::SPOCS . '.' . '0',[]);

        return [
            [
                'name' => $spoc['name'],
                'email'=> $spoc['email']
            ]
        ];
    }

    public function update(array $bankingAccount, Event $event)
    {
        if ($this->shouldNotify($bankingAccount, $event) === true)
        {
            $this->notifyViaEmail($bankingAccount, $event);
        }
    }
}
