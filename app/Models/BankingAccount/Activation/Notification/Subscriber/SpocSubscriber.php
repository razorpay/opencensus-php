<?php


namespace RZP\Models\BankingAccount\Activation\Notification\Subscriber;

use Mail;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Activation\Notification\Event;
use RZP\Models\BankingAccount\Activation\Notification\Constants;

class SpocSubscriber extends Base
{
    protected $name = 'spoc';

    protected function shouldNotify(BankingAccount\Entity $bankingAccount, Event $event)
    {
        $doesNotHaveSpoc = empty($bankingAccount->spocs->first());

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
                return ($bankingAccount->bankingAccountActivationDetails->getAssigneeTeam() ===  'sales');
        }

        return true;
    }

    protected function getNameAndEmails(BankingAccount\Entity $bankingAccount, Event $event)
    {
        $spoc = $bankingAccount->spocs->first();

        return [
            [
                'name' => $spoc['name'],
                'email'=> $spoc['email']
            ]
        ];
    }

    public function update(BankingAccount\Entity $bankingAccount, Event $event)
    {
        if ($this->shouldNotify($bankingAccount, $event) === true)
        {
            $this->notifyViaEmail($bankingAccount, $event);
        }
    }
}
