<?php


namespace RZP\Models\BankingAccount\Activation\Notification\Subscriber;

use Mail;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Activation\Notification\Event;
use RZP\Models\BankingAccount\Activation\Notification\Constants;

class OpsSubscriber extends Base
{
    protected $name = 'ops';

    protected function shouldNotify(array $bankingAccount, Event $event)
    {
        switch ($event->getName())
        {
            case Event::STATUS_CHANGE:
                return in_array($event->getProperties()[Constants::NEW_STATUS],
                    [
                        BankingAccount\Status::PROCESSED,
                        BankingAccount\Status::API_ONBOARDING,
                        BankingAccount\Status::ACCOUNT_ACTIVATION,
                    ]);
            case Event::ASSIGNEE_CHANGE:
                return ($bankingAccount[BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][BankingAccount\Entity::ASSIGNEE_TEAM] ===  'ops');

            case Event::ACCOUNT_OPENING_WEBHOOK_DATA_AMBIGUITY:
                return true;
        }

        return true;
    }

    protected function getNameAndEmails(array $bankingAccount, Event $event)
    {
        $reviewer = $bankingAccount[BankingAccount\Entity::REVIEWERS][0] ?? null;

        $emails = [
            [
                'name' => 'X-Onboarding',
                'email' => 'x-onboarding@razorpay.com'
            ]
        ];

        if (($event->getName() === Event::STATUS_CHANGE &&
            $event->getProperties()[Constants::NEW_STATUS] === BankingAccount\Status::API_ONBOARDING) ||
            $event->getName() === Event::ACCOUNT_OPENING_WEBHOOK_DATA_AMBIGUITY
        )
        {
            $emails[] = [
                'name' => 'X-CA-Onboarding',
                'email'=> 'x-caonboarding@razorpay.com'
            ];
        }

        if (empty($reviewer) === false)
        {
            $emails[] = [
                'name' => $reviewer['name'],
                'email'=> $reviewer['email'],
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
