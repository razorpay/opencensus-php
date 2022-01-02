<?php

namespace RZP\Models\Merchant\FreshdeskTicket\Processor;

use RZP\Services\FreshdeskTicketClient;
use RZP\Models\Merchant\FreshdeskTicket\Instance;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FDConstants;

class Constants  extends FDConstants
{
    const GetAgentCreatedTicket = "GetAgentCreatedTicket";

    const SCHEDULER_VS_END_POINT = [
        self::GetAgentCreatedTicket => FreshdeskTicketClient::FILTER_TICKETS
    ];

    const SCHEDULER_VS_FD_INSTANCES = [
        self::GetAgentCreatedTicket => [Instance::RZPIND, Instance::RZPCAP]
    ];
}