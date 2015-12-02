<?php

namespace Models\Card\IIN\Import;

use Models\Card;
use EE\Exception;

/**
 * This class takes the column names, rows and the input and tries to
 * format the data as best as possible.
 *
 * Formatted data: Each input row should be a associative array with keys
 * from Card\Detail.
 */
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

    /**
     * This function is called by the importer class with the reqired
     * parameters.
     *
     * This returns after structuring the data. Each row array is converted
     * to arrays with keys with keys from Card\Detail.
     *
     * @param array $input      the post data received
     * @param array $columns    the title of each column
     * @param array $data       the rows
     *
     * @return array The structured data.
     */
    public function formatData($input, $columns, $data)
    {
        $this->setNetworkType($input);
        $structured = $this->structureData($columns, $data);

        return $structured;

    }

    /**
     * Determines the network type from the user input network, or from the file
     * name.
     *
     * It sets the $network variable.
     *
     * @param array $input the post data
     */
    protected function setNetworkType($input)
    {
        if (isset($input['network']) && $input['network'] !== "")
        {
            $this->network = $input['network'];
            return;
        }

        if (isset($input['file']))
        {
            $filename = $input['file']->getClientOriginalName();
            foreach ($this->networkType as $nt => $origName)
            {
                if (stripos($filename, $nt) !== false)
                {
                    $this->network = $origName;
                    return;
                }
            }
        }

        throw new Exception\ServerErrorException("Was not able to determine the network type.");
    }

    /**
     * Checks if the is a known mapping of the key (from Card\Detail) to the
     * column name.
     *
     * @param string $key    The key to map to the columns
     * @param array $column  The array of column names.
     *
     * @return int          The index of column that maps.
     */
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

    /**
     * This does the main work. It takes the column names and the rows and
     * tries to structure the data.
     *
     * @param array $columns     the column names.
     * @param array $data        the rows.
     *
     * @return array            the rows after structuring.
     */
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
