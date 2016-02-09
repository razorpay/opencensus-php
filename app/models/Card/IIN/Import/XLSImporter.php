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

    protected function updateIntoDB(& $conflits)
    {
        $columns = array(IIN\Entity::NETWORK, IIN\Entity::TYPE, IIN\Entity::CATEGORY, IIN\Entity::COUNTRY);
        
        foreach ($conflits as $iin => $entry) 
        {
            list($input, $conflict) = $this->getInputForIinUpdate($entry['db_entry'], $entry['file_entry'], $columns);

            if(!$conflict and !empty($input))
            {
                $entity = IIN\Entity::find($iin); 
                $entity->edit($input);
                $entity->saveOrFail();
            }

            if(!$conflict)
            {
                unset($conflits[$iin]);
            }
        }
    }

    protected function getInputForIinUpdate($dbEntry, $fileEntry, $columns)
    {
        unset($fileEntry[IIN\Entity::IIN]);
        $conflict = false;

        foreach ($columns as $column)
        {
            if(isset($dbEntry[$column]))
            {
                if($dbEntry[$column] !== $fileEntry[$column])
                {
                    $conflict = true;
                }
                unset($fileEntry[$column]);
            }

        }

        return [$fileEntry, $conflict];
    }
}
