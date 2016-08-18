<?php

namespace RZP\Models\Customer\Address;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create($input)
    {
        $address = (new Entity)->build($input);

        $this->repo->saveOrFail($address);

        return $address;
    }
}