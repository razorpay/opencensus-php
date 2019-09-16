<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;
use RZP\Models\P2p\Vpa\Entity;

/**
 * @property  P2p\Vpa\Service $service
*/
class VpaController extends Controller
{
    public function fetchHandles()
    {
        $input = $this->request()->all();

        $response = (new P2p\Vpa\Handle\Service)->fetchAll($input);

        return $this->response($response);
    }

    public function createHandle()
    {
        $input = $this->request()->all();

        $response = (new P2p\Vpa\Handle\Service)->add($input);

        return $this->response($response);
    }

    public function updateHandle()
    {
        $input = $this->request()->all();

        $input[P2p\Vpa\Handle\Entity::CODE] = $this->request()->route(P2p\Vpa\Handle\Entity::CODE);

        $response = (new P2p\Vpa\Handle\Service)->update($input);

        return $this->response($response);
    }

    public function initiateCreate()
    {
        $input = $this->request()->all();

        $response = $this->service->initiateAdd($input);

        return $this->response($response);
    }

    public function create()
    {
        $input = $this->request()->all();

        $response = $this->service->add($input);

        return $this->response($response);
    }

    public function fetchAll()
    {
        $input = $this->request()->all();

        $response = $this->service->fetchAll($input);

        return $this->response($response);
    }

    public function fetch()
    {
        $input[Entity::ID] = $this->request()->route('vpa_id');

        $response = $this->service->fetch($input);

        return $this->response($response);
    }

    public function assignBankAccount()
    {
        $input = $this->request()->all();

        $input[Entity::ID] = $this->request()->route('vpa_id');

        $response = $this->service->assignBankAccount($input);

        return $this->response($response);
    }

    public function setDefault()
    {
        $input = $this->request()->all();

        $input[Entity::ID] = $this->request()->route('vpa_id');

        $response = $this->service->setDefault($input);

        return $this->response($response);
    }

    public function initiateCheckAvailability()
    {
        $input = $this->request()->all();

        $response = $this->service->initiateCheckAvailability($input);

        return $this->response($response);
    }

    public function checkAvailability()
    {
        $input = $this->request()->all();

        $response = $this->service->checkAvailability($input);

        return $this->response($response);
    }

    public function delete()
    {
        $input[Entity::ID] = $this->request()->route('vpa_id');

        $response = $this->service->delete($input);

        return $this->response($response);
    }
}
