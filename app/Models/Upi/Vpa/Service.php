<?php

namespace RZP\Models\Upi\Vpa;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Customer;
use RZP\Models\BankAccount;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->device = $this->app['basicauth']->getDevice();

        $this->core = new Customer\Core;
    }

    public function create($input)
    {
        $this->trace->info(TraceCode::VPA_CREATE_REQUEST, $input);

        $bankAccount = null;

        if (isset($input[Entity::BANK_ACCOUNT_ID]) === true)
        {
            $bankAccountId = $input[Entity::BANK_ACCOUNT_ID];

            $bankAccount = (new BankAccount\Repository)->findOrFailPublic($bankAccountId);
        }

        $vpa = (new Core)->createVpa($input, $this->device->customer, $bankAccount);

        $this->trace->info(TraceCode::VPA_CREATED, $vpa->toArray());

        return $vpa->toArrayPublic();
    }

    public function getById($vpaId)
    {
        $vpa = $this->repo->vpa->findOrFailPublic($vpaId);

        return $vpa->toArrayPublic();
    }

    public function getAll($input)
    {
        $vpas = $this->repo->vpa->fetch($input);

        return $vpas->toArrayPublic();
    }

    public function delete($vpaId)
    {
        $this->trace->info(TraceCode::VPA_DELETE_REQUEST, $vpaId);

        $vpa = $this->repo->vpa->findOrFailPublic($vpaId);

        $this->trace->info(TraceCode::VPA_DELETED, $vpa->toArray());

        $this->repo->vpa->deleteOrFail($vpa);
    }

    public function edit($vpaId, $input)
    {
        $vpa = $this->repo->vpa->findOrFailPublic($vpaId);

        $vpa = (new Core)->editVpa($vpa, $input);

        return $vpa->toArrayPublic();
    }
}
