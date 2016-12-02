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

            Entity::stripSignWithoutValidation($bankAccountId);

            $bankAccount = (new BankAccount\Repository)->findOrFailPublic($bankAccountId);
        }

        $vpa = (new Core)->createVpa($input, $this->device->customer, $bankAccount);

        // TODO: Make sure customer owns this bank account
        // Put an assert here?

        $this->trace->info(TraceCode::VPA_CREATED, $vpa->toArray());

        return $vpa->toArrayPublic();
    }

    // TODO: Fix authorization here
    // Make sure customer owns the VPA before returning it
    public function getById($vpaId)
    {
        $vpa = $this->repo->vpa->findOrFailPublic($vpaId);

        return $vpa->toArrayPublic();
    }

    public function getAll()
    {
        $customerId = $this->device->customer->getId();

        $vpas = $this->repo->vpa->fetchByCustomerId($customerId);

        return $vpas->toArrayPublic();
    }

    public function delete($vpaId)
    {
        // TODO: Fix authorization here
        // Make sure customer owns the VPA before deleting it
        $this->trace->info(TraceCode::VPA_DELETE_REQUEST, $vpaId);

        $vpa = $this->repo->vpa->findOrFailPublic($vpaId);

        $this->trace->info(TraceCode::VPA_DELETED, $vpa->toArray());

        $this->repo->vpa->deleteOrFail($vpa);
    }

    // TODO: Fix authorization here
    // Make sure customer owns the VPA before editing it
    public function edit($vpaId, $input)
    {
        $vpa = $this->repo->vpa->findOrFailPublic($vpaId);

        $vpa = (new Core)->editVpa($vpa, $input);

        $this->eventVpaEdited($vpa);

        return $vpa->toArrayPublic();
    }

    protected function eventVpaEdited($vpa)
    {
        $this->app['events']->fire('api.vpa.edited', array($vpa));
    }
}
