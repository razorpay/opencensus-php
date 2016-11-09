<?php

namespace App\Generic;

use App\Base;
use App\Admin;

class Service extends Base\Service
{
    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->trace = $app['trace'];
    }

    public function call(array $input, $route)
    {
        $response = $this->makeRawApiCall($input, $route);

        return [null, $response];
    }

    public function makeRawApiCall($input, $path)
    {
        $input['mode'] = 'live';

        $input['file'] = null;

        $request = new Admin\RawApiRequest($input, $path);

        return $request->send();
    }
}
