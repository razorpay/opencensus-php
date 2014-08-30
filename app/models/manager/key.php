<?php

namespace Models\Manager;

use Models\DAL;
use Models\Service;

class Key extends Manager
{
    protected static $createRules = array(
        'id'                    => 'required',
        'merchant_id'           => 'required',
        'delay_roll'            => 'required|in:0,1'
    );

    public static function buildKeyUpdateData($old_key_data)
    {
        return array(
            'delay_roll'    =>  $old_key_data['delay_roll']
        );
    }
}