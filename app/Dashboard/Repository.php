<?php

namespace RZP\Dashboard;

use Base;

class Repository extends Base\Repository
{
    protected $repo = '\Dashboard\Logs';

    public function persistAfterFail($data)
    {
        $attributes = array(
            'json'  =>  json_encode($data)
        );

        return $this->createOrFail($attributes);
    }
}