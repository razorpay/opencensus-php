<?php

namespace Models\Terminal;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Terminal;

class Core extends Base\Core
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
        $params = array(
            Terminal\Entity::MERCHANT_ID => $terminal->getMerchantId());

        $this->repo = new Terminal\Repository();

        $existingTerminals = $this->repo->getByParams($params);

        $terminal->getValidator()->validateExistingTerminalsCount($existingTerminals);

        // Check no record with same 'gateway_merchant_id' exists
        $params = array(
            Terminal\Entity::GATEWAY_MERCHANT_ID => $terminal->getGatewayMerchantId());

        $existingTerminals = $this->repo->getByParams($params);

        if ($existingTerminals->count() !== 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GATEWAY_MERCHANT_ID_EXISTS,
                Terminal\Entity::GATEWAY_MERCHANT_ID);
        }
    }

    public function createTerminalsInTestMode($merchant)
    {
        $this->createRandomTerminalInTestMode($merchant, 'hdfc');

        $this->createRandomTerminalInTestMode($merchant, 'atom');
    }

    public function createRandomTerminalInTestMode($merchant, $gateway)
    {
        $input = [
            'merchant_id' => $merchant->getId(),
            'gateway'     => $gateway,
            'card'        => '1',
            'gateway_merchant_id' => str_random(),
            'gateway_terminal_id' => str_random(),
            'gateway_terminal_password' => str_random()
        ];

        $this->create($input, $merchant);
    }
}