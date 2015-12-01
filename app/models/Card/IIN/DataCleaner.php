<?php

namespace Models\Card\IIN;

use Models\Card;

class DataCleaner
{
    protected $duplicate = array();

    protected $dbConflicts = array();

    protected $uniqueIins = array();

    protected $repo = null;

    public function __construct()
    {
        $this->repo = new Repository();
    }

    public function getDuplicateEntries()
    {
        return $this->duplicate;
    }

    public function getDBConflicts()
    {
        return $this->dbConflicts;
    }

    public function parse($formatedData)
    {
        $uniqueRecords = $this->removeDuplicate($formatedData);
        $cleaned = $this->removeDBConflicts($uniqueRecords);
        return $cleaned;
    }

    protected function removeDBConflicts($uniqueRecords)
    {
        $dbRecords = $this->repo->find($this->uniqueIins);

        foreach ($dbRecords as $entity) {
            $iin = (string)$entity->getIinAttribute();

            $this->dbConflicts[$iin] = array(
                                'db_entry'   => $entity->toArray(),
                                'file_entry' => $uniqueRecords[$iin]);
            unset($uniqueRecords[$iin]);
        }

        return $uniqueRecords;
    }

    protected function removeDuplicate($formatedData)
    {
        $indexed = array();
        // Indexeing the data based on IIN number
        foreach($formatedData as $d)
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
