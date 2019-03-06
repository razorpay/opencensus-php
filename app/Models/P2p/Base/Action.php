<?php

namespace RZP\Models\P2p\Base;

class Action
{
    const FETCH         = 'fetch';
    const FETCH_ALL     = 'fetchAll';

    protected $actionToRoute = [];

    public function toRoute(string $action)
    {
        if (isset($this->actionToRoute[$action]) === false)
        {
            return null;
        }

        return $this->actionToRoute[$action];
    }
}
