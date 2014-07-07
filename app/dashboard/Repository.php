<?php

namespace Dashboard;

class Repository extends \Models\Base\Repository
{
    protected $repo = 'Logs';

    public function persistAfterFail($data)
    {
        $attributes = array(
            'json'  =>  json_encode($data)
        );

        $repo = $this->repo;

        return $repo::createOrFail($attributes);
    }
}