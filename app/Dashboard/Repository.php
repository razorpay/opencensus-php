<?php

namespace RZP\Dashboard;

use RZP\Base;

class Repository extends Base\Repository
{
    public function persistAfterFail($data)
    {
        $attributes = array(
            'json'  =>  json_encode($data)
        );

        return $this->createOrFail($attributes);
    }
}
