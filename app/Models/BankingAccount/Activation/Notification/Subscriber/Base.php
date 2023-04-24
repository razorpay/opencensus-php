<?php


namespace RZP\Models\BankingAccount\Activation\Notification\Subscriber;

use Mail;

use RZP\Trace\TraceCode;
use RZP\Models\Base\Core;
use RZP\Mail\Base\Mailable;
use RZP\Models\BankingAccount;
use RZP\Mail\BankingAccount\Activation\BaseV2;
use RZP\Mail\BankingAccount\Activation\Base as BaseEmail;
use RZP\Models\BankingAccount\Activation\Notification\Event;

abstract class Base extends Core
{
    /** @var string $name */
    protected $name;

    abstract public function update(array $bankingAccount, Event $event);

    //  --------------------- Notify Via Email -------------------------//
    protected function getMailableForEvent(array $bankingAccount, Event $event)
    {
        $mailableClass = 'RZP\\Mail\\BankingAccount\\Activation\\' . studly_case($event->getName());

        if($event->getName() === Event::STATUS_CHANGE)
        {
            /** @var $mailable BaseV2 */
            $mailable = new $mailableClass($bankingAccount, $event->toArray());
        } else {
            /** @var $mailable BaseEmail */
            $mailable = new $mailableClass($bankingAccount['id'], $event->toArray());
        }

        $mailable->subject(sprintf("[%s] %s", $event->getType(), $mailable->subject));

        return $mailable;
    }

    protected function getNameAndEmails(array $bankingAccount, Event $event)
    {
        return [];
    }

    protected function modifyMailable(Mailable & $mailable, array $bankingAccount, Event $event)
    {
        $nameAndEmails = $this->getNameAndEmails($bankingAccount, $event);

        foreach($nameAndEmails as $nameAndEmail)
        {
            $mailable->to($nameAndEmail['email'], $nameAndEmail['name']);
        }
    }

    protected function notifyViaEmail(array $bankingAccount, Event $event)
    {
        $mailable = $this->getMailableForEvent($bankingAccount, $event);

        $this->modifyMailable($mailable, $bankingAccount, $event);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_EVENT_SUBSCRIBER_NOTIFY,
            [
                'event' => $event->toArray(),
                'banking_account_id' => $bankingAccount[BankingAccount\Entity::ID],
                'subscriber' => $this->name,
                'mailto' => $mailable->to
            ]);

        Mail::queue($mailable);
    }

    public function getSubscriberName() {
        return $this->name;
    }
}
