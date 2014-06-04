<?php

namespace Models\DAL;

class CardDetail extends DAL
{
    protected $table = 'iins';

    protected $primaryKey = 'iin';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = array('*');

    public static function retrieveDetails($iin)
    {
        if (strlen($iin) > 6)
        {
            $iin = intval(substr($iin, 0, 6)); 
        }

        // retrieve card details
        $cardDetails = self::find($iin);

        return $cardDetails;
    }
}