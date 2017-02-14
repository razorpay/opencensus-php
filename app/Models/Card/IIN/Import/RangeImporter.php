<?php

namespace RZP\Models\Card\IIN\Import;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card\IIN;

class RangeImporter
{
    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = \Trace::getFacadeRoot();
    }

    public function import($input)
    {
        if (isset($input['network']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Please pass network name as input for given file');
        }

        $formattedData = (new Formatter)->formatIinDataRange($input);

        $successCount = $this->addOrUpdate($formattedData);

        return [
            'success' => $successCount,
        ];
    }

    protected function addOrUpdate(Base\PublicCollection $data)
    {
        $iins = new Base\PublicCollection;

        foreach ($data->all() as $iin => $detail)
        {
            $iinEntity = $this->app['repo']->iin->find($iin);

            if ($iinEntity === null)
            {
                $iinEntity = (new IIN\Entity)->build($detail);
            }
            else
            {
                $iinEntity->edit($detail);
            }

            $iins->push($iinEntity);
        }

        $this->app['repo']->saveOrFailCollection($iins);

        return $iins->count();
    }
}