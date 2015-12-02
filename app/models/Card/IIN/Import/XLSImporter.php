<?php

namespace Models\Card\IIN\Import;

use Models\Card\IIN;

/**
 * This class is called by the service function with the input data.
 * The handles the rest of processing.
 */
class XLSImporter
{
    /**
     * This is the main function.
     *
     * @param array $input    the post data
     *
     * @return array $input   contains the duplicates and db conflicts
     */
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

    /**
     * This enter the unique entries into the database.
     *
     * The input array should be associative and contian uniqe entries.
     *
     * @param array $cleaned        the input entries.
     */
    protected function enterIntoDB($cleaned)
    {
        $count = count($cleaned);

        // Too many entries crashes the sql query
        for ($i = 0; $i < $count; $i += 5000)
        {
            IIN\Entity::insert(array_slice($cleaned, $i, 5000));
        }

    }

}
