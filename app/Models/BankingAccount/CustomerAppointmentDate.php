<?php
namespace RZP\Models\BankingAccount;

use Carbon\Carbon;

class CustomerAppointmentDate
{
    const RBL_BANK_HOLIDAYS = [
        '2023-01-26',
    ];

    /**
     * Estimated TAT for Docket Delivery Based on City
     * from the time of adding Customer Appointment Date by Sales
     * 
     */
    const DOCKET_DELIVERY_TAT = [

        'bangalore'         => 1, // present in DB for some leads - from govt data source
        'bengaluru'         => 1, // added from LMS using /cities URL

        'mumbai'            => 2,
        'navi mumbai'       => 2,

        'pune'              => 2,

        'delhi'             => 2,
        'central delhi'     => 2,
        'east delhi'        => 2,
        'new delhi'         => 2,
        'north delhi'       => 2,
        'north east delhi'  => 2,
        'north west delhi'  => 2,
        'south delhi'       => 2,
        'south west delhi'  => 2,

        'gurgaon'           => 2,
        'gurugram'          => 2,

        'noida'             => 2,
        'chennai'           => 2,
        'faridabad'         => 2,
        'ghaziabad'         => 2,
        'hyderabad'         => 3,
        'kolkata'           => 3,
        'howrah'            => 3,
        'ahmedabad'         => 3,
        'surat'             => 3,
        'lucknow'           => 3,
        'visakhapatnam'     => 3,
        'coimbatore'        => 3,
        'jaipur'            => 4,
        'indore'            => 4,
        'ernakulam'         => 4,
        'ludhiana'          => 4,
        'chandigarh'        => 4,
        'aurangabad'        => 4,
        'agra'              => 4,
        'vadodara'          => 4,
        'kanpur'            => 4,
        'guwahati'          => 4,
        'madurai'           => 4,
        'kozhikode'         => 4,
        'kanchipuram'       => 4,
        'bhubaneshwar'      => 4,
        'bhopal'            => 4,
        'ambala'            => 4,
        'allahabad'         => 4,
    ];

    /**
     * Get Estimated Docket Delivery Date based on the city  
     * Reference Date `$docketInitiatedDate` is optional and 
     * if not passed, it will assume today's date
     */
    public static function getEstimatedDocketDeliveryDate(string $city, $docketInitiatedDate = null)
    {
        if (empty($city))
        {
            return null;
        }

        $city = strtolower($city);

        $tat = 5;

        if (array_key_exists($city, self::DOCKET_DELIVERY_TAT))
        {
            $tat = self::DOCKET_DELIVERY_TAT[$city];
        }

        if (empty($docketInitiatedDate))
        {
            $docketInitiatedDate = Carbon::now()->format('Y-m-d');
        }

        return Carbon::createFromFormat('Y-m-d', $docketInitiatedDate)->addDays($tat)->format('Y-m-d');
    }

    public static function getCustomerAppointmentDateOptions(string $city)
    {
        $startDate = self::getEstimatedDocketDeliveryDate($city);

        return [
            'city'              => $city,
            'startDate'         => $startDate,
            'rblBankHolidays'   => self::RBL_BANK_HOLIDAYS,
        ];
    }
}