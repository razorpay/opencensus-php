<?php

namespace RZP\Models\Payment\Store\Mock;

use RZP\Models\Payment\Store\Api;

class ApiMock extends Api
{

    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * To fetch stores for a given user from Store microservice
     * @return array
     */
    public function fetchStores() : array
    {
        return [];
    }
}
