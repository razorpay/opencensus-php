<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\V2;

use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Kotak\Headings;
use RZP\Models\FundTransfer\Kotak\Reconciliation\V3;

class RowProcessor extends V3\RowProcessor
{
    public function __construct($row)
    {
        parent::__construct($row);

        $this->version = Attempt\Version::V2;
    }

    public static function isV2($row): bool
    {
        if (($row[Headings::ENRICHMENT_2] !== null) and
            ($row[Headings::ENRICHMENT_2] === Attempt\Version::V2))
        {
            return true;
        }

        return false;
    }
}
