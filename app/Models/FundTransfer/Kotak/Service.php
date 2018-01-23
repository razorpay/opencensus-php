<?php

namespace RZP\Models\FundTransfer\Kotak;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function generateSettlementFile($setlAttempts)
    {
        // Hard-coding for now
        $fileDetails = (new NodalAccount)->generateSettlementFile($setlAttempts, false);

        $urls = [];

        foreach ($fileDetails as $fileDetail)
        {
            $urls[($fileDetail->get())['id']] = $fileDetail->getUrl();
        }

        return $urls;
    }

    public function deleteSetlFile($setlFileType)
    {
        (new FileDeleter)->deleteFileIfExists($setlFileType);
    }
}
