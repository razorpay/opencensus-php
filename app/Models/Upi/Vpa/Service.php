<?php

namespace RZP\Models\Upi\Vpa;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Customer;

class Service extends Base\Service
{
    public function create($input, $customer)
    {
        $this->trace->info(TraceCode::VPA_CREATE_REQUEST, $input);

        $bankAccount = (new Customer\Service)->fetchUpiBankAccounts($customer->getPublicId());

        $vpa = (new Core)->createVpa($input, $customer, $bankAccount);

        $this->trace->info(TraceCode::VPA_CREATED, $Vpa->toArray());

        return $vpa->toArrayPublic();
    }

    public function getVpaById($vpaId)
    {
        $vpa = $this->repo->vpa->findOrFailPublic($vpaId);

        return $vpa->toArrayPublic();
    }

    public function getAllVpas($input)
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
}
