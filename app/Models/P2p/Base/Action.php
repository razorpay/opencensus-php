<?php

namespace RZP\Models\P2p\Base;

class Action
{
    const FETCH         = 'fetch';
    const FETCH_ALL     = 'fetchAll';

    protected $actionToRoute = [];

    protected $redactRules = [];

    public function toRoute(string $action)
    {
        if (isset($this->actionToRoute[$action]) === false)
        {
            return null;
        }

        return $this->actionToRoute[$action];
    }

    public function getRedactRules(string $action)
    {
        if (isset($this->redactRules[$action]) === false)
        {
            return null;
        }

        return $this->redactRules[$action];
    }
}
