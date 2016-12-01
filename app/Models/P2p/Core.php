<?php

namespace RZP\Models\P2p;

use RZP\Models\Upi;
use RZP\Models\Base;
use RZP\Models\Merchant\Account;

class Core extends Base\Core
{
    public function create($input)
    {
        $p2p = (new Entity)->build($input);

        $p2p->generateId();

        $p2p->setStatus(Status::CREATED);

        $p2p->setGateway(Gateway::UPI_NPCI);

        $p2p->merchant()->associate($this->merchant);

        $this->repo->saveOrFail($p2p);

        return $p2p;
    }

    public function authorize($id, $input)
    {
        $p2p = $this->repo->p2p->fetchWithSourceSink($id);

        $gatewayInput = $this->preProcessGatewayInput($p2p, $input);

        $upiCore = new Upi\Core;

        $data = $upiCore->callUpiGateway('make_request', $gatewayInput);

        return $data;
    }

    protected function preProcessGatewayInput($p2p, $input)
    {
        $gatewayInput = [];

        $gatewayInput['method'] = 'ReqPay';
        $gatewayInput['params'] = [
            'p2p'      => $p2p->toArray(),
            'customer' => $p2p->customer(),
            'gateway'  => $input,
            'device'   => $this->app['basicauth']->getDevice()
        ];

        return $gatewayInput;
    }
}
