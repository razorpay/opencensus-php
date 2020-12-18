<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Error\P2p\ErrorCode;
use RZP\Gateway\P2p\Upi\Npci;
use RZP\Exception\LogicException;
use RZP\Models\P2p\Device\Entity;
use RZP\Gateway\P2p\Base\Request;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Upi\Mock\Scenario;
use RZP\Models\P2p\Device\RegisterToken;

class DeviceGateway extends Gateway implements Contracts\DeviceGateway
{
    const SMS_VERIFICATION_RECEIVER = '917624918474';

    public function initiateVerification(Response $response)
    {
        $request = new Request();

        $contact    = $this->scenario->getContact();
        $token      = $this->input->get(Entity::REGISTER_TOKEN)->get(RegisterToken\Entity::TOKEN);

        $request->setDestination(self::SMS_VERIFICATION_RECEIVER);
        $request->setContent(secure_url("v1/upi/callback/p2p_upi_sharp?c=$contact&t=$token"));
        $request->setAction('send');

        $response->setRequest($request);
    }

    public function verification(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::DE101]))
        {
            return;
        }

        $token = $this->input->get(Entity::REGISTER_TOKEN);

        if ($this->scenario->is(Scenario::DE102))
        {
            $status = $token->get(RegisterToken\Entity::STATUS);

            if (in_array($status, [RegisterToken\Status::CREATED, RegisterToken\Status::PENDING]))
            {
                if ($token->get(RegisterToken\Entity::CREATED_AT) < ($this->getCurrentTimestamp() - 45))
                {
                    $response->setError(ErrorCode::BAD_REQUEST_SMS_FAILED, 'SMS verificarion period expired');

                    return $response;
                }

                $request = new Request();
                $request->setRedirect($token->get(RegisterToken\Entity::CREATED_AT) + 10);
                $response->setRequest($request);

                return;
            }
            else if ($status === RegisterToken\Status::COMPLETED)
            {
                $response->setData([
                    'token'         => $token->get(RegisterToken\Entity::TOKEN),
                    'device'        => [
                        'id'        => $token->get(RegisterToken\Entity::DEVICE_ID),
                    ]
                ]);

                return;
            }
            else
            {
                throw new LogicException('Status has to be either:created, pending or completed');
            }
        }

        $response->setData([
            'token'    => $this->input->get('register_token')->get('token'),
            'device_data' => [
                'contact'      => $this->scenario->getContact(),
                'gateway_data' => [
                    'device_id' => 'GDID' . $this->scenario->getContact(),
                    // force set token to be empty
                    'cl_token'  => null,
                    'cl_expiry' => null,
                ],
            ],
        ]);
    }

    public function initiateGetToken(Response $response)
    {
        if ($this->checkForPreActions($response, [Scenario::DE201], [Scenario::DE202], [Scenario::DE203]))
        {
            return;
        }

        // Else return a request to simply post
        $request = new Request();
        $request->setRedirect($this->getContextDeviceToken()->get(Entity::CREATED_AT));
        $response->setRequest($request);
    }

    public function getToken(Response $response)
    {
        // The first callback is received for action getChallenge
        if ($this->inputSdk()->has(Npci\ClAction::GET_CHALLENGE))
        {
            if ($this->handleFailureScenarios($response, [Scenario::DE304, Scenario::DE305]))
            {
                return;
            }
            // No need to check id  the register app is needed we can always force the token update
            $token = $this->retrieveTokenWithChallenge();

            $this->cl()->setData([
                // The case where token is fetched but not set yet, thus considered invalid
                Npci\ClInput::CL_TOKEN      => $token,
                // Now we have a token but we will not rely on that
                Npci\ClInput::CL_EXPIRY     => null,
            ]);

            $request = $this->cl()->registerAppRequest();

            $response->setRequest($request);

            return;
        }

        // The second callback is received for action getChallenge
        if ($this->inputSdk()->has(Npci\ClAction::REGISTER_APP))
        {
            if ($this->handleFailureScenarios($response, [Scenario::DE306]))
            {
                return;
            }

            $registerApp = filter_var($this->inputSdk()->get(Npci\ClAction::REGISTER_APP), FILTER_VALIDATE_BOOLEAN);

            if (empty($registerApp) === true)
            {
                // It should be GATEWAY_ERROR_NPCI_REGISTRATION_FAILED
                $response->setError(ErrorCode::GATEWAY_ERROR_TOKEN_REGISTRATION_FAILED,
                                    'NPCI CL registerApp method failed');

                return;
            }

            // Registration token succeed, we can not update the token and expiry

            // There must have been a better way to handle token
            $token      = $this->input->get(Fields::CALLBACK)->get(Npci\ClOutput::TOKEN);
            $expiry     = $this->input->get(Fields::CALLBACK)->get(Npci\ClOutput::EXPIRY);

            $response->setData([
                Entity::DEVICE_TOKEN => [
                    Entity::ID            => $this->getContextDeviceToken()->get(Entity::ID),
                    Entity::GATEWAY_DATA  => [
                        Npci\ClInput::CL_TOKEN  => $token,
                        Npci\ClInput::CL_EXPIRY => $expiry,
                    ],
                ]
            ]);

            return;
        }

        // Its a callback from initiateGetToken
        if ($this->checkForPreActions($response, [Scenario::DE301], [Scenario::DE302], [Scenario::DE303]))
        {
            return;
        }

        // No pre action are needed
        $response->setData([
            Entity::DEVICE_TOKEN => [
                Entity::ID            => $this->getContextDeviceToken()->get(Entity::ID),
                'gateway_data'        => [],
            ]
        ]);

        return $response;
    }

    public function deregister(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::DE401]))
        {
            return;
        }

        $response->setData([
            'success' => true,
        ]);

        return $response;
    }

    /**
     *  Token is actually fetched from NPCI end itself, NPCI does the validation here we will do
     */
    private function retrieveTokenWithChallenge()
    {
        $challenge = $this->inputSdk()->get(Npci\ClAction::GET_CHALLENGE);
        $type      = $this->input->get(Fields::CALLBACK)->get(Npci\ClOutput::TYPE);

        $allowedType = [Npci\ClOutput::INITIAL, Npci\ClOutput::ROTATE];
        if (in_array($type, $allowedType, true) === false)
        {
            throw new Exception\LogicException(
                sprintf('Invalid type %s in callback', $type));
        }

        // Just to verify all the details only for sharp
        $parts = explode('|', base64_decode($challenge));
        $actualType = ($parts[1] ?? null);
        if ($type !== $actualType)
        {
            throw new Exception\LogicException(
                sprintf('Type %s must match to %s', $actualType, $type));
        }

        $deviceId = $this->getContextDevice()->get(Entity::UUID);
        $actualDeviceId = ($parts[2] ?? null);
        if ($deviceId !== $actualDeviceId)
        {
            throw new Exception\LogicException(
                sprintf('DeviceId %s must match to %s', $actualDeviceId, $deviceId));
        }

        // Now the token is the first part of challenge
        return $parts[0];
    }

    // These are the pre action needed for registration and rotation
    private function checkForPreActions(
        Response $response,
        array $failure,
        array $registration,
        array $rotation): bool
    {
        if ($this->handleFailureScenarios($response, $failure))
        {
            return true;
        }

        // Now validate cl was ever registered
        if ($this->scenario->in($registration) or $this->cl()->shouldRegisterToken() === true)
        {
            $response->setRequest($this->cl()->registerRequest());

            return true;
        }

        if ($this->scenario->in($rotation) or $this->cl()->shouldRotateToken() === true)
        {
            $response->setRequest($this->cl()->rotateRequest());

            return true;
        }

        return false;
    }
}
