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

        $this->validateExistingTerminal($terminal);

        $this->repo->saveOrFail($terminal);

        return $terminal;
    }

    protected function validateExistingTerminal($terminal)
    {
        // Check no other terminal id exists for the merchant right now
        $params = array(
            Terminal\Entity::MERCHANT_ID => $terminal->getMerchantId());

        $this->repo = new Terminal\Repository();

        $existingTerminals = $this->repo->getByParams($params);

        $count = $existingTerminals->count();

        if ($count === 2)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_GATEWAY_TERMINAL_ONLY_TWO_ALLOWED);
        }
        else if ($count === 1)
        {
            if ($terminal->getGateway() === $existingTerminals->first()->getGateway())
            {
                throw new Exception\BadRequestException(
                    null, ErrorCode::BAD_REQUEST_TERMINAL_EXISTS_FOR_GATEWAY);
            }
        }
        else if ($count > 2)
        {
            throw new Exception\LogicException('Terminal count should not exceed 2');
        }

        // Check no record with same 'gateway_merchant_id' exists
        $params = array(
            Terminal\Entity::GATEWAY_MERCHANT_ID => $terminal->getGatewayMerchantId());

        $existingTerminals = $this->repo->getByParams($params);

        if ($existingTerminals->count() !== 0)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_GATEWAY_MERCHANT_ID_EXISTS,
                Terminal\Entity::GATEWAY_MERCHANT_ID);
        }
    }
}