<?php 

namespace Models\Service;

use Models\Manager;
use Models\DAL;

class Card extends Service
{
    /**
     * Retrieve card details (network, type, category, bank and country code) using iin
     */
    public function retrieveDetails($iin)
    {
        $cardDetailsArray = false;

        $cardDetails = DAL\CardDetail::find($iin);

        if(null !== $cardDetails) {
            $cardDetailsArray = $cardDetails->toArray();
        }

        return $cardDetailsArray;
    }
}