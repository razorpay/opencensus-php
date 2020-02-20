<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class MerchantFreshdeskTicket extends Base
{
    public function create(array $attributes = array())
    {
        $ticket = $this->createEntityInTestAndLive('merchant_freshdesk_tickets', $attributes);

        return $ticket;
    }
}
