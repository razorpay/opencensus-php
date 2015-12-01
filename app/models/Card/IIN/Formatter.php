<?php

namespace Models\Card\IIN;

use Models\Card;

class Formatter
{
    private $mapping = array(
        Card\Detail::IIN => array('BIN', 'IIN'),
        Card\Detail::CATEGORY => array('CARD_BRAND'),
        Card\Detail::NETWORK => array('NETWORK'),
        Card\Detail::TYPE => array('TYPE'),
        Card\Detail::COUNTRY => array('TYPE'),
        Card\Detail::ISSUER => array('TYPE'));

    private $typeMap = array(
        'FC' => 'credit',
        'DC' => 'credit',
        'FD' => 'debit',
        'DD' => 'debit');

    private $countryMap = array(
        'DC' => 'IN',
        'DD' => 'IN',
        'FD' => NULL,
        'FC' => NULL);

    private $columns = array(
        Card\Detail::IIN,
        Card\Detail::CATEGORY,
        Card\Detail::NETWORK,
        Card\Detail::TYPE,
        Card\Detail::COUNTRY,
        Card\Detail::ISSUER);

    // Mapping of string to be searched to
    // the original name that should be in database
    private $networkType = array( 'VISA' => 'Visa', 'MASTER' => 'MasterCard');

    private $network;

    public function formatData($input, $columns, $data)
    {
        $this->setNetwotkType($input);
        $structured = $this->structureData($columns, $data);

        return $structured;

    }

    protected function setNetwotkType($input)
    {
        if (isset($input['network']))
        {
            $this->network = $input['network'];
            return;
        }

        if (isset($input['file']))
        {
            $filename = $input['file']->getClientOriginalName();
            var_dump($filename);
            foreach ($this->networkType as $nt => $origName)
            {
                if (stripos($filename, $nt) !== false)
                {
                    $this->network = $origName;
                    return;
                }
            }
        }

        throw new Exception("Was not able to determine the network type.");
    }

    private function getFromMapping($key, $columns)
    {
        foreach ($this->mapping[$key] as $option)
        {
            for ($in = 0; $in < count($columns); $in++)
            {
                if (strtoupper($columns[$in]) === $option)
                {
                    return $in;
                }
            }
        }

        return false;
    }

    protected function structureData($columns, $data)
    {
        $map = array();

        // Mapping IIN
        $ret = $this->getFromMapping(Card\Detail::IIN, $columns);
        $map[Card\Detail::IIN] = $ret;

        // Mapping Category
        $ret = $this->getFromMapping(Card\Detail::CATEGORY, $columns);
        $map[Card\Detail::CATEGORY] = $ret;

        // Card Network
        $map[Card\Detail::NETWORK] = $this->network;

        // Card Type
        // A function which will parse the given type to the required type
        $typeIndex = $this->getFromMapping(Card\Detail::TYPE, $columns);
        $map[Card\Detail::TYPE] = $typeIndex;

        //  Country
        // A function which will parse the given type to the required type
        $countryIndex = $this->getFromMapping(Card\Detail::COUNTRY, $columns);
        $map[Card\Detail::COUNTRY] = $countryIndex;

        $data = array_map(function ($row) use ($map)
        {
            $iin = $row[$map[Card\Detail::IIN]];
            $category = $row[$map[Card\Detail::CATEGORY]];
            $network = $map[Card\Detail::NETWORK];
            $type = $this->typeMap[$row[$map[Card\Detail::TYPE]]];
            $country = $this->countryMap[$row[$map[Card\Detail::COUNTRY]]];

            $issuer = NULL;

            // $international = !$country;

            $record = [$iin, $category, $network, $type,
                       $country, $issuer];
            $record = array_combine($this->columns, $record);
            return $record;

        }, $data);

        return $data;

    }
}
