<?php

namespace RZP\Models\Vpa;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;

class Core extends Base\Core
{
    public function createVpaForCustomer(array $input, Merchant\Entity $merchant, Customer\Entity $customer): Entity
    {
        $vpa = (new Entity)->build($input);

        // Multiple merchants/customers can have same address
        $duplicate = $this->repo->vpa->findByAddress($input[Entity::ADDRESS]);

        if ($duplicate !== null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_DUPLICATE_VPA,
                $vpa->toArrayPublic());
        }

        $vpa->generateId();

        $vpa->merchant()->associate($merchant);

        $vpa->entity()->associate($customer);

        $this->repo->saveOrFail($vpa);

        return $vpa;
    }
}
