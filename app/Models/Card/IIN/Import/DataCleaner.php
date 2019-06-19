<?php

namespace RZP\Models\Card\IIN\Import;

use RZP\Models\Card\Network;
use RZP\Models\Card\IIN;
use RZP\Exception;

/**
 * This takes tha formatted data and cleans the duplicte entries and the
 * entries that are already present in database
 */
class DataCleaner
{
    protected $duplicate = array();

    protected $dbConflicts = array();

    protected $uniqueIins = array();

    protected $networkCheckFails = array();

    protected $repo = null;

    public function __construct()
    {
        $this->repo = new IIN\Repository;
    }

    /**
     * returns the duplicate entries (only after they have been parsed).
     *
     * The returned associative array has iin number as key and array of
     * duplicate enties as values.
     *
     * @return array duplicate entries
     */
    public function getDuplicateEntries()
    {
        return $this->duplicate;
    }

    /**
     * returns the duplicate entries (only after they have been parsed)
     *
     * The returned associative array has iin number as key and values are
     * associative array having <code>db_entry</code> and
     * <code>file_entry</code> keys.
     *
     * @return array duplicate entries
     */
    public function getDBConflicts()
    {
        return $this->dbConflicts;
    }

    public function getNetworkCheckFails()
    {
        return $this->networkCheckFails;
    }

    /**
     * It takes the formatted data and returns the cleaned data after removing
     * the duplicate entries and the entries that are present in the database.
     *
     * Formatted data: Each input row should be a associative array with keys
     * from IIN.
     *
     * @param array $formattedData   array of formatted datas.
     *
     * @return array   cleaned data
     */
    public function parse($network, $data, $checkForConflicts = true)
    {
        $uniqueRecords = $this->removeDuplicate($data, $network);

        if ($checkForConflicts)
        {
            $cleaned = $this->removeDBConflicts($uniqueRecords);

            return $cleaned;
        }

        return $uniqueRecords;
    }

    /**
     * Cleans out entries that are present in the database and sets the
     * dbConflicts array.
     *
     * Should be called with the values returned form removeDuplicates.
     * This helps in not to reindex the arrays.
     *
     * @param $uniqueRecords   associaive array of formatted data with iin as their key
     *
     * @return array          associative array of formatted data with iin as their key
     */
    protected function removeDBConflicts($uniqueRecords)
    {
        $dbRecords = $this->repo->findMany($this->uniqueIins);

        foreach ($dbRecords as $entity)
        {
            $iin = $entity->getIin();

            $this->dbConflicts[$iin] = array(
                'db_entry'   => $entity->toArray(),
                'file_entry' => $uniqueRecords[$iin]
            );

            unset($uniqueRecords[$iin]);
        }

        return $uniqueRecords;
    }

    /**
     * Removes the entries that have same iin numbers. It also indexes the
     * entires by thier iin number.
     *
     * It also sets the uniqueIins array.
     *
     * @param array $formattedData   array of formatted data
     *
     * @return array  associative array of formatted data with iin as thier key.
     */
    protected function removeDuplicate($data, $inputNetwork)
    {
        // Indexing the data based on IIN number
        $indexed = array();

        $index = 0;
        foreach ($data as $input)
        {
            $iin = $input[IIN\Entity::IIN];

            if (isset($inputNetwork))
            {
                $network = Network::$fullName[Network::detectNetwork($iin)];
                $input[IIN\Entity::NETWORK] = $network;
            }

            if (isset($inputNetwork) and
                ($inputNetwork !== 'all') and
                (strcmp($inputNetwork, $network) !== 0))
            {
               $this->networkCheckFails[$iin][] = $index;
            }
            else if (isset($indexed[$iin]))
            {
                if ($input !== $indexed[$iin])
                {
                    // Add to duplicate list
                    $this->duplicate[$iin][] = $index;
                }
            }
            else
            {
                array_push($this->uniqueIins, $iin);
                $indexed[$iin] = $input;
            }

            $index++;
        }

        return $indexed;
    }
}
