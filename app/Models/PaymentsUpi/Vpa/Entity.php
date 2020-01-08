<?php

namespace RZP\Models\PaymentsUpi\Vpa;

use RZP\Models\PaymentsUpi\Base;

class Entity extends Base\Entity
{
    const USERNAME  = 'username';
    const HANDLE    = 'handle';
    const NAME      = 'name';

    protected $entity = 'payments_upi_vpa';

    protected $generateIdOnCreate = true;
}
