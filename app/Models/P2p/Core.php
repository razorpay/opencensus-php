<?php

namespace RZP\Models\P2p;

use RZP\Models\Upi;
use RZP\Models\Base;
use RZP\Models\Merchant\Account;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Core extends Base\Core
{
    public function create($input)
    {
        $p2p = (new Entity)->build($input);

        $p2p->setStatus(Status::CREATED);

        $p2p->setGateway(Gateway::UPI_NPCI);

        $p2p->merchant()->associate($this->merchant);

        $p2p->customer()->associate($this->device->customer);

        $this->repo->saveOrFail($p2p);

        return $p2p;
    }

    public function authorize($id, $input)
    {
        Entity::stripSignWithoutValidation($id);

        $p2p = $this->repo->p2p->findOrFail($id);

        $gatewayInput = $this->preProcessGatewayInput($p2p, $input);

        $upiCore = new Upi\Core;

        $data = $upiCore->callUpiGateway('make_request', $gatewayInput);

        return $data;
    }

    /**
     * TODO: Set up UPI under application Auth
     * The apache vhost configuration should forward
     * the mode in the authorization header
     *
     * (We'll have different IPs for prod and live)
     *
     * @param string $mode
     */
    protected function setMode($mode = 'test')
    {
        \Database\DefaultConnection::set($mode);
    }

    public function reject($id)
    {
        Entity::stripSignWithoutValidation($id);

        $p2p = $this->repo->p2p->findOrFail($id);

        $p2p->setStatus(Status::REJECTED);

        $this->repo->saveOrFail($p2p);

        return $p2p;
    }

    public function postAuthorize(string $id, $input)
    {
        $this->setMode();
        Entity::stripSignWithoutValidation($id);

        $p2p = $this->repo->p2p->findOrFail($id);

        // TODO: Do a constant time comparision
        if ($p2p->source->bankAccount->getMpin() === $input['mpin'])
        {
            // Confirm the payment
            $p2p->setStatus('transferred')->saveOrFail();
        }
        else
        {
            // Fail the payment
            $p2p->setStatus('failed');

            $p2p->setErrorCode('13');

            $p2p->setErrorDescription('An unknown error has occurred.');

            $p2p->saveOrFail();
        }

        return $p2p;
    }

    protected function preProcessGatewayInput($p2p, $input)
    {
        $gatewayInput = [];

        $source = $p2p->source;

        if (is_null($source->bankAccount) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_UNMAPPED_VPA,
                $source->toArrayPublic()
            );
        }

        $gatewayInput['method'] = 'ReqPay';
        $gatewayInput['params'] = [
            'p2p'          => $p2p,
            'customer'     => $p2p->customer->toArray(),
            'source'       => $source->toArray(),
            'bank_account' => $source->bankAccount->toArray(),
            'sink'         => $p2p->sink->toArray(),
            'gateway'      => $input,
            'device'       => $this->app['basicauth']->getDevice()
        ];

        return $gatewayInput;
    }
}
