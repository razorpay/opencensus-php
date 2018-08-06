<?php

namespace RZP\Gateway\Base;

trait GatewayTerminalTrait
{
    public function merchantOnboard(array $input)
    {
        return $this->getGatewayTerminalObject(Terminal::MERCHANT_ONBOARD)
                    ->merchantOnboard($input['gateway_input'],
                                      $input['merchant_details']);
    }

    protected function getGatewayTerminalObject(string $action)
    {
        $terminalClass = $this->getNamespace() . '\Terminal';

        $object = (new $terminalClass)->setTerminalParams($action);

        return $object;
    }
}
