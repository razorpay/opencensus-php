<?php 

namespace DataMapper;

use DomainObject\Card as CardDO;
use \DB;
use \ERR;

class Card extends DataMapper
{
    const table = 'cards';

    /**
     * attributes which can be set by us in db.
     */
    private static $attr_insert = array(
        'number',
        'cardholder',
        'cvv',
        
        'expiry_month',
        'expiry_year',

        'last4',
        'type',
        'country',

        'address_line1',
        'address_line2',
        'address_state',
        'address_city' ,
        'address_zip',
        'address_country',
        );

    private static $attr_required = array(
        'number',
        'cardholder',
        'expiry_month',
        'expiry_year',
        'last4',
        'type',
        'country'
        );

    private static $attr_update = array(
        'cvv_check',
        'address_line1_check',
        'address_zip_check'
        );

    private static $attr_db = array(
        'id' ,
        'number',
        'cardholder',
        'cvv',

        'expiry_month',
        'expiry_year',

        'last4',
        'type',
        'country',


        'address_line1',
        'address_line2',
        'address_state',
        'address_city',
        'address_zip',
        'address_country',

        'cvv_check',
        'address_line1_check',
        'address_zip_check',

        'created_at',
        'updated_at'
        );

    private $row = array();

	public function insert(CardDO $card_do)
    {
        $data = $card_do->get_card_data();

        foreach ($data as $key=>$value)
        {
            if (!in_array($key, static::$attr_insert))
            {
                continue;
            }

            if (($value === null) or 
                ($value === ''))
            {
                if (in_array($key, static::$attr_required))
                {
                    throw new \InvalidArgumentException($key);
                }
            }
            else
            {
                $this->row[$key] = $value;
            }
        }

        try
        {
            $id = DB::table(self::table)
                    ->insert_get_id($this->row);
            $card_do->set_id((int) $id);
        }
        catch(Exception $e)
        {
            var_dump($e);
            return ERR::DB_PROBLEM;
        }

        return ERR::SUCCESS;
    }

    public function update($domain_object)
    {
        ;
    }
}

