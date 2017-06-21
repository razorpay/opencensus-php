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

    public function getById($vpaId)
    {
        $customerId = $this->device->customer->getId();

        $vpa = $this->repo->vpa->findByIdAndCustomerIdOrFail($vpaId, $customerId);

        return $vpa->toArrayPublic();
    }

    public function getByIdPrivate($vpaId)
    {
        $merchantId = $this->merchant->getId();

        $vpa = $this->repo->vpa->findByIdAndMerchantIdOrFail($vpaId, $merchantId);

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
        $this->trace->info(TraceCode::VPA_DELETE_REQUEST, [ 'id' => $vpaId]);

        $customerId = $this->device->customer->getId();

        $vpa = $this->repo->vpa->findByIdAndCustomerIdOrFail($vpaId, $customerId);

        $this->trace->info(TraceCode::VPA_DELETED, $vpa->toArray());

        $this->repo->vpa->deleteOrFail($vpa);

        return $vpa->toArrayPublic();
    }

    public function edit($vpaId, $input)
    {
        $customerId = $this->device->customer->getId();

        $vpa = $this->repo->vpa->findByIdAndCustomerIdOrFail($vpaId, $customerId);

        $vpa = (new Core)->editVpa($vpa, $input);

        $this->eventVpaEdited($vpa);

        return $vpa->toArrayPublic();
    }

    public function isValid($vpa)
    {
        // TODO Check for generic validity, rather than @razor
        $match = preg_match('/[a-z0-9][a-z0-9\.-]{2,}@razor/', $vpa);

        $valid = $match !== 0;

        return ['valid' => $valid];
    }

    public function isAvailable($vpa)
    {
        $validResult = $this->isValid($vpa);

        if ($validResult['valid'] === false)
        {
            $available = false;
        }
        else
        {
            $existing = $this->repo->vpa->findByAddress($vpa);

            $available = is_null($existing);
        }

        return array_merge($validResult, ['available' => $available]);
    }

    protected function eventVpaEdited($vpa)
    {
        $eventPayload = [
            'main' => $vpa
        ];

        $this->app['events']->fire('api.vpa.edited', $eventPayload);
    }
}
