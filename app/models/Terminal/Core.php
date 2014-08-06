<?php

namespace Models\Terminal;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Terminal;

class Core
{
    protected $repo = null;

    public function create($input, $merchant)
    {
        $input['merchant_id'] = $merchant->getKey();
        $terminal = (new Terminal\Entity)->build($input);

        $this->validateNoExistingTerminal($terminal);

        $this->repo->saveOrFail($terminal);

        return $terminal;
    }

    protected function validateNoExistingTerminal($terminal)
    {
        // Check no other terminal id exists for the merchant right now
        $params = array(
            Terminal\Entity::MERCHANT_ID => $terminal->getMerchantId());

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
            Terminal\Entity::GATEWAY_MERCHANT_ID => $terminal->getGatewayMerchantId());

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