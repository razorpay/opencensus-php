<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

use RZP\Models\Base;
use RZP\Exception\AssertionException;

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
}
