<?php


namespace RZP\Models\Merchant\FreshdeskTicket\Processor;

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

        return [Constants::SUCCESS => true];
    }

    protected function setCfMerchantIdDashboardForTicket(Entity $ticket)
    {
        $url = self::FRESHDESK_INSTANCES[Type::SUPPORT_DASHBOARD][$ticket->getFdInstance()];

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
