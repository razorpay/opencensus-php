<?php

namespace App\Session;

use Auth;

class CustomDatabaseSessionHandler extends \Illuminate\Session\DatabaseSessionHandler
{
    protected function getDefaultPayload($data)
    {
        $payload = parent::getDefaultPayload($data);

        // Add admin to $payload
        if (Auth::guard('admin')->user() !== null)
        {
            $payload['admin_id'] = Auth::guard('admin')->user()->id;
        }
        else
        {
            $payload['admin_id'] = null;
        }

        return $payload;
    }
}