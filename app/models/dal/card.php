<?php 

namespace Models\DAL;

use Constants\Field;

class Card extends DAL
{
    protected $table = \Constants\Table::CARD;

    protected $fillable = array(
        Field\Card::NAME,

        Field\Card::EXPIRY_MONTH,
        Field\Card::EXPIRY_YEAR,

        Field\Card::LAST4,
        Field\Card::NETWORK,
        Field\Card::COUNTRY,
        Field\Card::TYPE,
        Field\Card::BANK,

        Field\Card::ADDRESS_LINE,
        Field\Card::ADDRESS_LINE2,
        Field\Card::ADDRESS_STATE,
        Field\Card::ADDRESS_CITY,
        Field\Card::ADDRESS_ZIP,
        Field\Card::ADDRESS_COUNTRY,

        'cvv_check',
        'address_line1_check',
        'address_zip_check');

    protected $guarded = array(Field\Card::ID);

//    protected $appends = array('object');

    public function getId()
    {
        return $this->getAttribute(Field\Card::ID);
    }

    const FLAG_DEFAULT          = 0x0;
    const NO_CHECK_FIELDS       = 0x1;
    const WITH_OBJECT_FIELD     = 0x2;
    const ONLY_PUBLIC_FIELDS    = 0x4;
    
    public function getCardData($flag = 0x0)
    {
        $data = $this->attributes;

        unset($data['created_at']);
        unset($data['updated_at']);

        if ($flag & self::WITH_OBJECT_FIELD)
            $data['object'] = 'card';

        if ($flag & self::ONLY_PUBLIC_FIELDS)
        {
            unset($data['id']);
            unset($data['number']);
        }


        if ($flag & self::NO_CHECK_FIELDS)
        {
            unset(
                $data['cvv_check'],
                $data['address_line1_check'],
                $data['address_zip_check']);
        }

        return $data;
    }
}