<?php


namespace RZP\Models\Merchant\FreshdeskTicket\Processor;

use RZP\Models\Merchant\FreshdeskTicket\Entity;
use RZP\Models\Merchant\FreshdeskTicket\Core;
use RZP\Models\Merchant\FreshdeskTicket\Constants;

class TicketCreateCallback extends Base
{
    protected function getRedactedInput($input)
    {
        return $input;
    }

    public function processEvent($input)
    {
        $ticket = (new Core)->create($input, $input[Entity::MERCHANT_ID], true);

        $this->setCfMerchantIdDashboardForTicket($ticket);

        return [Constants::SUCCESS => true];
    }

    protected function setCfMerchantIdDashboardForTicket(Entity $ticket)
    {
        $url = self::FRESKDESK_INSTANCES[$ticket->getFdInstance()];

        $data = [
            Constants::CUSTOM_FIELDS => [
                Constants::CF_MERCHANT_ID_DASHBOARD  => $this->getQueryParamMerchantIdForSearchAPI($ticket->merchant),
            ],
        ];

        $this->app[Constants::FRESHDESK_CLIENT]->updateTicketV2(
            $ticket->getTicketId(),
            $data,
            $url
        );
    }
}
