<?php

namespace Dashboard;

class Repository extends \Models\Base\Repository
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