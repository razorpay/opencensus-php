<?php

namespace RZP\Models\OAuthToken;

use Request;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function create()
    {
        $input = Request::all();

        $entity = $this->core()->create($input);

        return $entity;
    }
}
