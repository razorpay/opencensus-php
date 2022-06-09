<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

final class Metric
{
    // Counters
    const FRESHDESK                       = 'freshdesk';
    const GET_AGENT_CREATED_TICKET_FAILED = 'get_agent_created_ticket_failed';
    const FRESHDESK_RESPONSE_TIME         = 'freshdesk_response_time.histogram';
}
