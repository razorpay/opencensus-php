<?php

namespace Models\Card\IIN;

class XLSImporter
{
    public function import($input)
    {
        // Extracts and returns the columns and data
        $ret = (new XLSFileHandler)->getData($input);

        $formatedData = (new Formatter)->formatData(
                                                    $input,
                                                    $ret['columns'],
                                                    $ret['data']);

        $dataCleaner = new DataCleaner();
        $cleaned = $dataCleaner->parse($formatedData);
        $this->enterIntoDB($cleaned);

        $duplicates = $dataCleaner->getDuplicateEntries();
        $conflits = $dataCleaner->getDBConflicts();

        return array(
            'duplicates'   => $duplicates,
            'db_conflicts' => $conflits);
    }

    protected function enterIntoDB($cleaned)
    {
        $count = count($cleaned);

        // Too many entries crashes the sql query
        for ($i = 0; $i < $count; $i += 5000)
        {
            Entity::insert(array_slice($cleaned, $i, 5000));
        }

    }

}
