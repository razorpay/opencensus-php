<?php

namespace RZP\Gateway\Cybersource\Mock;

use App;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use RZP\Gateway\Cybersource;

class Gateway extends Cybersource\Gateway
{
    use Base\Mock\GatewayTrait;

    protected function postRequest($request)
    {
        // Redirect the request internally
        $serverResponse = $this->callGatewayRequestInternally($request);

        return $serverResponse;
    }

    protected function callGatewayRequestInternally($request)
    {
        $server = $this->getServer();

        $server->setInput($request['content']);

        $input = $request['content'];

        $action = null;

        switch(true)
        {
            case isset($input['payerAuthEnrollService']):
                $action = 'enroll';
                break;

            case isset($input['payerAuthValidateService']):
                $action = 'auth_validate';
                break;

            case isset($input['ccAuthService']):
                $action = 'authorize';
                break;

            case isset($input['ccCaptureService']):
            case isset($input['ccCreditService']):
                break;

            default:
                throw new Exception\LogicException('Unrecognized request type');
        }

        if ($action === null)
        {
            $action = $this->action;
        }

        $action = camel_case($action);

        $response = $server->$action($input);

        return $response;
    }

    protected function getServer()
    {
        $app = App::getFacadeRoot();

        return $app['gateway']->server($this->gateway);
    }
}