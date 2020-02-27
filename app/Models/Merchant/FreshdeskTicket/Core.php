<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Base\Core
{
    public function create(array $input, string $merchantId): Entity
    {
        $params = ['type' => $input[Entity::TYPE]];

        $tickets = $this->repo->merchant_freshdesk_tickets->fetch($params, $merchantId);

        if ($tickets->count() !== 0)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::FRESHDESK_TICKET_ALREADY_EXISTS,
                null,
                [
                    'merchant_id' => $merchantId,
                    'type' => $input[Entity::TYPE]
                ]
            );
        }
        
        $ticketEntity = new Entity;

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $ticketEntity->merchant()->associate($merchant);

        $input[Entity::MERCHANT_ID] = $merchantId;

        $ticketEntity->build($input);

        $this->repo->saveOrFail($ticketEntity);

        return $ticketEntity;
    }
}
