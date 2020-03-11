<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Error\P2p\ErrorCode;
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

                $request->setPoll($token->get(RegisterToken\Entity::CREATED_AT) + 10);

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
                'contact'      => $this->input->get('sdk')->get('contact', $this->scenario->getContact()),
                'gateway_data' => [
                    'gateway_device_id' => str_random(16),
                ],
            ],
        ]);
    }

    public function initiateGetToken(Response $response)
    {
        $request = new Request();

        $request->setSdk('npci');
        $request->setContent([
            'token'     => 'I_AM_REFRESTED_TOKEN',
            'payload'   => '<payload>And_i_am_refreshed_payload</payload>'
        ]);
        $request->setAction('saveToken');
        $request->setCallback([
            'verify_code'   => str_random(16),
        ]);

        $response->setRequest($request);
    }

    public function getToken(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::DE201]))
        {
            return;
        }

        $response->setData([
            Entity::DEVICE_TOKEN => [
                Entity::ID            => $this->getContextDeviceToken()->get(Entity::ID),
                'gateway_data' => [
                    'token'     => 'I_AM_REFRESTED_TOKEN',
                    'payload'   => '<payload>And_i_am_refreshed_payload</payload>'
                ],
            ]
        ]);

        return $response;
    }

    public function deregister(Response $response)
    {
        $response->setData([
            'success' => true,
        ]);

        return $response;
    }
}
