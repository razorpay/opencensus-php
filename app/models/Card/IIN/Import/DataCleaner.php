<?php

namespace Models\Card\IIN\Import;

use Models\Card;
use Models\Card\IIN;

/**
 * This takes tha formatted data and cleans the duplicte entries and the
 * entries that are already present in database
 */
class DataCleaner
{
    protected $duplicate = array();

    protected $dbConflicts = array();

    protected $uniqueIins = array();

    protected $repo = null;

    public function __construct()
    {
        $this->repo = new IIN\Repository();
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

    /**
     * It takes the formatted data and returns the cleaned data after removing
     * the duplicate entries and the entries that are present in the database.
     *
     * Formatted data: Each input row should be a associative array with keys
     * from Card\Detail.
     *
     * @param array $formattedData   array of formatted datas.
     *
     * @return array   cleaned data
     */
    public function parse($formattedData)
    {
        $uniqueRecords = $this->removeDuplicate($formattedData);
        $cleaned = $this->removeDBConflicts($uniqueRecords);
        return $cleaned;
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
        $dbRecords = $this->repo->find($this->uniqueIins);

        foreach ($dbRecords as $entity) {

            $iin = sprintf("%06d", $entity->getIinAttribute()) ;

            $this->dbConflicts[$iin] = array(
                                'db_entry'   => $entity->toArray(),
                                'file_entry' => $uniqueRecords[$iin]);
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
    protected function removeDuplicate($formattedData)
    {
        $indexed = array();
        // Indexeing the data based on IIN number
        foreach($formattedData as $d)
        {
            $indexed[$d[Card\Detail::IIN]][] = $d;
        }

        $data = array();
        foreach($indexed as $rows)
        {
            $len = count($rows);
            $iin = $rows[0][Card\Detail::IIN];
            if ($len === 1)
            {
                array_push($this->uniqueIins, $iin);
                $data[$iin] = $rows[0];
            }
            else
            {
                $this->duplicate[$iin] = $rows;
            }
        }
        return $data;
    }
}
