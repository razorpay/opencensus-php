<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Exception\AssertionException;
use RZP\Models\Base\PublicCollection;

class Repository extends Base\Repository
{
    protected $entity = 'merchant_freshdesk_tickets';

    /**
     * @param $ticket
     * @throws AssertionException
     */
    public function createTicketEntity($ticket)
    {
        assertTrue($ticket->exists === false);

        $ticket->saveOrFail();
    }

    public function fetch(array $params, string $mid = null, string $connection = null): PublicCollection
    {
        //keep ticket Id string always
        if ((empty($params[Constants::TICKET_ID]) === false))
        {
            if (is_array($params[Constants::TICKET_ID]) === false)
            {
                $params[Constants::TICKET_ID] = strval($params[Constants::TICKET_ID]);
            }
            else
            {
                $params[Constants::TICKET_ID] = array_map('strval', $params[Constants::TICKET_ID]);
            }
        }

        $this->trace->info(TraceCode::FRESHDESK_FETCH_PARAMS, [
            'params' => $params
        ]);

        return parent::fetch($params, $mid);
    }
}
