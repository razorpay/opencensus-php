<?php

namespace Tests\Functional\Fixtures\Entity;

use Models\Merchant\Account;

class Hdfc extends Base
{
    public function createAuthorized(array $attributes = array())
    {
        $attributes['action'] = 4;
        $attributes['status'] = 'authorized';

        return $this->createEntity($attributes);
    }

    public function createCaptured(array $attributes = array())
    {
        $attributes['action'] = 5;
        $attributes['status'] = 'captured';

        return $this->createEntity($attributes);
    }
}