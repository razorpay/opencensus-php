<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;
use RZP\Models\P2p\BankAccount\Entity;

/**
 * @property  P2p\BankAccount\Service $service
*/
class BankAccountController extends Controller
{
    public function fetchBanks()
    {
        $input = $this->request()->all();

        $response = (new P2p\BankAccount\Bank\Service)->fetchAll($input);

        return $this->response($response);
    }

    public function manageBulkBanks()
    {
        $input = $this->request()->all();

        $response = (new P2p\BankAccount\Bank\Service)->manageBulk($input);

        return $this->response($response);
    }

    public function retrieveBanks()
    {
        $input = $this->request()->all();

        $response = (new P2p\BankAccount\Bank\Service)->retrieveBanks($input);

        return $this->response($response);
    }

    public function initiateRetrieve()
    {
        $input = $this->request()->all();

        $input[Entity::BANK_ID] = $this->request()->route('bank_id');

        $response = $this->service->initiateRetrieve($input);

        return $this->response($response);
    }

    public function retrieve()
    {
        $input = $this->request()->all();

        $input[Entity::BANK_ID] = $this->request()->route('bank_id');

        $response = $this->service->retrieve($input);

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
        $input[Entity::ID] = $this->request()->route('ba_id');

        $response = $this->service->fetch($input);

        return $this->response($response);
    }

    public function initiateSetUpiPin()
    {
        $input = $this->request()->all();

        $input[Entity::ID] = $this->request()->route('ba_id');

        $response = $this->service->initiateSetUpiPin($input);

        return $this->response($response);
    }

    public function setUpiPin()
    {
        $input = $this->request()->all();

        $input[Entity::ID] = $this->request()->route('ba_id');

        $response = $this->service->setUpiPin($input);

        return $this->response($response);
    }

    public function initiateFetchBalance()
    {
        $input = $this->request()->all();

        $input[Entity::ID] = $this->request()->route('ba_id');

        $response = $this->service->initiateFetchBalance($input);

        return $this->response($response);
    }

    public function fetchBalance()
    {
        $input = $this->request()->all();

        $input[Entity::ID] = $this->request()->route('ba_id');

        $response = $this->service->fetchBalance($input);

        return $this->response($response);
    }
}
