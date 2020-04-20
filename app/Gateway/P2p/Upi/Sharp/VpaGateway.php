<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Error\P2p\ErrorCode;
use RZP\Models\P2p\Vpa\Entity;
use RZP\Gateway\P2p\Base\Request;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Upi\Mock\Scenario;

class VpaGateway extends Gateway implements Contracts\VpaGateway
{
    public function initiateAdd(Response $response)
    {
        $request = new Request();
        $request->setRedirect($this->getContextDevice()->get(Entity::CREATED_AT));

        $response->setRequest($request);
    }

    public function add(Response $response)
    {
        $failures = [Scenario::VA301, Scenario::VA302, Scenario::VA303, Scenario::VA304, Scenario::VA305];

        if ($this->handleFailureScenarios($response, $failures))
        {
            return;
        }

        $response->setData([
            'vpa'   => [
                'username'      => $this->input->get('username'),
                'handle'        => $this->context->handleCode(),
                'gateway_data'  => [
                    'id'                => 'SRPVPA' . $this->input->get('bank_account')->get('id'),
                    'bank_account_id'   => 'SRPBANK' . $this->input->get('bank_account')->get('id'),
                ]
            ],
            'bank_account'  => [
                'id'    => $this->input->get('bank_account')->get('id')
            ]
        ]);
    }

    public function setDefault(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::VA401]))
        {
            return;
        }

        $response->setData([
            Entity::VPA => [
                Entity::ID  => $this->input->get(Entity::VPA)->get(Entity::ID),
            ],
            Entity::SUCCESS => true,
        ]);
    }

    public function assignBankAccount(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::VA501]))
        {
            return;
        }

        $response->setData([
            'vpa'   => [
                'id'    => $this->input->get('vpa')->get('id'),
            ],
            'bank_account'  => [
                'id'    => $this->input->get('bank_account')->get('id'),
            ]
        ]);
    }

    public function initiateCheckAvailability(Response $response)
    {
        $this->initiateAdd($response);
    }

    public function checkAvailability(Response $response)
    {
        $failures = [Scenario::VA201, Scenario::VA202, Scenario::VA203, Scenario::VA204, Scenario::VA205];

        if ($this->handleFailureScenarios($response, $failures))
        {
            return;
        }

        $response->setData([
            'available'     => true,
            'username'      => $this->input->get('username'),
            'handle'        => $this->context->handleCode(),
        ]);
    }

    public function delete(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::VA601]))
        {
            return;
        }

        $response->setData([
            'success'   => true,
            'vpa'   => [
                'id'    => $this->input->get('vpa')->get('id'),
            ],
        ]);
    }

    public function validate(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::VA701, Scenario::VA702]))
        {
            return;
        }

        $response->setData([
            'validated'         => true,
            'type'              => $this->input->get('type'),
            'username'          => $this->input->get('username'),
            'handle'            => $this->input->get('handle'),
            // TODO: Need to enable verified
            //'verified'          => $this->scenario->is(Scenario::VA703),
            'beneficiary_name'  => 'Razorpay Customer' . ($this->isScenario(Scenario::VA704) ? ' Long(Name)' : ''),
        ]);
    }

    public function handleBeneficiary(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::VA801, Scenario::VA802]))
        {
            return;
        }

        $response->setData([
            'type'              => $this->input->get('type'),
            'username'          => $this->input->get('username'),
            'handle'            => $this->input->get('handle'),
            'blocked'           => false,
            'spammed'           => false,
            'blocked_at'        => null,
        ]);
    }

    public function fetchAll(Response $response)
    {
        if (empty($this->input->get('blocked')) === true)
        {
            $response->setError(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE, 'Only blocked vpas are available');
            return;
        }

        if ($this->handleFailureScenarios($response, [Scenario::VA901]))
        {
            return;
        }

        $output['data'] = [];

        if ($this->isScenario(Scenario::VA902))
        {
            $response->setData($output);
            return;
        }

        $sub = (int) $this->scenario->getParsedSub(Scenario::VA903);
        $count = ($sub % 100);
        $mask = floor($sub / 100);

        for ($i = 0; $i < $count; $i++)
        {
            $output['data'][] = $this->mockBlockedVpa($mask, $i);
        }
        $response->setData($output);
    }

    private function mockBlockedVpa($mask, $i)
    {
        $spammed    = boolval($mask & 1);           // Whether is spammed
        $long       = boolval($mask & 2);           // Whether long vpa

        return [
            'type'              => 'vpa',
            'username'          => ($long ? 'very.long.' : '') . 'blocked.' . $i,
            'handle'            => 'razorhdfc',
            'blocked'           => true,
            'spammed'           => $spammed,
            'blocked_at'        => null,
        ];
    }
}
