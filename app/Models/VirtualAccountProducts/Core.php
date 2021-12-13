<?php

namespace RZP\Models\VirtualAccountProducts;

use RZP\Models\Base;
use RZP\Models\VirtualAccount;

class Core extends Base\Core
{
    public function create(VirtualAccount\Entity $virtualAccount)
    {
        if (($virtualAccount->hasOrder() === false) or
            ($virtualAccount->hasCustomer() === false))
        {
            return;
        }

        $virtualAccountProduct = new Entity();

        $virtualAccountProduct->virtualAccount()->associate($virtualAccount);

        $virtualAccountProduct->entity()->associate($virtualAccount->entity);

        $this->repo->virtual_account_products->saveOrFail($virtualAccountProduct);
    }
}
