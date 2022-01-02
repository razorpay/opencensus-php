<?php


namespace RZP\Models\Merchant\FreshdeskTicket\Processor;

use RZP\Notifications\Support\Events;
use RZP\Notifications\Support\Handler;
use RZP\Models\Merchant\FreshdeskTicket\Entity;
use RZP\Models\Merchant\FreshdeskTicket\Core;
use RZP\Models\Merchant\FreshdeskTicket\Constants;
use RZP\Models\Merchant\FreshdeskTicket\TicketStatus;
use RZP\Models\Merchant\FreshdeskTicket\Type;

class TicketCreateCallback extends Base
{
    protected function getRedactedInput($input)
    {
        return $input;
    }

    public function processEvent($input)
    {
        if (empty($input[Constants::STATUS]) === false)
        {
            $input[Constants::STATUS] = TicketStatus::getDatabaseStatusMappingForStatusString($input[Constants::STATUS]);
        }

        $ticket = (new Core)->create($input, $input[Entity::MERCHANT_ID], true);

        $this->setCfMerchantIdDashboardForTicket($ticket);

        if((array_key_exists(Entity::CREATED_BY, $input) === true) and ($input[Entity::CREATED_BY] == Constants::AGENT))
        {
            (new Handler(['ticket'=> $ticket]))->sendForEvent(Events::AGENT_TICKET_CREATED);
        }

        return [Constants::SUCCESS => true];
    }
}
