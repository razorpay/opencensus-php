<?php 

namespace Models\DAL;

class Card extends DAL
{
    protected $table = 'cards';

    protected $fillable = array(
        'name',
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
        'address_zip_check');

    protected $guarded = array('id');

    protected $appends = array('object');

    public function getId()
    {
        return $this->getAttribute('id');
    }

    const FLAG_DEFAULT          = 0x0;
    const NO_CHECK_FIELDS       = 0x1;
    const WITH_OBJECT_FIELD     = 0x2;
    const ONLY_PUBLIC_FIELDS    = 0x4;
    
    public function getCardData($flag = 0x0)
    {
        $data = $this->attr;

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