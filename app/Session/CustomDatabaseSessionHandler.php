<?php

namespace App\Session;

use Auth;

class CustomDatabaseSessionHandler extends \Illuminate\Session\DatabaseSessionHandler
{
    protected function getDefaultPayload($data)
    {
        $payload = parent::getDefaultPayload($data);

        $payload['admin_id'] = null;

        if (Auth::guard('api')->user() !== null)
        {
            $payload['admin_id'] = Auth::guard('api')->user()->id;
        }

        return $payload;
    }
}
