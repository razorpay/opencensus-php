<?php

namespace Models\Terminal;

use EE\Exception;
use Models\Terminal;

class Core
{
    protected $repo = null;

    public function create($input, $merchant)
    {
        $terminal = (new Terminal\Entity)->build($input);

        $terminal->merchant()->associate($merchant);

        $this->validateNoExistingTerminal($terminal);

        $this->repo->saveOrFail($terminal);

        return $terminal->toArray();
    }

    protected function validateNoExistingTerminal($terminal)
    {
        // Check no other terminal id exists for the merchant right now
        $params = array(
            Terminal\Entity::MERCHANT_ID => $terminal->getMerchantId());

        sd($params);
        $this->repo = new Terminal\Repository();

        $existingTerminals = $this->repo->getTerminalsByParams($params);

        if ($existingTerminals !== null)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_GATEWAY_TERMINAL_ID_EXISTS_FOR_MERCHANT);
        }

        // Check no record with same 'gateway_merchant_id' exists
        $params = array(
            Terminal\Entity::GATEWAY_MERCHANT_ID => $terminal->getMerchantId());

        $existingTerminals = $this->repo->getTerminalsByParams($params);

        if ($existingTerminals !== null)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_GATEWAY_MERCHANT_ID_EXISTS,
                Terminal\Entity::GATEWAY_MERCHANT_ID);
        }
    }
}