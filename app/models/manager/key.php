<?php 

namespace Models\Manager;

use Models\DAL;
use Models\Service;

class Key extends Manager
{
    protected static $createRules = array(
        'id'                    => 'required|alpha_num|size:32',
        'merchant_id'           => 'required|numeric',
        'delay_roll'            => 'required|in:true,false'
    );

    public static function generateKeyData()
    {
        return array(
            'key_id'    =>  bin2hex(openssl_random_pseudo_bytes(16)),
            'secret'    =>  bin2hex(openssl_random_pseudo_bytes(32))
        );
    }

    public static function buildKeyUpdateData($old_key_data, $key_data)
    {
        return array(
            'old_id'        =>  $old_key_data['id'],
            'id'            =>  $key_data['key_id'],
            'secret'        =>  \Hash::make($key_data['secret']),
            'delay_roll'    =>  $old_key_data['delay_roll']
        );
    }
}