<?php

namespace RZP\Models\Terminal;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Terminal;

class Core extends Base\Core
{
    public function create($input, $merchant)
    {
        $input['merchant_id'] = $merchant->getKey();

        $terminal = (new Terminal\Entity)->build($input);

        $this->validateExistingTerminal($terminal);

        $this->repo->saveOrFail($terminal);

        return $terminal;
    }

    public function edit($terminal, $input)
    {
        $this->validateExistingTerminal($terminal);

        if ((isset($input['restore'])) and
            ($input['restore'] === '1'))
        {
            $terminal->restoreOrFail();
        }
        else
        {
            $this->trace->info(
                TraceCode::TERMINAL_EDIT,
                [
                    'terminal_id' => $terminal->getId(),
                    'fields' => array_keys($input),
                ]);

            $terminal->edit($input);

            $this->repo->saveOrFail($terminal);
        }

        return $terminal;
    }

    public function validateExistingTerminal($terminal)
    {
        $params = array(
            Terminal\Entity::MERCHANT_ID => $terminal->getMerchantId());

        $existingTerminals = $this->repo->terminal->fetch($params);

        //
        // Checks that existing terminals don't
        // have same gateway field as the new one
        //
        $terminal->getValidator()->validateExistingTerminalsCount($existingTerminals);

        $this->validateExistingTerminalGatewayMerchantId($terminal);
    }

    protected function validateExistingTerminalGatewayMerchantId($terminal)
    {
        // Check no record with same 'gateway_merchant_id' exists
        $params = array(
            Terminal\Entity::GATEWAY_MERCHANT_ID => $terminal->getGatewayMerchantId());

        $existingTerminals = $this->repo->terminal->fetch($params);

        if ($existingTerminals->count() === 1)
        {
            $existingTerminal = $existingTerminals[0];

            if ($existingTerminal->getGatewayMerchantId() === $terminal->getGatewayMerchantId())
            {
                return;
            }
        }

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

        $input['merchant_id'] = $merchant->getKey();

        $terminal = (new Terminal\Entity)->build($input);

        $this->validateExistingTerminal($terminal);

        $terminal->setConnection(Mode::TEST);

        $this->repo->saveOrFail($terminal);

        return $terminal;
   }
}
