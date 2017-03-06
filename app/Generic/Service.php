<?php

namespace App\Generic;

use App\Base;
use App\Admin;
use App\Trace\TraceCode;
use Input;

class Service extends Base\Service
{
    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->trace = $app['trace'];
    }

    public function call(array $input, $route)
    {
        $type = Input::get('type') ?? 'admin';
        list($error, $response) = $this->makeRawApiCall($input, $route, $type);

        return [$error, $response];
    }

    public function makeRawApiCall($input, $path, $type)
    {
        $input['mode'] = 'live';

        if ($type === 'merchant') {
            $input['auth'] = 'proxy';
        } else {
            $input['auth'] = 'admin'; // admin auth
        }

        $input['token'] = session('api_admin.token'); // admin auth token

        $input['file'] = null;

        $input['merchant_id'] = Input::get('merchant_id') ?? '';

        $autoBuildQuery = false;

        $request = new Admin\RawApiRequest($input, $path, $autoBuildQuery);

        return $request->send();
    }

    public function makeRawApiCallInternal($input, $path)
    {
        $input['mode'] = 'live';

        $input['auth'] = 'internal'; // app auth

        $input['file'] = null;

        $autoBuildQuery = false;
        $this->trace->info(TraceCode::MISC_TRACE_CODE, $input);

        $request = new Admin\RawApiRequest($input, $path, $autoBuildQuery);

        return $request->send();
    }
}
