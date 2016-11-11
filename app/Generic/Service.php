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
        list($error, $response) = $this->makeRawApiCall($input, $route);

        return [$error, $response];
    }

    public function makeRawApiCall($input, $path)
    {
        $input['mode'] = 'live';

        $input['auth'] = 'admin'; // app auth

        $input['file'] = null;

        $autoBuildQuery = false;

        $request = new Admin\RawApiRequest($input, $path, $autoBuildQuery);

        return $request->send();
    }
}
