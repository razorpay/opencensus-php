<?php

namespace Models\Card\IIN\Import;

use Models\Card\IIN;
use EE\Exception;
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
        if(!isset($input['network']))
        {
            throw new Exception\BadRequestException("please pass network name as input for given file");
        }

        // Extracts and returns the columns and data
        $ret = (new XLSFileHandler)->getData($input);

        $formatedData = (new Formatter)->formatData($ret['columns'], $ret['data']);

        $dataCleaner = new DataCleaner();
        $cleaned = $dataCleaner->parse($input['network'], $formatedData);
        $duplicates = $dataCleaner->getDuplicateEntries();
        $conflits = $dataCleaner->getDBConflicts();
        $networkCheckFails = $dataCleaner->getNetworkCheckFails();

        $this->enterIntoDB($cleaned);
        $this->updateIntoDB($conflits);

        $successCount = count($cleaned);

        return array(
            'duplicates'   => $duplicates,
            'db_conflicts' => $conflits,
            'network_errors' => $networkCheckFails,
            'success' => $successCount,
        );
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
        foreach (array_chunk($cleaned, 5000) as $chunks)
        {
            IIN\Entity::insert($chunks);
        }

    }

    protected function updateIntoDB(& $conflicts)
    {
        $columns = array(IIN\Entity::NETWORK, IIN\Entity::TYPE, IIN\Entity::COUNTRY);

        foreach ($conflicts as $iin => $entry)
        {
            list($input, $conflict, $diff) = $this->getInputForIinUpdate($entry['db_entry'], $entry['file_entry'], $columns);

            if (($conflict === false) and
                (empty($input) === false))
            {
                $entity = IIN\Entity::find($iin);
                $entity->edit($input);
                $entity->saveOrFail();
            }

            if ($conflict === false)
            {
                unset($conflicts[$iin]);
            }
            else
            {
                $conflicts[$iin] = $diff;
            }
        }
    }

    protected function getInputForIinUpdate($dbEntry, $fileEntry, $columns)
    {
        unset($fileEntry[IIN\Entity::IIN]);

        $conflict = false;

        $diff = [];

        foreach ($columns as $column)
        {
            if (($column === 'country') or
                ((isset($dbEntry[$column])) and
                 ($dbEntry[$column] !== '')))
            {
                if ($dbEntry[$column] !== $fileEntry[$column])
                {
                    $conflict = true;

                    $diff[$column] = ['db' => $dbEntry[$column], 'file' => $fileEntry[$column]];
                }

                unset($fileEntry[$column]);
            }
        }

        return [$fileEntry, $conflict, $diff];
    }
}
