<?php


namespace RZP\Models\BankingAccount\Activation\Notification\Subscriber;

use Mail;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Activation\Notification\Event;
use RZP\Models\BankingAccount\Activation\Notification\Constants;

class OpsSubscriber extends Base
{
    protected $name = 'ops';

    protected function shouldNotify(BankingAccount\Entity $bankingAccount, Event $event)
    {
        switch ($event->getName())
        {
            case Event::STATUS_CHANGE:
                return in_array($event->getProperties()[Constants::NEW_STATUS],
                    [
                        BankingAccount\Status::PROCESSED,
                    ]);
            case Event::ASSIGNEE_CHANGE:
                return ($bankingAccount->bankingAccountActivationDetails->getAssigneeTeam() ===  'ops');
        }

        return true;
    }

    protected function getNameAndEmails(BankingAccount\Entity $bankingAccount)
    {
        $reviewer = $bankingAccount->reviewers->first();

        $emails = [
            [
                'name' => 'X-Onboarding',
                'email' => 'x-onboarding@razorpay.com'
            ]
        ];

        if (empty($reviewer) === false)
        {
            $emails[] = [
                'name' => $reviewer['name'],
                'email'=> $reviewer['email']
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
