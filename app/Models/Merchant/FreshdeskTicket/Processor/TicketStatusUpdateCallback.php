<?php

namespace RZP\Models\Merchant\FreshdeskTicket\Processor;

use RZP\Models\Merchant\FreshdeskTicket\Entity;
use RZP\Models\Merchant\FreshdeskTicket\Constants;
use RZP\Models\Merchant\FreshdeskTicket\TicketStatus;
use RZP\Models\Merchant\FreshdeskTicket\Service as FreshdeskService;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FreshdeskConstants;


class TicketStatusUpdateCallback extends Base
{

    protected function getRedactedInput($input)
    {
        return $input;
    }

    public function processEvent($input)
    {
        (new FreshdeskService())->updateStatusForTicket($input[FreshdeskConstants::TYPE], $input[FreshdeskConstants::TICKET_ID],
                                                        $input[Entity::MERCHANT_ID],
                                                        $input[Entity::TICKET_DETAILS][Constants::FD_INSTANCE],
                                                        TicketStatus::getDatabaseStatusMappingForStatusString($input[FreshdeskConstants::STATUS]
                                                        )
        );

        return [FreshdeskConstants::SUCCESS => true];
    }
}