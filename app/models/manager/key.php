<?php

namespace Models\Manager;

class Key extends EntityManager
{
    protected static $createRules = array(
        'key_id'        => 'required|alpha_num',
        'merchant_id'   => 'required|numeric',
        'secret'        => 'required|max:100',
        'live'          => 'size:1|in:0,1',
        'active'        => 'size:1|in:0,1'
    );

    protected static $generators = array('id');

    protected static $unsetCreateInput = array('key_id');

    protected function generateId($input)
    {
        $this->setField('id', $input['key_id']);
    }
}