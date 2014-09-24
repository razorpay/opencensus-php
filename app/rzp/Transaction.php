<?php

namespace RZP;

class Transaction extends Entity
{
    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function all($options = array())
    {
        return parent::all();
    }
}