<?php 

namespace Models\DAL;

use DO;
use \DB;
use \ERR;

class Card extends DataMapper
{
    const table = 'cards';

    protected static $timestamps = true;

    protected static $primaryKey = 'id';

    protected static $attributes = array(
        'id' => 'db',
        'number' => 'db|insert_req',
        'cardholder' => 'db|insert_req',
        'cvv' => 'db|insert_req',

        'expiry_month' => 'db|insert_req',
        'expiry_year' => 'db|insert_req',

        'last4' => 'db|insert_req',
        'type' => 'db|insert_req',
        'country' => 'db|insert_req',

        'address_line1' => 'db|insert_req',
        'address_line2' => 'db|insert_req',
        'address_state' => 'db|insert_req',
        'address_city' => 'db|insert_req',
        'address_zip' => 'db|insert_req',
        'address_country' => 'db|insert_req',

        'cvv_check' => 'db|insert|update',
        'address_line1_check'  => 'db|insert|update',
        'address_zip_check'  => 'db|insert|update',

        'created_at' => 'db',
        'updated_at' => 'db');

}