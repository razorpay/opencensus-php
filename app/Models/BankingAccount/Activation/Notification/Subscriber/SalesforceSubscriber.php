<?php


namespace RZP\Models\BankingAccount\Activation\Notification\Subscriber;

use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Activation\Notification\Event;
use RZP\Models\BankingAccount\Activation\Notification\Constants;


class SalesforceSubscriber extends Base
{
    protected $name = 'salesforce';

    protected function shouldNotify(Event $event)
    {
        switch ($event->getName())
        {
            case Event::STATUS_CHANGE:
                return true;
            case Event::SUBSTATUS_CHANGE:
                return true;
        }

        return false;
    }

    protected function sendRBLUpdateRequest(BankingAccount\Entity $bankingAccount)
    {

        $status = $bankingAccount->getStatus();
        $subStatus = BankingAccount\Status::sanitizeStatus($bankingAccount->getSubStatus());

        $payload = [
            'merchant_id'      => $bankingAccount->getMerchantId(),
            'ca_id'            => 'bacc_'.$bankingAccount->getId(),
            'ca_type'          => Constants::RBL,
            'ca_status'        => $status,
            'ca_substatus'     => $subStatus,
        ];

        $this->app->salesforce->sendLeadStatusUpdate($payload, Constants::RBL);
    }

    public function update(BankingAccount\Entity $bankingAccount, Event $event)
    {
        if ($this->shouldNotify($event) === true)
        {
            $this->sendRBLUpdateRequest($bankingAccount);
        }
    }
}
