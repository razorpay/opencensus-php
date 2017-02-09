<?php

namespace RZP\Models\Card\Iin\Import;

use App;
use RZP\Models\Card\IIN;
use RZP\Models\Base as BaseModel;

class Base
{
    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = \Trace::getFacadeRoot();
    }

    /**
     * This enter the unique entries into the database.
     *
     * The input array should be associative and contian uniqe entries.
     *
     * @param array $cleaned        the input entries.
     */
    protected function enterIntoDB($cleaned, $chunkSize = 5000)
    {
        // Too many entries crashes the sql query
        foreach (array_chunk($cleaned, $chunkSize) as $chunks)
        {
            $iins = new BaseModel\PublicCollection;

            foreach ($chunks as & $chunk)
            {
                $iinEntity = (new IIN\Entity)->build($chunk);

                $iins->push($iinEntity);
            }

            $this->app['repo']->saveOrFailCollection($iins);
        }
    }

    protected function updateIntoDB(& $conflicts)
    {
        $columns = array(IIN\Entity::TYPE, IIN\Entity::COUNTRY, IIN\Entity::ISSUER);

        foreach ($conflicts as $iinId => $entry)
        {
            list($input, $conflict, $diff) =
                $this->getInputForIinUpdate($entry['db_entry'], $entry['file_entry'], $columns);

            if (($conflict === false) and
                (empty($input) === false))
            {
                $entity = $this->app['repo']->iin->find($iinId);

                $entity->edit($input);

                $this->app['repo']->saveOrFail($entity);
            }

            if ($conflict === false)
            {
                unset($conflicts[$iinId]);
            }
            else
            {
                $conflicts[$iinId] = $diff;
            }
        }
    }
}