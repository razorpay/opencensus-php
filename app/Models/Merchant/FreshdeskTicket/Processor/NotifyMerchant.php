<?php


namespace RZP\Models\Merchant\FreshdeskTicket\Processor;

use RZP\Models\Merchant\FreshdeskTicket;
use RZP\Models\Merchant\FreshdeskTicket\Constants;

class NotifyMerchant extends Base
{
    protected function getRedactedInput($input)
    {
        return $input;
    }

    public function processEvent($input)
    {
        $ticket = $this->repo->merchant_freshdesk_tickets->fetch([
            FreshdeskTicket\Entity::TICKET_ID => $input[FreshdeskTicket\Entity::TICKET_ID],
        ])->filter(function ($ticket) use ($input)
        {
            return $ticket->getFdInstance() == $input[Constants::FD_INSTANCE];
        })->firstOrFail();

        $this->notifyMerchantIfApplicable($ticket, $input[Constants::NOTIFICATION_EVENT], $this->extractRequesterItem($input));

        return [Constants::SUCCESS => true];
    }
}
