<?php

namespace Models\Card;

use \Constants\Table;

class Detail extends Eloquent
{
    const IIN = 'iin';

    const CATEGORY = 'category';

    const BRAND = 'brand';

    const TYPE = 'card_type';

    const COUNTRY = 'country_code';

    const BANK = 'bank';

    protected $table = Table::IIN;

    protected $primaryKey = self::IIN;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = array('*');

    public static function retrieveDetails($iin)
    {
        if (strlen($iin) > 6)
        {
            $iin = intval(substr($iin, 0, 6));
        }

        //
        // retrieve card details
        //
        $cardDetails = self::find($iin);

        return $cardDetails;
    }
}