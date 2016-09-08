<?php

namespace App\Session;

use Auth;

class CustomDatabaseSessionHandler extends \Illuminate\Session\DatabaseSessionHandler
{
    /**
     * {@inheritDoc}
     */
    public function write($sessionId, $data)
    {
        $payload = $this->getDefaultPayload($data);
        if (Auth::guard('admin')->user() !== null)
        {
            $payload['admin_id'] = Auth::guard('admin')->user()->id;
        }
        else
        {
            $payload['admin_id'] = null;
        }

        if (! $this->exists) {
            $this->read($sessionId);
        }

        if ($this->exists) {
            $this->getQuery()->where('id', $sessionId)->update($payload);
        } else {
            $payload['id'] = $sessionId;

            $this->getQuery()->insert($payload);
        }

        $this->exists = true;
    }
}