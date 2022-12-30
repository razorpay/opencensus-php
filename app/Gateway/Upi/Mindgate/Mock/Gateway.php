<?php

namespace RZP\Gateway\Upi\Mindgate\Mock;

use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Mindgate;
use RZP\Gateway\Upi\Mindgate\Action;
use RZP\Exception\GatewayErrorException;
use RZP\Gateway\Upi\Base\Mock as UpiMock;

class Gateway extends Mindgate\Gateway
{
    use Base\Mock\GatewayTrait;
    use UpiMock\GatewayTrait;

    public function getIntentUrl(array $input)
    {
        // Since the call does not go to server,
        // We need to mock error at gateway itself
        switch ($input['payment']['description'])
        {
            case 'Gateway Failure':
                throw new GatewayErrorException(ErrorCode::GATEWAY_ERROR_FATAL_ERROR);

            default:
                return parent::getIntentUrl($input);
        }
    }

    public function decryptGatewayResponse(string $input)
    {
        $result = $this->parseGatewayResponse($input, Action::CALLBACK);

        return $result;
    }
}
