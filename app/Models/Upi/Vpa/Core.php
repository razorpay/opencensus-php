<?php

namespace RZP\Models\Upi\Vpa;

use RZP\Models\Base;
use RZP\Models\Merchant\Account;
use RZP\Error\ErrorCode;
use RZP\Exception;

class Core extends Base\Core
{
    public function createVpa($input, $customer, $bankAccount)
    {
        $vpa = (new Entity)->build($input);

        $duplicate = $this->repo->vpa->findByAddress($input[Entity::ADDRESS]);

        if (is_null($duplicate) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_DUPLICATE_VPA,
                $vpa->toArrayPublic());
        }

        sd($duplicate);

        $vpa->generateId();

        $vpa->customer()->associate($customer);

        $vpa->bankAccount()->associate($bankAccount);

        $this->repo->saveOrFail($vpa);

        return $vpa;
    }

    public function editVpa($vpa, $input)
    {
        $vpa->edit($input);

        $this->repo->saveOrFail($vpa);

        return $vpa;
    }
}
