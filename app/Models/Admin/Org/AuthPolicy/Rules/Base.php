<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use App;
use RZP\Exception;

abstract class Base
{
    public function __construct()
    {
        $app = App::getFacadeRoot();
        $this->repo = $app['repo'];
    }

    public function validate($admin, $password)
    {
        throw new Exception\RuntimeException(
            'Validate function not implemented');
    }
}