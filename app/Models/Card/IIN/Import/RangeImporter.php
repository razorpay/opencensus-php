<?php

namespace RZP\Models\Card\IIN\Import;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card\IIN;

class RangeImporter extends Base\Core
{
    public function import($input)
    {
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
            $iinEntity = $this->repo->iin->find($iin);

            if ($iinEntity === null)
            {
                $iinEntity = (new IIN\Entity)->build($detail);
            }
            else
            {
                unset($detail['iin']);

                $iinEntity->edit($detail);
            }

            $iins->push($iinEntity);
        }

        $this->repo->saveOrFailCollection($iins);

        return $iins->count();
    }
}
